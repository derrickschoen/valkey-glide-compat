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
     * @return Client|\Redis
     * @throws \RuntimeException If no suitable backend is available
     */
    public static function create(?string $preferredBackend = null): Client|\Redis
    {
        $backend = $preferredBackend ?? self::detectBackend();

        return match ($backend) {
            self::BACKEND_GLIDE => new Client(),
            self::BACKEND_PHPREDIS => new \Redis(),
            default => throw new \RuntimeException("Unknown backend: {$backend}"),
        };
    }

    /**
     * Create a cluster client using the best available backend.
     *
     * @param string|null $preferredBackend Force a specific backend
     * @return ClusterClient|\RedisCluster
     * @throws \RuntimeException If no suitable backend is available
     */
    public static function createCluster(?string $preferredBackend = null): ClusterClient|\RedisCluster
    {
        $backend = $preferredBackend ?? self::detectBackend();

        return match ($backend) {
            self::BACKEND_GLIDE => new ClusterClient(),
            self::BACKEND_PHPREDIS => new \RedisCluster(null),
            default => throw new \RuntimeException("Unknown backend: {$backend}"),
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
