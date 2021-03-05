<?php

namespace Spartan\Rest\Definition;

/**
 * TransformInterface
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
interface TransformInterface
{
    public function request($value);

    public function response($value);
}
