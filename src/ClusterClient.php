<?php

declare(strict_types=1);

namespace ValkeyGlideCompat;

use ValkeyGlideCluster;
use ValkeyGlideCompat\Concern\ClusterRoutedCommands;
use ValkeyGlideCompat\Concern\GlidePassthrough;
use ValkeyGlideCompat\Concern\NullGuardCommands;
use ValkeyGlideCompat\Concern\Serialization;
use ValkeyGlideCompat\Concern\SerializedCallHandler;
use ValkeyGlideCompat\Concern\SerializedCommands;

/**
 * RedisCluster-compatible client wrapping ValkeyGlideCluster.
 *
 * Translates the phpredis RedisCluster class API to ValkeyGlideCluster calls.
 */
class ClusterClient implements ClientInterface
{
    use NullGuardCommands;
    use ClusterRoutedCommands;
    use Serialization;
    use SerializedCommands;
    use SerializedCallHandler;
    use GlidePassthrough;

    // Data types (phpredis Redis::REDIS_*)
    public const REDIS_NOT_FOUND = Constants::REDIS_NOT_FOUND;
    public const REDIS_STRING = Constants::REDIS_STRING;
    public const REDIS_SET = Constants::REDIS_SET;
    public const REDIS_LIST = Constants::REDIS_LIST;
    public const REDIS_ZSET = Constants::REDIS_ZSET;
    public const REDIS_HASH = Constants::REDIS_HASH;
    public const REDIS_STREAM = Constants::REDIS_STREAM;

    // Transaction modes
    public const MULTI = Constants::MULTI;
    public const PIPELINE = Constants::PIPELINE;

    // Option constants
    public const OPT_SERIALIZER = Constants::OPT_SERIALIZER;
    public const OPT_PREFIX = Constants::OPT_PREFIX;
    public const OPT_READ_TIMEOUT = Constants::OPT_READ_TIMEOUT;
    public const OPT_SCAN = Constants::OPT_SCAN;
    public const OPT_FAILOVER = Constants::OPT_FAILOVER;
    public const OPT_TCP_KEEPALIVE = Constants::OPT_TCP_KEEPALIVE;
    public const OPT_COMPRESSION = Constants::OPT_COMPRESSION;
    public const OPT_REPLY_LITERAL = Constants::OPT_REPLY_LITERAL;
    public const OPT_COMPRESSION_LEVEL = Constants::OPT_COMPRESSION_LEVEL;
    public const OPT_NULL_MULTIBULK_AS_NULL = Constants::OPT_NULL_MULTIBULK_AS_NULL;
    public const OPT_MAX_RETRIES = Constants::OPT_MAX_RETRIES;
    public const OPT_BACKOFF_ALGORITHM = Constants::OPT_BACKOFF_ALGORITHM;
    public const OPT_BACKOFF_BASE = Constants::OPT_BACKOFF_BASE;
    public const OPT_BACKOFF_CAP = Constants::OPT_BACKOFF_CAP;
    public const OPT_PACK_IGNORE_NUMBERS = Constants::OPT_PACK_IGNORE_NUMBERS;

    // Serializer constants
    public const SERIALIZER_NONE = Constants::SERIALIZER_NONE;
    public const SERIALIZER_PHP = Constants::SERIALIZER_PHP;
    public const SERIALIZER_IGBINARY = Constants::SERIALIZER_IGBINARY;
    public const SERIALIZER_MSGPACK = Constants::SERIALIZER_MSGPACK;
    public const SERIALIZER_JSON = Constants::SERIALIZER_JSON;

    // Scan constants
    public const SCAN_NORETRY = Constants::SCAN_NORETRY;
    public const SCAN_RETRY = Constants::SCAN_RETRY;
    public const SCAN_PREFIX = Constants::SCAN_PREFIX;
    public const SCAN_NOPREFIX = Constants::SCAN_NOPREFIX;

    // Backoff algorithm constants
    public const BACKOFF_ALGORITHM_DEFAULT = Constants::BACKOFF_ALGORITHM_DEFAULT;
    public const BACKOFF_ALGORITHM_DECORRELATED_JITTER = Constants::BACKOFF_ALGORITHM_DECORRELATED_JITTER;
    public const BACKOFF_ALGORITHM_FULL_JITTER = Constants::BACKOFF_ALGORITHM_FULL_JITTER;
    public const BACKOFF_ALGORITHM_EQUAL_JITTER = Constants::BACKOFF_ALGORITHM_EQUAL_JITTER;
    public const BACKOFF_ALGORITHM_EXPONENTIAL = Constants::BACKOFF_ALGORITHM_EXPONENTIAL;
    public const BACKOFF_ALGORITHM_UNIFORM = Constants::BACKOFF_ALGORITHM_UNIFORM;
    public const BACKOFF_ALGORITHM_CONSTANT = Constants::BACKOFF_ALGORITHM_CONSTANT;

    private ValkeyGlideCluster $glide;

    /**
     * @param string|null $name Cluster name (for seed configuration via php.ini)
     * @param array<string>|null $seeds Seed nodes as ['host:port', ...]
     * @param float|null $timeout Connection timeout in seconds
     * @param float|null $read_timeout Read timeout in seconds
     * @param bool $persistent Use persistent connections (accepted for phpredis compatibility, unused)
     * @param mixed $auth Authentication credentials (string password or ['user', 'pass'])
     * @param bool|null $use_tls Whether to use TLS encryption
     * @param array<string, mixed>|null $advanced_config Advanced configuration (e.g. TLS config)
     * @phpstan-ignore-next-line constructor.unusedParameter
     */
    public function __construct(
        ?string $name = null,
        ?array $seeds = null,
        ?float $timeout = null,
        ?float $read_timeout = null,
        bool $persistent = false,
        mixed $auth = null,
        ?bool $use_tls = null,
        ?array $advanced_config = null,
    ) {
        if (! extension_loaded('valkey_glide')) {
            throw new \RuntimeException(
                'ext-valkey_glide not loaded. Use ClientFactory::createCluster() or PhpRedisClusterClient instead.'
            );
        }

        // Parse seeds from 'host:port' format to ValkeyGlideCluster address dict format
        $parsedSeeds = null;
        if ($seeds !== null) {
            $parsedSeeds = array_map(function (string $seed): array {
                // Handle IPv6 addresses like [::1]:6379
                if (str_starts_with($seed, '[')) {
                    $bracketClose = strpos($seed, ']');
                    if ($bracketClose !== false) {
                        $host = substr($seed, 1, $bracketClose - 1);
                        $port = ($bracketClose + 1 < strlen($seed) && $seed[$bracketClose + 1] === ':')
                            ? (int) substr($seed, $bracketClose + 2)
                            : 6379;

                        return ['host' => $host, 'port' => $port];
                    }
                }

                // Use strrpos to find the last colon (handles host:port for IPv4/hostnames)
                $lastColon = strrpos($seed, ':');
                if ($lastColon === false) {
                    return ['host' => $seed, 'port' => 6379];
                }

                $host = substr($seed, 0, $lastColon);
                $port = (int) substr($seed, $lastColon + 1);

                return ['host' => $host, 'port' => $port];
            }, $seeds);
        }

        // Parse auth credentials
        $credentials = null;
        if ($auth !== null) {
            if (is_string($auth)) {
                $credentials = ['password' => $auth];
            } elseif (is_array($auth) && count($auth) === 2) {
                $credentials = [
                    'username' => $auth[0],
                    'password' => $auth[1],
                ];
            }
        }

        // Pass ONLY seeds (phpredis-style params), NOT addresses
        $this->glide = new ValkeyGlideCluster(
            name: $name,
            seeds: $parsedSeeds,
            timeout: $timeout,
            read_timeout: $read_timeout,
            credentials: $credentials,
            use_tls: $use_tls,
            advanced_config: $advanced_config,
        );
    }

    public function getDriver(): ValkeyGlideCluster
    {
        return $this->glide;
    }

    /**
     * Set a client option.
     *
     * OPT_SERIALIZER is handled at the PHP level since the C extension
     * does not use it for serialization. Other options are forwarded.
     */
    public function setOption(int $option, mixed $value): bool
    {
        if ($option === self::OPT_SERIALIZER) {
            $this->serializer = (int) $value;

            return true;
        }

        return $this->glide->setOption($option, $value);
    }

    public function getOption(int $option): mixed
    {
        if ($option === self::OPT_SERIALIZER) {
            return $this->serializer;
        }

        return $this->glide->getOption($option);
    }

    public function close(): bool
    {
        return $this->glide->close();
    }

}
