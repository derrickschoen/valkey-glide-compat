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
    abstract protected function getGlideClient(): \ValkeyGlide|\ValkeyGlideCluster;

    public function ping(mixed $route, ?string $message = null): mixed
    {
        if ($message === null) {
            return $this->getGlideClient()->ping($route);
        }

        return $this->getGlideClient()->ping($route, $message);
    }

    public function flushDB(mixed $route, bool $async = false): mixed
    {
        return $this->getGlideClient()->flushDB($route, $async);
    }

    public function flushAll(mixed $route, bool $async = false): mixed
    {
        return $this->getGlideClient()->flushAll($route, $async);
    }

    public function dbSize(mixed $route): mixed
    {
        return $this->getGlideClient()->dbSize($route);
    }

    public function info(mixed $route, string ...$sections): mixed
    {
        return $this->getGlideClient()->info($route, ...$sections);
    }

    public function time(mixed $route): mixed
    {
        return $this->getGlideClient()->time($route);
    }

    public function randomKey(mixed $route): mixed
    {
        return $this->getGlideClient()->randomKey($route);
    }

    public function echo(mixed $route, string $msg): mixed
    {
        return $this->getGlideClient()->echo($route, $msg);
    }

    public function client(mixed $route, string $subcommand, ?string $arg = null): mixed
    {
        return $this->getGlideClient()->client($route, $subcommand, $arg);
    }

    public function rawcommand(mixed $route, string $command, mixed ...$args): mixed
    {
        return $this->getGlideClient()->rawcommand($route, $command, ...$args);
    }
}
