<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ArgumentInterface;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Criteria Argument
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Criteria implements ArgumentInterface
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        foreach ($value as $attrFullName => $data) {
            // $attrFullName: table.column
            [$alias, $attr] = explode('.', $attrFullName, 2);
            if (!$resource->isAttribute($attr)) {
                throw new \InvalidArgumentException("Invalid criteria attribute `{$attrFullName}`");
            }

            $data = (array)$data;
            foreach ($data as $criteria) {
                $attrFullName = preg_replace('/^[a-zA-Z0-9_]$/', '', $alias) . '.' . $attr;
                [$criterion, $condition] = explode(' ', $criteria, 2);
                $condition = preg_replace('/^[^a-zA-Z0-9_\.]$/', '', $condition);
                $resource->checkCriterionAllowed($attr, $criterion);
                $resource->query()->where("{$attrFullName} {$criterion} {$condition}");
            }
        }
    }
}
