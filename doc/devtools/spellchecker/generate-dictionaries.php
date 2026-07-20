#!/usr/bin/env php
<?php

declare(strict_types=1);

$root = realpath(__DIR__ . '/../../..');

if ($root === false) {
    fwrite(STDERR, "Unable to locate Tiki root.\n");
    exit(1);
}

require_once $root . '/vendor_bundled/vendor/autoload.php';

require_once __DIR__ . '/src/Config.php';
require_once __DIR__ . '/src/GetChangedFiles.php';
require_once __DIR__ . '/src/Dictionary.php';
require_once __DIR__ . '/src/PhpParser.php';
require_once __DIR__ . '/src/JsParser.php';
require_once __DIR__ . '/src/VueParser.php';
require_once __DIR__ . '/src/DictionaryGenerator.php';

use TikiDevTools\Spellchecker\ChangedFiles;
use TikiDevTools\Spellchecker\Config;
use TikiDevTools\Spellchecker\DictionaryGenerator;

$mode = 'changed';
$base = 'origin/master';

$args = array_slice($argv, 1);

for ($i = 0; $i < count($args); $i++) {
    switch ($args[$i]) {
        case '--changed':
            $mode = 'changed';
            break;

        case '--mr':
            $mode = 'mr';
            break;

        case '--base':
            if (! isset($args[$i + 1])) {
                fwrite(STDERR, "--base requires a value.\n");
                exit(1);
            }

            $base = $args[++$i];
            break;

        case '--help':
        case '-h':
            echo <<<TXT
                Usage:

                php generate-dictionaries.php

                Options:

                    --changed         Generate dictionaries for files changed from HEAD.
                    --mr              Generate dictionaries for files changed from <base>...HEAD.
                    --base <branch>   Base branch for --mr (default: origin/master).
                    --help            Show this help.

                Examples:

                php generate-dictionaries.php                                Run for changed files (default).
                php generate-dictionaries.php --changed                      Run for changed files.
                php generate-dictionaries.php --all                          Run for all files.
                php generate-dictionaries.php --mr                           Run for merge request files.
                php generate-dictionaries.php --mr --base upstream/master    Run for merge request files against upstream/master.
                php generate-dictionaries.php --mr --base origin/main        Run for merge request files against origin/main.

                Note: The base branch should be set correctly.

            TXT;
            exit(0);
    }
}

$config = new Config($root . '/cspell.json');

$generator = new DictionaryGenerator($root, $config);

$generator->generate($mode, $base);
// echo PHP_EOL;
echo "Finished generating dictionaries." . PHP_EOL;

switch ($mode) {
    case 'changed':
        $files = ChangedFiles::changed($root);
        break;

    case 'mr':
        $files = ChangedFiles::mergeRequest($root, $base);
        break;

    case 'all':
    default:
        passthru(
            'npx cspell --config ' . escapeshellarg($root . '/cspell.json') . ' "**/*"',
            $exit
        );
        exit($exit);
}

if (! $files) {
    echo "No changed files.\n";
    exit(0);
}

$command = 'npx cspell --config ' .
    escapeshellarg($root . '/cspell.json');

foreach ($files as $file) {
    $command .= ' ' . escapeshellarg($file);
}

passthru($command, $exit);
exit($exit);
echo PHP_EOL;
