<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValkeyGlideCompat\ClientInterface;
use ValkeyGlideCompat\Constants;
use ValkeyGlideCompat\PhpRedisClient;

class PhpRedisClientMissingExtensionTest extends TestCase
{
    #[Test]
    public function constructor_throws_if_ext_redis_is_missing(): void
    {
        if (extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis is loaded');
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('ext-redis not loaded');

        new PhpRedisClient();
    }
}

class PhpRedisClientTest extends TestCase
{
    private ?PhpRedisClient $client = null;

    protected function setUp(): void
    {
        if (! extension_loaded('redis')) {
            $this->markTestSkipped('ext-redis not loaded');
        }

        $this->client = new PhpRedisClient();

        $host = getenv('VALKEY_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('VALKEY_PORT') ?: 6379);

        try {
            if (! $this->client->connect($host, $port)) {
                $this->markTestSkipped("Could not connect to Redis at {$host}:{$port}");
            }
        } catch (\Throwable $e) {
            $this->markTestSkipped("Could not connect to Redis at {$host}:{$port} ({$e->getMessage()})");
        }
    }

    protected function tearDown(): void
    {
        if ($this->client === null) {
            return;
        }

        try {
            if ($this->client->isConnected()) {
                $this->client->flushDB();
                $this->client->close();
            }
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
            'OPT_SCAN' => ['OPT_SCAN'],
            'OPT_FAILOVER' => ['OPT_FAILOVER'],
            'OPT_TCP_KEEPALIVE' => ['OPT_TCP_KEEPALIVE'],
            'OPT_COMPRESSION' => ['OPT_COMPRESSION'],
            'OPT_REPLY_LITERAL' => ['OPT_REPLY_LITERAL'],
            'OPT_COMPRESSION_LEVEL' => ['OPT_COMPRESSION_LEVEL'],
            'OPT_NULL_MULTIBULK_AS_NULL' => ['OPT_NULL_MULTIBULK_AS_NULL'],
            'OPT_MAX_RETRIES' => ['OPT_MAX_RETRIES'],
            'OPT_BACKOFF_ALGORITHM' => ['OPT_BACKOFF_ALGORITHM'],
            'OPT_BACKOFF_BASE' => ['OPT_BACKOFF_BASE'],
            'OPT_BACKOFF_CAP' => ['OPT_BACKOFF_CAP'],
            'OPT_PACK_IGNORE_NUMBERS' => ['OPT_PACK_IGNORE_NUMBERS'],
            'SERIALIZER_NONE' => ['SERIALIZER_NONE'],
            'SERIALIZER_PHP' => ['SERIALIZER_PHP'],
            'SERIALIZER_IGBINARY' => ['SERIALIZER_IGBINARY'],
            'SERIALIZER_MSGPACK' => ['SERIALIZER_MSGPACK'],
            'SERIALIZER_JSON' => ['SERIALIZER_JSON'],
            'SCAN_NORETRY' => ['SCAN_NORETRY'],
            'SCAN_RETRY' => ['SCAN_RETRY'],
            'SCAN_PREFIX' => ['SCAN_PREFIX'],
            'SCAN_NOPREFIX' => ['SCAN_NOPREFIX'],
            'BACKOFF_ALGORITHM_DEFAULT' => ['BACKOFF_ALGORITHM_DEFAULT'],
            'BACKOFF_ALGORITHM_DECORRELATED_JITTER' => ['BACKOFF_ALGORITHM_DECORRELATED_JITTER'],
            'BACKOFF_ALGORITHM_FULL_JITTER' => ['BACKOFF_ALGORITHM_FULL_JITTER'],
            'BACKOFF_ALGORITHM_EQUAL_JITTER' => ['BACKOFF_ALGORITHM_EQUAL_JITTER'],
            'BACKOFF_ALGORITHM_EXPONENTIAL' => ['BACKOFF_ALGORITHM_EXPONENTIAL'],
            'BACKOFF_ALGORITHM_UNIFORM' => ['BACKOFF_ALGORITHM_UNIFORM'],
            'BACKOFF_ALGORITHM_CONSTANT' => ['BACKOFF_ALGORITHM_CONSTANT'],
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
        $value = constant(PhpRedisClient::class . '::' . $constName);
        $constantsValue = constant(Constants::class . '::' . $constName);

        $this->assertSame($constantsValue, $value);
    }

    #[Test]
    public function get_driver_returns_redis_instance(): void
    {
        $this->assertInstanceOf(\Redis::class, $this->client->getDriver());
    }

    #[Test]
    public function set_get_del_round_trip_works(): void
    {
        $this->assertNotFalse($this->client->set('phpredis:test', 'value'));
        $this->assertSame('value', $this->client->get('phpredis:test'));
        $this->assertSame(1, $this->client->del('phpredis:test'));
        $this->assertFalse($this->client->get('phpredis:test'));
    }

    #[Test]
    public function set_option_get_option_passthrough_works(): void
    {
        $this->assertTrue($this->client->setOption(PhpRedisClient::OPT_PREFIX, 'phpredis:'));
        $this->assertSame('phpredis:', $this->client->getOption(PhpRedisClient::OPT_PREFIX));

        $this->client->setOption(PhpRedisClient::OPT_PREFIX, '');
    }

    #[Test]
    public function serializer_php_round_trips_natively(): void
    {
        $this->assertTrue($this->client->setOption(PhpRedisClient::OPT_SERIALIZER, PhpRedisClient::SERIALIZER_PHP));

        $data = ['foo' => 'bar', 'count' => 7];
        $this->assertNotFalse($this->client->set('phpredis:ser', $data));

        $result = $this->client->get('phpredis:ser');
        $this->assertSame($data, $result);

        $this->client->setOption(PhpRedisClient::OPT_SERIALIZER, PhpRedisClient::SERIALIZER_NONE);
    }

    #[Test]
    public function close_and_is_connected_work(): void
    {
        $this->assertTrue($this->client->isConnected());
        $this->assertTrue($this->client->close());
        $this->assertFalse($this->client->isConnected());

        $host = getenv('VALKEY_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('VALKEY_PORT') ?: 6379);

        $this->assertTrue($this->client->connect($host, $port));
        $this->assertTrue($this->client->isConnected());
    }

    #[Test]
    public function connect_accepts_null_persistent_id(): void
    {
        $this->assertTrue($this->client->close());

        $host = getenv('VALKEY_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('VALKEY_PORT') ?: 6379);

        $result = $this->client->connect($host, $port, 0.0, null);
        $this->assertTrue($result);
        $this->assertTrue($this->client->isConnected());
    }

    #[Test]
    public function pconnect_accepts_null_persistent_id(): void
    {
        $this->assertTrue($this->client->close());

        $host = getenv('VALKEY_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('VALKEY_PORT') ?: 6379);

        $result = $this->client->pconnect($host, $port, 0.0, null);
        $this->assertTrue($result);
        $this->assertTrue($this->client->isConnected());
    }

    #[Test]
    public function magic_call_passthrough_works_for_incr_decr(): void
    {
        $this->client->set('phpredis:counter', 10);

        $this->assertSame(11, $this->client->incr('phpredis:counter'));
        $this->assertSame(10, $this->client->decr('phpredis:counter'));
    }
}
