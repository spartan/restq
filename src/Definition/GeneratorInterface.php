<?php

namespace Spartan\Rest\Definition;

/**
 * GeneratorInterface
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
interface GeneratorInterface
{
    const COLUMN_MAPPINGS = [
        'created_at' => [
            'type'     => 'string',
            'readOnly' => true,
            'format'   => 'date-time',
            'name'     => 'CreatedAt',
        ],
        'updated_at' => [
            'type'     => ['string', 'null'],
            'readOnly' => true,
            'format'   => 'date-time',
            'name'     => 'UpdatedAt',
        ],
        'deleted_at' => [
            'type'     => ['string', 'null'],
            'readOnly' => true,
            'format'   => 'date-time',
            'name'     => 'DeletedAt',
        ],
        'status'     => [
            'enum' => ['inactive', 'active'],
            'map'  => [
                'inactive' => 0,
                'active'   => 1,
            ],
            'name' => 'Status',
        ],
        'options'    => [
            'type'     => 'array',
            'items'    => [
                'enum' => ['one', 'two', 'three'],
                'map'  => [
                    1 => 'one',
                    2 => 'two',
                    4 => 'three',
                ],
            ],
            'minItems' => 1,
            'maxItems' => 3,
            'name'     => 'Options',
        ],
    ];

    /**
     * Generate schema for tables
     *
     * @param null|string|array $tables Filter tables
     *
     * @return array
     */
    public function generate($tables = null): array;
}
