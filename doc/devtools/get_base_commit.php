<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Automatically detects the central TikiWiki remote, performs a safety rebase,
 * handles conflicts, and returns the target base commit hash/name for diffing.
 *
 * @return string The base commit to diff against (e.g., 'origin/master')
 */
function getBaseCommitOrAbort(): string
{

    // 1. Check if the current branch even has an upstream configured yet
    exec("git rev-parse --abbrev-ref --symbolic-full-name @{u} 2>/dev/null", $output, $returnCode);

    if ($returnCode !== 0) {
        /**
         * This is a brand-new branch with no upstream on the server yet.
         * Since NOTHING has been pushed, EVERY single commit on this branch is unpushed.
         * We track back to where the branch dynamically split from your local tracking.
         */
        $baseCommit = trim(shell_exec("git merge-base @{u} HEAD 2>/dev/null"));
    } else {
        /**
         * The branch already exists on the remote.
         * We only care about the new commits/amendments made since the last push.
         */
        $baseCommit = '@{u}';
    }
    return $baseCommit;
}
