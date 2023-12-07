<?php

const MINIMUMVERSION = '4.22.2';

copy(__DIR__ . '/composer.json', __DIR__ . '/composer.json.original');

$content = json_decode(file_get_contents(__DIR__ . '/composer.json'), true);
$content['require']['laravel/nova'] = MINIMUMVERSION;

file_put_contents(__DIR__ . '/composer.json', json_encode($content, JSON_PRETTY_PRINT));

shell_exec('composer install');

copy(__DIR__ . '/composer.json.original', __DIR__ . '/composer.json');

unlink(__DIR__ . '/composer.json.original');