<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Concern;

use ValkeyGlideCompat\Constants;

/**
 * PHP-level serialization support.
 *
 * The C extension stores the OPT_SERIALIZER value but does NOT use it to
 * serialize/unserialize data in set()/get(). This trait provides the
 * serialization logic at the PHP layer.
 */
trait Serialization
{
    private int $serializer = Constants::SERIALIZER_NONE;

    protected function serializeValue(mixed $value): mixed
    {
        if ($this->serializer === Constants::SERIALIZER_NONE) {
            return $value;
        }

        return match ($this->serializer) {
            Constants::SERIALIZER_PHP => \serialize($value),
            Constants::SERIALIZER_JSON => json_encode($value),
            Constants::SERIALIZER_IGBINARY => function_exists('igbinary_serialize')
                ? igbinary_serialize($value)
                : throw new \RuntimeException('igbinary extension not loaded'),
            Constants::SERIALIZER_MSGPACK => function_exists('msgpack_pack')
                ? msgpack_pack($value)
                : throw new \RuntimeException('msgpack extension not loaded'),
            default => $value,
        };
    }

    protected function unserializeValue(mixed $value): mixed
    {
        if (! is_string($value) || $this->serializer === Constants::SERIALIZER_NONE) {
            return $value;
        }

        return match ($this->serializer) {
            Constants::SERIALIZER_PHP => \unserialize($value),
            Constants::SERIALIZER_JSON => json_decode($value, true),
            Constants::SERIALIZER_IGBINARY => function_exists('igbinary_unserialize')
                ? igbinary_unserialize($value)
                : throw new \RuntimeException('igbinary extension not loaded'),
            Constants::SERIALIZER_MSGPACK => function_exists('msgpack_unpack')
                ? msgpack_unpack($value)
                : throw new \RuntimeException('msgpack extension not loaded'),
            default => $value,
        };
    }
}
