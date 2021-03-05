<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Total Argument
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Total
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        $query = clone $resource->query();

        $resource->withMeta(
            [
                'x-total' => $query->clear()->count(),
            ]
        );
    }
}
