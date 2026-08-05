#!/usr/bin/env php
<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Fills in section 1 of the Backport merge request template from git history.
 * Run with --help for usage. See https://dev.tiki.org/Backport-Policy
 */

if (PHP_SAPI !== 'cli') {
    die('Only available through command-line.');
}

require_once __DIR__ . '/../vcscommons.php';

const TEMPLATE_FILE = __DIR__ . '/../../../.gitlab/merge_request_templates/Backport.md';

/**
 * Runs git with escaped arguments, returning its output lines.
 */
function git(array $args, &$status = null)
{
    $command = 'git';
    foreach ($args as $argument) {
        $command .= ' ' . escapeshellarg($argument);
    }

    $output = [];
    exec($command . ' 2>/dev/null', $output, $status);

    return $output;
}

/**
 * The target branch may live under any remote, or only locally.
 */
function resolveRef(string $branch)
{
    $status = null;

    foreach (['upstream/' . $branch, 'origin/' . $branch, $branch] as $candidate) {
        git(['rev-parse', '--verify', '--quiet', $candidate], $status);
        if ($status === 0) {
            return $candidate;
        }
    }

    error('Cannot resolve branch "' . $branch . '". Fetch it first, ex: git fetch upstream ' . $branch);
}

function joinUnique(array $values)
{
    return implode(' ', array_unique($values));
}

/**
 * Where the branch should be pushed, not where it came from: `git checkout -b bp
 * upstream/27.x` leaves it tracking upstream, which is not where a backport branch
 * belongs. So trust the tracking ref only when it names this same branch, meaning
 * it really was pushed there.
 */
function pushRemote(string $branch)
{
    $status = null;

    $configured = git(['config', '--get', 'remote.pushDefault'], $status);
    if ($status === 0 && ! empty($configured)) {
        return trim($configured[0]);
    }

    $tracking = git(['rev-parse', '--abbrev-ref', '--symbolic-full-name', '@{upstream}'], $status);
    if ($status === 0 && ! empty($tracking) && str_contains($tracking[0], '/')) {
        [$remote, $trackedBranch] = explode('/', trim($tracking[0]), 2);
        if ($trackedBranch === $branch) {
            return $remote;
        }
    }

    return 'origin';
}

/**
 * Offline check against the remote-tracking ref, so a branch pushed from elsewhere
 * looks unpushed until fetched.
 */
function isPushed(string $remote, string $branch)
{
    $status = null;
    $remoteHead = git(['rev-parse', '--verify', '--quiet', $remote . '/' . $branch], $status);

    if ($status !== 0 || empty($remoteHead)) {
        return false;
    }

    return trim($remoteHead[0]) === trim(implode('', git(['rev-parse', 'HEAD'])));
}

function pushBranch(string $remote, string $branch)
{
    $status = null;

    info('Pushing ' . $branch . ' to ' . $remote . '...');
    passthru('git push -u ' . escapeshellarg($remote) . ' ' . escapeshellarg($branch), $status);

    if ($status !== 0) {
        error('Push failed, so the link would not work. Nothing else done.');
    }
}

/**
 * Browsable https URL of the project behind a remote.
 */
function remoteWebUrl(string $remote)
{
    $status = null;
    $output = git(['remote', 'get-url', $remote], $status);

    if ($status !== 0 || empty($output)) {
        error('Cannot read the URL of remote "' . $remote . '".');
    }

    $url = preg_replace('/\.git$/', '', trim($output[0]));

    // git@host:path
    if (preg_match('#^[^@]+@([^:]+):(.+)$#', $url, $matches)) {
        return 'https://' . $matches[1] . '/' . $matches[2];
    }

    // https://[credentials@]host/path, which must not be printed back
    if (preg_match('#^(https?)://(?:[^/@]+@)?(.+)$#', $url, $matches)) {
        return $matches[1] . '://' . $matches[2];
    }

    error('Cannot turn the URL of remote "' . $remote . '" into a project address: ' . $url);
}

/**
 * The template with section 1's bullets replaced.
 */
function fillTemplate(array $values)
{
    if (! is_readable(TEMPLATE_FILE)) {
        error('Cannot read the Backport template at ' . TEMPLATE_FILE);
    }

    $labels = implode('|', array_map('preg_quote', array_keys($values)));

    return preg_replace_callback(
        '/^- \*\*(' . $labels . ')\*\*:.*$/m',
        function ($matches) use ($values) {
            return '- **' . $matches[1] . '**: ' . $values[$matches[1]];
        },
        file_get_contents(TEMPLATE_FILE)
    );
}

$arguments = array_slice($_SERVER['argv'], 1);

if (in_array('-h', $arguments, true) || in_array('--help', $arguments, true)) {
    echo <<<HELP
Fills in section 1 of the Backport merge request template from git history.

Usage:
  php doc/devtools/gitlab/backport_mr.php TARGET [options]

  TARGET             Release branch this is backported to, ex: 27.x

Options:
  --push             Push the branch to its remote first, then print the link
  --print            Print only the four bullets, to paste into an open request
  -h, --help         Show this help message

Examples:
  php doc/devtools/gitlab/backport_mr.php 27.x
  php doc/devtools/gitlab/backport_mr.php 27.x --push
  php doc/devtools/gitlab/backport_mr.php 27.x --print

Notes:
  Run it from the backport branch, after cherry-picking. With no option it prints a
  link opening the New merge request page with the whole template already filled in,
  so the branch has to be pushed for that link to work.

  Values come from the "See merge request !NNNN" footers and the "(cherry picked from
  commit ...)" trailers that `git cherry-pick -x` writes. Cherry-pick without -x and
  the hashes cannot be recovered. "From branch" needs the release branches fetched,
  ex: git fetch upstream.

  See https://dev.tiki.org/Backport-Policy

HELP;
    exit(0);
}

$bulletsOnly = in_array('--print', $arguments, true);
$doPush = in_array('--push', $arguments, true);
$positional = array_values(array_diff($arguments, ['--print', '--push']));
$target = $positional[0] ?? '';

if ($target === '') {
    error('usage: php doc/devtools/gitlab/backport_mr.php TARGET [--push|--print], see --help');
}

$ref = resolveRef($target);

$mergeRequests = [];
$hashes = [];

// A commit that travelled master -> 30.x -> 29.x carries one footer and one trailer per
// hop, oldest first, so the last of each is the hop it was picked from.
foreach (git(['rev-list', '--reverse', $ref . '..HEAD']) as $commit) {
    $body = implode("\n", git(['log', '-1', '--format=%B', $commit]));

    if (preg_match_all('/See merge request [^!]*!(\d+)/', $body, $matches)) {
        $mergeRequests[] = '!' . end($matches[1]);
    }

    if (preg_match_all('/cherry picked from commit ([0-9a-f]{7,})/', $body, $matches)) {
        $hashes[] = substr(end($matches[1]), 0, 12);
    }
}

// Lowest release branch holding the last picked commit is the branch it came from.
$from = '';

if (! empty($hashes)) {
    $releaseBranches = [];

    foreach (git(['branch', '-r', '--contains', end($hashes), '--format=%(refname:short)']) as $branch) {
        $name = basename($branch);
        if (preg_match('/^\d+\.x$/', $name)) {
            $releaseBranches[] = $name;
        }
    }

    if (! empty($releaseBranches)) {
        usort($releaseBranches, function ($a, $b) {
            return (int)$a <=> (int)$b;
        });
        $from = reset($releaseBranches);
    }
}

$values = [
    'Backport of' => empty($mergeRequests) ? '_not found, fill in by hand_' : joinUnique($mergeRequests),
    'Cherry-picked from' => empty($hashes) ? '_no cherry-pick -x trailers, fill in by hand_' : joinUnique($hashes),
    'From branch' => $from === '' ? '_not found, fill in by hand_' : $from,
    'Target branch' => $target,
];

if ($bulletsOnly) {
    foreach ($values as $label => $value) {
        echo '- **' . $label . '**: ' . $value . PHP_EOL;
    }

    exit(0);
}

$source = trim(implode('', git(['rev-parse', '--abbrev-ref', 'HEAD'])));

if ($source === 'HEAD' || $source === '') {
    error('Detached HEAD, check out the backport branch first.');
}

$remote = pushRemote($source);

if ($doPush) {
    pushBranch($remote, $source);
} elseif (! isPushed($remote, $source)) {
    echo color('Branch ' . $source . ' is not on ' . $remote . ' yet, so the link would not work.', 'yellow') . PHP_EOL;
    echo PHP_EOL;
    echo '    git push -u ' . $remote . ' ' . $source . PHP_EOL;
    echo PHP_EOL;
    echo 'Then run this again, or pass --push to do both at once.' . PHP_EOL;
    exit(1);
}

$query = http_build_query([
    'merge_request[source_branch]' => $source,
    'merge_request[target_branch]' => $target,
    'merge_request[description]' => fillTemplate($values),
]);

$project = remoteWebUrl($remote);

info('Open this to create the merge request, description already filled in:');
echo $project . '/-/merge_requests/new?' . $query . PHP_EOL;

exit(0);
