<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValkeyGlide;
use ValkeyGlideCompat\Client;
use ValkeyGlideCompat\Constants;

class ConstantsTest extends TestCase
{
    protected function setUp(): void
    {
        if (! extension_loaded('valkey_glide')) {
            $this->markTestSkipped('ext-valkey_glide not loaded');
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function dataTypeConstantsProvider(): array
    {
        return [
            'NOT_FOUND' => ['REDIS_NOT_FOUND', 'VALKEY_GLIDE_NOT_FOUND'],
            'STRING' => ['REDIS_STRING', 'VALKEY_GLIDE_STRING'],
            'SET' => ['REDIS_SET', 'VALKEY_GLIDE_SET'],
            'LIST' => ['REDIS_LIST', 'VALKEY_GLIDE_LIST'],
            'ZSET' => ['REDIS_ZSET', 'VALKEY_GLIDE_ZSET'],
            'HASH' => ['REDIS_HASH', 'VALKEY_GLIDE_HASH'],
            'STREAM' => ['REDIS_STREAM', 'VALKEY_GLIDE_STREAM'],
        ];
    }

    #[Test]
    #[DataProvider('dataTypeConstantsProvider')]
    public function data_type_constants_match(string $compatConst, string $glideConst): void
    {
        $compatValue = constant(Client::class . '::' . $compatConst);
        $glideValue = constant(ValkeyGlide::class . '::' . $glideConst);

        $this->assertSame($glideValue, $compatValue, "{$compatConst} should match ValkeyGlide::{$glideConst}");
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
        ];
    }

    #[Test]
    #[DataProvider('sharedConstantsProvider')]
    public function shared_constants_match_c_extension(string $constName): void
    {
        $compatValue = constant(Client::class . '::' . $constName);
        $glideValue = constant(ValkeyGlide::class . '::' . $constName);

        $this->assertSame($glideValue, $compatValue, "Client::{$constName} should match ValkeyGlide::{$constName}");
    }

    #[Test]
    #[DataProvider('sharedConstantsProvider')]
    public function constants_class_matches_client_class(string $constName): void
    {
        $constantsValue = constant(Constants::class . '::' . $constName);
        $clientValue = constant(Client::class . '::' . $constName);

        $this->assertSame($clientValue, $constantsValue, "Constants::{$constName} should match Client::{$constName}");
    }
}
