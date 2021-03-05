<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Sort Argument
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Sort
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        foreach ((array)$value as $attr) {
            if ($attr[0] == '-') {
                $resource->query()->addDescendingOrderByColumn(substr($attr, 1));
            } else {
                $resource->query()->addAscendingOrderByColumn($attr);
            }
        }
    }
}
