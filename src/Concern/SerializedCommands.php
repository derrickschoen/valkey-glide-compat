<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Concern;

/**
 * Serialization wrappers for common value-storing commands.
 *
 * phpredis applies OPT_SERIALIZER to ALL value-storing commands, not just
 * set()/get(). This trait wraps the most commonly used commands to
 * serialize values before storage and unserialize after retrieval.
 *
 * Commands NOT wrapped here still pass through via __call() without
 * serialization. This is a known limitation — the trait covers the ~20
 * most commonly used commands. Users needing serialization on exotic
 * commands should use serializeValue()/unserializeValue() manually.
 */
trait SerializedCommands
{
    abstract protected function getGlideClient(): \ValkeyGlide|\ValkeyGlideCluster;

    abstract protected function serializeValue(mixed $value): mixed;

    abstract protected function unserializeValue(mixed $value): mixed;

    // =========================================================================
    // Hash commands
    // =========================================================================

    public function hSet(string $key, mixed ...$fields_and_vals): mixed
    {
        // hSet can be called as hSet($key, $field, $value) or hSet($key, $f1, $v1, $f2, $v2, ...)
        // Serialize only the value arguments (odd-indexed: 1, 3, 5, ...)
        $serialized = [];
        foreach ($fields_and_vals as $i => $arg) {
            $serialized[] = ($i % 2 === 1) ? $this->serializeValue($arg) : $arg;
        }

        return $this->getGlideClient()->hSet($key, ...$serialized);
    }

    public function hGet(string $key, string $field): mixed
    {
        $result = $this->getGlideClient()->hGet($key, $field);

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function hGetAll(string $key): mixed
    {
        $result = $this->getGlideClient()->hGetAll($key);
        if (! is_array($result)) {
            return $result;
        }

        $deserialized = [];
        foreach ($result as $k => $v) {
            $deserialized[$k] = $this->unserializeValue($v);
        }

        return $deserialized;
    }

    /** @param array<string, mixed> $members */
    public function hMSet(string $key, array $members): mixed
    {
        $serialized = array_map(fn ($v) => $this->serializeValue($v), $members);

        return $this->getGlideClient()->hMset($key, $serialized);
    }

    /** @param array<string> $fields */
    public function hMGet(string $key, array $fields): mixed
    {
        $result = $this->getGlideClient()->hMget($key, $fields);
        if (! is_array($result)) {
            return $result;
        }

        return array_map(
            fn ($v) => $v === false ? $v : $this->unserializeValue($v),
            $result,
        );
    }

    // =========================================================================
    // List commands
    // =========================================================================

    public function lPush(string $key, mixed ...$values): mixed
    {
        $serialized = array_map(fn ($v) => $this->serializeValue($v), $values);

        return $this->getGlideClient()->lPush($key, ...$serialized);
    }

    public function rPush(string $key, mixed ...$values): mixed
    {
        $serialized = array_map(fn ($v) => $this->serializeValue($v), $values);

        return $this->getGlideClient()->rPush($key, ...$serialized);
    }

    public function lPop(string $key, int $count = 0): mixed
    {
        $result = $count > 0
            ? $this->getGlideClient()->lPop($key, $count)
            : $this->getGlideClient()->lPop($key);

        if (is_array($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function rPop(string $key, int $count = 0): mixed
    {
        $result = $count > 0
            ? $this->getGlideClient()->rPop($key, $count)
            : $this->getGlideClient()->rPop($key);

        if (is_array($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function lIndex(string $key, int $index): mixed
    {
        $result = $this->getGlideClient()->lindex($key, $index);

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function lRange(string $key, int $start, int $end): mixed
    {
        $result = $this->getGlideClient()->lrange($key, $start, $end);
        if (! is_array($result)) {
            return $result;
        }

        return array_map(fn ($v) => $this->unserializeValue($v), $result);
    }

    public function lSet(string $key, int $index, mixed $value): mixed
    {
        return $this->getGlideClient()->lSet($key, $index, $this->serializeValue($value));
    }

    // =========================================================================
    // Set commands
    // =========================================================================

    public function sAdd(string $key, mixed $value, mixed ...$other_values): mixed
    {
        $serialized = $this->serializeValue($value);
        $serializedOthers = array_map(fn ($v) => $this->serializeValue($v), $other_values);

        return $this->getGlideClient()->sAdd($key, $serialized, ...$serializedOthers);
    }

    public function sMembers(string $key): mixed
    {
        $result = $this->getGlideClient()->sMembers($key);
        if (! is_array($result)) {
            return $result;
        }

        return array_map(fn ($v) => $this->unserializeValue($v), $result);
    }

    public function sPop(string $key, int $count = 0): mixed
    {
        $result = $count > 0
            ? $this->getGlideClient()->sPop($key, $count)
            : $this->getGlideClient()->sPop($key);

        if (is_array($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function sRandMember(string $key, int $count = 0): mixed
    {
        $result = $count !== 0
            ? $this->getGlideClient()->sRandMember($key, $count)
            : $this->getGlideClient()->sRandMember($key);

        if (is_array($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        return $result === false ? $result : $this->unserializeValue($result);
    }

    // =========================================================================
    // Multi-key commands
    // =========================================================================

    /** @param array<string, mixed> $key_values */
    public function mSet(array $key_values): mixed
    {
        $serialized = array_map(fn ($v) => $this->serializeValue($v), $key_values);

        return $this->getGlideClient()->mset($serialized);
    }

    /** @param array<string> $keys */
    public function mGet(array $keys): mixed
    {
        $result = $this->getGlideClient()->mget($keys);
        if (! is_array($result)) {
            return $result;
        }

        return array_map(
            fn ($v) => $v === false ? $v : $this->unserializeValue($v),
            $result,
        );
    }

    // =========================================================================
    // String commands (result deserialization only)
    // =========================================================================

    public function getSet(string $key, mixed $value): mixed
    {
        $result = $this->getGlideClient()->getset($key, $this->serializeValue($value));

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function getDel(string $key): mixed
    {
        $result = $this->getGlideClient()->getDel($key);

        return $result === false ? $result : $this->unserializeValue($result);
    }

    /** @param array<string, mixed> $options */
    public function getEx(string $key, array $options = []): mixed
    {
        $result = $this->getGlideClient()->getEx($key, $options);

        return $result === false ? $result : $this->unserializeValue($result);
    }
}
