<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Tests;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValkeyGlideCompat\ClientInterface;
use ValkeyGlideCompat\ClusterClient;

#[Group('cluster-integration')]
class ClusterClientIntegrationTest extends TestCase
{
    private ?ClusterClient $client = null;

    protected function setUp(): void
    {
        if (! extension_loaded('valkey_glide')) {
            $this->markTestSkipped('ext-valkey_glide not loaded');
        }

        $host = getenv('VALKEY_CLUSTER_HOST');
        $port = getenv('VALKEY_CLUSTER_PORT');

        if (! $host || ! $port) {
            $this->markTestSkipped('VALKEY_CLUSTER_HOST/VALKEY_CLUSTER_PORT not set');
        }

        $this->client = new ClusterClient(null, ["{$host}:{$port}"]);
    }

    protected function tearDown(): void
    {
        if ($this->client !== null) {
            // Clean up test keys — use del on known keys rather than flushDB
            // since flushDB on cluster requires a route parameter
            try {
                $this->client->del(
                    'cluster_test',
                    'cluster_del',
                    'cluster_exists',
                    'cluster_ser',
                    'cluster_hser',
                    '{tag}key1',
                    '{tag}key2',
                    'cluster_lpush',
                    'cluster_opt',
                );
            } catch (\Throwable) {
                // Ignore cleanup errors
            }
            $this->client->close();
        }
    }

    // =========================================================================
    // Connection & basics
    // =========================================================================

    #[Test]
    public function it_connects_to_cluster(): void
    {
        $result = $this->client->ping('randomNode');

        $this->assertTrue($result === true || $result === 'PONG');
    }

    #[Test]
    public function ping_with_no_args_works(): void
    {
        $result = $this->client->ping();

        $this->assertTrue($result === true || $result === 'PONG');
    }

    #[Test]
    public function it_implements_client_interface(): void
    {
        $this->assertInstanceOf(ClientInterface::class, $this->client);
    }

    // =========================================================================
    // CRUD
    // =========================================================================

    #[Test]
    public function it_can_set_and_get_string(): void
    {
        $this->client->set('cluster_test', 'hello_cluster');
        $result = $this->client->get('cluster_test');

        $this->assertSame('hello_cluster', $result);
    }

    #[Test]
    public function it_can_delete_key(): void
    {
        $this->client->set('cluster_del', 'value');
        $deleted = $this->client->del('cluster_del');

        $this->assertSame(1, $deleted);
        $this->assertFalse($this->client->get('cluster_del'));
    }

    #[Test]
    public function it_can_check_exists(): void
    {
        $this->client->set('cluster_exists', 'value');

        $this->assertSame(1, $this->client->exists('cluster_exists'));
        $this->assertSame(0, $this->client->exists('cluster_missing_key'));
    }

    // =========================================================================
    // Serialization on cluster
    // =========================================================================

    #[Test]
    public function serializer_php_round_trips_on_cluster(): void
    {
        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_PHP);

        $data = ['cluster' => 'data', 'num' => 99];
        $this->client->set('cluster_ser', $data);
        $result = $this->client->get('cluster_ser');

        $this->assertSame($data, $result);

        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_NONE);
    }

    #[Test]
    public function hset_hget_serialize_on_cluster(): void
    {
        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_PHP);

        $data = ['nested' => ['a', 'b']];
        $this->client->hSet('cluster_hser', 'field', $data);

        $result = $this->client->hGet('cluster_hser', 'field');
        $this->assertSame($data, $result);

        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_NONE);
    }

    #[Test]
    public function mset_mget_serialize_on_cluster(): void
    {
        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_PHP);

        // Use hash tags to ensure same slot
        $data = [
            '{tag}key1' => ['x' => 1],
            '{tag}key2' => ['y' => 2],
        ];
        $this->client->mSet($data);

        $result = $this->client->mGet(['{tag}key1', '{tag}key2']);
        $this->assertSame(['x' => 1], $result[0]);
        $this->assertSame(['y' => 2], $result[1]);

        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_NONE);
    }

    #[Test]
    public function lpush_lpop_serialize_on_cluster(): void
    {
        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_PHP);

        $this->client->lPush('cluster_lpush', ['a' => 1]);

        $popped = $this->client->lPop('cluster_lpush');
        $this->assertSame(['a' => 1], $popped);

        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_NONE);
    }

    // =========================================================================
    // Options
    // =========================================================================

    #[Test]
    public function set_option_serializer_works(): void
    {
        $result = $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_PHP);

        $this->assertTrue($result);

        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_NONE);
    }

    #[Test]
    public function get_option_serializer_works(): void
    {
        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_PHP);

        $value = $this->client->getOption(ClusterClient::OPT_SERIALIZER);
        $this->assertSame(ClusterClient::SERIALIZER_PHP, $value);

        $this->client->setOption(ClusterClient::OPT_SERIALIZER, ClusterClient::SERIALIZER_NONE);
    }

    // =========================================================================
    // TLS connection
    // =========================================================================

    #[Test]
    public function it_connects_via_tls(): void
    {
        $tlsHost = getenv('VALKEY_CLUSTER_TLS_HOST');
        $tlsPort = getenv('VALKEY_CLUSTER_TLS_PORT');
        $caCert = getenv('VALKEY_TLS_CA_CERT');

        if (! $tlsHost || ! $tlsPort) {
            $this->markTestSkipped('VALKEY_CLUSTER_TLS_HOST/VALKEY_CLUSTER_TLS_PORT not set');
        }

        $advancedConfig = null;
        if ($caCert) {
            $advancedConfig = [
                'tls_config' => ['use_insecure_tls' => true],
            ];
        }

        $tlsClient = new ClusterClient(
            seeds: ["{$tlsHost}:{$tlsPort}"],
            use_tls: true,
            advanced_config: $advancedConfig,
        );

        $result = $tlsClient->ping('randomNode');
        $this->assertTrue($result === true || $result === 'PONG');

        $tlsClient->close();
    }
}
