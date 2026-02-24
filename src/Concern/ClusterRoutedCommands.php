<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Concern;

/**
 * Wraps cluster-specific methods where ValkeyGlideCluster prepends a $route parameter.
 *
 * Used by ClusterClient only.
 */
trait ClusterRoutedCommands
{
    /** @return \ValkeyGlide|\ValkeyGlideCluster */
    abstract protected function getDriver(): object;

    public function ping(mixed $route = null, ?string $message = null): mixed
    {
        // ValkeyGlideCluster requires a route parameter; when omitted,
        // use 'randomNode' to match phpredis RedisCluster::ping() behavior.
        $route ??= 'randomNode';

        if ($message === null) {
            return $this->getDriver()->ping($route);
        }

        return $this->getDriver()->ping($route, $message);
    }

    public function flushDB(mixed $route, bool $async = false): mixed
    {
        return $this->getDriver()->flushDB($route, $async);
    }

    public function flushAll(mixed $route, bool $async = false): mixed
    {
        return $this->getDriver()->flushAll($route, $async);
    }

    public function dbSize(mixed $route): mixed
    {
        return $this->getDriver()->dbSize($route);
    }

    public function info(mixed $route, string ...$sections): mixed
    {
        return $this->getDriver()->info($route, ...$sections);
    }

    public function time(mixed $route): mixed
    {
        return $this->getDriver()->time($route);
    }

    public function randomKey(mixed $route): mixed
    {
        return $this->getDriver()->randomKey($route);
    }

    public function echo(mixed $route, string $msg): mixed
    {
        return $this->getDriver()->echo($route, $msg);
    }

    public function client(mixed $route, string $subcommand, ?string $arg = null): mixed
    {
        return $this->getDriver()->client($route, $subcommand, $arg);
    }

    public function rawcommand(mixed $route, string $command, mixed ...$args): mixed
    {
        return $this->getDriver()->rawcommand($route, $command, ...$args);
    }
}
