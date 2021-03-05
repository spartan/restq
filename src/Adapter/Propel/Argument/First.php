<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Propel\Runtime\ActiveQuery\Criteria;
use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * First Argument
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class First
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        $resource->query()
                 ->orderById(Criteria::ASC)
                 ->limit((int)$value);
    }
}
