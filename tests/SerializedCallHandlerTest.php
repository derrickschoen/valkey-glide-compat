<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValkeyGlideCompat\Client;

class SerializedCallHandlerTest extends TestCase
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
    // Fast path: SERIALIZER_NONE passes through unchanged
    // =========================================================================

    #[Test]
    public function fast_path_no_serializer_passes_through(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);

        $this->client->set('sch_fast', 'value');
        $this->client->append('sch_fast', '_appended');

        $this->assertSame('value_appended', $this->client->get('sch_fast'));
    }

    // =========================================================================
    // Specific arg position serialization
    // =========================================================================

    #[Test]
    public function append_serializes_value_with_php_serializer(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        // Append serializes its value argument
        $data = ['appended' => true];
        $this->client->set('sch_append', '');
        $this->client->append('sch_append', $data);

        // Read back raw to verify serialized form was appended
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
        $raw = $this->client->get('sch_append');

        // Should contain both: the serialized empty string + the serialized array
        $this->assertStringContainsString(serialize($data), $raw);
    }

    #[Test]
    public function setnx_round_trips_with_serializer(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['setnx' => 'data'];
        $this->client->setnx('sch_setnx', $data);

        $result = $this->client->get('sch_setnx');
        $this->assertSame($data, $result);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function setex_round_trips_with_serializer(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['setex' => 'data'];
        $this->client->setex('sch_setex', 60, $data);

        $result = $this->client->get('sch_setex');
        $this->assertSame($data, $result);

        $ttl = $this->client->ttl('sch_setex');
        $this->assertGreaterThan(0, $ttl);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function psetex_round_trips_with_serializer(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $data = ['psetex' => 'data'];
        $this->client->psetex('sch_psetex', 60000, $data);

        $result = $this->client->get('sch_psetex');
        $this->assertSame($data, $result);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Serialize-all-args-from commands
    // =========================================================================

    #[Test]
    public function srem_serializes_members(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $val1 = ['member' => 1];
        $val2 = ['member' => 2];
        $this->client->sAdd('sch_srem', $val1, $val2);

        $this->client->sRem('sch_srem', $val1);

        $members = $this->client->sMembers('sch_srem');
        $this->assertCount(1, $members);
        $this->assertSame($val2, $members[0]);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function zrem_serializes_members(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $val1 = ['zmember' => 1];
        $val2 = ['zmember' => 2];
        $this->client->zAdd('sch_zrem', 1.0, $val1);
        $this->client->zAdd('sch_zrem', 2.0, $val2);

        $this->client->zRem('sch_zrem', $val1);

        $this->assertSame(1, $this->client->zCard('sch_zrem'));

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Sorted set round-trip
    // =========================================================================

    #[Test]
    public function zadd_zrange_round_trip(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $a = ['z' => 'a'];
        $b = ['z' => 'b'];
        $this->client->zAdd('sch_zadd', 1.0, $a);
        $this->client->zAdd('sch_zadd', 2.0, $b);

        $range = $this->client->zRange('sch_zadd', 0, -1);
        $this->assertCount(2, $range);
        $this->assertSame($a, $range[0]);
        $this->assertSame($b, $range[1]);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    // =========================================================================
    // Passthrough commands unaffected by serializer
    // =========================================================================

    #[Test]
    public function del_exists_ttl_unaffected_by_serializer(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $this->client->set('sch_key', 'some_value');

        // exists
        $this->assertSame(1, $this->client->exists('sch_key'));

        // ttl on non-expiring key
        $this->assertSame(-1, $this->client->ttl('sch_key'));

        // del
        $this->assertSame(1, $this->client->del('sch_key'));
        $this->assertSame(0, $this->client->exists('sch_key'));

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function incr_decr_work_with_no_serializer(): void
    {
        // incr/decr require plain numeric values, so use SERIALIZER_NONE
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);

        $this->client->set('sch_counter', '10');
        $this->assertSame(11, $this->client->incr('sch_counter'));
        $this->assertSame(10, $this->client->decr('sch_counter'));
    }

    // =========================================================================
    // Array result unserialization
    // =========================================================================

    #[Test]
    public function sdiff_unserializes_results(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $val1 = ['diff' => 1];
        $val2 = ['diff' => 2];
        $val3 = ['diff' => 3];
        $this->client->sAdd('sch_sdiff1', $val1, $val2, $val3);
        $this->client->sAdd('sch_sdiff2', $val2);

        $diff = $this->client->sDiff('sch_sdiff1', 'sch_sdiff2');
        $this->assertCount(2, $diff);

        // Sort for deterministic comparison
        usort($diff, fn ($a, $b) => serialize($a) <=> serialize($b));
        $expected = [$val1, $val3];
        usort($expected, fn ($a, $b) => serialize($a) <=> serialize($b));
        $this->assertSame($expected, $diff);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function sinter_unserializes_results(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $val1 = ['inter' => 1];
        $val2 = ['inter' => 2];
        $this->client->sAdd('sch_sinter1', $val1, $val2);
        $this->client->sAdd('sch_sinter2', $val1);

        $inter = $this->client->sInter('sch_sinter1', 'sch_sinter2');
        $this->assertCount(1, $inter);
        $this->assertSame($val1, $inter[0]);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }

    #[Test]
    public function sunion_unserializes_results(): void
    {
        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

        $val1 = ['union' => 1];
        $val2 = ['union' => 2];
        $this->client->sAdd('sch_sunion1', $val1);
        $this->client->sAdd('sch_sunion2', $val2);

        $union = $this->client->sUnion('sch_sunion1', 'sch_sunion2');
        $this->assertCount(2, $union);

        usort($union, fn ($a, $b) => serialize($a) <=> serialize($b));
        $expected = [$val1, $val2];
        usort($expected, fn ($a, $b) => serialize($a) <=> serialize($b));
        $this->assertSame($expected, $union);

        $this->client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
    }
}
