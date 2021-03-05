<?php

require_once __DIR__ . '/../vendor/autoload.php';

$envs = explode("\n", trim(file_get_contents(__DIR__ . '/.env')));

foreach ($envs as $env) {
    putenv($env);
}
