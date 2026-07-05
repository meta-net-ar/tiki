<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Print the uncovered executable lines of one source file from a Clover XML
 * coverage report, grouped into ranges.
 *
 * Made for the tracker field characterization coverage audit (see the
 * tiki-tracker-field-coverage skill): after running the per-type test with
 * coverage, every line range this tool prints must be explained by a
 * "Not covered:" waiver in the test docblock — an uncovered, unwaived branch
 * means the coverage contract is not complete. Nothing in the tool is
 * tracker-specific; it works on any Clover report.
 *
 * Usage:
 *   php -d pcov.directory=lib/core/Tracker/Field lib/test/phpunit.php \
 *       --filter 'Characterization.*FooTest' --coverage-clover /tmp/cov.xml \
 *       --coverage-filter lib/core/Tracker/Field
 *   php lib/test/tools/uncovered-lines.php /tmp/cov.xml lib/core/Tracker/Field/Foo.php
 *
 * --coverage-filter matters: without it, Clover report generation computes
 * 0%-covered entries for every file under phpunit.xml's <source> (all of
 * ./lib), reading each one — slow, and on constrained filesystems it dies
 * on fd exhaustion inside php-code-coverage.
 *
 * The second argument is matched as a substring of the file paths in the
 * report, so a bare class filename is enough. Requires a coverage driver
 * (pcov: `apt-get install php8.4-pcov`, or Xdebug in coverage mode).
 */

if (PHP_SAPI !== 'cli') {
    die('Command line only');
}

$cloverPath = $argv[1] ?? null;
$needle = $argv[2] ?? null;
if (! $cloverPath || ! $needle) {
    fwrite(STDERR, "Usage: php lib/test/tools/uncovered-lines.php <clover.xml> <source-file-substring>\n");
    exit(2);
}

$xml = @simplexml_load_file($cloverPath);
if (! $xml) {
    fwrite(STDERR, "Cannot parse Clover report: $cloverPath\n");
    exit(2);
}

$found = false;
foreach ($xml->xpath('//file') as $file) {
    $name = (string) $file['name'];
    if (strpos($name, $needle) === false) {
        continue;
    }
    $found = true;

    $uncovered = [];
    foreach ($file->line as $line) {
        if ((string) $line['type'] === 'stmt' && (int) $line['count'] === 0) {
            $uncovered[] = (int) $line['num'];
        }
    }

    echo $name, "\n";
    if (! $uncovered) {
        echo "  all executable lines covered\n";
        continue;
    }

    sort($uncovered);
    $ranges = [];
    $start = $prev = array_shift($uncovered);
    foreach ($uncovered as $num) {
        if ($num === $prev + 1) {
            $prev = $num;
            continue;
        }
        $ranges[] = $start === $prev ? (string) $start : "$start-$prev";
        $start = $prev = $num;
    }
    $ranges[] = $start === $prev ? (string) $start : "$start-$prev";

    echo '  uncovered lines: ', implode(', ', $ranges), "\n";
}

if (! $found) {
    fwrite(STDERR, "No file matching '$needle' in $cloverPath\n");
    exit(1);
}
