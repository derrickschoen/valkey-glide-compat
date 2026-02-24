<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Concern;

/**
 * Passthrough stubs for Glide clients.
 *
 * Satisfies ClientInterface methods not already covered by
 * NullGuardCommands or SerializedCommands by delegating directly
 * to the underlying ValkeyGlide/ValkeyGlideCluster driver.
 *
 * Only contains commands that do NOT involve value serialization
 * (pure key/metadata operations). Commands that handle values go
 * into SerializedCommands so that OPT_SERIALIZER is respected.
 */
trait GlidePassthrough
{
    /** @return \ValkeyGlide|\ValkeyGlideCluster */
    abstract protected function getDriver(): object;

    // =========================================================================
    // String commands (numeric / non-serialized)
    // =========================================================================

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
    // Hash commands (non-value operations)
    // =========================================================================

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

    public function hIncrBy(string $key, string $field, int $value): mixed
    {
        return $this->getDriver()->hIncrBy($key, $field, $value);
    }

    public function hIncrByFloat(string $key, string $field, float $value): mixed
    {
        return $this->getDriver()->hIncrByFloat($key, $field, $value);
    }

    // =========================================================================
    // List commands (non-value operations)
    // =========================================================================

    public function lLen(string $key): mixed
    {
        return $this->getDriver()->lLen($key);
    }

    public function lTrim(string $key, int $start, int $end): mixed
    {
        return $this->getDriver()->lTrim($key, $start, $end);
    }

    // =========================================================================
    // Set commands (non-value operations)
    // =========================================================================

    public function sCard(string $key): mixed
    {
        return $this->getDriver()->sCard($key);
    }

    // =========================================================================
    // Sorted set commands (non-value operations)
    // =========================================================================

    public function zCard(string $key): mixed
    {
        return $this->getDriver()->zCard($key);
    }

    public function zCount(string $key, string $min, string $max): mixed
    {
        return $this->getDriver()->zCount($key, $min, $max);
    }
}
