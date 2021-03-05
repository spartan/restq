<?php

namespace Spartan\Rest\Domain\Resource;

use Spartan\Rest\Adapter\Propel\Resource;

class AuthorBook extends Resource
{

    public const PROPEL = \Spartan\Rest\Domain\Model\AuthorBook::class;

    public const SCHEMA = [
        '$schema' => 'https://json-schema.org/draft/2019-09/schema',
        '$id' => 'https://example.com/author_book',
        'type' => 'object',
        'properties' => [
            'author' => [
                '$ref' => 'https://example.com/author',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\Author',
                'propel' => 'Author',
                'column' => 'author_id',
            ],
            'book' => [
                '$ref' => 'https://example.com/book',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\Book',
                'propel' => 'Book',
                'column' => 'book_id',
            ],
        ],
        'required' => [
            'author',
            'book',
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
                'author_id',
                'book_id',
            ],
            'unique' => [
                
            ],
            'introspect' => true,
            'indexOnly' => false,
            'immutable' => false,
        ],
    ];


}
