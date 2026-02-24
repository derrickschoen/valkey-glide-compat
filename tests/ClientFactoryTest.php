<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValkeyGlideCompat\Client;
use ValkeyGlideCompat\ClientFactory;
use ValkeyGlideCompat\ClientInterface;
use ValkeyGlideCompat\ClusterClient;
use ValkeyGlideCompat\PhpRedisClient;
use ValkeyGlideCompat\PhpRedisClusterClient;

class ClientFactoryTest extends TestCase
{
    #[Test]
    public function create_cluster_requires_name_or_non_empty_seeds(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Cluster configuration is required');

        ClientFactory::createCluster(ClientFactory::BACKEND_PHPREDIS, seeds: null);
    }

    #[Test]
    public function detect_backend_returns_available_backend(): void
    {
        if (extension_loaded('valkey_glide')) {
            $this->assertSame(ClientFactory::BACKEND_GLIDE, ClientFactory::detectBackend());

            return;
        }

        if (extension_loaded('redis')) {
            $this->assertSame(ClientFactory::BACKEND_PHPREDIS, ClientFactory::detectBackend());

            return;
        }

        $this->expectException(\RuntimeException::class);
        ClientFactory::detectBackend();
    }

    #[Test]
    public function create_returns_glide_client_when_forced(): void
    {
        if (! extension_loaded('valkey_glide')) {
            $this->markTestSkipped('ext-valkey_glide not loaded');
        }

        $client = ClientFactory::create(ClientFactory::BACKEND_GLIDE);

        $this->assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(Client::class, $client);
    }

    #[Test]
    public function create_returns_phpredis_client_when_forced(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis not loaded');
        }

        $client = ClientFactory::create(ClientFactory::BACKEND_PHPREDIS);

        $this->assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(PhpRedisClient::class, $client);
    }

    #[Test]
    public function create_cluster_returns_glide_client_when_forced(): void
    {
        if (! extension_loaded('valkey_glide')) {
            $this->markTestSkipped('ext-valkey_glide not loaded');
        }

        $host = getenv('VALKEY_CLUSTER_HOST');
        $port = getenv('VALKEY_CLUSTER_PORT');

        if (! $host || ! $port) {
            $this->markTestSkipped('VALKEY_CLUSTER_HOST/VALKEY_CLUSTER_PORT not set');
        }

        try {
            $client = ClientFactory::createCluster(
                ClientFactory::BACKEND_GLIDE,
                seeds: ["{$host}:{$port}"],
            );
        } catch (\Throwable $e) {
            $this->markTestSkipped('Could not initialize Glide cluster client: ' . $e->getMessage());
        }

        $this->assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(ClusterClient::class, $client);
    }

    #[Test]
    public function create_cluster_returns_phpredis_client_when_forced(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis not loaded');
        }

        $host = getenv('VALKEY_CLUSTER_HOST');
        $port = getenv('VALKEY_CLUSTER_PORT');

        if (! $host || ! $port) {
            $this->markTestSkipped('VALKEY_CLUSTER_HOST/VALKEY_CLUSTER_PORT not set');
        }

        try {
            $client = ClientFactory::createCluster(
                ClientFactory::BACKEND_PHPREDIS,
                seeds: ["{$host}:{$port}"],
            );
        } catch (\Throwable $e) {
            $this->markTestSkipped('Could not initialize RedisCluster from redis.clusters.seeds: ' . $e->getMessage());
        }

        $this->assertInstanceOf(ClientInterface::class, $client);
        $this->assertInstanceOf(PhpRedisClusterClient::class, $client);
    }
}
