<?php

declare(strict_types=1);

namespace ValkeyGlideCompat;

/**
 * RedisCluster-compatible client wrapping phpredis.
 */
class PhpRedisClusterClient implements ClientInterface
{
    use Concern\PhpRedisPassthrough;

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
    public const OPT_SLAVE_FAILOVER = Constants::OPT_SLAVE_FAILOVER;

    // Failover strategy constants
    public const FAILOVER_NONE = Constants::FAILOVER_NONE;
    public const FAILOVER_ERROR = Constants::FAILOVER_ERROR;
    public const FAILOVER_DISTRIBUTE = Constants::FAILOVER_DISTRIBUTE;
    public const FAILOVER_DISTRIBUTE_SLAVES = Constants::FAILOVER_DISTRIBUTE_SLAVES;

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

    private \RedisCluster $redis;

    /**
     * @param string|null $name Cluster name (for seed configuration via php.ini)
     * @param array<string>|null $seeds Seed nodes as ['host:port', ...]
     * @param float|null $timeout Connection timeout in seconds
     * @param float|null $read_timeout Read timeout in seconds
     * @param bool $persistent Use persistent connections
     * @param mixed $auth Authentication credentials (string password or ['user', 'pass'])
     * @param array<string, mixed>|null $context Stream context options
     */
    public function __construct(
        ?string $name = null,
        ?array $seeds = null,
        ?float $timeout = null,
        ?float $read_timeout = null,
        bool $persistent = false,
        mixed $auth = null,
        ?array $context = null,
    ) {
        if (! extension_loaded('redis')) {
            throw new \RuntimeException('ext-redis not loaded');
        }

        $this->redis = new \RedisCluster(
            $name,
            $seeds ?? [],
            $timeout ?? 0.0,
            $read_timeout ?? 0.0,
            $persistent,
            $auth,
            $context,
        );
    }

    public function getDriver(): \RedisCluster
    {
        return $this->redis;
    }

    public function set(string $key, mixed $value, mixed $options = null): mixed
    {
        if ($options === null) {
            return $this->redis->set($key, $value);
        }

        return $this->redis->set($key, $value, $options);
    }

    public function get(string $key): mixed
    {
        return $this->redis->get($key);
    }

    public function expire(string $key, int $timeout, ?string $mode = null): mixed
    {
        if ($mode === null) {
            return $this->redis->expire($key, $timeout);
        }

        return $this->redis->expire($key, $timeout, $mode);
    }

    public function setOption(int $option, mixed $value): bool
    {
        return $this->redis->setOption($option, $value);
    }

    public function getOption(int $option): mixed
    {
        return $this->redis->getOption($option);
    }

    public function close(): bool
    {
        return $this->redis->close();
    }

    /** @param array<mixed> $arguments */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->redis->$name(...$arguments);
    }
}
