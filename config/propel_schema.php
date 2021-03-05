<?php

require_once __DIR__ . '/bootstrap.php';

$schema = new \Spartan\Db\Adapter\Propel\Schema(__DIR__ . '/generated-reversed-database/schema.xml');

$schema->setDatabaseAttribute('identifierQuoting', 'true');
$schema->setDatabaseAttribute('namespace', 'Spartan\\Rest\\Domain\\Model');

$schema->getSchema()->saveXML(__DIR__ . '/schema.xml');
