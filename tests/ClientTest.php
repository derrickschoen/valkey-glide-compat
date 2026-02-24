<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValkeyGlideCompat\Client;
use ValkeyGlideCompat\ClientFactory;
use ValkeyGlideCompat\ClientInterface;

class ClientTest extends TestCase
{
    private ?Client $client = null;

    protected function setUp(): void
    {
        if (! extension_loaded('valkey_glide')) {
            $this->markTestSkipped('ext-valkey_glide not loaded');
        }

        $this->client = new Client();
        $host = getenv('VALKEY_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('VALKEY_PORT') ?: 6379);
        $this->client->connect($host, $port);
    }

    protected function tearDown(): void
    {
        if ($this->client !== null) {
            $this->client->flushDB();
            $this->client->close();
        }
    }

    // =========================================================================
    // Interface tests
    // =========================================================================

    #[Test]
    public function it_implements_client_interface(): void
    {
        $this->assertInstanceOf(ClientInterface::class, $this->client);
    }

    // =========================================================================
    // Basic CRUD tests
    // =========================================================================

    #[Test]
    public function it_can_set_and_get_a_string(): void
    {
        $this->client->set('test_key', 'hello');
        $result = $this->client->get('test_key');

        $this->assertSame('hello', $result);
    }

    #[Test]
    public function it_returns_false_for_missing_key(): void
    {
        $result = $this->client->get('nonexistent_key');

        $this->assertFalse($result);
    }

    #[Test]
    public function it_can_delete_a_key(): void
    {
        $this->client->set('del_key', 'value');
        $deleted = $this->client->del('del_key');

        $this->assertSame(1, $deleted);
        $this->assertFalse($this->client->get('del_key'));
    }

    #[Test]
    public function it_can_check_key_exists(): void
    {
        $this->client->set('exists_key', 'value');

        $this->assertSame(1, $this->client->exists('exists_key'));
        $this->assertSame(0, $this->client->exists('missing_key'));
    }

    #[Test]
    public function it_can_increment_and_decrement(): void
    {
        $this->client->set('counter', '10');

        $this->assertSame(11, $this->client->incr('counter'));
        $this->assertSame(10, $this->client->decr('counter'));
    }

    #[Test]
    public function it_can_set_and_check_ttl(): void
    {
        $this->client->set('ttl_key', 'value');
        $this->client->expire('ttl_key', 100);

        $ttl = $this->client->ttl('ttl_key');
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(100, $ttl);
    }

    #[Test]
    public function it_can_ping(): void
    {
        $result = $this->client->ping();

        $this->assertTrue($result === true || $result === 'PONG');
    }

    // =========================================================================
    // Null-guard tests
    // =========================================================================

    #[Test]
    public function set_with_null_options_does_not_throw(): void
    {
        $result = $this->client->set('null_opt_key', 'value', null);

        $this->assertNotFalse($result);
        $this->assertSame('value', $this->client->get('null_opt_key'));
    }

    #[Test]
    public function expire_with_null_mode_does_not_throw(): void
    {
        $this->client->set('expire_null', 'value');
        $result = $this->client->expire('expire_null', 60, null);

        $this->assertTrue((bool) $result);
    }

    #[Test]
    public function expire_at_with_null_mode_does_not_throw(): void
    {
        $this->client->set('expireat_null', 'value');
        $result = $this->client->expireAt('expireat_null', time() + 60, null);

        $this->assertTrue((bool) $result);
    }

    #[Test]
    public function pexpire_with_null_mode_does_not_throw(): void
    {
        $this->client->set('pexpire_null', 'value');
        $result = $this->client->pexpire('pexpire_null', 60000, null);

        $this->assertTrue((bool) $result);
    }

    #[Test]
    public function pexpire_at_with_null_mode_does_not_throw(): void
    {
        $this->client->set('pexpireat_null', 'value');
        $result = $this->client->pexpireAt('pexpireat_null', (int) (microtime(true) * 1000) + 60000, null);

        $this->assertTrue((bool) $result);
    }

    #[Test]
    public function script_flush_with_null_mode_does_not_throw(): void
    {
        $result = $this->client->scriptFlush(null);

        $this->assertTrue((bool) $result);
    }

    #[Test]
    public function ping_with_null_does_not_throw(): void
    {
        $result = $this->client->ping(null);

        $this->assertTrue($result === true || $result === 'PONG');
    }

    #[Test]
    public function flush_db_with_null_does_not_throw(): void
    {
        $result = $this->client->flushDB(null);

        $this->assertTrue((bool) $result);
    }

    #[Test]
    public function flush_all_with_null_does_not_throw(): void
    {
        $result = $this->client->flushAll(null);

        $this->assertTrue((bool) $result);
    }

    // =========================================================================
    // Serialization tests (PHP-level via Serialization trait)
    // =========================================================================

    #[Test]
    public function serializer_php_round_trips_array(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['foo' => 'bar', 'num' => 42];
        $this->client->set('ser_php', $data);
        $result = $this->client->get('ser_php');

        $this->assertSame($data, $result);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function serializer_json_round_trips_array(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_JSON);

        $data = ['name' => 'test', 'count' => 5];
        $this->client->set('ser_json', $data);
        $result = $this->client->get('ser_json');

        $this->assertSame($data, $result);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function serializer_none_works_with_strings(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);

        $this->client->set('ser_none', 'plain_string');
        $result = $this->client->get('ser_none');

        $this->assertSame('plain_string', $result);
    }

    // =========================================================================
    // Connection info tests
    // =========================================================================

    #[Test]
    public function it_tracks_connection_info(): void
    {
        $host = getenv('VALKEY_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('VALKEY_PORT') ?: 6379);

        $this->assertSame($host, $this->client->getHost());
        $this->assertSame($port, $this->client->getPort());
        $this->assertTrue($this->client->isConnected());
    }

    #[Test]
    public function close_updates_connected_state(): void
    {
        $client = new Client();
        $host = getenv('VALKEY_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('VALKEY_PORT') ?: 6379);
        $client->connect($host, $port);

        $this->assertTrue($client->isConnected());

        $client->close();
        $this->assertFalse($client->isConnected());
    }

    // =========================================================================
    // Data structure command tests
    // =========================================================================

    #[Test]
    public function hash_commands_work(): void
    {
        $this->client->hSet('myhash', 'field1', 'value1');
        $this->client->hSet('myhash', 'field2', 'value2');

        $this->assertSame('value1', $this->client->hGet('myhash', 'field1'));

        $all = $this->client->hGetAll('myhash');
        $this->assertSame('value1', $all['field1']);
        $this->assertSame('value2', $all['field2']);
    }

    #[Test]
    public function list_commands_work(): void
    {
        $this->client->lPush('mylist', 'a', 'b', 'c');

        $this->assertSame(3, $this->client->lLen('mylist'));

        $popped = $this->client->lPop('mylist');
        $this->assertContains($popped, ['a', 'b', 'c']);
    }

    #[Test]
    public function set_commands_work(): void
    {
        $this->client->sAdd('myset', 'a', 'b', 'c');

        $members = $this->client->sMembers('myset');
        sort($members);
        $this->assertSame(['a', 'b', 'c'], $members);
    }

    #[Test]
    public function sorted_set_commands_work(): void
    {
        $this->client->zAdd('myzset', 1.0, 'a');
        $this->client->zAdd('myzset', 2.0, 'b');
        $this->client->zAdd('myzset', 3.0, 'c');

        $range = $this->client->zRange('myzset', 0, -1);
        $this->assertSame(['a', 'b', 'c'], $range);
    }

    // =========================================================================
    // Backend detection tests
    // =========================================================================

    #[Test]
    public function it_detects_available_backend(): void
    {
        $backend = ClientFactory::detectBackend();

        $this->assertSame(ClientFactory::BACKEND_GLIDE, $backend);
    }

    #[Test]
    public function it_exposes_underlying_glide_client(): void
    {
        $glide = $this->client->getGlideClient();

        $this->assertInstanceOf(\ValkeyGlide::class, $glide);
    }

    #[Test]
    public function it_exposes_underlying_driver(): void
    {
        $driver = $this->client->getDriver();

        $this->assertInstanceOf(\ValkeyGlide::class, $driver);
    }

    // =========================================================================
    // Connection alias tests
    // =========================================================================

    #[Test]
    public function pconnect_works_as_connect_alias(): void
    {
        $client = new Client();
        $host = getenv('VALKEY_HOST') ?: '127.0.0.1';
        $port = (int) (getenv('VALKEY_PORT') ?: 6379);

        $result = $client->pconnect($host, $port);

        $this->assertTrue($result);
        $this->assertTrue($client->isConnected());
        $this->assertSame($host, $client->getHost());
        $this->assertSame($port, $client->getPort());

        $client->close();
    }

    // =========================================================================
    // Serialization + options tests
    // =========================================================================

    #[Test]
    public function set_with_serializer_and_options(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['key' => 'value', 'count' => 100];
        $this->client->set('ser_with_opts', $data, ['EX' => 60]);

        $result = $this->client->get('ser_with_opts');
        $this->assertSame($data, $result);

        $ttl = $this->client->ttl('ser_with_opts');
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(60, $ttl);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function serializer_stores_php_serialized_format(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['hello' => 'world'];
        $this->client->set('ser_format', $data);

        // Switch to NONE to read the raw stored value
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);

        $raw = $this->client->get('ser_format');
        $this->assertIsString($raw);
        $this->assertSame(serialize($data), $raw);
    }

    // =========================================================================
    // Serialized hash command tests
    // =========================================================================

    #[Test]
    public function hset_hget_serialize_values(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['nested' => ['a', 'b', 'c']];
        $this->client->hSet('hash_ser', 'field1', $data);

        $result = $this->client->hGet('hash_ser', 'field1');
        $this->assertSame($data, $result);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function hgetall_deserializes_values(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $this->client->hSet('hash_all', 'f1', ['a' => 1]);
        $this->client->hSet('hash_all', 'f2', ['b' => 2]);

        $all = $this->client->hGetAll('hash_all');
        $this->assertSame(['a' => 1], $all['f1']);
        $this->assertSame(['b' => 2], $all['f2']);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Serialized multi-key command tests
    // =========================================================================

    #[Test]
    public function mset_mget_serialize_values(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = [
            'mser_a' => ['x' => 1],
            'mser_b' => ['y' => 2],
        ];
        $this->client->mSet($data);

        $result = $this->client->mGet(array_keys($data));
        $this->assertSame(['x' => 1], $result[0]);
        $this->assertSame(['y' => 2], $result[1]);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Serialized list command tests
    // =========================================================================

    #[Test]
    public function lpush_lpop_serialize_values(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $this->client->lPush('list_ser', ['a' => 1], ['b' => 2]);

        $popped = $this->client->lPop('list_ser');
        $this->assertIsArray($popped);
        $this->assertTrue($popped === ['a' => 1] || $popped === ['b' => 2]);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Serialized hash (hMSet/hMGet) tests
    // =========================================================================

    #[Test]
    public function hmset_hmget_serialize_values(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $members = [
            'field1' => ['a' => 1],
            'field2' => ['b' => 2],
        ];
        $this->client->hMSet('hmset_ser', $members);

        $result = $this->client->hMGet('hmset_ser', ['field1', 'field2']);
        $this->assertSame(['a' => 1], $result['field1']);
        $this->assertSame(['b' => 2], $result['field2']);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Serialized rPush/rPop tests
    // =========================================================================

    #[Test]
    public function rpush_rpop_serialize_values(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $this->client->rPush('rpush_ser', ['x' => 1], ['y' => 2]);

        // rPop returns the last element pushed
        $popped = $this->client->rPop('rpush_ser');
        $this->assertSame(['y' => 2], $popped);

        $popped2 = $this->client->rPop('rpush_ser');
        $this->assertSame(['x' => 1], $popped2);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Serialized lIndex/lRange/lSet tests
    // =========================================================================

    #[Test]
    public function lindex_deserializes_value(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['indexed' => true];
        $this->client->lPush('lindex_ser', $data);

        $result = $this->client->lIndex('lindex_ser', 0);
        $this->assertSame($data, $result);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function lrange_deserializes_values(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $this->client->rPush('lrange_ser', ['a' => 1], ['b' => 2], ['c' => 3]);

        $result = $this->client->lRange('lrange_ser', 0, -1);
        $this->assertCount(3, $result);
        $this->assertSame(['a' => 1], $result[0]);
        $this->assertSame(['b' => 2], $result[1]);
        $this->assertSame(['c' => 3], $result[2]);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function lset_serializes_value(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        // Push a placeholder, then overwrite with lSet
        $this->client->rPush('lset_ser', 'placeholder');
        $this->client->lSet('lset_ser', 0, ['replaced' => true]);

        $result = $this->client->lIndex('lset_ser', 0);
        $this->assertSame(['replaced' => true], $result);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Serialized set command tests (sAdd/sMembers, sPop, sRandMember)
    // =========================================================================

    #[Test]
    public function sadd_smembers_serialize_values(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $val1 = ['set' => 'member1'];
        $val2 = ['set' => 'member2'];
        $this->client->sAdd('sadd_ser', $val1, $val2);

        $members = $this->client->sMembers('sadd_ser');
        $this->assertCount(2, $members);

        // Sort by serialized form since set order is undefined
        usort($members, fn ($a, $b) => serialize($a) <=> serialize($b));
        $expected = [$val1, $val2];
        usort($expected, fn ($a, $b) => serialize($a) <=> serialize($b));
        $this->assertSame($expected, $members);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function spop_deserializes_value(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['popped' => 'from_set'];
        $this->client->sAdd('spop_ser', $data);

        $result = $this->client->sPop('spop_ser');
        $this->assertSame($data, $result);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function srandmember_deserializes_value(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['random' => 'member'];
        $this->client->sAdd('srand_ser', $data);

        // Single element (count=0)
        $result = $this->client->sRandMember('srand_ser');
        $this->assertSame($data, $result);

        // With count — returns array
        $resultArr = $this->client->sRandMember('srand_ser', 1);
        $this->assertIsArray($resultArr);
        $this->assertCount(1, $resultArr);
        $this->assertSame($data, $resultArr[0]);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Serialized getSet/getDel/getEx tests
    // =========================================================================

    #[Test]
    public function getset_serializes_both_ways(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $initial = ['old' => 'value'];
        $replacement = ['new' => 'value'];
        $this->client->set('getset_ser', $initial);

        $old = $this->client->getSet('getset_ser', $replacement);
        $this->assertSame($initial, $old);

        // Verify the new value is stored
        $current = $this->client->get('getset_ser');
        $this->assertSame($replacement, $current);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function getdel_deserializes_value(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['will_be' => 'deleted'];
        $this->client->set('getdel_ser', $data);

        $result = $this->client->getDel('getdel_ser');
        $this->assertSame($data, $result);

        // Key should be gone
        $this->assertFalse($this->client->get('getdel_ser'));

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function getex_deserializes_value(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['with' => 'expiry'];
        $this->client->set('getex_ser', $data);

        $result = $this->client->getEx('getex_ser', ['EX' => 60]);
        $this->assertSame($data, $result);

        // Verify TTL was set
        $ttl = $this->client->ttl('getex_ser');
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(60, $ttl);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // ClientFactory tests
    // =========================================================================

    #[Test]
    public function client_factory_creates_client_instance(): void
    {
        $client = ClientFactory::create(ClientFactory::BACKEND_GLIDE);

        $this->assertInstanceOf(Client::class, $client);
    }

    // =========================================================================
    // Multi/exec passthrough tests
    // =========================================================================

    #[Test]
    public function multi_exec_passthrough(): void
    {
        $this->client->multi();
        $this->client->set('multi_key', 'multi_value');
        $this->client->get('multi_key');
        $results = $this->client->exec();

        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertSame('multi_value', $results[1]);
    }

    // =========================================================================
    // Option forwarding tests
    // =========================================================================

    #[Test]
    public function opt_prefix_forwards_to_c_extension(): void
    {
        // Set prefix via compat layer
        $result = $this->client->setOption(Client::OPT_PREFIX, 'test:');
        $this->assertTrue($result);

        // Get prefix via compat layer
        $prefix = $this->client->getOption(Client::OPT_PREFIX);
        $this->assertSame('test:', $prefix);

        // Verify it reached the C extension by checking via the underlying glide client
        $glidePrefix = $this->client->getGlideClient()->getOption(\ValkeyGlide::OPT_PREFIX);
        $this->assertSame('test:', $glidePrefix);

        // Clean up
        $this->client->setOption(Client::OPT_PREFIX, '');
    }

    #[Test]
    public function opt_prefix_works_with_raw_phpredis_integer(): void
    {
        // PHPRedis OPT_PREFIX = 2 (not the fork's 17)
        $this->client->setOption(2, 'raw:');
        $this->assertSame('raw:', $this->client->getOption(2));

        // Verify C extension actually received it
        $this->assertSame('raw:', $this->client->getGlideClient()->getOption(\ValkeyGlide::OPT_PREFIX));

        // Clean up
        $this->client->setOption(2, '');
    }

    #[Test]
    public function opt_reply_literal_works_with_raw_phpredis_integer(): void
    {
        // PHPRedis OPT_REPLY_LITERAL = 8, fork's internal value is 1
        $this->client->setOption(8, true);

        // Verify via C extension (fork uses value 1 for OPT_REPLY_LITERAL)
        $this->assertTrue((bool) $this->client->getGlideClient()->getOption(\ValkeyGlide::OPT_REPLY_LITERAL));

        // Clean up
        $this->client->setOption(8, false);
    }
}
