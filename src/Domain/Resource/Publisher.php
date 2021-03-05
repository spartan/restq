<?php

namespace Spartan\Rest\Domain\Resource;

use Spartan\Rest\Adapter\Propel\Resource;

class Publisher extends Resource
{

    public const PROPEL = \Spartan\Rest\Domain\Model\Publisher::class;

    public const SCHEMA = [
        '$schema' => 'https://json-schema.org/draft/2019-09/schema',
        '$id' => 'https://example.com/publisher',
        'type' => 'object',
        'properties' => [
            'id' => [
                'type' => 'integer',
                'readOnly' => true,
                'minimum' => 1,
                'format' => 'date-time',
                'name' => 'Id',
            ],
            'name' => [
                'type' => 'string',
                'maxLength' => 100,
                'format' => 'date-time',
                'name' => 'Name',
            ],
            'country' => [
                '$ref' => 'https://example.com/country',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\Country',
                'propel' => 'Country',
                'column' => 'country_id',
            ],
            'books' => [
                'type' => 'array',
                'items' => [
                    '$ref' => 'https://example.com/book',
                ],
                'propel' => 'Book',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\Book',
                'column' => 'books',
            ],
        ],
        'required' => [
            'name',
            'country',
        ],
        'additionalProperties' => false,
        'resource' => [
            'criteria' => [
                '=',
            ],
            'arguments' => [
                'paginate',
                'limit',
            ],
            'directives' => [
                
            ],
            'index' => [
                'id',
                'country_id',
            ],
            'unique' => [
                
            ],
            'introspect' => true,
            'indexOnly' => false,
            'immutable' => false,
        ],
    ];


}
