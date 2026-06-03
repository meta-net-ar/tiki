<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.


function getTikiTargetRemote(): string
{
    exec('git remote -v', $remoteLines);

    $targetRemote = 'origin';

    foreach ($remoteLines as $line) {
        // Dynamically find the remote containing 'tikiwiki'
        if (str_contains(strtolower($line), 'tikiwiki') && preg_match('/^([^\s]+)/', $line, $matches)) {
            $remoteName = $matches[1];

            // Prioritize non-origin names (like upstream/parent) if it's a fork environment
            if ($remoteName !== 'origin') {
                $targetRemote = $remoteName;
                break;
            }
        }
    }
    return $targetRemote;
}

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

function hasLocalChanges(): bool
{
    exec('git status --porcelain', $statusOutput);

    return ! empty($statusOutput);
}
/**
 * Automatically detects the central TikiWiki remote, performs a safety rebase,
 * handles conflicts, and returns the target base commit hash/name for diffing.
 *
 * @return string The base commit to diff against (e.g., 'origin/master')
 */
function getBaseCommitOrAbort(bool $skipRebase = false): string
{

    $targetRemote = getTikiTargetRemote();
    $targetBranch = getTikiTargetBranch();

    echo "🔄 Target tracking remote identified as: '$targetRemote/$targetBranch'" . PHP_EOL;

    if ($skipRebase) {
        echo "⏭️ Skipping automatic rebase." . PHP_EOL;
        exec("git fetch $targetRemote $targetBranch");
    } elseif (hasLocalChanges()) {
        echo PHP_EOL . "⚠️ Pending changes detected. Skipped Automatic rebase." . PHP_EOL;
        exec("git fetch $targetRemote $targetBranch");
    } else {
        echo "🔄 Attempting to fetch and rebase $targetBranch..." . PHP_EOL;

        exec("git pull --rebase $targetRemote $targetBranch 2>&1", $rebaseOutput, $rebaseCode);

        if ($rebaseCode !== 0) {
            echo PHP_EOL . "❌ Automatic rebase failed." . PHP_EOL;
            echo "The branch could not be rebased onto '$targetRemote/$targetBranch'." . PHP_EOL;
            echo "Resolve the issue and try again." . PHP_EOL;
            echo 'To continue without rebasing: TIKI_LOCAL_CHECKS_ARGS="--skip-rebase" git push' . PHP_EOL;
            echo PHP_EOL;

            exit(1);
        }

        echo "✅ Successfully rebased with $targetRemote/$targetBranch. Proceeding with checks..." . PHP_EOL . PHP_EOL;
    }
    // Robust Merge-Base Resolution to get the clean fork point
    exec("git merge-base $targetRemote/$targetBranch HEAD 2>&1", $mergeBaseOut, $mergeBaseCode);

    return ($mergeBaseCode === 0 && ! empty($mergeBaseOut[0])) ? $mergeBaseOut[0] : "$targetRemote/$targetBranch";
}
