<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Concern;

/**
 * Stores connection parameters for phpredis introspection methods.
 *
 * Translates phpredis-style connect() positional args to ValkeyGlide named params,
 * and provides getHost()/getPort()/isConnected() methods.
 *
 * Used by Client only (cluster connects in constructor).
 */
trait ConnectionInfo
{
    private string $host = '';
    private int $port = 0;
    private bool $connected = false;

    /** @return \ValkeyGlide|\ValkeyGlideCluster */
    abstract protected function getDriver(): object;

    /**
     * @note ValkeyGlide manages connection pooling internally; persistent_id
     *       is accepted for API compatibility but has no effect.
     */
    public function connect(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 0.0,
        ?string $persistent_id = null,
        int $retry_interval = 0,
        float $read_timeout = 0.0,
    ): bool {
        if ($persistent_id !== null) {
            trigger_error(
                'ValkeyGlideCompat: persistent_id is accepted for API '
                . 'compatibility but not used by ValkeyGlide. The extension '
                . 'manages connection pooling internally.',
                E_USER_NOTICE,
            );
        }

        $this->host = $host;
        $this->port = $port;

        $result = $this->getDriver()->connect(
            host: $host,
            port: $port,
            timeout: $timeout > 0 ? $timeout : null,
            read_timeout: $read_timeout > 0 ? $read_timeout : null,
        );

        $this->connected = (bool) $result;

        return $result;
    }

    public function pconnect(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 0.0,
        ?string $persistent_id = null,
        int $retry_interval = 0,
        float $read_timeout = 0.0,
    ): bool {
        return $this->connect($host, $port, $timeout, $persistent_id, $retry_interval, $read_timeout);
    }

    public function open(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 0.0,
        ?string $persistent_id = null,
        int $retry_interval = 0,
        float $read_timeout = 0.0,
    ): bool {
        return $this->connect($host, $port, $timeout, $persistent_id, $retry_interval, $read_timeout);
    }

    public function popen(
        string $host = '127.0.0.1',
        int $port = 6379,
        float $timeout = 0.0,
        ?string $persistent_id = null,
        int $retry_interval = 0,
        float $read_timeout = 0.0,
    ): bool {
        return $this->connect($host, $port, $timeout, $persistent_id, $retry_interval, $read_timeout);
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }

    public function close(): bool
    {
        $result = $this->getDriver()->close();
        $this->connected = false;

        return $result;
    }
}
