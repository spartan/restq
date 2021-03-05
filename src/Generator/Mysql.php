<?php

namespace Spartan\Rest\Generator;

use Spartan\Rest\Definition\GeneratorInterface;
use Spartan\Rest\Definition\ResourceInterface;

/**
 * Mysql Generator
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
class Mysql implements GeneratorInterface
{
    protected ?\PDO $con;

    protected $options = [
        'schema'    => 'https://json-schema.org/draft/2019-09/schema',
        'domain'    => 'https://example.com',
        'tables'    => [],
        'namespace' => '',
        'propel2'   => true,
    ];

    public function __construct(\PDO $con, array $options = [])
    {
        $this->con     = $con;
        $this->options = $options + $this->options;
    }

    /**
     * @param null $tables
     *
     * @return array
     */
    public function generate($tables = null): array
    {
        $tables = $this->con->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);

        $dbSchema  = [];
        $relations = []; // one-to-many
        foreach ($tables as $table) {
            if ($this->options['tables'] && !in_array($table, (array)$this->options['tables'])) {
                continue;
            }

            $tableSchema    = [
                '$schema'              => $this->options['schema'],
                '$id'                  => $this->options['domain'] . '/' . $table,
                'type'                 => 'object',
                'properties'           => [],
                'required'             => [],
                'additionalProperties' => false,
                'resource'             => [
                    'criteria'   => ['='],
                    'arguments'  => ['paginate', 'limit'],
                    'directives' => [],
                    'index'      => [],
                    'unique'     => [],
                    'introspect' => true,
                    'indexOnly'  => false,
                    'immutable'  => false,
                ],
            ];
            $tableRelations = [];

            $columns = $this->con->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(\PDO::FETCH_ASSOC);

            $createTable = $this->con->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_ASSOC)['Create Table'];
            $lines       = explode("\n", $createTable);
            foreach ($lines as $line) {
                if (substr(trim($line), 0, 10) == 'CONSTRAINT') {
                    $segments                     = explode('`', $line);
                    $tableRelations[$segments[3]] = $segments[5]; // colName => tableName
                    $relations[]                  = [$segments[5] /* parent */, $table /* child */];
                }
            }

            foreach ($columns as $column) {
                $columnSchema  = [];
                $columnName    = $column['Field'];
                $columnPhpName = self::camelCase($columnName);

                if (isset(self::COLUMN_MAPPINGS[$columnName])) {
                    $columnSchema = self::COLUMN_MAPPINGS[$columnName];
                } elseif (isset($tableRelations[$columnName])) {
                    // relation
                    $columnSchema['$ref'] = $this->options['domain'] . '/' . $tableRelations[$columnName];
                    if (substr($columnName, -3) == '_id') {
                        $relationName             = substr($columnName, 0, -3);
                        $relationNamePropel       = self::camelCase($relationName);
                        $columnName               = $relationName;
                        $columnSchema['resource'] = $this->options['namespace'] . '\\' . self::camelCase($relationName);
                        $columnSchema['propel']   = $relationNamePropel;
                        $columnSchema['column']   = $column['Field'];
                    }
                    $this->checkIndex($column, $columnSchema, $tableSchema);
                } else {
                    $this->checkType($column, $columnSchema, $tableSchema);
                    $this->checkRange($column, $columnSchema, $tableSchema);
                    $this->checkAutoIncrement($column, $columnSchema, $tableSchema);
                    $this->checkNull($column, $columnSchema, $tableSchema);
                    $this->checkDates($column, $columnSchema, $tableSchema);
                    $this->checkIndex($column, $columnSchema, $tableSchema);
                    $columnSchema['name'] = $columnPhpName;
                }

                if (strtolower($column['Null']) == 'no' && !isset($columnSchema['readOnly'])) {
                    $tableSchema['required'][] = $tableRelations[$columnName] ?? $columnName;
                }

                $tableSchema['properties'][$columnName] = $columnSchema;
            }

            $dbSchema[$table] = $tableSchema;
        }

        foreach ($relations as $data) {
            [$tableParent, $tableChild] = $data;

            if ($this->options['tables'] && !in_array($tableParent, (array)$this->options['tables'])) {
                continue;
            }

            $tableChildPlural = (new \Symfony\Component\String\Inflector\EnglishInflector)->pluralize($tableChild)[0];

            $dbSchema[$tableParent]['properties'][$tableChildPlural] = [
                'type'     => 'array',
                'items'    => [
                    '$ref' => $this->options['domain'] . '/' . $tableChild,
                ],
                'propel'   => self::camelCase($tableChild),
                'resource' => $this->options['namespace'] . '\\' . self::camelCase($tableChild),
                'column'   => $tableChildPlural,
            ];
        }

        return $dbSchema;
    }

    public function checkType($column, &$schema, array &$tableSchema)
    {
        /*
            array (
              'Field' => 'condition',
              'Type' => 'json',
              'Null' => 'NO',
              'Key' => '',
              'Default' => NULL,
              'Extra' => '',
            ),
         */

        $columnType = explode('(', $column['Type'])[0];

        $mappings = [
            'tinyint'   => ResourceInterface::TYPE_INTEGER,
            'smallint'  => ResourceInterface::TYPE_INTEGER,
            'mediumint' => ResourceInterface::TYPE_INTEGER,
            'int'       => ResourceInterface::TYPE_INTEGER,
            'bigint'    => ResourceInterface::TYPE_INTEGER,
            'float'     => ResourceInterface::TYPE_NUMBER,
            'double'    => ResourceInterface::TYPE_NUMBER,
            'decimal'   => ResourceInterface::TYPE_NUMBER,
            'json'      => ResourceInterface::TYPE_OBJECT,
        ];

        $schema['type'] = isset($mappings[$columnType])
            ? $mappings[$columnType]
            : ResourceInterface::TYPE_STRING;
    }

    public function checkNull(array $column, array &$schema, array &$tableSchema)
    {
        if (strtolower($column['Null']) != 'no') {
            $schema['type']   = (array)$schema['type'];
            $schema['type'][] = 'null';

            if ($schema['type'] == ResourceInterface::TYPE_STRING) {
                $schema['minLength'] = 1;
            }
        }
    }

    public function checkAutoIncrement(array $column, array &$columnSchema, array &$tableSchema)
    {
        if (strtolower($column['Extra']) == 'auto_increment') {
            $columnSchema = [
                'type'     => ResourceInterface::TYPE_INTEGER,
                'readOnly' => true,
                'minimum'  => 1,
            ];
        }
    }

    public function checkRange(array $column, array &$columnSchema, array &$tableSchema)
    {
        if (strpos($column['Type'], 'unsigned')) {
            $columnSchema['minimum'] = 0;
        }

        if ($columnSchema['type'] == ResourceInterface::TYPE_STRING) {
            $maxLength = preg_replace('/[^0-9]/', '', $column['Type']);
            if ($maxLength) {
                $columnSchema['maxLength'] = (int)$maxLength;
            }

            if (substr($column['Type'], 0, 4) == 'char') {
                $columnSchema['minLength'] = (int)$maxLength;
            }
        }
    }

    public function checkIndex(array $column, array &$columnSchema, array &$tableSchema)
    {
        if ($column['Key']) {
            $tableSchema['resource']['index']   = $tableSchema['resource']['index'] ?? [];
            $tableSchema['resource']['index'][] = $column['Field'];
        }
    }

    public function checkDates(array $column, array &$columnSchema, array &$tableSchema)
    {
        $field = strtolower($column['Type']);

        switch ($field) {
            case 'datetime':
                $columnSchema['format'] = 'date-time';
                break;
            case 'timestamp':
                $columnSchema['format'] = 'date-time';
                break;
            case 'date':
                $columnSchema['format'] = 'date';
                break;
            case 'time':
                $columnSchema['format'] = 'time';
                break;
        }
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function camelCase(string $name): string
    {
        return preg_replace_callback(
            '/(_[a-z]{1})/',
            function ($value) {
                return strtoupper($value[0][1]);
            },
            ucfirst($name)
        );
    }
}
