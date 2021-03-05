<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Propel\Runtime\ActiveQuery\Criteria;
use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Last Argument
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Last
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        $resource->query()
                 ->orderById(Criteria::DESC)
                 ->limit((int)$value);
    }
}
