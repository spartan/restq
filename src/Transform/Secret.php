<?php

namespace Spartan\Rest\Transform;

use Spartan\Rest\Definition\TransformInterface;

class Secret implements TransformInterface
{
    /**
     * @param mixed $value
     */
    public function request($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     */
    public function response($value)
    {
        return str_pad('', strlen($value), '*');
    }
}
