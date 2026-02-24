<?php

declare(strict_types=1);

namespace ValkeyGlideCompat;

/**
 * Factory for creating Redis-compatible client instances.
 *
 * Detects available backends (ValkeyGlide extension or phpredis)
 * and creates the appropriate client.
 */
class ClientFactory
{
    public const BACKEND_GLIDE = 'glide';
    public const BACKEND_PHPREDIS = 'phpredis';

    /**
     * Create a standalone client using the best available backend.
     *
     * @param string|null $preferredBackend Force a specific backend ('glide' or 'phpredis')
     * @return ClientInterface
     * @throws \RuntimeException If no suitable backend is available
     */
    public static function create(?string $preferredBackend = null): ClientInterface
    {
        $backend = $preferredBackend ?? self::detectBackend();

        return match ($backend) {
            self::BACKEND_GLIDE => new Client(),
            self::BACKEND_PHPREDIS => new PhpRedisClient(),
            default => throw new \RuntimeException("Unknown backend: {$backend}"),
        };
    }

    /**
     * Create a cluster client using the best available backend.
     *
     * @param string|null $preferredBackend Force a specific backend
     * @param string|null $name Cluster name
     * @param array<string>|null $seeds Seed nodes as ['host:port', ...]
     * @param float|null $timeout Connection timeout in seconds
     * @param float|null $read_timeout Read timeout in seconds
     * @param bool $persistent Use persistent connections
     * @param mixed $auth Authentication credentials
     * @param bool|null $use_tls Whether to use TLS (Glide backend)
     * @param array<string, mixed>|null $context Stream context options (phpredis backend)
     * @param array<string, mixed>|null $advanced_config Advanced config (Glide backend)
     * @return ClientInterface
     * @throws \RuntimeException If no suitable backend is available
     */
    public static function createCluster(
        ?string $preferredBackend = null,
        ?string $name = null,
        ?array $seeds = null,
        ?float $timeout = null,
        ?float $read_timeout = null,
        bool $persistent = false,
        mixed $auth = null,
        ?bool $use_tls = null,
        ?array $context = null,
        ?array $advanced_config = null,
    ): ClientInterface {
        $backend = $preferredBackend ?? self::detectBackend();

        if (! in_array($backend, [self::BACKEND_GLIDE, self::BACKEND_PHPREDIS], true)) {
            throw new \RuntimeException("Unknown backend: {$backend}");
        }

        if (($seeds === null || $seeds === []) && $name === null) {
            throw new \RuntimeException(
                'Cluster configuration is required. Pass $name or non-empty $seeds.'
            );
        }

        return match ($backend) {
            self::BACKEND_GLIDE => new ClusterClient(
                $name,
                $seeds,
                $timeout,
                $read_timeout,
                $persistent,
                $auth,
                $use_tls,
                $advanced_config,
            ),
            self::BACKEND_PHPREDIS => new PhpRedisClusterClient(
                $name,
                $seeds,
                $timeout,
                $read_timeout,
                $persistent,
                $auth,
                $context,
            ),
        };
    }

    /**
     * Detect the best available backend.
     *
     * Prefers ValkeyGlide if available, falls back to phpredis.
     */
    public static function detectBackend(): string
    {
        if (extension_loaded('valkey_glide')) {
            return self::BACKEND_GLIDE;
        }

        if (extension_loaded('redis')) {
            return self::BACKEND_PHPREDIS;
        }

        throw new \RuntimeException(
            'No Redis-compatible extension found. Install ext-valkey_glide or ext-redis.'
        );
    }

    /**
     * Check if a specific backend is available.
     */
    public static function isAvailable(string $backend): bool
    {
        return match ($backend) {
            self::BACKEND_GLIDE => extension_loaded('valkey_glide'),
            self::BACKEND_PHPREDIS => extension_loaded('redis'),
            default => false,
        };
    }
}
