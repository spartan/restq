<?php

namespace Spartan\Rest\Adapter\Propel\Argument;

use Manager\Domain\Model\PlayerQuery;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Spartan\Rest\Adapter\Propel\Resource;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Grouping Argument for "or" criteria
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class OrGroup
{
    /**
     * @param mixed                      $value
     * @param ResourceInterface|Resource $resource
     *
     */
    public function __invoke($value, ResourceInterface $resource)
    {
        /*
         *  $value = {
         *      "name": {"*": "..."},
         *      "latin_name": {"*": "..."}
         *  }
         */

        $conditions     = [];
        $conditionIndex = 0;
        $criteriaMap    = Resource::criteriaConditionMap();

        foreach ($value as $attrName => $attrData) {
            if (!$resource->isAttribute($attrName)) {
                throw new \InvalidArgumentException("Invalid criteria attribute `{$attrName}`");
            }

            $attrPropelName = $resource::SCHEMA['properties'][$attrName]['name'] ?? '';

            $attrData = (array)$attrData;
            foreach ($attrData as $criterion => $criterionValue) {
                /*
                 * $criterion == '*'
                 * $criterionValue == 'some name'
                 */
                $resource->checkCriterionAllowed($attrName, $criterion);

                $map = $criteriaMap[$criterion]($resource, $attrName, $criterionValue);

                $resource->query()->condition(
                    "{$attrName}.{$conditionIndex}",
                    "{$map['column']} {$map['criteria']} ?",
                    $map['value']
                );

                $conditions[] = "{$attrName}.{$conditionIndex}";
                $conditionIndex++;
            }
        }

        /**
         * @see ModelCriteria::where()
         */

        $resource->query()->where($conditions, \Propel\Runtime\ActiveQuery\Criteria::LOGICAL_OR);
    }
}
