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
    /** @return \ValkeyGlide|\ValkeyGlideCluster */
    abstract protected function getDriver(): object;

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

        return $this->getDriver()->hSet($key, ...$serialized);
    }

    public function hGet(string $key, string $field): mixed
    {
        $result = $this->getDriver()->hGet($key, $field);

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function hGetAll(string $key): mixed
    {
        $result = $this->getDriver()->hGetAll($key);
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

        return $this->getDriver()->hMset($key, $serialized);
    }

    /** @param array<string> $fields */
    public function hMGet(string $key, array $fields): mixed
    {
        $result = $this->getDriver()->hMget($key, $fields);
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

        return $this->getDriver()->lPush($key, ...$serialized);
    }

    public function rPush(string $key, mixed ...$values): mixed
    {
        $serialized = array_map(fn ($v) => $this->serializeValue($v), $values);

        return $this->getDriver()->rPush($key, ...$serialized);
    }

    public function lPop(string $key, int $count = 0): mixed
    {
        $result = $count > 0
            ? $this->getDriver()->lPop($key, $count)
            : $this->getDriver()->lPop($key);

        if (is_array($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function rPop(string $key, int $count = 0): mixed
    {
        $result = $count > 0
            ? $this->getDriver()->rPop($key, $count)
            : $this->getDriver()->rPop($key);

        if (is_array($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function lIndex(string $key, int $index): mixed
    {
        $result = $this->getDriver()->lindex($key, $index);

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function lRange(string $key, int $start, int $end): mixed
    {
        $result = $this->getDriver()->lrange($key, $start, $end);
        if (! is_array($result)) {
            return $result;
        }

        return array_map(fn ($v) => $this->unserializeValue($v), $result);
    }

    public function lSet(string $key, int $index, mixed $value): mixed
    {
        return $this->getDriver()->lSet($key, $index, $this->serializeValue($value));
    }

    // =========================================================================
    // Set commands
    // =========================================================================

    public function sAdd(string $key, mixed $value, mixed ...$other_values): mixed
    {
        $serialized = $this->serializeValue($value);
        $serializedOthers = array_map(fn ($v) => $this->serializeValue($v), $other_values);

        return $this->getDriver()->sAdd($key, $serialized, ...$serializedOthers);
    }

    public function sMembers(string $key): mixed
    {
        $result = $this->getDriver()->sMembers($key);
        if (! is_array($result)) {
            return $result;
        }

        return array_map(fn ($v) => $this->unserializeValue($v), $result);
    }

    public function sPop(string $key, int $count = 0): mixed
    {
        $result = $count > 0
            ? $this->getDriver()->sPop($key, $count)
            : $this->getDriver()->sPop($key);

        if (is_array($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function sRandMember(string $key, int $count = 0): mixed
    {
        $result = $count !== 0
            ? $this->getDriver()->sRandMember($key, $count)
            : $this->getDriver()->sRandMember($key);

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

        return $this->getDriver()->mset($serialized);
    }

    /** @param array<string> $keys */
    public function mGet(array $keys): mixed
    {
        $result = $this->getDriver()->mget($keys);
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
        $result = $this->getDriver()->getset($key, $this->serializeValue($value));

        return $result === false ? $result : $this->unserializeValue($result);
    }

    public function getDel(string $key): mixed
    {
        $result = $this->getDriver()->getDel($key);

        return $result === false ? $result : $this->unserializeValue($result);
    }

    /** @param array<string, mixed> $options */
    public function getEx(string $key, array $options = []): mixed
    {
        $result = $this->getDriver()->getEx($key, $options);

        return $result === false ? $result : $this->unserializeValue($result);
    }

    // =========================================================================
    // String commands (value serialization)
    // =========================================================================

    public function append(string $key, mixed $value): mixed
    {
        return $this->getDriver()->append($key, $this->serializeValue($value));
    }

    public function setex(string $key, int $expire, mixed $value): mixed
    {
        return $this->getDriver()->setex($key, $expire, $this->serializeValue($value));
    }

    public function psetex(string $key, int $expire, mixed $value): mixed
    {
        return $this->getDriver()->psetex($key, $expire, $this->serializeValue($value));
    }

    public function setnx(string $key, mixed $value): mixed
    {
        return $this->getDriver()->setnx($key, $this->serializeValue($value));
    }

    // =========================================================================
    // Hash commands (value serialization)
    // =========================================================================

    public function hVals(string $key): mixed
    {
        $result = $this->getDriver()->hVals($key);
        if (! is_array($result)) {
            return $result;
        }

        return array_map(fn ($v) => $this->unserializeValue($v), $result);
    }

    public function hSetNx(string $key, string $field, mixed $value): mixed
    {
        return $this->getDriver()->hSetNx($key, $field, $this->serializeValue($value));
    }

    // =========================================================================
    // List commands (value serialization)
    // =========================================================================

    public function lRem(string $key, mixed $value, int $count): mixed
    {
        return $this->getDriver()->lRem($key, $this->serializeValue($value), $count);
    }

    // =========================================================================
    // Set commands (member serialization)
    // =========================================================================

    public function sIsMember(string $key, mixed $member): mixed
    {
        return $this->getDriver()->sIsMember($key, $this->serializeValue($member));
    }

    public function sRem(string $key, mixed ...$members): mixed
    {
        $serialized = array_map(fn ($v) => $this->serializeValue($v), $members);

        return $this->getDriver()->sRem($key, ...$serialized);
    }

    // =========================================================================
    // Sorted set commands (member serialization)
    // =========================================================================

    public function zAdd(string $key, mixed ...$args): mixed
    {
        if (empty($args)) {
            return 0;
        }

        // Detect if first arg is an options array (NX, XX, GT, LT, CH)
        $startIdx = 0;
        if (isset($args[0]) && is_array($args[0])) {
            $startIdx = 1;
        }

        // From startIdx, arguments alternate: score, member, score, member, ...
        // Serialize the member args (odd positions relative to startIdx)
        for ($i = $startIdx, $len = count($args); $i < $len; $i++) {
            if (($i - $startIdx) % 2 === 1) {
                $args[$i] = $this->serializeValue($args[$i]);
            }
        }

        return $this->getDriver()->zAdd($key, ...$args);
    }

    public function zRange(string $key, mixed $start, mixed $end, mixed ...$args): mixed
    {
        $result = $this->getDriver()->zRange($key, $start, $end, ...$args);
        if (! is_array($result)) {
            return $result;
        }

        // Detect WITHSCORES (assoc array: member => score) vs indexed array
        if (array_is_list($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        // WITHSCORES: keys are members, values are scores
        $out = [];
        foreach ($result as $member => $score) {
            $out[$this->unserializeValue($member)] = $score;
        }

        return $out;
    }

    public function zRem(string $key, mixed ...$members): mixed
    {
        $serialized = array_map(fn ($v) => $this->serializeValue($v), $members);

        return $this->getDriver()->zRem($key, ...$serialized);
    }

    public function zScore(string $key, mixed $member): mixed
    {
        return $this->getDriver()->zScore($key, $this->serializeValue($member));
    }

    public function zRank(string $key, mixed $member): mixed
    {
        return $this->getDriver()->zRank($key, $this->serializeValue($member));
    }

    public function zRevRank(string $key, mixed $member): mixed
    {
        return $this->getDriver()->zRevRank($key, $this->serializeValue($member));
    }

    public function zIncrBy(string $key, float $value, mixed $member): mixed
    {
        return $this->getDriver()->zIncrBy($key, $value, $this->serializeValue($member));
    }
}
