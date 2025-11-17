<?php

/**
 * Automatically detects the central TikiWiki remote, performs a safety rebase,
 * handles conflicts, and returns the target base commit hash/name for diffing.
 *
 * @return string The base commit to diff against (e.g., 'origin/master')
 */
function getBaseCommitOrAbort(): string
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

    echo "🔄 Target tracking remote identified as: '$targetRemote'" . PHP_EOL;
    echo "🔄 Attempting to fetch and rebase master..." . PHP_EOL;

    exec("git pull --rebase $targetRemote master 2>&1", $rebaseOutput, $rebaseCode);

    if ($rebaseCode !== 0) {
        echo PHP_EOL . "❌ REBASE CONFLICT DETECTED!" . PHP_EOL;
        echo "Your local branch has conflicts with the '$targetRemote' repository." . PHP_EOL;
        echo "Please resolve the conflicts manually before pushing." . PHP_EOL;

        // Abort the git push immediately
        exit(1);
    }

    echo "✅ Successfully rebased with $targetRemote/master. Proceeding with checks..." . PHP_EOL . PHP_EOL;

    // Robust Merge-Base Resolution to get the clean fork point
    exec("git merge-base $targetRemote/master HEAD 2>&1", $mergeBaseOut, $mergeBaseCode);

    return ($mergeBaseCode === 0 && ! empty($mergeBaseOut[0])) ? $mergeBaseOut[0] : "$targetRemote/master";
}
