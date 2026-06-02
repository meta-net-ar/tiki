<?php

/**
 * Automatically detects the central TikiWiki remote, performs a safety rebase,
 * handles conflicts, and returns the target base commit hash/name for diffing.
 *
 * @return string The base commit to diff against (e.g., 'origin/master')
 */
function getBaseCommitOrAbort(bool $skipRebase = false): string
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
    if (! $skipRebase) {
        echo "🔄 Target tracking remote identified as: '$targetRemote'" . PHP_EOL;
        echo "🔄 Attempting to fetch and rebase master..." . PHP_EOL;
        exec("git pull --rebase $targetRemote master 2>&1", $rebaseOutput, $rebaseCode);
        if ($rebaseCode !== 0) {
            echo PHP_EOL . "❌ Automatic rebase failed." . PHP_EOL;
            echo "The branch could not be rebased onto '$targetRemote/master'." . PHP_EOL;
            echo PHP_EOL;
            echo "Common causes include:" . PHP_EOL;
            echo "  - Merge conflicts" . PHP_EOL;
            echo "  - Uncommitted local changes" . PHP_EOL;
            echo "  - Authentication or network issues" . PHP_EOL;
            echo "  - Remote repository access problems" . PHP_EOL;
            echo PHP_EOL;
            echo "If you do not want to rebase at this time, you can rerun the checks with:" . PHP_EOL;
            echo 'TIKI_LOCAL_CHECKS_ARGS="--skip-rebase" git push' . PHP_EOL;
            echo PHP_EOL;
            echo "Otherwise, resolve the issue above and try again." . PHP_EOL;

            exit(1);
        }
        echo "✅ Successfully rebased with $targetRemote/master. Proceeding with checks..." . PHP_EOL . PHP_EOL;
    } else {
        echo "⏭️ Skipping automatic rebase." . PHP_EOL;
        exec("git fetch $targetRemote master");
    }

    // Robust Merge-Base Resolution to get the clean fork point
    exec("git merge-base $targetRemote/master HEAD 2>&1", $mergeBaseOut, $mergeBaseCode);

    return ($mergeBaseCode === 0 && ! empty($mergeBaseOut[0])) ? $mergeBaseOut[0] : "$targetRemote/master";
}
