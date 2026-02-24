<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValkeyGlideCompat\Client;
use ValkeyGlideCompat\ClientInterface;
use ValkeyGlideCompat\ClusterClient;
use ValkeyGlideCompat\Constants;

class ClusterClientTest extends TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('valkey_glide')) {
            $this->markTestSkipped('ext-valkey_glide not loaded');
        }
    }

    // =========================================================================
    // Interface tests
    // =========================================================================

    #[Test]
    public function it_implements_client_interface(): void
    {
        $this->assertTrue(
            is_a(ClusterClient::class, ClientInterface::class, true),
            'ClusterClient should implement ClientInterface',
        );
    }

    // =========================================================================
    // Constants tests
    // =========================================================================

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
    public function cluster_constants_match_client_constants(string $constName): void
    {
        $clusterValue = constant(ClusterClient::class . '::' . $constName);
        $clientValue = constant(Client::class . '::' . $constName);

        $this->assertSame(
            $clientValue,
            $clusterValue,
            "ClusterClient::{$constName} should match Client::{$constName}",
        );
    }

    #[Test]
    #[DataProvider('sharedConstantsProvider')]
    public function cluster_constants_match_constants_class(string $constName): void
    {
        $clusterValue = constant(ClusterClient::class . '::' . $constName);
        $constantsValue = constant(Constants::class . '::' . $constName);

        $this->assertSame(
            $constantsValue,
            $clusterValue,
            "ClusterClient::{$constName} should match Constants::{$constName}",
        );
    }

    // =========================================================================
    // Seed parsing tests (IPv6)
    // =========================================================================

    #[Test]
    public function ipv6_seed_parsing_does_not_crash(): void
    {
        // We can't easily connect to an IPv6 cluster in test, but we can
        // verify that the constructor doesn't crash on IPv6 seed formats.
        // The constructor will attempt to connect and may fail, which is expected.
        $this->expectNotToPerformAssertions();

        try {
            new ClusterClient(null, ['[::1]:6379']);
        } catch (\Throwable) {
            // Connection failure is expected — we only test that parsing doesn't crash
        }
    }
}
