<?php

namespace Spartan\Rest\Transform;

use Jenssegers\Optimus\Optimus;
use Spartan\Rest\Definition\TransformInterface;

/**
 * FakeInt Transform
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class FakeInt implements TransformInterface
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
                'prime'   => getenv('FAKE_INT_PRIME'),
                'inverse' => getenv('FAKE_INT_INVERSE'),
                'random'  => getenv('FAKE_INT_RANDOM'),
            ];
    }

    /**
     * @param $value
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    public function request($value)
    {
        return $this->optimus()->decode((int)$value);
    }

    /**
     * @param $value
     *
     * @return int
     * @throws \InvalidArgumentException
     */
    public function response($value)
    {
        return $this->optimus()->encode((int)$value);
    }

    /**
     * @return Optimus
     */
    protected function optimus()
    {
        return (new Optimus($this->config['prime'], $this->config['inverse'], $this->config['random']));
    }

    /**
     * @param       $value
     * @param array $config
     *
     * @return int
     */
    public static function encode($value, array $config = []): int
    {
        return (new self($config))->response($value);
    }

    /**
     * @param       $value
     * @param array $config
     *
     * @return int
     */
    public static function decode($value, array $config = []): int
    {
        return (new self($config))->request($value);
    }
}
