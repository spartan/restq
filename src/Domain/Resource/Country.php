<?php

namespace Spartan\Rest\Domain\Resource;

use Spartan\Rest\Adapter\Propel\Resource;

class Country extends Resource
{

    public const PROPEL = \Spartan\Rest\Domain\Model\Country::class;

    public const SCHEMA = [
        '$schema' => 'https://json-schema.org/draft/2019-09/schema',
        '$id' => 'https://example.com/country',
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
            'iso2' => [
                'type' => 'string',
                'maxLength' => 2,
                'minLength' => 2,
                'format' => 'date-time',
                'name' => 'Iso2',
            ],
            'continent' => [
                'type' => 'string',
                'maxLength' => 2,
                'minLength' => 2,
                'format' => 'date-time',
                'name' => 'Continent',
            ],
            'currency' => [
                'type' => 'string',
                'maxLength' => 3,
                'minLength' => 3,
                'format' => 'date-time',
                'name' => 'Currency',
            ],
            'authors' => [
                'type' => 'array',
                'items' => [
                    '$ref' => 'https://example.com/author',
                ],
                'propel' => 'Author',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\Author',
                'column' => 'authors',
            ],
            'publishers' => [
                'type' => 'array',
                'items' => [
                    '$ref' => 'https://example.com/publisher',
                ],
                'propel' => 'Publisher',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\Publisher',
                'column' => 'publishers',
            ],
        ],
        'required' => [
            'name',
            'iso2',
            'continent',
            'currency',
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
            ],
            'unique' => [
                
            ],
            'introspect' => true,
            'indexOnly' => false,
            'immutable' => false,
        ],
    ];


}
