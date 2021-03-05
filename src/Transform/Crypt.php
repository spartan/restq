<?php

namespace Spartan\Rest\Transform;

use Spartan\Fluent\Str;
use Spartan\Rest\Definition\TransformInterface;

/**
 * Crypt Transform
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Crypt implements TransformInterface
{
    /**
     * @param $value
     *
     * @return int
     */
    public function request($value)
    {
        return Str::encrypt($value);
    }

    /**
     * @param $value
     *
     * @return int
     */
    public function response($value)
    {
        return Str::decrypt($value);
    }
}
