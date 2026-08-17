<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once __DIR__ . '/../ci/vcscommons.php';

define('GIT_MIN_VERSION', 2.0);
define('TIKIVCS', 'https://gitlab.com/tikiwiki/tiki.git');

function getBinName()
{
    return 'git';
}

function getMinVersion()
{
    return GIT_MIN_VERSION;
}

/**
 * @return bool
 */
function check_bin_version()
{
    return version_compare(trim(shell_exec("git --version  2> /dev/null | awk '{print \$3}'")), GIT_MIN_VERSION, '>');
}

/**
 * Indicates whether versioned files have been modified in the specified checkout
 *
 * @param $localPath string Path of the checkout
 * @return bool true if at least 1 versioned file has been modified, added or removed, false otherwise
 */
function has_uncommited_changes($localPath)
{
    $localPath = escapeshellarg($localPath);
    $count = trim(shell_exec("git status -s $localPath | wc -l"));
    return $count > 0;
}

/**
 * Retrieves the parent folders of uncommitted changes in a given local path.
 *
 * @param string $localPath The local path to check for uncommitted changes.
 * @return array An array of parent folders containing uncommitted changes.
 */
function getUncommittedChangesParentFolder($localPath)
{
    $localPath = escapeshellarg($localPath);
    $output = trim(shell_exec("git status -s $localPath | awk '{print \$2}' | xargs -I{} dirname {} | sort | uniq"));

    $parentFolders = array_filter(explode("\n", trim($output)));
    $processedFolders = array_map(function ($folder) {
        $parts = explode('/', $folder);
        // Iterate backwards and find the first non-empty directory
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            if (! empty($parts[$i])) {
                return '/' . $parts[$i];
            }
        }
        // If no non-empty directory found, return the root
        return '/';
    }, $parentFolders);

     // Remove duplicates and empty entries
     $uniqueFolders = array_unique(array_filter($processedFolders));

    return $uniqueFolders;
}

function add($file)
{
    shell_exec("git add $file");
}

function delete_file($file, $message = null)
{
    shell_exec("git rm -f $file");
    if ($message) {
        shell_exec("git commit -m $message $file");
    }
}

/**
 * Get the number of changes in the specified checkout
 *
 * @param string $localPath Path of the checkout
 * @return array               The files that differ (additions, removals and modifications) from the repository
 *
 *
 * @see Similar function has_uncommited_changes()
 */
function files_differ($localPath)
{
    $localPath = escapeshellarg($localPath);
    $ret = trim(shell_exec("git -C $localPath status -s | awk '{print \$2}'"));
    return explode(PHP_EOL, $ret);
}

/**
 * @param $localPath
 * @param bool $ignore_externals
 */
function update_working_copy($localPath, $ignore_externals = false)
{
    $localPath = escapeshellarg($localPath);
    shell_exec("git -C $localPath pull");
}

/**
 * Get current version
 * @param $path
 * @return string
 */
function get_revision($path)
{
    if (str_starts_with($path, "http")) {
        shell_exec("git fetch");
    }
    return trim(shell_exec("git rev-parse HEAD"));
}

/**
 * Get the current branch
 * @return string
 */
function getCurrentBranch()
{
    return trim(shell_exec("git rev-parse --abbrev-ref HEAD"));
}

/**
 * Find the revision number for a particular tag
 *
 * @param $releaseNumber
 * @return int
 */
function get_tag_revision($releaseNumber)
{
    $revision = 0;

    // --stop-on-copy makes it only return the tag commit, not the whole history since time began
    $log = shell_exec("git describe --tags tags/$releaseNumber");
    $log = ! empty($log) ? trim($log) : '';

    if (preg_match('/^r(\d+)/ms', $log, $matches)) {
        $revision = (int)$matches[1];
    }

    return $revision;
}

/**
 * @param $msg
 * @param bool $displaySuccess
 * @param bool $dieOnRemainingChanges
 * @return int
 */
function commit($msg, $displaySuccess = true, $dieOnRemainingChanges = true)
{
    $msg = escapeshellarg($msg);
    shell_exec("git add .");
    shell_exec("git commit -m $msg");

    if ($dieOnRemainingChanges && has_uncommited_changes('.')) {
        error("Commit seems to have failed. Uncommitted changes exist in the working folder.\n");
    } else {
        push();
    }

    return get_revision('.');
}
function commit_specific_lang($lang, $msg, $displaySuccess = true, $dieOnRemainingChanges = true)
{
    $msg = escapeshellarg($msg);
    shell_exec("git add ./lang/$lang");
    shell_exec("git commit -m $msg ./lang/$lang");

    if (has_uncommited_changes("./lang/$lang")) {
        error("Commit seems to have failed. Uncommitted changes exist in the working folder.\n");
    }

    return get_revision("./lang/$lang");
}

/**
 * Checkout a branch in Git.
 *
 * @param string $branch The name of the branch to checkout.
 * @param bool $newBranch Optional. Whether to create a new branch. Default is false.
 * @return bool Returns true if the branch was successfully checked out, false otherwise.
 */
function checkoutBranch($branch, $newBranch = false)
{
    $branch = escapeshellarg($branch);
    if ($newBranch) {
        $output = shell_exec("git checkout -b $branch 2>&1");
    } else {
        $output = shell_exec("git checkout $branch 2>&1");
    }

    if (str_contains($output, 'fatal') || str_contains($output, 'error')) {
        error("Failed to " . ($newBranch ? "create and " : "") . "checkout branch $branch: $output\n");
        return false;
    }

    return true;
}

function push()
{
    shell_exec("git push");
}

function push_create_merge_request($merge_title, $merge_desciption, $target_branch, $current_branch = "master")
{
    $merge_title = escapeshellarg($merge_title);
    $merge_desciption = escapeshellarg($merge_desciption);
    $target_branch = escapeshellarg($target_branch);
    shell_exec("git push -u origin $current_branch -o merge_request.create -o merge_request.target=$target_branch -o merge_request.title=$merge_title -o merge_request.description=$merge_desciption");
}

/**
 * @param $localPath
 * @param $minRevision
 * @param string $maxRevision
 * @return bool
 */
function get_logs_contrib($localPath, $minRevision, $maxRevision = 'HEAD')
{
    $step = 20000;
    if (empty($maxRevision)) {
        return false;
    }
    $logs = shell_exec("git --no-pager log -n $step --pretty=format:\"%H;%x09;%an;%x09;%ad;%x09;%s\" $maxRevision");
    return $logs;
}

function get_logs($localPath, $minRevision, $maxRevision = 'HEAD')
{
    if (empty($minRevision) || empty($maxRevision)) {
        return false;
    }
    $logs = shell_exec("git --no-pager log --pretty=format:\"%H | %an | %ad |%n%n%s%n------------------------------------------------------------------------\" $minRevision..$maxRevision");
    return $logs;
}

/**
 * Get contributors
 * @param $path
 * @param $contributors
 * @param $minRevision
 * @param $maxRevision
 * @param int $step
 */
function get_contributors($path, &$contributors, $minRevision, $maxRevision, $step = 20000)
{
    $logsHistory = [];

    do {
        if (! $minRevision || $minRevision == 1) {
            $minRevision = shell_exec("git --no-pager log -n $step --pretty=format:\"%H\" $maxRevision | tail -n 1");
        }

        echo "\rRetrieving logs from revision $minRevision to $maxRevision ...\t\t\t";
        $logs = get_logs_contrib($path, $minRevision, $maxRevision);
        $logs = preg_split("/((\r?\n)|(\r\n?))/", $logs);
        $logsHistory = array_merge($logsHistory, $logs);
        $maxRevision = $minRevision;
        $minRevision = 1;
    } while ($step <= count($logs));

    foreach ($logsHistory as $line) {
        $data = explode(';', $line);
        $author = $data[2];

        if (! isset($contributors[$author])) {
            $contributors[$author] = [];
        }

        $contributors[$author]['Author'] = $author;
        $contributors[$author]['First Commit'] = $data[4];

        if (isset($contributors[$author]['Number of Commits'])) {
            $contributors[$author]['Number of Commits']++;
        } else {
            $contributors[$author]['Last Commit'] = $data[4];
            $contributors[$author]['Number of Commits'] = 1;
        }
    }
}

/**
 * Verify if a tag exists
 * @param $tag
 * @param bool $remote
 * @return bool
 */
function tag_exists($tag, $remote = false)
{
    shell_exec("git fetch --all --tags --prune");
    $searchedTag = shell_exec("git tag --list '$tag'");
    $searchedTag = ! empty($searchedTag) ? trim($searchedTag) : '';

    return ! empty($searchedTag);
}

/**
 * Delete a tag
 * @param $tag
 * @param string $commitMsg
 */
function delete_tag($tag, $commitMsg = "")
{
    shell_exec("git tag -d $tag"); // Delete from local
    shell_exec("git push --delete origin $tag"); // Delete from remote
}

/**
 * Create a new tag
 * @param $tag
 * @param $commitMsg
 * @param string $branch
 * @param string $revision
 */
function create_tag($tag, $commitMsg, $branch = "", $revision = "")
{
    shell_exec("git tag -a $tag -m \"$commitMsg\"");
    shell_exec("git push origin $tag");
}

/**
 * Export a git project
 * @param $source
 * @param $dest
 * @return mixed
 */
function export($source, $dest)
{
    $files = shell_exec("git -C $source ls-files");
    foreach (preg_split("/((\r?\n)|(\r\n?))/", $files) as $file) {
        // do stuff with $line
        if ($file === ".git" || $file === "." || empty($file)) {
            continue;
        }
        $s1 = join('/', [$source, $file]);
        $s2 = join('/', [$dest, $file]);
        $path = pathinfo($s2);
        if (! file_exists($path['dirname'])) {
            mkdir($path['dirname'], 0777, true);
        }
        if (! copy($s1, $s2)) {
            error('Error copying ' . $s1 . ' to ' . $s2);
        }
    }
}
