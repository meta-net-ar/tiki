#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');

require_once __DIR__ . '/../../../vendor_bundled/vendor/autoload.php';
require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/Dictionary.php';
require_once __DIR__ . '/src/PhpParser.php';
require_once __DIR__ . '/src/JsParser.php';
require_once __DIR__ . '/src/VueParser.php';
require_once __DIR__ . '/src/DictionaryGenerator.php';

if ($root === false) {
    fwrite(STDERR, "Unable to locate Tiki root.\n");
    exit(1);
}

$config = new Config('../../../cspell.json');

$generator = new DictionaryGenerator(
    $root,
    $config
);

$generator->generate();

echo PHP_EOL;
echo "Finished generating dictionaries." . PHP_EOL;