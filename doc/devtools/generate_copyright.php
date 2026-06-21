<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.


define("ROOT", realpath(__DIR__ . '/../..'));
const COPYRIGHTS_FILENAME = 'copyright.txt';

const COPYRIGHTS = ROOT . '/' . COPYRIGHTS_FILENAME;

function tiki_version(): string
{
    require_once ROOT . '/lib/setup/twversion.class.php';
    return (new TWVersion())->version;
}


function get_current_major(): int
{
    $current_version = tiki_version();
    return (int) explode('.', $current_version)[0];
}



function update_copyright_file($newVersion)
{
    global $nbCommiters;

    // Single git scan (fast)
    $cmd = 'git log --format="%aN||%aE||%at"';
    $lines = explode("\n", shell_exec($cmd));

    $contributors = [];

    foreach ($lines as $line) {
        if (! $line) {
            continue;
        }

        list($name, $email, $ts) = explode('||', $line);

        $key = strtolower(trim($email));

        if (! isset($contributors[$key])) {
            $contributors[$key] = [
                'Nickname' => $name,
                'Name' => $name,
                'FirstTS' => (int)$ts,
                'LastTS' => (int)$ts,
                'Number of Commits' => 1,
            ];
        } else {
            $contributors[$key]['Number of Commits']++;

            if ($ts < $contributors[$key]['FirstTS']) {
                $contributors[$key]['FirstTS'] = (int)$ts;
            }

            if ($ts > $contributors[$key]['LastTS']) {
                $contributors[$key]['LastTS'] = (int)$ts;
            }
        }
    }

    // Convert timestamps to readable dates
    foreach ($contributors as &$c) {
        $c['First Commit'] = gmdate("Y-m-d", $c['FirstTS']);
        $c['Last Commit']  = gmdate("Y-m-d", $c['LastTS']);
        unset($c['FirstTS'], $c['LastTS']);
    }
    unset($c);

    // Sort alphabetically by name
    uasort($contributors, fn($a, $b) => strcasecmp($a['Name'], $b['Name']));

    $nbCommiters = count($contributors);
    $totalContributors = $nbCommiters;
    $now = gmdate('Y-m-d');

    $copyrights = <<<EOS
Tiki Copyright
----------------

The following list attempts to gather the copyright holders for Tiki
as of version $newVersion.

Accounts listed below with commits have contributed source code.

This is how we implement the Tiki Social Contract.
http://tiki.org/Social+Contract

List of members of the Community
As of $now, the community has:
  * $totalContributors members,
  * $nbCommiters of those people who made at least one code commit

This list is automatically generated and alphabetically sorted
by the following script: php doc/devtools/release.php

====================================================================

EOS;

    foreach ($contributors as $info) {
        $copyrights .= "\nName: {$info['Nickname']}";

        $orderedKeys = ['Name', 'First Commit', 'Last Commit', 'Number of Commits'];

        foreach ($orderedKeys as $k) {
            if (empty($info[$k]) || ($k === 'Name' && $info[$k] === $info['Nickname'])) {
                continue;
            }

            $copyrights .= "\n$k: " . $info[$k];
        }

        $copyrights .= "\n";
    }

    if (file_put_contents(COPYRIGHTS, $copyrights) === false) {
        fwrite(STDERR, "Copyrights update failed.\n");
        return false;
    }

    return [
        'contributors' => $totalContributors,
        'commits' => count($lines)
    ];
}


$newVersion = tiki_version();
$ucf = update_copyright_file($newVersion);

if (! $ucf) {
    exit(1);
}

echo ("\r>> ✅ Copyrights updated: {$ucf['contributors']} contributors " . "and {$ucf['commits']} commits");

exit(0);
