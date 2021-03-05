<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Exclude Argument
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Exclude
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        $resourceClass = get_class($resource);
        $queryResource = new $resourceClass();
        $payload       = ['attr' => ['id']] + $value;
        $data          = $queryResource->search($payload);

        $ids = [];
        foreach ($data as $datum) {
            $ids[] = $datum['id'];
        }

        if (count($ids)) {
            $resource->query()->filterById($ids, \Propel\Runtime\ActiveQuery\Criteria::NOT_IN);
        }
    }
}
