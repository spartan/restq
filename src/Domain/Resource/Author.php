<?php

namespace Spartan\Rest\Domain\Resource;

use Spartan\Rest\Adapter\Propel\Resource;

class Author extends Resource
{

    public const PROPEL = \Spartan\Rest\Domain\Model\Author::class;

    public const SCHEMA = [
        '$schema' => 'https://json-schema.org/draft/2019-09/schema',
        '$id' => 'https://example.com/author',
        'type' => 'object',
        'properties' => [
            'id' => [
                'type' => 'integer',
                'readOnly' => true,
                'minimum' => 1,
                'format' => 'date-time',
                'name' => 'Id',
            ],
            'first_name' => [
                'type' => 'string',
                'maxLength' => 100,
                'format' => 'date-time',
                'name' => 'FirstName',
            ],
            'last_name' => [
                'type' => 'string',
                'maxLength' => 100,
                'format' => 'date-time',
                'name' => 'LastName',
            ],
            'birth_date' => [
                'type' => 'string',
                'format' => 'date-time',
                'name' => 'BirthDate',
            ],
            'country' => [
                '$ref' => 'https://example.com/country',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\Country',
                'propel' => 'Country',
                'column' => 'country_id',
            ],
            'status' => [
                'enum' => [
                    'inactive',
                    'active',
                ],
                'map' => [
                    'inactive' => 0,
                    'active' => 1,
                ],
                'name' => 'Status',
            ],
            'author_books' => [
                'type' => 'array',
                'items' => [
                    '$ref' => 'https://example.com/author_book',
                ],
                'propel' => 'AuthorBook',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\AuthorBook',
                'column' => 'author_books',
            ],
        ],
        'required' => [
            'first_name',
            'last_name',
            'birth_date',
            'country',
            'status',
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

    public function withComputedName(&$input)
    {
        // do nothing
    }

    public function computedName($attr)
    {
        return $attr['first_name'] . ' ' . $attr['last_name'];
    }
}
