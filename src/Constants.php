<?php

declare(strict_types=1);

namespace ValkeyGlideCompat;

/**
 * phpredis-compatible constant names.
 *
 * Values are identical to ValkeyGlide's C extension constants.
 * This class provides the Redis::REDIS_* naming convention that phpredis users expect.
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

    // Option constants (OPT_*)
    public const OPT_REPLY_LITERAL = 1;
    public const OPT_SERIALIZER = 16;
    public const OPT_PREFIX = 17;
    public const OPT_READ_TIMEOUT = 18;
    public const OPT_SCAN = 19;
    public const OPT_FAILOVER = 20;
    public const OPT_TCP_KEEPALIVE = 21;
    public const OPT_COMPRESSION = 22;
    public const OPT_COMPRESSION_LEVEL = 23;
    public const OPT_NULL_MULTIBULK_AS_NULL = 24;
    public const OPT_MAX_RETRIES = 25;
    public const OPT_BACKOFF_ALGORITHM = 26;
    public const OPT_BACKOFF_BASE = 27;
    public const OPT_BACKOFF_CAP = 28;
    public const OPT_PACK_IGNORE_NUMBERS = 29;

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
}
