<?php

declare(strict_types=1);

namespace ValkeyGlideCompat;

/**
 * Common interface for standalone and cluster clients.
 *
 * Covers the most commonly used Redis commands that both client types
 * explicitly implement. Commands not listed here are still accessible
 * via __call() on all client implementations.
 */
interface ClientInterface
{
    // Connection / lifecycle
    public function close(): bool;

    /** Return the underlying backend driver instance. */
    public function getDriver(): object;

    // Options
    public function setOption(int $option, mixed $value): bool;

    public function getOption(int $option): mixed;

    // String commands
    public function set(string $key, mixed $value, mixed $options = null): mixed;

    public function get(string $key): mixed;

    public function getSet(string $key, mixed $value): mixed;

    public function getDel(string $key): mixed;

    /** @param array<string, mixed> $options */
    public function getEx(string $key, array $options = []): mixed;

    /** @param array<string, mixed> $key_values */
    public function mSet(array $key_values): mixed;

    /** @param array<string> $keys */
    public function mGet(array $keys): mixed;

    public function incr(string $key): mixed;

    public function decr(string $key): mixed;

    public function incrBy(string $key, int $value): mixed;

    public function decrBy(string $key, int $value): mixed;

    public function incrByFloat(string $key, float $value): mixed;

    public function append(string $key, mixed $value): mixed;

    public function setex(string $key, int $expire, mixed $value): mixed;

    public function psetex(string $key, int $expire, mixed $value): mixed;

    public function setnx(string $key, mixed $value): mixed;

    // Key commands
    public function del(string ...$keys): mixed;

    public function exists(string ...$keys): mixed;

    public function expire(string $key, int $timeout, ?string $mode = null): mixed;

    public function expireAt(string $key, int $timestamp, ?string $mode = null): mixed;

    public function pexpire(string $key, int $timeout, ?string $mode = null): mixed;

    public function pexpireAt(string $key, int $timestamp, ?string $mode = null): mixed;

    public function ttl(string $key): mixed;

    public function pttl(string $key): mixed;

    public function persist(string $key): mixed;

    public function type(string $key): mixed;

    public function rename(string $srcKey, string $dstKey): mixed;

    public function renameNx(string $srcKey, string $dstKey): mixed;

    public function unlink(string ...$keys): mixed;

    // Hash commands
    public function hSet(string $key, mixed ...$fields_and_vals): mixed;

    public function hGet(string $key, string $field): mixed;

    public function hGetAll(string $key): mixed;

    /** @param array<string, mixed> $members */
    public function hMSet(string $key, array $members): mixed;

    /** @param array<string> $fields */
    public function hMGet(string $key, array $fields): mixed;

    public function hDel(string $key, string ...$fields): mixed;

    public function hExists(string $key, string $field): mixed;

    public function hLen(string $key): mixed;

    public function hKeys(string $key): mixed;

    public function hVals(string $key): mixed;

    public function hIncrBy(string $key, string $field, int $value): mixed;

    public function hIncrByFloat(string $key, string $field, float $value): mixed;

    public function hSetNx(string $key, string $field, mixed $value): mixed;

    // List commands
    public function lPush(string $key, mixed ...$values): mixed;

    public function rPush(string $key, mixed ...$values): mixed;

    public function lPop(string $key, int $count = 0): mixed;

    public function rPop(string $key, int $count = 0): mixed;

    public function lLen(string $key): mixed;

    public function lIndex(string $key, int $index): mixed;

    public function lRange(string $key, int $start, int $end): mixed;

    public function lSet(string $key, int $index, mixed $value): mixed;

    public function lRem(string $key, mixed $value, int $count): mixed;

    public function lTrim(string $key, int $start, int $end): mixed;

    // Set commands
    public function sAdd(string $key, mixed $value, mixed ...$other_values): mixed;

    public function sMembers(string $key): mixed;

    public function sPop(string $key, int $count = 0): mixed;

    public function sRandMember(string $key, int $count = 0): mixed;

    public function sCard(string $key): mixed;

    public function sIsMember(string $key, mixed $member): mixed;

    public function sRem(string $key, mixed ...$members): mixed;

    // Sorted set commands
    public function zAdd(string $key, mixed ...$args): mixed;

    public function zRange(string $key, mixed $start, mixed $end, mixed ...$args): mixed;

    public function zRem(string $key, mixed ...$members): mixed;

    public function zCard(string $key): mixed;

    public function zScore(string $key, mixed $member): mixed;

    public function zRank(string $key, mixed $member): mixed;

    public function zRevRank(string $key, mixed $member): mixed;

    public function zCount(string $key, string $min, string $max): mixed;

    public function zIncrBy(string $key, float $value, mixed $member): mixed;
}
