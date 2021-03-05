<?php

namespace Spartan\Rest\Transform;

use Hashids\Hashids;
use Spartan\Rest\Definition\TransformInterface;

/**
 * HashId Transform
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class HashId implements TransformInterface
{
    protected array $config = [];

    /**
     * FakeInt constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config + [
                'salt'     => getenv('HASH_ID_SALT'),
                'pad'      => getenv('HASH_ID_PAD'),
                'alphabet' => getenv('HASH_ID_ALPHABET'),
            ];
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    public function request($value)
    {
        return $this->hashid()->decode($value)[0] ?? null;
    }

    /**
     * @param $value
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    public function response($value)
    {
        return $this->hashid()->encode($value);
    }

    /**
     * @return Hashids
     */
    protected function hashid()
    {
        return new Hashids($this->config['salt'], $this->config['pad'], $this->config['alphabet']);
    }

    /**
     * @param       $value
     * @param array $config
     *
     * @return int
     */
    public static function encode($value, array $config = []): string
    {
        return (new self($config))->response($value);
    }

    /**
     * @param       $value
     * @param array $config
     *
     * @return int
     */
    public static function decode($value, array $config = [])
    {
        return (new self($config))->request($value);
    }
}
