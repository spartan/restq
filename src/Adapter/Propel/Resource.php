<?php

namespace Spartan\Rest\Adapter\Propel;

use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Map\TableMap;
use Spartan\Db\Adapter\Propel\Propel2;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Resource Propel
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Resource extends \Spartan\Rest\Resource
{
    const JOIN_CRITERIA_MAP = [
        self::CRITERIA_EQUAL           => '=',
        self::CRITERIA_NOT_EQUAL       => '=',
        self::CRITERIA_NOT_EQUAL_ALT   => '=',
        self::CRITERIA_GREATER         => '=',
        self::CRITERIA_GREATER_EQUAL   => '=',
        self::CRITERIA_LESS            => '=',
        self::CRITERIA_LESS_EQUAL      => '=',
        self::CRITERIA_BINARY_AND      => '=',
        self::CRITERIA_BINARY_OR       => '=',
        self::CRITERIA_LIKE            => '~',
        self::CRITERIA_NOT_LIKE        => '~',
        self::CRITERIA_STARTS_WITH     => '~',
        self::CRITERIA_NOT_STARTS_WITH => '~',
        self::CRITERIA_ENDS_WITH       => '~',
        self::CRITERIA_NOT_ENDS_WITH   => '~',
        self::CRITERIA_IN              => self::CRITERIA_IN,
        self::CRITERIA_NOT_IN          => self::CRITERIA_NOT_IN,
        self::CRITERIA_NULL            => self::CRITERIA_NULL,
        self::CRITERIA_NOT_NULL        => self::CRITERIA_NOT_NULL,
        self::CRITERIA_BETWEEN         => self::CRITERIA_BETWEEN,
    ];

    const PROPEL = '';

    /**
     * Cached schema
     */
    protected static array $schema = [];

    protected static int $joinIndex = 0;

    protected static array $argumentsMap = [
        'limit'     => 'Spartan\Rest\Adapter\Propel\Argument\Limit',
        'paginate'  => 'Spartan\Rest\Adapter\Propel\Argument\Paginate',
        'filtered'  => 'Spartan\Rest\Adapter\Propel\Argument\Filtered',
        'total'     => 'Spartan\Rest\Adapter\Propel\Argument\Total',
        'sort'      => 'Spartan\Rest\Adapter\Propel\Argument\Sort',
        'first'     => 'Spartan\Rest\Adapter\Propel\Argument\First',
        'last'      => 'Spartan\Rest\Adapter\Propel\Argument\Last',
        'criteria'  => 'Spartan\Rest\Adapter\Propel\Argument\Criteria',
        'exclude'   => 'Spartan\Rest\Adapter\Propel\Argument\Exclude',
        'distinct'  => 'Spartan\Rest\Adapter\Propel\Argument\Distinct',
        'orGroup'   => 'Spartan\Rest\Adapter\Propel\Argument\OrGroup',
        'useObject' => 'Spartan\Rest\Adapter\Propel\Argument\UseObject',
    ];

    protected static array $computed = [];

    protected $ignoreComputed = false;

    /**
     * Propel2 Query
     *
     * @var ModelCriteria
     */
    protected $query;

    /**
     * Arguments used during a query
     */
    protected array $arguments = [];

    public function ignoreComputed()
    {
        $this->ignoreComputed = true;

        return $this;
    }

    /*
     * SEARCH
     *
     */

    /**
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Propel\Runtime\ActiveQuery\Exception\UnknownRelationException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function prepareSearch(array &$input): array
    {
        // process args
        $this->processArgs($input);

        $this->withAttr($input);
        $this->withArgs($input);
        $this->withRefs($input, $this, $this->query());

        $this->resolveArguments();

        return $input;
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Propel\Runtime\Exception\InvalidArgumentException
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \ReflectionException
     */
    public function executeSearch(array $payload, array &$input): array
    {
        if ($input['args'][':useObject'] ?? false) {
            $formatter = count($this->query()->getJoins())
                ? ModelCriteria::FORMAT_OBJECT
                : ModelCriteria::FORMAT_ON_DEMAND;
        } else {
            $formatter = count($this->query()->getJoins())
                ? ModelCriteria::FORMAT_ARRAY
                : ModelCriteria::FORMAT_ON_DEMAND;
        }

        if ($this->query()->getLimit() > 0 && count($this->query()->getJoins())) {
            if ($this->fixPaginationWithRelationsOnQuery() === false) {
                // we expect no results so we don't run the selection
                return [];
            }
        }

        if ($input['args'][':useObject'] ?? false) {
            $result = $this->query()
                           ->setFormatter($formatter)
                           ->find()
                           ->toArray(null, false, TableMap::TYPE_FIELDNAME);
        } else {
            $result = self::convertFieldName(
                $this->query()
                     ->setFormatter($formatter)
                     ->find()
                     ->toArray()
            );
        }

        foreach ($result as &$datum) {
            $datum = $this->processAttrOnResponse($datum, $input);
        }

        return $result;
    }

    /**
     * Convert CamelCase to under_score
     *
     * @param array $data
     *
     * @return array
     */
    public static function convertFieldName(array $data)
    {
        $filter = new \Laminas\Filter\Word\CamelCaseToUnderscore();
        $result = [];

        if (isset($data[0])) {
            foreach ($data as $datum) {
                $result[] = self::convertFieldName($datum);
            }
        } else {
            foreach ($data as $fieldName => $fieldValue) {
                /*
                 * Cleanup a stupid Propel issue returning [[]] on empty array result
                 */
                if ($fieldValue == [[]]) {
                    $fieldValue = [];
                }

                $result[strtolower($filter->filter($fieldName))] = is_array($fieldValue)
                    ? self::convertFieldName($fieldValue)
                    : $fieldValue;
            }
        }

        return $result;
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function prepareCreate(array &$input): array
    {
        $data = $input;
        foreach ($this->defaults() as $attrName => $default) {
            if ((static::SCHEMA['properties'][$attrName]['type'] ?? 'null') == 'object') {
                $data[$attrName] = array_replace_recursive($default, $data[$attrName] ?? []);
            } else {
                $data[$attrName] = $data[$attrName] ?? $default;
            }
        }

        return $this->processAttrOnRequest($data);
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function create(array $input)
    {
        Propel2::transaction();

        $result = parent::create($input);

        Propel2::commit();

        return $result;
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function executeCreate(array $payload, array &$input): array
    {
        $model = $this->insert($payload, $input);

        // include computed?
        // self::$computed = $this->computedMap();

        $attr   = $model->toArray(TableMap::TYPE_FIELDNAME);
        $result = $this->processAttrOnResponse(
            $attr,
            ['attr' => array_keys($attr)]
        );

        $this->createSubReferences($result, $input);

        return $result;
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return mixed
     */
    public function insert(array $payload, array &$input)
    {
        $modelName = static::PROPEL;
        $model     = new $modelName();
        $model->fromArray($payload, TableMap::TYPE_FIELDNAME);
        $model->save();

        return $model;
    }

    public function createSubReferences(array $result, array $input)
    {
        $references = array_intersect_key(
            $input,
            array_flip($this->referenceColumnMap())
        );

        foreach ($references as $referenceName => $data) {
            /*
             * Special case when you update using {id: 1, name: Test}
             * This is to avoid creation of already existing relation
             */
            if (is_array($data) && isset($data['id'])) {
                continue;
            }

            // one-to-many relation only
            if ((static::SCHEMA['properties'][$referenceName]['type'] ?? 'object') == 'array') {
                $reference = $this->referenceObject($referenceName);

                // parent column name in child reference
                $columnMap        = $reference->referenceColumnMap();
                $parentColumnName = null;
                $parentClassName  = get_class($this);

                foreach ($columnMap as $columnName) {
                    // is_array IS FOR multiple-references on the same column
                    if (($reference::SCHEMA['properties'][$columnName]['resource'] ?? null) == $parentClassName) {
                        $parentColumnName = $columnName;
                        break;
                    } elseif (isset($reference::SCHEMA['properties'][$columnName]['oneOf'][0]['resource'])) {
                        foreach ($reference::SCHEMA['properties'][$columnName]['oneOf'] as $def) {
                            if ($def['resource'] == $parentClassName) {
                                $parentColumnName = $columnName;
                                break 2;
                            }
                        }
                    }
                }

                foreach ((array)$data as $datum) {
                    $datum[$parentColumnName] = $result['id'];
                    foreach ($reference->defaults() as $columnName => $defaultValue) {
                        if (is_array($defaultValue)) {
                            // type: object
                            $datum[$columnName] = ($datum[$columnName] ?? []) + $defaultValue;
                        } elseif (!isset($datum[$columnName])) {
                            // type: !object
                            $datum[$columnName] = $defaultValue;
                        }
                    }

                    $reference->create($datum);
                }
            }
        }
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Laminas\Filter\Exception\RuntimeException
     */
    public function prepareUpdate(array &$input): array
    {
        $this->processArgs($input);

        $this->withArgs($input);

        // process attributes
        return $this->processAttrOnRequest($input['attr']);
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function executeUpdate(array $payload, array &$input)
    {
        // fix json issues
        $tableName = ((static::PROPEL)::TABLE_MAP)::TABLE_NAME;

        $criteria = new Criteria();
        foreach ($payload as $column => $value) {
            if ($this->isAttributeObject($column)) {
                $value = addslashes((is_array($value) ? json_encode($value) : $value) ?: '{}');
                $criteria->add(
                    "{$tableName}.{$column}",
                    "JSON_MERGE_PATCH(COALESCE({$column}, '{}'), '{$value}')",
                    Criteria::CUSTOM_EQUAL
                );
            } else {
                $criteria->add("{$tableName}.{$column}", $value);
            }
        }

        return $this->query()->update($criteria);
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function prepareDelete(array &$input)
    {
        $this->withArgs($input);

        $this->resolveArguments();

        return $input;
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return array|int
     * @throws \InvalidArgumentException
     * @throws \LogicException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function executeDelete(array $payload, array &$input)
    {
        return $this->query()->delete();
    }

    /*
     *
     * Search
     *
     */

    /**
     * @param array $input
     *
     * @throws \InvalidArgumentException
     */
    public function withAttr(array &$input)
    {
        if (!$input['attr']) {
            throw new \InvalidArgumentException('Missing attribute list');
        }

        foreach ($input['attr'] as $name) {
            if ($this->isAttribute($name)) {
                $this->withAttribute($name, $input);
            } elseif ($this->isComputed($name)) {
                $this->withComputed($name, $input);
            } elseif (strpos($name, '.')) {
                [$realName, $column] = explode('.', $name, 2);
                if ($this->isReference($realName)) {
                    // relation
                    $input[$realName]           = $input[$realName] ?? ['attr' => []];
                    $input[$realName]['attr'][] = $column;
                } else {
                    // json
                    $input['attr'][] = $realName;
                }
            } else {
                throw new \InvalidArgumentException("Unknown attribute `{$name}`");
            }
        }
    }

    public function withAttribute(string $name, array $input)
    {
        // propel loads all attributes
    }

    /**
     * @param string $name
     * @param array  $input
     */
    public function withComputed(string $name, array &$input)
    {
        self::$computed[] = static::SCHEMA['properties'][$name]['computed'];

        $this->{'withComputed' . $this->propelName($name)}($input);
    }

    /**
     * @param array $input
     *
     * @throws \InvalidArgumentException
     */
    public function withArgs(array &$input)
    {
        foreach ($input['args'] as $arg => $value) {
            if ($this->isAttribute($arg)) {
                $this->withCriteria($arg, $value);
            } elseif ($this->isReference($arg)) {
                $column = $this->referenceColumnName($arg);
                $this->withCriteria($column, $value);
            } elseif ($this->isArgument($arg)) {
                $this->withArgument($arg, $value);
            } elseif ($this->isDirective($arg)) {
                $this->withDirective($arg, $value);
            } else {
                throw new \InvalidArgumentException("Cannot search on `{$arg}`");
            }
        }
    }

    /**
     * @param string $attr
     * @param mixed  $value
     *
     * @throws \InvalidArgumentException
     */
    public function withCriteria(string $attr, $value)
    {
        if ($value === null) {
            $this->checkCriterionAllowed($attr, self::CRITERIA_EQUAL);
            $this->addCriteria($attr, self::CRITERIA_NULL, $value);
        } elseif (is_scalar($value)) {
            $this->checkCriterionAllowed($attr, self::CRITERIA_EQUAL);
            $this->addCriteria($attr, self::CRITERIA_EQUAL, $value);
        } elseif (is_array($value)) {
            foreach ($value as $criteria => $subValue) {
                $this->checkCriterionAllowed($attr, $criteria);
                $this->addCriteria($attr, $criteria, $subValue);
            }
        }
    }

    /**
     * @param $attr
     * @param $criteria
     * @param $value
     *
     * @throws \InvalidArgumentException
     */
    public function addCriteria($attr, $criteria, $value)
    {
        $map = self::criteriaMap();

        if (!isset($map[$criteria])) {
            throw new \InvalidArgumentException("Unknown criteria: `$criteria``");
        }

        $map[$criteria]($this, $attr, $value);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws \InvalidArgumentException
     */
    public function withArgument(string $name, $value)
    {
        $name = substr($name, 1);
        $this->checkArgumentAllowed($name);
        $this->arguments[$name] = $value;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @throws \InvalidArgumentException
     */
    public function withDirective(string $name, $value)
    {
        $name = substr($name, 1);
        $this->checkDirectiveAllowed($name);
        $this->directives[$name] = $value;
    }

    /**
     * @param array         $input
     * @param               $resource
     * @param ModelCriteria $query
     *
     * @throws \InvalidArgumentException
     * @throws \Propel\Runtime\ActiveQuery\Exception\UnknownRelationException
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function withRefs(array &$input, Resource $resource, ModelCriteria $query)
    {
        $references = array_intersect_key(
            $input,
            array_flip($resource->referenceColumnMap())
        );

        if ($references == []) {
            return;
        }

        $subQuery = null;
        if (get_class($resource) !== get_class($this)) {
            $subQuery = $query->useQuery('r' . (self::$joinIndex - 1));
        }

        foreach ($references as $reference => $referenceData) {
            $this->withReference($reference, $referenceData, $resource, $subQuery ?: $query);
            $input[$reference] = $referenceData;
        }

        if (get_class($resource) !== get_class($this)) {
            $subQuery->endUse();
        }
    }

    /**
     * @param string        $name
     * @param array         $data
     * @param               $resource
     * @param ModelCriteria $query
     *
     * @throws \Propel\Runtime\ActiveQuery\Exception\UnknownRelationException
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \InvalidArgumentException
     */
    public function withReference(string $name, array &$data, Resource $resource, ModelCriteria $query)
    {
        $propelName = $resource::SCHEMA['properties'][$name]['propel']
            ?? $data['use']; // hack for multi-references
        $joinIndex  = self::$joinIndex;
        $joinType   = isset($data['args']) && !isset($data['args'][':optional'])
            ? Criteria::INNER_JOIN
            : Criteria::LEFT_JOIN;
        $resource   = $resource->referenceObject($name, $data['use'] ?? null);

        $query->joinWith("{$propelName} r{$joinIndex}", $joinType);

        if (isset($data['args'])) {
            // transform args if required
            $resource->processArgs($data);
            foreach ($data['args'] as $attr => $value) {
                $resource->withCriteriaOnJoin($attr, $value, $query);
            }
        }

        foreach ($data['attr'] ?? [] as $attr) {
            if ($resource->isComputed($attr)) {
                $resource->withComputed($attr, $data);
            }
        }

        self::$joinIndex++;

        $this->withRefs($data, $resource, $query);
    }

    /**
     * @param string        $name
     * @param               $value
     * @param ModelCriteria $query
     *
     * @throws \Propel\Runtime\Exception\PropelException
     */
    public function withCriteriaOnJoin(string $name, $value, ModelCriteria $query)
    {
        $alias = 'r' . self::$joinIndex;

        if (!isset(static::SCHEMA['properties'][$name])) {
            return;
        }

        if ($this->isReference($name)) {
            $name = $this->referenceColumnName($name);
        }

        $closures = self::criteriaJoinMap();

        // TODO: is allowed?

        if ($value === null) {
            $query->addJoinCondition($alias, "{$alias}.{$name} IS NULL");
        } elseif (is_scalar($value)) {
            $query->addJoinCondition($alias, "{$alias}.{$name} = ?", $value);
        } else {
            foreach ($value as $criterion => $comparison) {
                $closureKey = static::JOIN_CRITERIA_MAP[$criterion];
                $closures[$closureKey]($query, $alias, $name, $criterion, $comparison);
            }
        }
    }

    /**
     * List of arguments
     *
     * @return array
     */
    public function arguments()
    {
        return $this->arguments;
    }

    /**
     * List of directives
     *
     * @return array
     */
    public function directives()
    {
        return $this->directives;
    }

    /**
     * Resolve resource arguments
     */
    public function resolveArguments()
    {
        foreach ($this->arguments as $argument => $value) {
            $argumentClass = self::$argumentsMap[$argument];
            is_string($argumentClass)
                ? (new $argumentClass())($value, $this)
                : $argumentClass($value, $this); // closure
        }
    }

    /*
     *
     * Processing
     *
     */

    /**
     * For SEARCH, UPDATE, DELETE
     *
     * Required on request
     *
     * @param array $input
     *
     * @throws \InvalidArgumentException
     */
    public function processArgs(array &$input)
    {
        foreach ($input['args'] as $attr => &$criteria) {
            if ($this->isAttribute($attr)) {
                if (!is_array($criteria)) {
                    $criteria = ['=' => $criteria];
                }

                foreach ($criteria as $criterion => &$value) {
                    if (is_array($value)) {
                        foreach ($value as &$subValue) {
                            $subValue = static::transformRequest(static::SCHEMA['properties'][$attr], $subValue);
                        }
                    } else {
                        $value = static::transformRequest(static::SCHEMA['properties'][$attr], $value);
                    }
                }
            } elseif ($this->isReference($attr)) {
                if (!is_array($criteria)) {
                    $criteria = ['=' => $criteria];
                }

                $resource = $this->referenceObject($attr);
                foreach ($criteria as $criterion => &$value) {
                    if (!is_array($value)) {
                        $value = $resource::transformRequest($resource::SCHEMA['properties']['id'], $value);
                    } else {
                        foreach ($value as &$subValue) {
                            $subValue = $resource::transformRequest($resource::SCHEMA['properties']['id'], $subValue);
                        }
                    }
                }
            } elseif (strpos($attr, '.')) {
                [$reference, $referenceAttr] = explode('.', $attr, 2);
                $input[$reference]                         = $input[$reference] ?? [];
                $input[$reference]['args']                 = $input[$reference]['args'] ?? [];
                $input[$reference]['args'][$referenceAttr] = $criteria;
                // we expect when we add join conditions to process args so we're done here
            }
        }
    }

    /**
     * For READING (read, search)
     *
     * @param array $attributes Key => Value
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    public function processAttrOnResponse(array $attributes, array $input): array
    {
        $computed = []; // avoid running foreach after we add it to the end of attributes array

        // fix attr as list with no args or relations
        if (isset($input[0])) {
            $input['attr'] = $input;
        }

        $attributesInput = array_intersect_key(
            $attributes,
            array_flip($input['attr'] ?? []) + $input + $this->aliases()
        );

        // do a cleanup first
        foreach ($attributesInput as $attribute => $value) {
            if ($this->isReference($attribute) && !is_array($value)) {
                unset($attributesInput[$attribute]);
            } elseif ($this->isReferenceAlias($attribute)) {
                $realAttribute = $this->aliases()[$attribute];
                /*
                 * Issues with aliases and Model::toArray fixes
                 */
                if ($realAttribute != $attribute) {
                    $attributesInput[$realAttribute] = $value;
                    unset($attributesInput[$attribute]);
                }
            }
        }

        foreach ($attributesInput as $attribute => &$value) {
            if ($this->isAttribute($attribute)) {
                $definition = static::SCHEMA['properties'][$attribute];
                $value      = static::transformResponse($definition, $value);
                $value      = static::cast($definition, $value);
            } elseif ($this->isReference($attribute)) {
                $reference = $this->referenceObject($attribute);
                if ($value !== null && isset($input[$attribute])) {
                    if (!isset($input[$attribute]['attr'])) {
                        // just join without attr
                        unset($attributesInput[$attribute]);
                    } elseif (isset($value[0])) {
                        foreach ($value as &$subValue) {
                            $subValue = $reference->processAttrOnResponse($subValue, $input[$attribute]);
                        }
                    } elseif (is_array($value)) {
                        $value = $reference->processAttrOnResponse($value, $input[$attribute]);
                    } else {
                        $value = $reference->processAttrOnResponse(['id' => $value], ['attr' => ['id']])['id'] ?? null;
                    }
                }
            } elseif ($this->isReferenceColumn($attribute)) { // ex: player_id
                $realAttribute = $this->referenceName($attribute); // ex: player
                $reference     = $this->referenceObject($realAttribute);
                if ($value !== null) {
                    $value = $reference->processAttrOnResponse(['id' => $value], ['attr' => ['id']])['id'] ?? null;
                }
                unset($attributesInput[$attribute]);
                $attributesInput[$realAttribute] = $value;
            }
        }

        if ($this->ignoreComputed === false) {
            foreach (self::$computed as $computedAlias) {
                if (strpos($computedAlias, ':')) {
                    [$schemaId, $attrFullName] = explode('|', $computedAlias);
                    if ($schemaId == static::SCHEMA['$id']) {
                        [$attrName, $subAttrName] = explode(':', $attrFullName);
                        $computed[$attrFullName] = is_array($attributes[$attrName])
                            ? $attributes[$attrName][$subAttrName] ?? null
                            : json_decode($attributes[$attrName], true)[$subAttrName] ?? null;
                    }
                } elseif ($computedColumn = array_search($computedAlias, $this->computedMap())) {
                    if ($attributes) {
                        $computed[$computedColumn] = $this->{'computed' . $this->propelName($computedColumn)}($attributes);
                    }
                }
            }
        }

        return $attributesInput + $computed;
    }

    /**
     * For MUTATION (create, update)
     *
     * @param array $payload
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Laminas\Filter\Exception\RuntimeException
     */
    public function processAttrOnRequest(array $payload)
    {
        $references = [];

        foreach ($payload as $attribute => &$value) {
            if ($this->isAttribute($attribute)) {
                $definition = static::SCHEMA['properties'][$attribute];
                $value      = static::sanitizeValue($definition, $value);
            } elseif ($this->isReference($attribute)) {
                $referenceColumn = $this->referenceColumnName($attribute);
                $referenceObject = $this->referenceObject($attribute);
                // fix when saving relation with object instead of scalar
                if (is_array($value) && !isset($value[0])) {
                    $value = $value['id'];
                }
                $references[$referenceColumn] = $value === null
                    ? $value
                    : $referenceObject->processAttrOnRequest(['id' => $value])['id'];
                unset($payload[$attribute]);
            }
        }

        return $payload + $references;
    }

    /**
     * Return true if t expects to have more than 0 results
     *
     * @return bool
     * @throws \Propel\Runtime\Exception\PropelException
     * @throws \ReflectionException
     */
    public function fixPaginationWithRelationsOnQuery(): bool
    {
        $customQuery = clone $this->query;

        $reflected = new \ReflectionObject($customQuery);
        $property  = $reflected->getProperty('with');
        $property->setAccessible(true);
        $property->setValue($customQuery, []);
        $property = $reflected->getProperty('isWithOneToMany');
        $property->setAccessible(true);
        $property->setValue($customQuery, false);

        $ids = $customQuery
            ->select(['Id'])
            ->setLimit($this->query()->getLimit())
            ->setOffset($this->query()->getOffset())
            ->find()
            ->toArray();

        if (count($ids) == 0) {
            return false;
        }

        // remove criteria that was already ran on simpleQuery
        $prop = new \ReflectionProperty(ModelCriteria::class, 'map');
        $prop->setAccessible(true);
        $prop->setValue($this->query, []);

        $this->query()->filterByPrimaryKeys($ids)->setLimit(-1)->setOffset(0);

        return true;
    }

    /*
     *
     * Helpers
     *
     */

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isComputedAlias(string $name): bool
    {
        return in_array($name, $this->computedMap());
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isReferenceAlias(string $name): bool
    {
        return isset($this->aliases()[$name])
            && isset(static::SCHEMA['properties'][$this->aliases()[$name]]['column']);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isReferenceColumn(string $name): bool
    {
        return isset($this->referenceColumnMap()[$name]);
    }

    /**
     * Ex: country_id => country
     *
     * @param string $name
     *
     * @return string
     */
    public function referenceName(string $name): string
    {
        return $this->referenceColumnMap()[$name];
    }

    /**
     * Return key-value pair ['column_name' => '']
     * Example: ['country_id' => 'country']
     *
     * @return array
     */
    public function referenceColumnMap(): array
    {
        static $map;

        if ($map === null) {
            foreach (static::SCHEMA['properties'] as $name => $definition) {
                if (isset($definition['column'])) {
                    $map[$definition['column']] = $name;
                }
            }
        }

        return $map ?: [];
    }

    /**
     * @param string $name
     *
     * @return false|int|string
     */
    public function referenceColumnName(string $name): string
    {
        return array_search($name, $this->referenceColumnMap());
    }

    /**
     * @param string $name
     *
     * @return Resource
     */
    public function referenceObject(string $name, string $use = null): ResourceInterface
    {
        $resourceClass = static::SCHEMA['properties'][$name]['resource'] ?? null;

        // multi-reference
        if ($resourceClass === null) {
            // hack
            $resourceClass = static::SCHEMA['properties'][$name]['oneOf'][0]['resource'];
            foreach (static::SCHEMA['properties'][$name]['oneOf'] as $item) {
                if ($item['propel'] == $use) {
                    $resourceClass = $item['resource'];
                    break;
                }
            }
        }

        return (new $resourceClass($this->request));
    }

    /**
     * Return [alias => real_name]
     * Ex: ['committee' => 'parent']
     *
     * @return array
     */
    public function aliases(): array
    {
        static $aliases = [];

        foreach (static::SCHEMA['properties'] as $name => $def) {
            if (isset($def['alias'])) {
                if (is_array($def['alias'])) {
                    $aliases += array_fill_keys($def['alias'], $name);
                } else {
                    $aliases[$def['alias']] = $name;
                }
            } elseif (isset($def['propel'])) {
                $filter = new \Laminas\Filter\Word\CamelCaseToUnderscore();

                $aliases[strtolower($filter->filter($def['propel']))] = $name;
            }
        }

        return $aliases;
    }

    /**
     * Converts snake_case to camelCase
     *
     * @param string $name
     *
     * @return string
     */
    public function propelName(string $name): string
    {
        return static::SCHEMA['properties'][$name]['name'] ??
            preg_replace_callback(
                '/(_[a-z]{1})/',
                function ($value) {
                    return strtoupper($value[0][1]);
                },
                ucfirst($name)
            );
    }

    /**
     * Key value with ['computed_name' => 'computed_alias']
     *
     * @return array
     */
    public function computedMap(): array
    {
        static $map;

        if ($map === null) {
            foreach (static::SCHEMA['properties'] as $name => $def) {
                if (isset($def['computed'])) {
                    $map[$name] = $def['computed'];
                }
            }
        }

        return $map ?: [];
    }

    /**
     * @param bool $newInstance
     *
     * @return ModelCriteria|\Spartan\Rest\Domain\Resource\Author
     */
    public function query($newInstance = false)
    {
        if ($this->query === null || $newInstance === true) {
            $queryClass  = static::PROPEL . 'Query';
            $this->query = $queryClass::create();
        }

        return $this->query;
    }

    public static function criteriaJoinMap()
    {
        return [
            '='                     => function (ModelCriteria $query, $alias, $name, $criterion, $comparison) {
                $query->addJoinCondition($alias, "{$alias}.{$name} {$criterion} ?", $comparison);
            },
            '~'                     => function (ModelCriteria $query, $alias, $name, $criterion, $comparison) {
                $comparison = '%' . str_replace('%', '', $comparison) . '%';
                $like       = $criterion[0] == '!' ? 'NOT LIKE' : 'LIKE';
                $type       = substr($criterion, -1);
                if ($type == '^') {
                    $comparison = substr($comparison, 1);
                } elseif ($type == '$') {
                    $comparison = substr($comparison, 0, -1);
                }

                $query->addJoinCondition($alias, "{$alias}.{$name} {$like} ?", $comparison);
            },
            self::CRITERIA_IN       => function (ModelCriteria $query, $alias, $name, $criterion, $comparison) {
                $query->addJoinCondition($alias, "{$alias}.{$name} IN ?", $comparison);
            },
            self::CRITERIA_NOT_IN   => function (ModelCriteria $query, $alias, $name, $criterion, $comparison) {
                $query->addJoinCondition($alias, "{$alias}.{$name} NOT IN ?", $comparison);
            },
            self::CRITERIA_NULL     => function (ModelCriteria $query, $alias, $name, $criterion, $comparison) {
                $query->addJoinCondition($alias, "{$alias}.{$name} IS NULL");
            },
            self::CRITERIA_NOT_NULL => function (ModelCriteria $query, $alias, $name, $criterion, $comparison) {
                $query->addJoinCondition($alias, "{$alias}.{$name} IS NOT NULL");
            },
            self::CRITERIA_BETWEEN  => function (ModelCriteria $query, $alias, $name, $criterion, $comparison) {
                // TODO
            },
        ];
    }

    public static function criteriaConditionMap()
    {
        return [
            self::CRITERIA_EQUAL           => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => '=',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_NOT_EQUAL       => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => '<>',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_NOT_EQUAL_ALT   => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => '<>',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_GREATER         => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => '>',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_GREATER_EQUAL   => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => '>=',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_LESS            => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => '<',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_LESS_EQUAL      => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => '<=',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_BINARY_AND      => function (Resource $resource, $attrName, $value) {
                return []; // TODO:
            },
            self::CRITERIA_BINARY_OR       => function (Resource $resource, $attrName, $value) {
                return []; // TODO:
            },
            self::CRITERIA_IN              => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'IN',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_NOT_IN          => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'NOT IN',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_NULL            => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'IS NULL',
                    'value'    => null,
                ];
            },
            self::CRITERIA_NOT_NULL        => function (Resource $resource, $attrName, $value) {
                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'IS NOT NULL',
                    'value'    => $value,
                ];
            },
            self::CRITERIA_LIKE            => function (Resource $resource, $attrName, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues

                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'LIKE',
                    'value'    => "%{$value}%",
                ];
            },
            self::CRITERIA_NOT_LIKE        => function (Resource $resource, $attrName, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues

                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'NOT LIKE',
                    'value'    => "%{$value}%",
                ];
            },
            self::CRITERIA_STARTS_WITH     => function (Resource $resource, $attrName, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues

                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'LIKE',
                    'value'    => "{$value}%",
                ];
            },
            self::CRITERIA_NOT_STARTS_WITH => function (Resource $resource, $attrName, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues

                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'NOT LIKE',
                    'value'    => "{$value}%",
                ];
            },
            self::CRITERIA_ENDS_WITH       => function (Resource $resource, $attrName, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues

                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'LIKE',
                    'value'    => "%{$value}",
                ];
            },
            self::CRITERIA_NOT_ENDS_WITH   => function (Resource $resource, $attrName, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues

                return [
                    'column'   => $resource::PROPEL . '.' . $resource->propelName($attrName),
                    'criteria' => 'NOT LIKE',
                    'value'    => "%{$value}",
                ];
            },
            self::CRITERIA_BETWEEN         => function (Resource $resource, $attrName, $value) {
                return []; // TODO:
            },
        ];
    }

    public static function criteriaMap()
    {
        return [
            self::CRITERIA_EQUAL           => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::EQUAL);
            },
            self::CRITERIA_NOT_EQUAL       => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::NOT_EQUAL);
            },
            self::CRITERIA_NOT_EQUAL_ALT   => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::NOT_EQUAL);
            },
            self::CRITERIA_GREATER         => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::GREATER_THAN);
            },
            self::CRITERIA_GREATER_EQUAL   => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::GREATER_EQUAL);
            },
            self::CRITERIA_LESS            => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::LESS_THAN);
            },
            self::CRITERIA_LESS_EQUAL      => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::LESS_EQUAL);
            },
            self::CRITERIA_BINARY_AND      => function (Resource $resource, $attr, $value) {
                if (is_array($value)) {
                    $value = array_sum($value);
                }

                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::BINARY_AND);
            },
            self::CRITERIA_BINARY_OR       => function (Resource $resource, $attr, $value) {
                if (is_array($value)) {
                    $value = array_sum($value);
                }

                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::BINARY_OR);
            },
            self::CRITERIA_IN              => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::IN);
            },
            self::CRITERIA_NOT_IN          => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::NOT_IN);
            },
            self::CRITERIA_NULL            => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::ISNULL);
            },
            self::CRITERIA_NOT_NULL        => function (Resource $resource, $attr, $value) {
                $resource->query()->filterBy($resource->propelName($attr), $value, Criteria::ISNOTNULL);
            },
            self::CRITERIA_LIKE            => function (Resource $resource, $attr, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues
                $resource->query()->filterBy($resource->propelName($attr), "%{$value}%", Criteria::LIKE);
            },
            self::CRITERIA_NOT_LIKE        => function (Resource $resource, $attr, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues
                $resource->query()->filterBy($resource->propelName($attr), "%{$value}%", Criteria::NOT_LIKE);
            },
            self::CRITERIA_STARTS_WITH     => function (Resource $resource, $attr, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues
                $resource->query()->filterBy($resource->propelName($attr), "{$value}%", Criteria::LIKE);
            },
            self::CRITERIA_NOT_STARTS_WITH => function (Resource $resource, $attr, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues
                $resource->query()->filterBy($resource->propelName($attr), "{$value}%", Criteria::NOT_LIKE);
            },
            self::CRITERIA_ENDS_WITH       => function (Resource $resource, $attr, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues
                $resource->query()->filterBy($resource->propelName($attr), "%{$value}", Criteria::LIKE);
            },
            self::CRITERIA_NOT_ENDS_WITH   => function (Resource $resource, $attr, $value) {
                $value = str_replace('%', '', $value); // avoid performance issues
                $resource->query()->filterBy($resource->propelName($attr), "%{$value}", Criteria::NOT_LIKE);
            },
            self::CRITERIA_BETWEEN         => function (Resource $resource, $attr, $value) {
                $min = $value[0];
                $max = $value[1];
                $resource
                    ->query()
                    ->where($resource::PROPEL . '.' . $resource->propelName($attr) . ' >= ?', $min)
                    ->where($resource::PROPEL . '.' . $resource->propelName($attr) . ' <= ?', $max);
            },
        ];
    }

    public function schema()
    {
        $schema = static::SCHEMA;

        foreach ($schema['properties'] as $name => &$definition) {
            // fix MANY-TO-ONE relations
            if (isset($definition['$ref'])) {
                $definition = [
                    'oneOf' => array_filter(
                        [
                            $this->referenceObject($name)::SCHEMA['properties']['id'],
                            [
                                'type'                 => 'object',
                                'properties'           => [
                                    'id' => $this->referenceObject($name)::SCHEMA['properties']['id'],
                                ],
                                'additionalProperties' => true,
                            ],
                            isset($definition['type']) ? ['type' => $definition['type']] : null,
                        ]
                    ),
                ];
            } elseif (isset($definition['items']['$ref'])) {
                $definition = true; // TODO: this is a fix
            } elseif (isset($definition['oneOf'][0]['$ref'])) {
                $resourceClass = $definition['oneOf'][0]['resource'];
                $definition    = $resourceClass::SCHEMA['properties']['id'];
            }
        }

        return $schema;
    }
}
