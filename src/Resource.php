<?php

namespace Spartan\Rest;

use Psr\Http\Message\ServerRequestInterface;
use Spartan\Filter\Filter\Group;
use Spartan\Fluent\Arr;
use Spartan\Rest\Definition\ResourceInterface;
use Spartan\Rest\Definition\TransformInterface;

/**
 * Resource Rest
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
abstract class Resource implements ResourceInterface
{
    const SCHEMA = [
        '$schema'              => 'https://json-schema.org/draft/2019-09/schema',
        '$id'                  => 'https://example.com/organization',
        'type'                 => 'object',
        'properties'           => [],
        'required'             => [],
        'additionalProperties' => false,
    ];

    const METHODS = [
        'get'     => 'search',
        'post'    => 'search',
        'put'     => 'create',
        'patch'   => 'update',
        'delete'  => 'delete',
        'options' => 'schema', // TODO
    ];

    protected static array $argumentsMap = [];

    protected static array $directivesMap = [
        'flatten' => 'Spartan\Rest\Directive\Flatten',
    ];

    protected static array $transformersMap = [
        'fakeInt'  => 'Spartan\Rest\Transform\FakeInt',
        'json'     => 'Spartan\Rest\Transform\Json',
        'password' => 'Spartan\Rest\Transform\Password',
        'crypt'    => 'Spartan\Rest\Transform\Crypt',
    ];

    protected ?ServerRequestInterface $request;

    /**
     * Generated meta by arguments
     */
    protected array $meta = [];

    /**
     * Directives used during a query
     */
    protected array $directives = [];

    /**
     * Resource constructor.
     *
     * @param ServerRequestInterface $request
     */
    public function __construct(ServerRequestInterface $request = null)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __invoke()
    {
        $httpMethod = strtolower($this->request->getMethod());
        $payload    = (array)$this->request->getParsedBody();

        if (!isset(static::METHODS[$httpMethod])) {
            throw new \InvalidArgumentException("HTTP method is not allowed: `{$httpMethod}`");
        }

        return $this->{static::METHODS[$httpMethod]}($payload);
    }

    /**
     * @param array $arguments
     */
    public static function registerArguments(array $arguments)
    {
        static::$argumentsMap = $arguments + static::$argumentsMap;
    }

    /**
     * @param array $directives
     */
    public static function registerDirectives(array $directives)
    {
        static::$directivesMap = $directives + static::$directivesMap;
    }

    /**
     * @param array $transformers
     */
    public static function registerTransformers(array $transformers)
    {
        static::$transformersMap = $transformers + static::$transformersMap;
    }

    /**
     * @return array
     */
    public static function maps()
    {
        return [
            'arguments'    => static::$argumentsMap,
            'directives'   => static::$directivesMap,
            'transformers' => static::$transformersMap,
        ];
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
     * @throws \LogicException
     */
    public function search(array $input): array
    {
        // sanitize
        if (isset($input[0])) {
            $input = ['attr' => $input];
        }
        $input += ['attr' => [], 'args' => []];

        $this->beforeSearch($input);
        $this->validateSearch($input);
        $this->authorizeSearch($input);

        $payload = $this->prepareSearch($input);
        $result  = $this->executeSearch($payload, $input);

        return $this->afterSearch($result, $input);
    }

    /**
     * @param array $input
     */
    public function beforeSearch(array &$input)
    {
        // hook
    }

    /**
     * @param array $input
     *
     * @throws \InvalidArgumentException
     */
    public function validateSearch(array &$input)
    {
        if ($this->schema()['writeOnly'] ?? false) {
            throw new \InvalidArgumentException('Schema is write only!');
        }

        foreach ($input['attr'] as $name => $value) {
            if (isset($schema['properties'][$name]['writeOnly'])) {
                throw new \InvalidArgumentException("Attribute `{$name}` is writeOnly");
            }
        }
    }

    /**
     * @param array $input
     */
    public function authorizeSearch(array &$input)
    {
        // hook
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function prepareSearch(array &$input): array
    {
        return $input;
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return array
     * @throws \LogicException
     */
    public function executeSearch(array $payload, array &$input): array
    {
        throw new \LogicException('Not implemented: ' . __METHOD__);
    }

    /**
     * @param array $result
     * @param array $input
     *
     * @return mixed
     */
    public function afterSearch(array $result, array $input)
    {
        return $this->resolveDirectives($result);
    }

    /*
     * CREATE
     *
     */

    /**
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Laminas\Filter\Exception\RuntimeException
     * @throws \LogicException
     */
    public function create(array $input)
    {
        // multi-create
        if (isset($input[0])) {
            $result = [];

            foreach ($input as $oneInput) {
                $result[] = $this->create($oneInput);
            }

            return $result;
        }

        $this->beforeCreate($input);
        $this->validateCreate($input);
        $this->authorizeCreate($input);

        $payload = $this->prepareCreate($input);
        $result  = $this->executeCreate($payload, $input);

        return $this->afterCreate($result, $input);
    }

    /**
     * @param array $input
     *
     * @return void
     */
    public function beforeCreate(array &$input)
    {
        // hook
    }

    /**
     * @param array $input
     *
     * @throws \InvalidArgumentException
     */
    public function validateCreate(array &$input)
    {
        if ($this->schema()['readOnly'] ?? false) {
            throw new \InvalidArgumentException('Schema is read only!');
        }

        /*
         * Include relation with '.' on save.
         * Ex: {"country.name": {"id": 1, "name": "US"}}
         */
        $original = $input;
        $input    = Arr::unflatten($input);

        foreach ($input as $name => &$value) {
            if (isset($schema['properties'][$name]['readOnly'])) {
                throw new \InvalidArgumentException("Attribute `{$name}` is readOnly");
            }

            if ($this->isReference($name) && !isset($original[$name]) && $value !== null) {
                $value = current($value);
            }
        }

        $jsonSchema = $this->schema();
        static::validateJsonSchema($input, $jsonSchema);
    }

    /**
     * @param array $input
     */
    public function authorizeCreate(array &$input)
    {
        // hook
    }

    /**
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Laminas\Filter\Exception\RuntimeException
     */
    public function prepareCreate(array &$input): array
    {
        return $this->processAttrOnRequest($input) + $this->defaults();
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return array
     * @throws \LogicException
     */
    public function executeCreate(array $payload, array &$input): array
    {
        throw new \LogicException('Not implemented: ' . __METHOD__);
    }

    /**
     * @param array $result
     * @param array $input
     *
     * @return array
     */
    public function afterCreate(array $result, array &$input)
    {
        return $result;
    }

    /**
     * @param array $payload
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \Laminas\Filter\Exception\RuntimeException
     */
    public function processAttrOnRequest(array $payload)
    {
        foreach ($payload as $attribute => &$value) {
            if ($this->isAttribute($attribute)) {
                $definition = static::SCHEMA['properties'][$attribute];
                $value      = static::sanitizeValue($definition, $value);
            }
        }

        return $payload;
    }

    /**
     * @param array $definition
     * @param       $value
     *
     * @return mixed
     * @throws \InvalidArgumentException
     * @throws \Laminas\Filter\Exception\RuntimeException
     */
    public static function sanitizeValue(array $definition, $value)
    {
        $value = static::filter($definition, $value);
        $value = static::cast($definition, $value);
        $value = static::transformRequest($definition, $value);

        return $value;
    }

    /*
     * UPDATE
     *
     */

    /**
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function update(array $input)
    {
        // sanitize
        $input += ['attr' => [], 'args' => []];

        $this->beforeUpdate($input);
        $this->validateUpdate($input);
        $this->authorizeUpdate($input);

        $payload = $this->prepareUpdate($input);
        $result  = $this->executeUpdate($payload, $input);

        return $this->afterUpdate($result, $input);
    }

    /**
     * @param array $input
     */
    public function beforeUpdate(array &$input)
    {
        // hook
    }

    /**
     * @param array $input
     *
     * @throws \InvalidArgumentException
     */
    public function validateUpdate(array &$input)
    {
        if (($this->schema()['readOnly'] ?? false) || ($this->schema()['writeOnce'] ?? false)) {
            throw new \InvalidArgumentException('Schema is immutable!');
        }

        /*
         * Include relation with '.' on save.
         * Ex: {"country.name": {"id": 1, "name": "US"}}
         */
        $original      = $input['attr'];
        $input['attr'] = Arr::unflatten($input['attr']);

        foreach ($input['attr'] as $name => &$value) {
            if (isset($schema['properties'][$name]['readOnly'])) {
                throw new \InvalidArgumentException("Attribute `{$name}` is readOnly");
            } elseif (isset($schema['properties'][$name]['writeOnce'])) {
                throw new \InvalidArgumentException("Attribute `{$name}` is immutable");
            }

            if ($this->isReference($name) && !isset($original[$name]) && $value !== null) {
                $value = current($value);
            }
        }

        $jsonSchema = $this->schema();
        unset($jsonSchema['required']);
        static::validateJsonSchema($input['attr'], $jsonSchema);
    }

    /**
     * @param array $input
     */
    public function authorizeUpdate(array &$input)
    {
        // hook
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function prepareUpdate(array &$input)
    {
        return $input;
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return mixed
     * @throws \LogicException
     */
    public function executeUpdate(array $payload, array &$input)
    {
        throw new \LogicException('Not implemented: ' . __METHOD__);
    }

    /**
     * @param       $result
     * @param array $input
     *
     * @return mixed
     */
    public function afterUpdate($result, array $input)
    {
        return $result;
    }

    /*
     * DELETE
     *
     */

    /**
     * @param array $input
     *
     * @return array
     * @throws \InvalidArgumentException
     * @throws \LogicException
     */
    public function delete(array $input)
    {
        // sanitize
        $input = ['args' => $input];

        $this->beforeDelete($input);
        $this->validateDelete($input);
        $this->authorizeDelete($input);

        $payload = $this->prepareDelete($input);
        $result  = $this->executeDelete($payload, $input);

        return $this->afterDelete($result, $input);
    }

    /**
     * @param array $input
     */
    public function beforeDelete(array &$input)
    {
        // hook
    }

    /**
     * @param array $input
     *
     * @throws \InvalidArgumentException
     */
    public function validateDelete(array &$input)
    {
        if (($this->schema()['readOnly'] ?? false) || ($this->schema()['writeOnce'] ?? false)) {
            throw new \InvalidArgumentException('Schema is immutable!');
        }
    }

    /**
     * @param array $input
     */
    public function authorizeDelete(array &$input)
    {
        // hook
    }

    /**
     * @param array $input
     *
     * @return array
     */
    public function prepareDelete(array &$input)
    {
        return $input;
    }

    /**
     * @param array $payload
     * @param array $input
     *
     * @return mixed
     * @throws \LogicException
     */
    public function executeDelete(array $payload, array &$input)
    {
        throw new \LogicException('Not implemented: ' . __METHOD__);
    }

    /**
     * @param       $result
     * @param array $input
     *
     * @return mixed
     */
    public function afterDelete($result, array $input)
    {
        return $result;
    }

    /*
     *
     * Validations and Filters
     *
     */

    /**
     * @param string $name
     * @param string $criteria
     *
     * @throws \InvalidArgumentException
     */
    public function checkCriterionAllowed(string $name, string $criteria)
    {
        if (!in_array($criteria, static::SCHEMA['resource']['criteria'])
            && !in_array($criteria . $name, static::SCHEMA['resource']['criteria'])) {
            throw new \InvalidArgumentException("Criteria `{$criteria}` is not allowed on `{$name}`");
        }
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     */
    public function checkArgumentAllowed(string $name)
    {
        if (!in_array($name, static::SCHEMA['resource']['arguments'])) {
            throw new \InvalidArgumentException("Argument `{$name}` is not allowed");
        }
    }

    /**
     * @param string $name
     *
     * @throws \InvalidArgumentException
     */
    public function checkDirectiveAllowed(string $name)
    {
        if (!in_array($name, static::SCHEMA['resource']['directives'])) {
            throw new \InvalidArgumentException("Directive `{$name}` is not allowed");
        }
    }

    public static function validateJsonSchema($payload, array $jsonSchema)
    {
        // check JSON schema
        $context = new \Swaggest\JsonSchema\Context();
        $schema  = \Swaggest\JsonSchema\Schema::import(
            json_decode(json_encode($jsonSchema)),
            $context
        );

        try {
            $schema->in(json_decode(json_encode($payload)));
        } catch (\Exception $e) {
            $class = get_class($e);
            throw new $class(substr(static::class, strrpos(static::class, '\\') + 1) . ': ' . $e->getMessage());
        }
    }

    /**
     * @param array $definition
     * @param       $value
     *
     * @return mixed
     */
    public static function cast(array $definition, $value)
    {
        if (isset($definition['type'])) {
            $type = (array)$definition['type'];

            if ($value === null && in_array('null', $type)) {
                return $value;
            }

            if ($type[0] === 'number') {
                $value = (float)$value;
            } elseif ($type[0] !== 'object') {
                settype($value, $type[0]);
            }
        }

        return $value;
    }

    /**
     * @param array $definition
     * @param       $value
     *
     * @return mixed
     * @throws \Laminas\Filter\Exception\RuntimeException
     */
    public static function filter(array $definition, $value)
    {
        if (isset($definition['filter'])) {
            return (new Group($definition['filter']))->filter($value);
        }

        return $value;
    }

    /**
     * On FETCH
     *
     * @param array $definition
     * @param       $value
     *
     * @return array|bool|mixed|null|string
     * @throws \InvalidArgumentException
     */
    public static function transformResponse(array $definition, $value)
    {
        // don't transform NULL
        if ($value === null) {
            return null;
        }

        // most common
        if (isset($definition['map'])) {
            if (in_array('array', (array)$definition['type'])) {
                $arrayValue = [];
                foreach ($definition['map'] as $key => $val) {
                    if ($key & $value) {
                        $arrayValue[] = $val;
                    }
                }
                $value = $arrayValue;
            } else {
                if (!isset($definition['map'][$value])) {
                    throw new \InvalidArgumentException("Unknown map value: `{$value}`");
                }
                $value = $definition['map'][$value];
            }
        }

        // fix json schema format
        if (isset($definition['format'])) {
            switch ($definition['format']) {
                case 'date':
                    /** @var string $value */
                    $value = substr($value, 0, 10);
                    break;
            }
        }

        // custom transform
        if (isset($definition['transform'])) {
            /** @var TransformInterface $transformClass */
            $transformClass = static::$transformersMap[$definition['transform']] ?? null;
            if (!$transformClass) {
                throw new \InvalidArgumentException("Unknown transformer `{$definition['transform']}`");
            }

            $value = (new $transformClass($definition))->response($value);
        }

        if (isset($definition['type']) && ($definition['type'] == 'object' || $definition['type'][0] == 'object') && !is_array($value)) {
            $value = json_decode($value, true);
            foreach ($value as $key => $val) {
                // safe check to avoid issues on renaming keys
                if (isset($definition['properties'][$key])) {
                    $value[$key] = self::transformResponse($definition['properties'][$key], $val);
                }
            }
        }

        return $value;
    }

    /**
     * On MUTATION
     *
     * @param array $definition
     * @param       $value
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function transformRequest(array $definition, $value)
    {
        // don't transform NULL
        if ($value === null) {
            return null;
        }

        // most common
        if (isset($definition['map'])) {
            if (is_array($value)) {
                $value = array_sum(
                    array_intersect_key(
                        array_flip($definition['map']),
                        array_flip($value)
                    )
                );
            } else {
                $key = array_search($value, $definition['map']);
                if ($key === false) {
                    throw new \InvalidArgumentException(
                        json_encode(
                            [
                                'message' => 'Map value not found',
                                'value'   => $value,
                                'map'     => $definition['map'],
                            ]
                        )
                    );
                }
                $value = $key;
            }
        }

        // fix json schema format
        if (isset($definition['format'])) {
            switch ($definition['format']) {
                case 'date':
                    $value = substr($value, 0, 10);
                    break;
            }
        }

        // custom transform
        if (isset($definition['transform'])) {
            /** @var TransformInterface $transformClass */
            $transformClass = static::$transformersMap[$definition['transform']] ?? null;
            if (!$transformClass) {
                throw new \InvalidArgumentException("Unknown transformer `{$definition['transform']}`");
            }

            $value = (new $transformClass($definition))->request($value);
        }

        if (isset($definition['type']) && $definition['type'] == 'object') {
            foreach ($value as $key => $val) {
                // safe check to avoid issues on renaming keys
                if (isset($definition['properties'][$key])) {
                    $value[$key] = self::transformRequest($definition['properties'][$key], $val);
                }
            }

            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return $value;
    }

    /**
     * @return array
     */
    public function defaults(): array
    {
        static $defaults;

        if ($defaults === null) {
            foreach (static::SCHEMA['properties'] as $name => $definition) {
                if (isset($definition['default'])) {
                    $defaults[$name] = $definition['default'];
                }
            }
        }

        return $defaults ?: [];
    }

    public function resolveDirectives($result)
    {
        foreach ($this->directives as $directive => $value) {
            $directiveClass = static::$directivesMap[$directive];
            $result         = is_string($directiveClass)
                ? (new $directiveClass())($value, $result, $this)
                : $directiveClass($value, $result, $this); // closure
        }

        return $result;
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
    public function isAttribute(string $name): bool
    {
        return isset(static::SCHEMA['properties'][$name]['name']);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isAttributeObject(string $name): bool
    {
        return $this->isAttribute($name)
            && isset(static::SCHEMA['properties'][$name]['type'])
            && in_array('object', (array)static::SCHEMA['properties'][$name]['type']);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isComputed(string $name): bool
    {
        return isset(static::SCHEMA['properties'][$name]['computed']);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isReference(string $name): bool
    {
        return isset(static::SCHEMA['properties'][$name]['$ref'])
            || isset(static::SCHEMA['properties'][$name]['items']['$ref'])
            || isset(static::SCHEMA['properties'][$name]['oneOf'][0]['$ref']);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isArgument(string $name): bool
    {
        return isset(static::$argumentsMap[substr($name, 1)]);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function isDirective(string $name): bool
    {
        return isset(static::$directivesMap[substr($name, 1)]);
    }

    public function request(): ServerRequestInterface
    {
        return $this->request;
    }

    public function withRequest(ServerRequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @param array $meta
     */
    public function withMeta(array $meta)
    {
        $this->meta = $meta + $this->meta;
    }

    /**
     * @return array
     */
    public function meta()
    {
        return $this->meta;
    }

    public function schema()
    {
        return static::SCHEMA;
    }
}
