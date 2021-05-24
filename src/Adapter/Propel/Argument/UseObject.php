<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * UseObject Argument
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class UseObject
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        // placeholder
    }
}
