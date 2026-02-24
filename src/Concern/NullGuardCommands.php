<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Concern;

/**
 * Wraps methods where ValkeyGlide rejects null for optional parameters.
 *
 * phpredis allows passing null for optional params, but the C extension
 * may throw TypeError. This trait omits null arguments before forwarding.
 *
 * Also applies serialization for set()/get() since the C extension does not
 * handle serialization natively.
 *
 * Used by both Client and ClusterClient (methods with identical signatures).
 */
trait NullGuardCommands
{
    /** @return \ValkeyGlide|\ValkeyGlideCluster */
    abstract protected function getDriver(): object;

    abstract protected function serializeValue(mixed $value): mixed;

    abstract protected function unserializeValue(mixed $value): mixed;

    public function set(string $key, mixed $value, mixed $options = null): mixed
    {
        $value = $this->serializeValue($value);

        if ($options === null) {
            return $this->getDriver()->set($key, $value);
        }

        return $this->getDriver()->set($key, $value, $options);
    }

    public function get(string $key): mixed
    {
        $result = $this->getDriver()->get($key);

        if ($result === false || $result === null) {
            return $result;
        }

        return $this->unserializeValue($result);
    }

    public function expire(string $key, int $timeout, ?string $mode = null): mixed
    {
        if ($mode === null) {
            return $this->getDriver()->expire($key, $timeout);
        }

        return $this->getDriver()->expire($key, $timeout, $mode);
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

    public function scriptFlush(?string $mode = null): mixed
    {
        if ($mode === null) {
            return $this->getDriver()->scriptFlush();
        }

        return $this->getDriver()->scriptFlush($mode);
    }
}
