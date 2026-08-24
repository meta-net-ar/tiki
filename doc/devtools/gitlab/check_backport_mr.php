#!/usr/bin/env php
<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Fails when a merge request targeting a release branch has an unfilled Backport template.
 *
 * Runs from gitlab-ci on merge request pipelines, reading the predefined
 * CI_MERGE_REQUEST_DESCRIPTION, so it also works for merge requests from forks.
 *
 * See https://dev.tiki.org/Backport-Policy
 */

if (PHP_SAPI !== 'cli') {
    die('Only available through command-line.');
}

require_once __DIR__ . '/../vcscommons.php';

const POLICY_URL = 'https://dev.tiki.org/Backport-Policy';

$description = getenv('CI_MERGE_REQUEST_DESCRIPTION') ?: '';
$target = getenv('CI_MERGE_REQUEST_TARGET_BRANCH_NAME') ?: '';

if ($target === '') {
    info('Not a merge request pipeline, nothing to check.');
    exit(0);
}

if (! preg_match('/^\d+\.x$/', $target)) {
    info('Target branch "' . $target . '" is not a release branch, nothing to check.');
    exit(0);
}

// Only merge requests that actually used the Backport template get the full check.
if (! str_contains($description, 'dev.tiki.org/Backport-Policy')) {
    echo color('This merge request targets release branch "' . $target . '" but does not use the Backport template.', 'red') . PHP_EOL;
    echo 'If this is a backport, pick the "Backport" description template and fill it in.' . PHP_EOL;
    echo 'Run php doc/devtools/gitlab/backport_mr.php ' . $target . ' to fill section 1 automatically.' . PHP_EOL;
    exit(1);
}

info('Checking the Backport template for a merge request into ' . $target);

$problems = [];

// Section 1: the placeholders must have been replaced.
$placeholders = [
    'Backport of' => ['_!MR-number_', '_#MR-number_'],
    'Cherry-picked from' => ['_commit-hash(es)_', '_#commit-hash(es)_'],
    'From branch' => ['_normally the one just above the target_'],
    'Target branch' => ['_ex: 27.x_'],
];

foreach ($placeholders as $label => $variants) {
    foreach ($variants as $placeholder) {
        if (str_contains($description, $placeholder)) {
            $problems[] = 'section 1: "' . $label . '" still holds its placeholder';
            break;
        }
    }
}

if (str_contains($description, 'fill in by hand')) {
    $problems[] = 'section 1: backport_mr.php could not resolve a field, it still says "fill in by hand"';
}

// Section 3: at least one reason must be ticked.
if (! preg_match('/^- \[[xX]\] (Security|Fatal|Regression|Compatibility|Needed|Blocks)/m', $description)) {
    $problems[] = 'section 3: tick at least one reason why this must be backported';
}

// Section 5: the release note is mandatory and must say more than the hint.
$releaseNote = '';

if (preg_match('/^## 5\.[^\n]*\n(.*?)^## 6\./ms', $description, $matches)) {
    $releaseNote = preg_replace('/^_Operators run old releases.*$/m', '', $matches[1]);
    $releaseNote = preg_replace('/\s+/', '', $releaseNote);
}

if ($releaseNote === '') {
    $problems[] = 'section 5: the release note entry is mandatory for a backport';
}

if (empty($problems)) {
    important('Backport template looks complete.');
    exit(0);
}

echo PHP_EOL;

foreach ($problems as $problem) {
    echo '  - ' . $problem . PHP_EOL;
}

echo PHP_EOL;
echo color(count($problems) . ' problem(s) found. See ' . POLICY_URL, 'red') . PHP_EOL;

exit(1);
