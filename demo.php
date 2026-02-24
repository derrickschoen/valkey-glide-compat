<?php

/**
 * Smoke test: proves the ValkeyGlide C extension AND the valkey-glide-compat
 * PHP library work end-to-end — exactly as a developer migrating from phpredis
 * would expect.
 *
 * Run inside the Docker container:
 *   docker compose exec php-dev php demo.php
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use ValkeyGlideCompat\Client;
use ValkeyGlideCompat\ClientFactory;
use ValkeyGlideCompat\ClusterClient;

// ── helpers ──────────────────────────────────────────────────────────────────

$pass = 0;
$fail = 0;

function section(string $title): void
{
    echo "\n\033[1;36m=== $title ===\033[0m\n";
}

function check(string $label, bool $ok, string $detail = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
        echo "  \033[32m[PASS]\033[0m $label";
    } else {
        $fail++;
        echo "  \033[31m[FAIL]\033[0m $label";
    }
    if ($detail !== '') {
        echo "  \033[90m($detail)\033[0m";
    }
    echo "\n";
}

// ── 1. Prove we're using the ValkeyGlide C extension, not phpredis ──────────

section('1. Backend detection');

check(
    'ext-valkey_glide is loaded',
    extension_loaded('valkey_glide'),
);
check(
    'ClientFactory detects "glide" backend',
    ClientFactory::detectBackend() === ClientFactory::BACKEND_GLIDE,
    'detected: ' . ClientFactory::detectBackend(),
);

// ── 2. Create a Client the phpredis way ─────────────────────────────────────

section('2. Client construction (phpredis-compatible API)');

$host = getenv('VALKEY_HOST') ?: '127.0.0.1';
$port = (int) (getenv('VALKEY_PORT') ?: 6379);

$client = new Client();
$connected = $client->connect($host, $port);

check('connect() returns true', $connected === true);
check('isConnected() returns true', $client->isConnected());
check('getHost() matches', $client->getHost() === $host, "host=$host");
check('getPort() matches', $client->getPort() === $port, "port=$port");

// Verify the underlying object is ValkeyGlide, NOT Redis
$glide = $client->getGlideClient();
check(
    'getGlideClient() returns ValkeyGlide instance',
    $glide instanceof \ValkeyGlide,
    get_class($glide),
);

// ── 3. Basic string CRUD ────────────────────────────────────────────────────

section('3. String CRUD (set / get / del / exists)');

$client->flushDB();

$client->set('greeting', 'hello world');
check('set + get round-trip', $client->get('greeting') === 'hello world');

$client->set('temp', 'bye');
$deleted = $client->del('temp');
check('del returns 1', $deleted === 1);
check('key is gone after del', $client->get('temp') === false);

check('exists returns 1 for present key', $client->exists('greeting') === 1);
check('exists returns 0 for absent key', $client->exists('nope') === 0);

// ── 4. set() with options (EX, NX, XX) ─────────────────────────────────────

section('4. set() with phpredis-style options');

$client->set('opt_ex', 'expires_soon', ['EX' => 30]);
$ttl = $client->ttl('opt_ex');
check('set with EX sets TTL', $ttl > 0 && $ttl <= 30, "ttl=$ttl");

$client->set('opt_nx', 'first');
$nxResult = $client->set('opt_nx', 'second', ['NX']);
check('set with NX fails on existing key', $nxResult === false);
check('original value preserved', $client->get('opt_nx') === 'first');

// ── 5. Data structures: Hash, List, Set, Sorted Set ─────────────────────────

section('5. Data structures');

// Hash
$client->hSet('user:1', 'name', 'Alice');
$client->hSet('user:1', 'age', '30');
check('hGet returns field value', $client->hGet('user:1', 'name') === 'Alice');
$all = $client->hGetAll('user:1');
check('hGetAll returns all fields', $all['name'] === 'Alice' && $all['age'] === '30');

// List
$client->lPush('queue', 'job1', 'job2', 'job3');
check('lLen returns correct count', $client->lLen('queue') === 3);
$popped = $client->lPop('queue');
check('lPop returns an element', in_array($popped, ['job1', 'job2', 'job3'], true));

// Set
$client->sAdd('tags', 'php', 'valkey', 'glide');
$members = $client->sMembers('tags');
sort($members);
check('sMembers returns all members', $members === ['glide', 'php', 'valkey']);

// Sorted Set
$client->zAdd('leaderboard', 100.0, 'alice');
$client->zAdd('leaderboard', 200.0, 'bob');
$client->zAdd('leaderboard', 150.0, 'charlie');
$top = $client->zRange('leaderboard', 0, -1);
check('zRange returns sorted order', $top === ['alice', 'charlie', 'bob']);

// ── 6. Serialization (the key compat feature) ──────────────────────────────

section('6. OPT_SERIALIZER — PHP serializer');

$client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);
check('getOption confirms PHP serializer', $client->getOption(Client::OPT_SERIALIZER) === Client::SERIALIZER_PHP);

$obj = ['user' => 'alice', 'scores' => [10, 20, 30], 'active' => true];
$client->set('php_ser', $obj);
$got = $client->get('php_ser');
check('PHP serialize round-trip preserves array', $got === $obj);

// Verify it's actually stored serialized (switch to NONE, read raw)
$client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
$raw = $client->get('php_ser');
check('raw storage is php serialize() format', $raw === serialize($obj));

section('6b. OPT_SERIALIZER — JSON serializer');

$client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_JSON);

$data = ['event' => 'click', 'count' => 42];
$client->set('json_ser', $data);
$got = $client->get('json_ser');
check('JSON serialize round-trip preserves array', $got === $data);

$client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);
$raw = $client->get('json_ser');
check('raw storage is valid JSON', $raw === json_encode($data));

// ── 7. Serialized data-structure commands ───────────────────────────────────

section('7. Serialization across data-structure commands');

$client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_PHP);

// Hash: hSet / hGet / hMSet / hMGet / hGetAll
$client->hSet('ser_hash', 'config', ['debug' => false, 'timeout' => 5]);
check('hSet+hGet with serializer', $client->hGet('ser_hash', 'config') === ['debug' => false, 'timeout' => 5]);

$client->hMSet('ser_hash2', [
    'a' => [1, 2, 3],
    'b' => ['x' => 'y'],
]);
$hmResult = $client->hMGet('ser_hash2', ['a', 'b']);
check('hMSet+hMGet with serializer', $hmResult['a'] === [1, 2, 3] && $hmResult['b'] === ['x' => 'y']);

// List: lPush / lPop / rPush / rPop / lIndex / lRange / lSet
$client->rPush('ser_list', ['first' => 1], ['second' => 2], ['third' => 3]);
check('lIndex(0) deserializes', $client->lIndex('ser_list', 0) === ['first' => 1]);
check('lRange deserializes all', $client->lRange('ser_list', 0, -1) === [['first' => 1], ['second' => 2], ['third' => 3]]);

$client->lSet('ser_list', 1, ['replaced' => true]);
check('lSet+lIndex roundtrip', $client->lIndex('ser_list', 1) === ['replaced' => true]);

$rpopped = $client->rPop('ser_list');
check('rPop deserializes', $rpopped === ['third' => 3]);

// Set: sAdd / sMembers / sPop / sRandMember
$client->sAdd('ser_set', ['tag' => 'a']);
$client->sAdd('ser_set', ['tag' => 'b']);
$smembers = $client->sMembers('ser_set');
usort($smembers, fn ($a, $b) => serialize($a) <=> serialize($b));
$expected = [['tag' => 'a'], ['tag' => 'b']];
usort($expected, fn ($a, $b) => serialize($a) <=> serialize($b));
check('sAdd+sMembers with serializer', $smembers === $expected);

$client->sAdd('ser_set2', ['only' => 'one']);
$spopped = $client->sPop('ser_set2');
check('sPop deserializes', $spopped === ['only' => 'one']);

$client->sAdd('ser_set3', ['rand' => 'member']);
$srand = $client->sRandMember('ser_set3');
check('sRandMember deserializes (single)', $srand === ['rand' => 'member']);
$srandArr = $client->sRandMember('ser_set3', 1);
check('sRandMember deserializes (count=1)', is_array($srandArr) && $srandArr[0] === ['rand' => 'member']);

// Multi-key: mSet / mGet
$client->mSet(['mk1' => ['x' => 1], 'mk2' => ['y' => 2]]);
$mresult = $client->mGet(['mk1', 'mk2']);
check('mSet+mGet with serializer', $mresult[0] === ['x' => 1] && $mresult[1] === ['y' => 2]);

// getSet / getDel / getEx
$client->set('gs_key', ['old' => 'val']);
$old = $client->getSet('gs_key', ['new' => 'val']);
check('getSet returns old deserialized', $old === ['old' => 'val']);
check('getSet stores new serialized', $client->get('gs_key') === ['new' => 'val']);

$client->set('gd_key', ['delete' => 'me']);
$gdResult = $client->getDel('gd_key');
check('getDel deserializes', $gdResult === ['delete' => 'me']);
check('getDel removes key', $client->get('gd_key') === false);

$client->set('ge_key', ['with' => 'expiry']);
$geResult = $client->getEx('ge_key', ['EX' => 60]);
check('getEx deserializes', $geResult === ['with' => 'expiry']);
$geTtl = $client->ttl('ge_key');
check('getEx sets TTL', $geTtl > 0 && $geTtl <= 60, "ttl=$geTtl");

$client->setOption(Client::OPT_SERIALIZER, Client::SERIALIZER_NONE);

// ── 8. Null-guard commands (phpredis compat for null params) ────────────────

section('8. Null-guard commands');

$client->set('ng_key', 'value');
check('set(key, val, null) works', $client->set('ng_key', 'v2', null) !== false);
check('expire(key, 60, null) works', (bool) $client->expire('ng_key', 60, null));
check('ping(null) works', $client->ping(null) === true || $client->ping(null) === 'PONG');

// ── 9. Multi/Exec transaction ───────────────────────────────────────────────

section('9. Multi/Exec transaction');

$client->multi();
$client->set('tx_key', 'tx_value');
$client->get('tx_key');
$client->incr('tx_counter');
$results = $client->exec();

check('exec returns array', is_array($results));
check('exec has 3 results', count($results) === 3);
check('get inside tx returns correct value', $results[1] === 'tx_value');
check('incr inside tx returns 1', $results[2] === 1);

// ── 10. ClientFactory ────────────────────────────────────────────────────────

section('10. ClientFactory');

$factoryClient = ClientFactory::create();
check('ClientFactory::create() returns Client', $factoryClient instanceof Client);
check('isAvailable("glide") is true', ClientFactory::isAvailable('glide'));

// ── 11. pconnect alias ──────────────────────────────────────────────────────

section('11. pconnect (persistent-connect alias)');

$pclient = new Client();
$pResult = $pclient->pconnect($host, $port);
check('pconnect() returns true', $pResult === true);
check('pconnect sets isConnected', $pclient->isConnected());
$pclient->close();
check('close after pconnect works', !$pclient->isConnected());

// ── 12. Constants match phpredis conventions ────────────────────────────────

section('12. Constants (phpredis-compatible)');

check('REDIS_STRING is 1', Client::REDIS_STRING === 1);
check('REDIS_LIST is 3', Client::REDIS_LIST === 3);
check('REDIS_SET is 2', Client::REDIS_SET === 2);
check('REDIS_HASH is 5', Client::REDIS_HASH === 5);
check('SERIALIZER_NONE is 0', Client::SERIALIZER_NONE === 0);
check('SERIALIZER_PHP is 1', Client::SERIALIZER_PHP === 1);
check('SERIALIZER_JSON is 4', Client::SERIALIZER_JSON === 4);

// ── 13. ClusterClient construction (unit-level, no cluster needed) ──────────

section('13. ClusterClient API surface');

check('ClusterClient implements ClientInterface', is_a(ClusterClient::class, \ValkeyGlideCompat\ClientInterface::class, true));
check('ClusterClient has same constants as Client', ClusterClient::OPT_SERIALIZER === Client::OPT_SERIALIZER);
check('ClusterClient SERIALIZER_PHP matches', ClusterClient::SERIALIZER_PHP === Client::SERIALIZER_PHP);

// ── cleanup ─────────────────────────────────────────────────────────────────

$client->flushDB();
$client->close();

// ── summary ─────────────────────────────────────────────────────────────────

echo "\n\033[1;36m" . str_repeat('=', 60) . "\033[0m\n";
if ($fail === 0) {
    echo "\033[1;32m  ALL $pass CHECKS PASSED\033[0m\n";
} else {
    echo "\033[1;31m  $fail FAILED\033[0m / $pass passed\n";
}
echo "\033[1;36m" . str_repeat('=', 60) . "\033[0m\n\n";

exit($fail > 0 ? 1 : 0);
