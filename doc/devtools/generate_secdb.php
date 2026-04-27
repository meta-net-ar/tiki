<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\TikiInit;

include_once('lib/core/Tiki/TikiInit.php');

define("ROOT", realpath(__DIR__ . '/../..'));
require_once __DIR__ . '/../../path_constants.php';
require_once('gittools.php');
function tiki_version(): string
{
    require_once ROOT . '/lib/setup/twversion.class.php';
    return (new TWVersion())->version;
}

/**
 *
 * Will remove old secdb files and generate a new one based on working copy files.
 *
 * @param $version string The current tiki version to use eg. 17.0 or 21.0RC1
 *
 * @return bool true on success
 */

function updateSecdb(string $version): bool
{
    // first unset any preexisting files.
    echo(">>");
    $vcs = str_ends_with($version, 'git');

    // if we are not creating a release skip deleting old files.
    if (! $vcs) {
        $files = glob(ROOT . '/' . TIKI_BASE_SQL_SCHEMA_PATH . '/tiki-secdb_*_mysql.sql');
        foreach ($files as $file) {
            $file = escapeshellarg($file);
            delete_file($file);
        }
        echo(' Removed ' . count($files) . ' old secdb files.');

        $excludes = [];
    } else {
        $excludes = array_keys(files_differ(ROOT));
    }

    $file = "/" . TIKI_BASE_SQL_SCHEMA_PATH . "/tiki-secdb_{$version}_mysql.sql";

    if (! $fp = @fopen(ROOT . $file, 'w')) {
        error('The SecDB file "' . ROOT . $file . '" is not writable or can\'t be created.');
        return false;
    }
    $queries = [];
    build_secdb_queries(ROOT, $version, $queries, $excludes);

    if (! empty($queries)) {
        sort($queries);
        fwrite($fp, "start transaction;\n");
        fwrite($fp, "DELETE FROM `tiki_secdb`;\n");
        // This index was originally created with a size limit that would raise an error on some versions,
        // notably on 18.0. Since this file is executed before any patch in installer/schema, the fix had to
        // be done here. It's a quick operation because table is empty, so no harm in leaving this here forever.
        fwrite($fp, "ALTER TABLE `tiki_secdb` DROP PRIMARY KEY, ADD PRIMARY KEY (`filename`(171),`tiki_version`(20));\n\n");

        $insertString = 'INSERT INTO `tiki_secdb` (`filename`, `md5_value`, `tiki_version`) VALUES ';

        $extendedInsertSize = 0;
        $extendedInsertMaxSize = 1 * 1024 * 1024 - 100; // 1MB with 100 bytes safety limit, some old versions of Mysql had max_allowed_packet=1MB

        foreach ($queries as $q) {
            if (($extendedInsertSize + strlen($q) + 2) > $extendedInsertMaxSize) {
                fwrite($fp, ";\n");
                $extendedInsertSize = 0;
            }
            if ($extendedInsertSize === 0) {
                fwrite($fp, $insertString . "\n");
                $extendedInsertSize = strlen($insertString) + 1;
            } else {
                fwrite($fp, ",\n");
                $extendedInsertSize += 2;
            }
            fwrite($fp, $q);
            $extendedInsertSize += strlen($q);
        }
        fwrite($fp, ";\n");

        fwrite($fp, "commit;\n");
    }
    fclose($fp);

    echo("\n $file was generated.\n");
    if (! $vcs) {
        $file = escapeshellarg(ROOT . $file); // escape file name for use in command line.
        add($file);
    }
    return true;
}

/**
 * Similar to md5_check_dir in tiki-admin_security.php but creates the sql queries for /db/tiki-secdb_{$version}_mysql.sql
 *
 * @param string $dir
 * @param string $version
 * @param array  $queries  queries returned
 * @param array  $excludes files to exclude when doing secdb on a checkout
 */
function build_secdb_queries(string $dir, string $version, array &$queries, array $excludes = []): void
{
    $d = dir($dir);
    $link = null;
    if (is_file(TIKI_CONFIG_PATH . '/virtuals.inc')) {
        $virtuals = array_map('trim', file(TIKI_CONFIG_PATH . '/virtuals.inc'));
    } else {
        $virtuals = [];
    }

    while (false !== ($e = $d->read())) {
        $entry = $dir . '/' . $e;
        if (is_link($entry)) {
            continue; // if is a symlink we should not run any hash
        }
        if (is_dir($entry)) {
            // do not descend and no git files
            if ($e != '..' && $e != '.' && $e != '.git' && $e != '.gitignore' && $e != 'node_modules' && $entry != ROOT . '/' . TEMP_PATH && $entry != ROOT . '/' . TIKI_VENDOR_CUSTOM_PATH && $entry != ROOT . '/' . TIKI_CUSTOMIZATIONS_SRC_PATH) {
                build_secdb_queries($entry, $version, $queries, $excludes);
            }
        } else {
            if (preg_match('/\.(sql|css|tpl|js|php)$/', $e) && realpath($entry) != __FILE__ && ! preg_match('#/local.php$#', $entry)) {
                $file = '.' . substr($entry, strlen(ROOT));

                if (in_array($entry, $excludes)) {
                    continue;
                }

                foreach ($virtuals as $virtual) {
                    if (str_contains($entry, "/$virtual/")) {
                        continue 2;
                    }
                }

                // Escape filename. Since this requires a connection to MySQL (due to the charset),
                // do so conditionally to reduce the risk of connection failure.
                if (! preg_match('/^[a-zA-Z!-9\/ _+.-@]+$/', $file)) {
                    if (! $link) {
                        try {
                            $credentialsFile = TikiInit::getCredentialsFile();
                            if (class_exists('Tiki\TikiInit') && file_exists($credentialsFile)) {
                                global $host_tiki, $user_tiki, $pass_tiki, $dbs_tiki;
                                require $credentialsFile;

                                $link = mysqli_connect(
                                    $host_tiki,
                                    $user_tiki,
                                    $pass_tiki,
                                    $dbs_tiki
                                );
                            } else {
                                $link = mysqli_connect();
                            }
                        } catch (Exception $e) {
                            global $phpCommand, $phpCommandArguments;
                            error(
                                "SecDB step failed because some filenames (e.g. {$file}) need" .
                                "escaping but no MySQL connection has been found (" . mysqli_connect_error() . ")."
                                . "\nTry this command line instead (replace HOST, USER and PASS with" .
                                "a valid MySQL host, user and password) :"
                                . "\n\n\t" . $phpCommand
                                . " -d mysqli.default_host=HOST -d mysqli.default_user=USER -d mysqli.default_pw=PASS "
                                . $phpCommandArguments . "\n"
                            );
                        }
                    }
                    $file = @mysqli_real_escape_string($link, $file);
                }

                if (is_readable($entry)) {
                    $hash = md5_file($entry);
                    $queries[] = "('$file', '$hash', '$version')";
                }
            }
        }
    }
    $d->close();
}

if (! updateSecdb(tiki_version())) {
    print "SecDB file creation Failed\n";
    exit(1);
}
print "SecDB file has been successfully created\n";
exit(0);
