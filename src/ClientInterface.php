<?php

declare(strict_types=1);

namespace ValkeyGlideCompat;

/**
 * Common interface for standalone and cluster clients.
 *
 * Covers the most commonly type-hinted methods that both client types
 * explicitly implement (not via __call).
 */
interface ClientInterface
{
    public function set(string $key, mixed $value, mixed $options = null): mixed;

    public function get(string $key): mixed;

    public function expire(string $key, int $timeout, ?string $mode = null): mixed;

    public function setOption(int $option, mixed $value): bool;

    public function getOption(int $option): mixed;

    public function close(): bool;

    /** @return \ValkeyGlide|\ValkeyGlideCluster */
    public function getGlideClient(): \ValkeyGlide|\ValkeyGlideCluster;
}
