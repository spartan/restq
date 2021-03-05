<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Paginate Argument
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Paginate
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        if (!is_array($value)) {
            $page    = (int)$value;
            $perPage = 20;
        } else {
            $page    = $value[0] ?? 1;
            $perPage = $value[1] ?? 20;
        }

        $resource->query()
                 ->offset(($page - 1) * $perPage)
                 ->limit($perPage);

        $resource->withMeta(
            [
                'x-page' => $page,
                'x-size' => $perPage,
            ]
        );
    }
}
