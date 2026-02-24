<?php

declare(strict_types=1);

namespace ValkeyGlideCompat;

use ValkeyGlide;
use ValkeyGlideCompat\Concern\ConnectionInfo;
use ValkeyGlideCompat\Concern\NullGuardCommands;
use ValkeyGlideCompat\Concern\Serialization;
use ValkeyGlideCompat\Concern\SerializedCommands;

/**
 * Redis-compatible client wrapping ValkeyGlide.
 *
 * Translates the phpredis Redis class API to ValkeyGlide calls,
 * allowing drop-in replacement in applications using phpredis.
 *
 * The C extension does not handle serialization natively, so this class
 * provides PHP-level serialization via the Serialization trait.
 */
class Client implements ClientInterface
{
    use NullGuardCommands;
    use ConnectionInfo;
    use Serialization;
    use SerializedCommands;

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

    private ValkeyGlide $glide;

    public function __construct()
    {
        $this->glide = new ValkeyGlide();
    }

    public function getGlideClient(): ValkeyGlide
    {
        return $this->glide;
    }

    // Standalone-specific null-guards (not in shared trait because cluster signatures differ)

    public function ping(?string $message = null): mixed
    {
        if ($message === null) {
            return $this->glide->ping();
        }

        return $this->glide->ping($message);
    }

    public function flushDB(?bool $sync = null): mixed
    {
        if ($sync === null) {
            return $this->glide->flushDB();
        }

        return $this->glide->flushDB($sync);
    }

    public function flushAll(?bool $sync = null): mixed
    {
        if ($sync === null) {
            return $this->glide->flushAll();
        }

        return $this->glide->flushAll($sync);
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

    /** @param array<mixed> $arguments */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->glide->$name(...$arguments);
    }
}
