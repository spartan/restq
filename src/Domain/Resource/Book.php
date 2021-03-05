<?php

namespace Spartan\Rest\Domain\Resource;

use Spartan\Rest\Adapter\Propel\Resource;

class Book extends Resource
{

    public const PROPEL = \Spartan\Rest\Domain\Model\Book::class;

    public const SCHEMA = [
        '$schema' => 'https://json-schema.org/draft/2019-09/schema',
        '$id' => 'https://example.com/book',
        'type' => 'object',
        'properties' => [
            'id' => [
                'type' => 'integer',
                'readOnly' => true,
                'minimum' => 1,
                'format' => 'date-time',
                'name' => 'Id',
            ],
            'title' => [
                'type' => 'string',
                'maxLength' => 150,
                'format' => 'date-time',
                'name' => 'Title',
            ],
            'isbn13' => [
                'type' => 'string',
                'maxLength' => 13,
                'minLength' => 13,
                'format' => 'date-time',
                'name' => 'Isbn13',
            ],
            'release_year' => [
                'type' => 'integer',
                'format' => 'date-time',
                'name' => 'ReleaseYear',
            ],
            'publisher' => [
                '$ref' => 'https://example.com/publisher',
                'resource' => 'Spartan\\Rest\\Domain\\Resource\\Publisher',
                'propel' => 'Publisher',
                'column' => 'publisher_id',
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
            'title',
            'isbn13',
            'release_year',
            'publisher',
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
                'isbn13',
                'publisher_id',
            ],
            'unique' => [
                
            ],
            'introspect' => true,
            'indexOnly' => false,
            'immutable' => false,
        ],
    ];


}
