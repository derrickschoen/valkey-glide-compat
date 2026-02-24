<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Concern;

/**
 * Passthrough stubs for phpredis clients.
 *
 * Satisfies ClientInterface methods not already explicitly defined on
 * PhpRedisClient/PhpRedisClusterClient by delegating to the underlying
 * \Redis or \RedisCluster driver.
 */
trait PhpRedisPassthrough
{
    /** @return \Redis|\RedisCluster */
    abstract public function getDriver(): object;

    // =========================================================================
    // String commands
    // =========================================================================

    public function getSet(string $key, mixed $value): mixed
    {
        return $this->getDriver()->getset($key, $value);
    }

    public function getDel(string $key): mixed
    {
        return $this->getDriver()->getDel($key);
    }

    /** @param array<string, mixed> $options */
    public function getEx(string $key, array $options = []): mixed
    {
        return $this->getDriver()->getEx($key, $options);
    }

    /** @param array<string, mixed> $key_values */
    public function mSet(array $key_values): mixed
    {
        return $this->getDriver()->mset($key_values);
    }

    /** @param array<string> $keys */
    public function mGet(array $keys): mixed
    {
        return $this->getDriver()->mget($keys);
    }

    public function incr(string $key): mixed
    {
        return $this->getDriver()->incr($key);
    }

    public function decr(string $key): mixed
    {
        return $this->getDriver()->decr($key);
    }

    public function incrBy(string $key, int $value): mixed
    {
        return $this->getDriver()->incrBy($key, $value);
    }

    public function decrBy(string $key, int $value): mixed
    {
        return $this->getDriver()->decrBy($key, $value);
    }

    public function incrByFloat(string $key, float $value): mixed
    {
        return $this->getDriver()->incrByFloat($key, $value);
    }

    public function append(string $key, mixed $value): mixed
    {
        return $this->getDriver()->append($key, $value);
    }

    public function setex(string $key, int $expire, mixed $value): mixed
    {
        return $this->getDriver()->setex($key, $expire, $value);
    }

    public function psetex(string $key, int $expire, mixed $value): mixed
    {
        return $this->getDriver()->psetex($key, $expire, $value);
    }

    public function setnx(string $key, mixed $value): mixed
    {
        return $this->getDriver()->setnx($key, $value);
    }

    // =========================================================================
    // Key commands
    // =========================================================================

    public function del(string ...$keys): mixed
    {
        return $this->getDriver()->del(...$keys);
    }

    public function exists(string ...$keys): mixed
    {
        return $this->getDriver()->exists(...$keys);
    }

    public function expireAt(string $key, int $timestamp, ?string $mode = null): mixed
    {
        if ($mode === null) {
            return $this->getDriver()->expireAt($key, $timestamp);
        }

        return $this->getDriver()->expireAt($key, $timestamp, $mode);
    }

    public function pexpire(string $key, int $timeout, ?string $mode = null): mixed
    {
        if ($mode === null) {
            return $this->getDriver()->pexpire($key, $timeout);
        }

        return $this->getDriver()->pexpire($key, $timeout, $mode);
    }

    public function pexpireAt(string $key, int $timestamp, ?string $mode = null): mixed
    {
        if ($mode === null) {
            return $this->getDriver()->pexpireAt($key, $timestamp);
        }

        return $this->getDriver()->pexpireAt($key, $timestamp, $mode);
    }

    public function ttl(string $key): mixed
    {
        return $this->getDriver()->ttl($key);
    }

    public function pttl(string $key): mixed
    {
        return $this->getDriver()->pttl($key);
    }

    public function persist(string $key): mixed
    {
        return $this->getDriver()->persist($key);
    }

    public function type(string $key): mixed
    {
        return $this->getDriver()->type($key);
    }

    public function rename(string $srcKey, string $dstKey): mixed
    {
        return $this->getDriver()->rename($srcKey, $dstKey);
    }

    public function renameNx(string $srcKey, string $dstKey): mixed
    {
        return $this->getDriver()->renameNx($srcKey, $dstKey);
    }

    public function unlink(string ...$keys): mixed
    {
        return $this->getDriver()->unlink(...$keys);
    }

    // =========================================================================
    // Hash commands
    // =========================================================================

    public function hSet(string $key, mixed ...$fields_and_vals): mixed
    {
        return $this->getDriver()->hSet($key, ...$fields_and_vals);
    }

    public function hGet(string $key, string $field): mixed
    {
        return $this->getDriver()->hGet($key, $field);
    }

    public function hGetAll(string $key): mixed
    {
        return $this->getDriver()->hGetAll($key);
    }

    /** @param array<string, mixed> $members */
    public function hMSet(string $key, array $members): mixed
    {
        return $this->getDriver()->hMset($key, $members);
    }

    /** @param array<string> $fields */
    public function hMGet(string $key, array $fields): mixed
    {
        return $this->getDriver()->hMget($key, $fields);
    }

    public function hDel(string $key, string ...$fields): mixed
    {
        return $this->getDriver()->hDel($key, ...$fields);
    }

    public function hExists(string $key, string $field): mixed
    {
        return $this->getDriver()->hExists($key, $field);
    }

    public function hLen(string $key): mixed
    {
        return $this->getDriver()->hLen($key);
    }

    public function hKeys(string $key): mixed
    {
        return $this->getDriver()->hKeys($key);
    }

    public function hVals(string $key): mixed
    {
        return $this->getDriver()->hVals($key);
    }

    public function hIncrBy(string $key, string $field, int $value): mixed
    {
        return $this->getDriver()->hIncrBy($key, $field, $value);
    }

    public function hIncrByFloat(string $key, string $field, float $value): mixed
    {
        return $this->getDriver()->hIncrByFloat($key, $field, $value);
    }

    public function hSetNx(string $key, string $field, mixed $value): mixed
    {
        return $this->getDriver()->hSetNx($key, $field, $value);
    }

    // =========================================================================
    // List commands
    // =========================================================================

    public function lPush(string $key, mixed ...$values): mixed
    {
        return $this->getDriver()->lPush($key, ...$values);
    }

    public function rPush(string $key, mixed ...$values): mixed
    {
        return $this->getDriver()->rPush($key, ...$values);
    }

    public function lPop(string $key, int $count = 0): mixed
    {
        return $count > 0
            ? $this->getDriver()->lPop($key, $count)
            : $this->getDriver()->lPop($key);
    }

    public function rPop(string $key, int $count = 0): mixed
    {
        return $count > 0
            ? $this->getDriver()->rPop($key, $count)
            : $this->getDriver()->rPop($key);
    }

    public function lLen(string $key): mixed
    {
        return $this->getDriver()->lLen($key);
    }

    public function lIndex(string $key, int $index): mixed
    {
        return $this->getDriver()->lindex($key, $index);
    }

    public function lRange(string $key, int $start, int $end): mixed
    {
        return $this->getDriver()->lrange($key, $start, $end);
    }

    public function lSet(string $key, int $index, mixed $value): mixed
    {
        return $this->getDriver()->lSet($key, $index, $value);
    }

    public function lRem(string $key, mixed $value, int $count): mixed
    {
        return $this->getDriver()->lRem($key, $value, $count);
    }

    public function lTrim(string $key, int $start, int $end): mixed
    {
        return $this->getDriver()->lTrim($key, $start, $end);
    }

    // =========================================================================
    // Set commands
    // =========================================================================

    public function sAdd(string $key, mixed $value, mixed ...$other_values): mixed
    {
        return $this->getDriver()->sAdd($key, $value, ...$other_values);
    }

    public function sMembers(string $key): mixed
    {
        return $this->getDriver()->sMembers($key);
    }

    public function sPop(string $key, int $count = 0): mixed
    {
        return $count > 0
            ? $this->getDriver()->sPop($key, $count)
            : $this->getDriver()->sPop($key);
    }

    public function sRandMember(string $key, int $count = 0): mixed
    {
        return $count !== 0
            ? $this->getDriver()->sRandMember($key, $count)
            : $this->getDriver()->sRandMember($key);
    }

    public function sCard(string $key): mixed
    {
        return $this->getDriver()->sCard($key);
    }

    public function sIsMember(string $key, mixed $member): mixed
    {
        return $this->getDriver()->sIsMember($key, $member);
    }

    public function sRem(string $key, mixed ...$members): mixed
    {
        return $this->getDriver()->sRem($key, ...$members);
    }

    // =========================================================================
    // Sorted set commands
    // =========================================================================

    public function zAdd(string $key, mixed ...$args): mixed
    {
        return $this->getDriver()->zAdd($key, ...$args);
    }

    public function zRange(string $key, mixed $start, mixed $end, mixed ...$args): mixed
    {
        return $this->getDriver()->zRange($key, $start, $end, ...$args);
    }

    public function zRem(string $key, mixed ...$members): mixed
    {
        return $this->getDriver()->zRem($key, ...$members);
    }

    public function zCard(string $key): mixed
    {
        return $this->getDriver()->zCard($key);
    }

    public function zScore(string $key, mixed $member): mixed
    {
        return $this->getDriver()->zScore($key, $member);
    }

    public function zRank(string $key, mixed $member): mixed
    {
        return $this->getDriver()->zRank($key, $member);
    }

    public function zRevRank(string $key, mixed $member): mixed
    {
        return $this->getDriver()->zRevRank($key, $member);
    }

    public function zCount(string $key, string $min, string $max): mixed
    {
        return $this->getDriver()->zCount($key, $min, $max);
    }

    public function zIncrBy(string $key, float $value, mixed $member): mixed
    {
        return $this->getDriver()->zIncrBy($key, $value, $member);
    }
}
