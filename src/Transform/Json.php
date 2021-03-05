<?php

namespace Spartan\Rest\Transform;

use Spartan\Rest\Definition\TransformInterface;

/**
 * Json Transform
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Json implements TransformInterface
{
    /**
     * @param $value
     *
     * @return int
     */
    public function request($value)
    {
        return json_encode($value);
    }

    /**
     * @param $value
     *
     * @return int
     */
    public function response($value)
    {
        return json_decode($value, true);
    }
}
