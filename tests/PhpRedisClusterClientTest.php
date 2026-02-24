<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValkeyGlideCompat\ClientInterface;
use ValkeyGlideCompat\Constants;
use ValkeyGlideCompat\PhpRedisClusterClient;

class PhpRedisClusterClientMissingExtensionTest extends TestCase
{
    #[Test]
    public function constructor_throws_if_ext_redis_is_missing(): void
    {
        if (extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis is loaded');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ext-redis not loaded');

        new PhpRedisClusterClient();
    }
}

class PhpRedisClusterClientTest extends TestCase
{
    private ?PhpRedisClusterClient $client = null;

    protected function setUp(): void
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
            $this->client = new PhpRedisClusterClient(null, ["{$host}:{$port}"]);
        } catch (\Throwable $e) {
            $this->markTestSkipped("Could not create RedisCluster client ({$e->getMessage()})");
        }
    }

    protected function tearDown(): void
    {
        if ($this->client === null) {
            return;
        }

        try {
            $this->client->close();
        } catch (\Throwable) {
            // Ignore cleanup errors.
        }
    }

    #[Test]
    public function it_implements_client_interface(): void
    {
        $this->assertInstanceOf(ClientInterface::class, $this->client);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function sharedConstantsProvider(): array
    {
        return [
            'OPT_SERIALIZER' => ['OPT_SERIALIZER'],
            'OPT_PREFIX' => ['OPT_PREFIX'],
            'OPT_READ_TIMEOUT' => ['OPT_READ_TIMEOUT'],
            'OPT_REPLY_LITERAL' => ['OPT_REPLY_LITERAL'],
            'SERIALIZER_NONE' => ['SERIALIZER_NONE'],
            'SERIALIZER_PHP' => ['SERIALIZER_PHP'],
            'SERIALIZER_IGBINARY' => ['SERIALIZER_IGBINARY'],
            'SERIALIZER_MSGPACK' => ['SERIALIZER_MSGPACK'],
            'SERIALIZER_JSON' => ['SERIALIZER_JSON'],
            'MULTI' => ['MULTI'],
            'PIPELINE' => ['PIPELINE'],
            'REDIS_NOT_FOUND' => ['REDIS_NOT_FOUND'],
            'REDIS_STRING' => ['REDIS_STRING'],
            'REDIS_SET' => ['REDIS_SET'],
            'REDIS_LIST' => ['REDIS_LIST'],
            'REDIS_ZSET' => ['REDIS_ZSET'],
            'REDIS_HASH' => ['REDIS_HASH'],
            'REDIS_STREAM' => ['REDIS_STREAM'],
        ];
    }

    #[Test]
    #[DataProvider('sharedConstantsProvider')]
    public function constants_match_constants_class(string $constName): void
    {
        $value = constant(PhpRedisClusterClient::class . '::' . $constName);
        $constantsValue = constant(Constants::class . '::' . $constName);

        $this->assertSame($constantsValue, $value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function clusterOnlyConstantsProvider(): array
    {
        return [
            'OPT_SLAVE_FAILOVER' => ['OPT_SLAVE_FAILOVER'],
            'FAILOVER_NONE' => ['FAILOVER_NONE'],
            'FAILOVER_ERROR' => ['FAILOVER_ERROR'],
            'FAILOVER_DISTRIBUTE' => ['FAILOVER_DISTRIBUTE'],
            'FAILOVER_DISTRIBUTE_SLAVES' => ['FAILOVER_DISTRIBUTE_SLAVES'],
        ];
    }

    #[Test]
    #[DataProvider('clusterOnlyConstantsProvider')]
    public function cluster_only_constants_match_constants_class(string $constName): void
    {
        $value = constant(PhpRedisClusterClient::class . '::' . $constName);
        $constantsValue = constant(Constants::class . '::' . $constName);

        $this->assertSame($constantsValue, $value);
    }

    #[Test]
    public function failover_constants_match_phpredis_values(): void
    {
        $this->assertSame(\RedisCluster::OPT_SLAVE_FAILOVER, PhpRedisClusterClient::OPT_SLAVE_FAILOVER);
        $this->assertSame(\RedisCluster::FAILOVER_NONE, PhpRedisClusterClient::FAILOVER_NONE);
        $this->assertSame(\RedisCluster::FAILOVER_ERROR, PhpRedisClusterClient::FAILOVER_ERROR);
        $this->assertSame(\RedisCluster::FAILOVER_DISTRIBUTE, PhpRedisClusterClient::FAILOVER_DISTRIBUTE);
        $this->assertSame(\RedisCluster::FAILOVER_DISTRIBUTE_SLAVES, PhpRedisClusterClient::FAILOVER_DISTRIBUTE_SLAVES);
    }

    #[Test]
    public function get_driver_returns_redis_cluster_instance(): void
    {
        $this->assertInstanceOf(\RedisCluster::class, $this->client->getDriver());
    }

    #[Test]
    public function set_get_round_trip_works(): void
    {
        $this->assertNotFalse($this->client->set('{phpredis_cluster}test', 'value'));
        $this->assertSame('value', $this->client->get('{phpredis_cluster}test'));
    }

    #[Test]
    public function serializer_php_round_trips_natively(): void
    {
        $this->assertTrue($this->client->setOption(PhpRedisClusterClient::OPT_SERIALIZER, PhpRedisClusterClient::SERIALIZER_PHP));

        $data = ['cluster' => true, 'count' => 3];
        $this->assertNotFalse($this->client->set('{phpredis_cluster}ser', $data));

        $result = $this->client->get('{phpredis_cluster}ser');
        $this->assertSame($data, $result);

        $this->client->setOption(PhpRedisClusterClient::OPT_SERIALIZER, PhpRedisClusterClient::SERIALIZER_NONE);
    }

    #[Test]
    public function constructor_passes_null_seeds_for_name_based_resolution(): void
    {
        // When using name-based cluster config, seeds should be null.
        // RedisCluster throws RedisClusterException if the name is
        // not configured in php.ini — but it must NOT throw TypeError.
        try {
            new PhpRedisClusterClient('test_cluster', null);
        } catch (\RedisClusterException) {
            // Expected — cluster name not configured in php.ini
            $this->assertTrue(true);

            return;
        }

        // If it connected, that's fine too
        $this->assertTrue(true);
    }

    #[Test]
    public function magic_call_passthrough_works_for_incr_decr(): void
    {
        $this->client->set('{phpredis_cluster}counter', 10);

        $this->assertSame(11, $this->client->incr('{phpredis_cluster}counter'));
        $this->assertSame(10, $this->client->decr('{phpredis_cluster}counter'));
    }
}
