<?php

declare(strict_types=1);

namespace ValkeyGlideCompat\Concern;

use ValkeyGlideCompat\Constants;

/**
 * Serialization-aware __call() dispatcher for Glide clients.
 *
 * When OPT_SERIALIZER is active, commands routed through __call() need
 * their arguments serialized and results unserialized. This trait handles
 * that transparently via lookup tables for O(1) dispatch.
 *
 * Fast path: when serializer is SERIALIZER_NONE, delegates directly with
 * zero overhead.
 */
trait SerializedCallHandler
{
    /** @return \ValkeyGlide|\ValkeyGlideCluster */
    abstract protected function getDriver(): object;

    abstract protected function serializeValue(mixed $value): mixed;

    abstract protected function unserializeValue(mixed $value): mixed;

    /**
     * Commands where specific argument positions need serialization.
     * command => [arg indices to serialize]
     *
     * @var array<string, int[]>
     */
    private static array $serializeArgPositions = [
        'lpushx'  => [1],
        'rpushx'  => [1],
        'lpos'    => [1],
        'smove'   => [2],
    ];

    /**
     * Commands where all args from a given index onward need serialization.
     * command => start index
     *
     * @var array<string, int>
     */
    private static array $serializeAllArgsFrom = [
        'smismember' => 1,
    ];

    /**
     * Commands that return a single value needing unserialization.
     *
     * @var array<string, true>
     */
    private static array $unserializeResult = [
        'rpoplpush'  => true,
        'brpoplpush' => true,
        'lmove'      => true,
        'blmove'     => true,
    ];

    /**
     * Commands that return an array of values needing unserialization.
     *
     * @var array<string, true>
     */
    private static array $unserializeArrayResult = [
        'sdiff'     => true,
        'sinter'    => true,
        'sunion'    => true,
        'sort'      => true,
    ];

    /**
     * Commands that return associative key=>value arrays where values
     * need unserialization (e.g., blpop returns [key => value]).
     *
     * @var array<string, true>
     */
    private static array $unserializeAssocValues = [
        'blpop'  => true,
        'brpop'  => true,
    ];

    /** @param array<mixed> $arguments */
    public function __call(string $name, array $arguments): mixed
    {
        // Fast path: no serializer active
        if ($this->serializer === Constants::SERIALIZER_NONE) {
            return $this->getDriver()->$name(...$arguments);
        }

        $lcName = strtolower($name);

        // Serialize arguments
        $arguments = $this->serializeCallArguments($lcName, $arguments);

        // Execute
        $result = $this->getDriver()->$name(...$arguments);

        // Unserialize result
        return $this->unserializeCallResult($lcName, $result);
    }

    /**
     * @param array<mixed> $arguments
     * @return array<mixed>
     */
    private function serializeCallArguments(string $lcName, array $arguments): array
    {
        // Specific arg positions
        if (isset(self::$serializeArgPositions[$lcName])) {
            foreach (self::$serializeArgPositions[$lcName] as $idx) {
                if (array_key_exists($idx, $arguments)) {
                    $arguments[$idx] = $this->serializeValue($arguments[$idx]);
                }
            }

            return $arguments;
        }

        // All args from index onward
        if (isset(self::$serializeAllArgsFrom[$lcName])) {
            $start = self::$serializeAllArgsFrom[$lcName];
            for ($i = $start, $len = count($arguments); $i < $len; $i++) {
                $arguments[$i] = $this->serializeValue($arguments[$i]);
            }

            return $arguments;
        }

        // Special: lInsert — serialize pivot (arg index 2) and value (arg index 3)
        if ($lcName === 'linsert') {
            if (array_key_exists(2, $arguments)) {
                $arguments[2] = $this->serializeValue($arguments[2]);
            }
            if (array_key_exists(3, $arguments)) {
                $arguments[3] = $this->serializeValue($arguments[3]);
            }

            return $arguments;
        }

        return $arguments;
    }

    private function unserializeCallResult(string $lcName, mixed $result): mixed
    {
        // Single value unserialization
        if (isset(self::$unserializeResult[$lcName])) {
            if ($result === false || $result === null) {
                return $result;
            }

            return $this->unserializeValue($result);
        }

        // Array of values
        if (isset(self::$unserializeArrayResult[$lcName])) {
            if (! is_array($result)) {
                return $result;
            }

            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        // Assoc key=>value (blpop/brpop return [key => value])
        if (isset(self::$unserializeAssocValues[$lcName])) {
            if (! is_array($result)) {
                return $result;
            }

            $out = [];
            foreach ($result as $k => $v) {
                $out[$k] = $this->unserializeValue($v);
            }

            return $out;
        }

        // Special: zrange with WITHSCORES — alternating member/score in assoc array
        if ($lcName === 'zrange' || $lcName === 'zrevrange' || $lcName === 'zrangebyscore' || $lcName === 'zrevrangebyscore') {
            return $this->unserializeZRangeResult($result);
        }

        // Special: zpopmin/zpopmax — return [member => score]
        if ($lcName === 'zpopmin' || $lcName === 'zpopmax' || $lcName === 'bzpopmin' || $lcName === 'bzpopmax') {
            if (! is_array($result)) {
                return $result;
            }

            $out = [];
            foreach ($result as $k => $v) {
                $out[$this->unserializeValue($k)] = $v;
            }

            return $out;
        }

        return $result;
    }

    /**
     * Unserialize zRange family results.
     * When called without WITHSCORES: returns indexed array of members.
     * When called with WITHSCORES: returns assoc [member => score].
     */
    private function unserializeZRangeResult(mixed $result): mixed
    {
        if (! is_array($result)) {
            return $result;
        }

        // Detect assoc (WITHSCORES) vs indexed array
        if (array_is_list($result)) {
            return array_map(fn ($v) => $this->unserializeValue($v), $result);
        }

        // WITHSCORES: keys are members, values are scores
        $out = [];
        foreach ($result as $member => $score) {
            $out[$this->unserializeValue($member)] = $score;
        }

        return $out;
    }
}
