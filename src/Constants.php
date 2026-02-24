<?php

declare(strict_types=1);

namespace ValkeyGlideCompat;

/**
 * phpredis-compatible constant names.
 *
 * OPT_* values 1-15 match both phpredis and the ValkeyGlide C extension.
 * The mapToExtension() helper exists as a stable API boundary.
 */
final class Constants
{
    // Data types (phpredis Redis::REDIS_*)
    public const REDIS_NOT_FOUND = 0;
    public const REDIS_STRING = 1;
    public const REDIS_SET = 2;
    public const REDIS_LIST = 3;
    public const REDIS_ZSET = 4;
    public const REDIS_HASH = 5;
    public const REDIS_STREAM = 6;

    // Transaction modes
    public const MULTI = 0;
    public const PIPELINE = 1;

    // Option constants — phpredis-compatible values (OPT_*)
    public const OPT_SERIALIZER = 1;
    public const OPT_PREFIX = 2;
    public const OPT_READ_TIMEOUT = 3;
    public const OPT_SCAN = 4;
    public const OPT_FAILOVER = 5;
    public const OPT_TCP_KEEPALIVE = 6;
    public const OPT_COMPRESSION = 7;
    public const OPT_REPLY_LITERAL = 8;
    public const OPT_COMPRESSION_LEVEL = 9;
    public const OPT_NULL_MULTIBULK_AS_NULL = 10;
    public const OPT_MAX_RETRIES = 11;
    public const OPT_BACKOFF_ALGORITHM = 12;
    public const OPT_BACKOFF_BASE = 13;
    public const OPT_BACKOFF_CAP = 14;
    public const OPT_PACK_IGNORE_NUMBERS = 15;

    // Serializer constants (SERIALIZER_*)
    public const SERIALIZER_NONE = 0;
    public const SERIALIZER_PHP = 1;
    public const SERIALIZER_IGBINARY = 2;
    public const SERIALIZER_MSGPACK = 3;
    public const SERIALIZER_JSON = 4;

    // Scan constants (SCAN_*)
    public const SCAN_NORETRY = 0;
    public const SCAN_RETRY = 1;
    public const SCAN_PREFIX = 2;
    public const SCAN_NOPREFIX = 3;

    // Backoff algorithm constants (BACKOFF_ALGORITHM_*)
    public const BACKOFF_ALGORITHM_DEFAULT = 0;
    public const BACKOFF_ALGORITHM_DECORRELATED_JITTER = 1;
    public const BACKOFF_ALGORITHM_FULL_JITTER = 2;
    public const BACKOFF_ALGORITHM_EQUAL_JITTER = 3;
    public const BACKOFF_ALGORITHM_EXPONENTIAL = 4;
    public const BACKOFF_ALGORITHM_UNIFORM = 5;
    public const BACKOFF_ALGORITHM_CONSTANT = 6;

    /** Translate a PHPRedis option ID to the C extension's ID. */
    public static function mapToExtension(int $option): int
    {
        return $option;
    }
}
