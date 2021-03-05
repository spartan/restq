<?php

namespace Spartan\Rest\Definition;

/**
 * ResourceInterface
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
interface ResourceInterface
{
    const VERSION_2019_09 = 'https://json-schema.org/draft/2019-09/schema';
    const VERSION_CURRENT = self::VERSION_2019_09;

    const SCOPE_SEARCH = 'search';
    const SCOPE_CREATE = 'create';
    const SCOPE_UPDATE = 'update';
    const SCOPE_DELETE = 'delete';

    const REQUEST  = 'request';
    const RESPONSE = 'response';

    const TYPE_NULL    = 'null';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_NUMBER  = 'number';
    const TYPE_STRING  = 'string';
    const TYPE_ARRAY   = 'array';
    const TYPE_OBJECT  = 'object';

    const CRITERIA_EQUAL           = '=';
    const CRITERIA_NOT_EQUAL       = '!=';
    const CRITERIA_NOT_EQUAL_ALT   = '<>';
    const CRITERIA_GREATER         = '>';
    const CRITERIA_GREATER_EQUAL   = '>=';
    const CRITERIA_LESS            = '<';
    const CRITERIA_LESS_EQUAL      = '<=';
    const CRITERIA_BINARY_AND      = '&';
    const CRITERIA_BINARY_OR       = '|';
    const CRITERIA_IN              = '[]';
    const CRITERIA_NOT_IN          = '![]';
    const CRITERIA_LIKE            = '*';
    const CRITERIA_NOT_LIKE        = '!*';
    const CRITERIA_NULL            = ' ';
    const CRITERIA_NOT_NULL        = '! ';
    const CRITERIA_STARTS_WITH     = '^';
    const CRITERIA_NOT_STARTS_WITH = '!^';
    const CRITERIA_ENDS_WITH       = '$';
    const CRITERIA_NOT_ENDS_WITH   = '!$';
    const CRITERIA_BETWEEN         = '()';

    public function search(array $input): array;

    public function create(array $input);

    public function update(array $input);

    public function delete(array $input);
}
