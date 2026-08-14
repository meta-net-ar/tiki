<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.


function getTikiTargetBranch(): string
{
    require_once __DIR__ . '/../../lib/setup/twversion.class.php';

    $twVersion = new TWVersion();

    if ($twVersion->branch === 'trunk') {
        return 'master';
    }

    if ($twVersion->branch === 'stable') {
        return preg_replace('/^(\d+)\..*$/', '$1.x', $twVersion->version);
    }

    return 'master';
}

/**
 * Resolves the base commit hash or reference to diff against.
 *
 * Prioritizes upstream '@{u}' if set. Falls back to finding the
 * merge-base of the local target branch.
 *
 * @return string Upstream tag, a SHA-1 commit hash, or empty string if branch is missing.
 */
function getBaseCommitOrAbort(): string
{
    exec('git rev-parse --abbrev-ref --symbolic-full-name @{u} 2>NUL', $upstreamOutput, $returnCode);

    if ($returnCode === 0) {
        return '@{u}';
    }
    $targetBranch = getTikiTargetBranch();
    // Verify if the branch actually exists in the local repository
    exec('git show-ref --verify --quiet ' . escapeshellarg("refs/heads/$targetBranch"), $output, $existCode);
    if ($existCode !== 0) {
        return '';
    }
    return trim((string) shell_exec(
        'git merge-base ' . escapeshellarg($targetBranch) . ' HEAD 2>NUL'
    ));
}
