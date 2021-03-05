<?php

namespace Spartan\Rest\Transform;

use Spartan\Rest\Definition\TransformInterface;

/**
 * Password Transform
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Password implements TransformInterface
{
    /**
     * @param $value
     *
     * @return int
     */
    public function request($value)
    {
        return password_hash($value, PASSWORD_ARGON2ID);
    }

    /**
     * @param $value
     *
     * @return int
     */
    public function response($value)
    {
        return null;
    }
}
