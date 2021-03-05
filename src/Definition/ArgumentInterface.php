<?php

namespace Spartan\Rest\Definition;

/**
 * ArgumentInterface
 *
 * @package Spartan\Rest
 * @author  Iulian N. <iulian@spartanphp.com>
 * @license LICENSE MIT
 */
interface ArgumentInterface
{
    public function __invoke($value, ResourceInterface $resource);
}
