<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// ** This is the main script to release Tiki **
//
// To get the Tiki release HOWTO, try:
//    php doc/devtools/release.php --howto
//
// You can also get a detailed help on this script with:
//    php doc/devtools/release.php --help
//

define('TOOLS', __DIR__);
define('ROOT', realpath(TOOLS . '/../..'));

define('CHANGELOG_FILENAME', 'changelog.md'); //
define('COPYRIGHTS_FILENAME', 'copyright.txt');

// Display all errors and warnings, strict level already included in E_ALL in php 8.4
define('ERROR_REPORTING_LEVEL', E_ALL);
error_reporting(ERROR_REPORTING_LEVEL);

chdir(ROOT . '/');

require_once ROOT . '/lib/setup/third_party.php';
require_once ROOT . '/path_constants.php';
require_once ROOT . '/' . DEPRECATED_DEVTOOLS_PATH . '/vcscommons.php';

$phpCommand = $_SERVER['_'] ?? 'php';
$phpCommandArguments = implode(' ', $_SERVER['argv']);

if (! ($options = get_options()) || $options['help']) {
    display_usage();
}

if (file_exists(ROOT . '/.git')) {
    $vcs = 'git';
} else {
    exit;
}

require_once TOOLS . '/' . $vcs . 'tools.php';


if ($options['devmode']) {
    $options['no-commit'] = true;
    $options['no-check-vcs'] = true;
    $options['no-first-update'] = true;
    $options['debug-packaging'] = true;
}
if ($options['howto']) {
    display_howto();
}


if (! check_bin_version()) {
    error("You need the VCS '" . getBinName() . "' program at least at version " . getMinVersion() . "\n");
}

if (! $options['no-check-vcs'] && has_uncommited_changes('.')) {
    error("Uncommitted changes exist in the working folder.\n");
}

include_once('lib/setup/twversion.class.php');
$TWV = new TWVersion();

if ($options['only-secdb']) {
    passthru("$phpCommand doc/devtools/generate_secdb.php", $exitCode);
}

$script = $_SERVER['argv'][0];
$version = $_SERVER['argv'][1] ?? '';
$subrelease = $_SERVER['argv'][2] ?? '';

if (! preg_match("/^\d+\.\d+$/", $version)) {
    error("Version number should be in X.X format.\n");
}

$isPre = str_starts_with($subrelease, 'pre');
if ($isPre) {
    $subrelease = substr($subrelease, 3);
    $pre = 'pre';
} else {
    $pre = '';
}
$splitedversion = explode('.', $version);
$mainversion = $splitedversion[0];

$check_version = $version . $subrelease;
if ($TWV->version !== $check_version && ! $options['devmode']) {
    error("The version in the code " . strtolower($TWV->version) . " differs from the version provided to the script $check_version.\nThe version should be modified in lib/setup/twversion.class.php to match the released version.");
}

echo color("\nTiki release process started for version '$version" . ($subrelease ? " $subrelease" : '') . "'\n", 'cyan');
if ($isPre) {
    echo color("The script is running in 'pre-release' mode, which means that no tag will be created.\n", 'yellow');
}

if (! $options['no-first-update'] && important_step('Update working copy to the last revision')) {
    echo "Update in progress...";
    update_working_copy('.');

    if (! $options['no-check-vcs'] && has_uncommited_changes('.')) {
        error("\rUncommitted changes exist in the working folder.\n");
    }
    $revision = get_revision('.');
    info("\r>> Checkout updated to revision $revision.");
}

if (empty($subrelease)) {
    $branch = "$mainversion.x";
    $tag = "tags/$version";
    $packageVersion = $version;
    if (! empty($pre)) {
        $packageVersion .= ".$pre";
    }
    $secdbVersion = $version;
} else {
    $branch = "$mainversion.x";
    $tag = "tags/$version$subrelease";
    $packageVersion = "$version.$pre$subrelease";
    $secdbVersion = "$version$subrelease";
}

if (! $options['no-lang-update'] && important_step("Update language files")) {
    passthru("$phpCommand console.php translation:getstrings");
    $removeFiles = glob('lang/*/language.php.old');
    foreach ($removeFiles as $rf) {
        unlink($rf);
    }
    unset($removeFiles);
    info('>> Language files updated and temporary files removed.');
    important_step('Commit updated language files', true, "[REL] Update language.php files for $secdbVersion");
}

if (! $options['no-changelog-update'] && important_step("Update '" . CHANGELOG_FILENAME . "' file (using final version number '$version')")) {
    $output = [];
    $returnVar = 0;
    exec("php doc/devtools/generate_changelog.php", $output, $returnVar);
    if ($returnVar === 0) {
        info(">> Changelog updated successfully using doc/devtools/generate_changelog.php script.");
        important_step("Commit new " . CHANGELOG_FILENAME, true, "[REL] Update " . CHANGELOG_FILENAME . " for $secdbVersion");
    } else {
        error("Changelog update failed. generate_changelog.php exited with code $returnVar.\nOutput:\n" . implode("\n", $output));
    }
}

if (! $options['no-copyright-update'] && important_step("Update '" . COPYRIGHTS_FILENAME . "' file (using final version number '$version')")) {
    passthru("$phpCommand doc/devtools/generate_copyright.php", $exitCode);
    if ($exitCode === 0) {
        info("\n>> Copyright updated successfully using doc/devtools/generate_copyright.php script.");
        important_step("Commit new " . COPYRIGHTS_FILENAME, true, "[REL] Update " . COPYRIGHTS_FILENAME . " for $secdbVersion");
    } else {
        error('Copyrights update failed.');
    }
}

if (! $options['no-secdb'] && important_step("Update SecDB file(s) 'db/tiki-secdb_{$version}_mysql.sql'")) {
    passthru("$phpCommand doc/devtools/generate_secdb.php", $exitCode);
    if ($exitCode === 0) {
        print "SecDB file has been successfully created\n";
    } else {
        error('SecDB file update failed.');
    }
}

if ($isPre) {
    if (! $options['no-packaging'] && important_step("Build packages files")) {
        build_packages($packageVersion);
        echo color("\nMake sure these tarballs are tested by at least 3 different people.\n\n", 'cyan');
    } else {
        echo color("This was the last step.\n", 'cyan');
    }
} else {
    if (! $options['no-tagging']) {
        $tagAlreadyExists = tag_exists($tag, true);
        if ($tagAlreadyExists && important_step("The Tag '$tag' already exists: Delete the existing tag in order to create a new one")) {
            $commit_msg = "[REL] Deleting tag '$tag' in order to create a new one";
            if ($options['no-commit']) {
                print "Skipping actual commit ('$commit_msg') because no-commit = true\n";
            } else {
                delete_tag($tag, $commit_msg);
                $tagAlreadyExists = false;
                info(">> Tag '$tag' deleted.");
            }
        }
        if (! $tagAlreadyExists) {
            update_working_copy('.');
            $revision = get_revision(ROOT);
            if (important_step("Tag release using branch '$branch' at revision $revision")) {
                $commit_msg = '[REL] Tagging release';
                if ($options['no-commit']) {
                    print "Skipping actual commit ('$commit_msg') because no-commit = true\n";
                } else {
                    create_tag($tag, $commit_msg, $branch, $revision);
                    info(">> Tag '$tag' created.");
                }
            }
        }
    }

    if (! $options['no-packaging'] && important_step("Build packages files")) {
        build_packages($packageVersion);
    } else {
        info("This was the last step.\n");
    }
}

// Helper functions


/**
 *
 * Deletes a directory and its contents.
 *
 * @param string $dir directory to delete.
 * @return string|bool returns the filename that an error occurred in, false otherwise
 */

function rrmdir($dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                @chmod($dir . "/" . $object, 0777);
                if (filetype($dir . "/" . $object) === 'dir') {
                    $error = rrmdir($dir . "/" . $object);
                    if ($error) {
                        return $error;
                    }
                } elseif (! @unlink($dir . "/" . $object)) {
                    return 'Could not delete ' . $dir . "/" . $object . "\n";
                }
            }
        }
        reset($objects);
        @unlink($dir . '/.DS_store');
        if (! @rmdir($dir)) {
            return 'Could not delete ' . $dir . "\n";
        }
    }
    return false;
}


/**
 *
 * Recursively deletes specific files or directories
 *
 * @param string $src Directory to search through
 * @param array $files An array of file names to delete.
 */

function removeFiles(string $src, array $files): void
{
    $lookup = array_flip($files);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $path => $info) {
        $name = $info->getFilename();

        if (! isset($lookup[$name])) {
            continue;
        }

        if ($info->isDir()) {
            rrmdir($path);
        } else {
            if (! is_writable($path)) {
                chmod($path, 0777);
            }
            unlink($path);
        }
    }
}

function removeNodeModules(string $src): ?string
{
    if (! is_dir($src)) {
        return null;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $path => $info) {
        if ($info->isDir() && ! $info->isLink() && $info->getFilename() === 'node_modules') {
            $error = rrmdir($path);
            if ($error) {
                return $error;
            }
        }
    }

    return null;
}


/**
 *
 * Recursively sets permissions. Files get 775 and directories 664.
 *
 * @param string $src The directory to set permissions for
 */

function setPermissions(string $src): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $path => $info) {
        if ($info->isLink()) {
            continue;
        }

        chmod($path, $info->isDir() ? 0755 : 0664);
    }
}


/**
 *
 * Prepares and generates the release packages.
 *
 * @param string $releaseVersion Version of tiki that is being released.
 */

function build_packages($releaseVersion)
{
    global $options;

    $workDir = $_SERVER['HOME'] . "/tikipack";
    $fileName = 'tiki-' . $releaseVersion;
    $relDir = $workDir . '/' . $releaseVersion;    // where the tiki dir and tarballs go
    $sourceDir = $relDir . '/' . $fileName;        // the git export

    echo "Seting up $workDir directory\n";
    if (! is_dir($workDir)) {
        if (! mkdir($workDir)) {
            error('Cant make ' . $workDir . "\n");
            die();
        }
    }

    // remove previous files if they exist.
    if (is_dir($relDir)) {
        echo "Removing previous files\n";
        $shellout = rrmdir($relDir);
        if ($shellout) {
            die($shellout . "\n");
        }
    }
    if (! mkdir($relDir)) {
        error('Cant make ' . $relDir . "\n");
        die();
    }

    // create an export in tikipack to work with
    echo "Exporting working copy into $sourceDir\n";
    $shellout = export(ROOT, $sourceDir);
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }


    if (! is_file($sourceDir . '/' . PRIMARY_COMPOSERJSON_FILE_PATH)) {
        echo 'composer.json not found. Aborting.' . "\n";
        die();
    }

    if (is_file($workDir . '/composer.phar')) {
        if (! unlink($workDir . '/composer.phar')) {
            echo "Can't delete tikipack/composer.phar. Aborting." . "\n";
            die();
        }
    }

    echo "Downloading composer.phar" . "\n";
    $checksum = file_get_contents('https://composer.github.io/installer.sig');
    $composerInstaller = $workDir . '/composer-setup.php';
    if (! file_put_contents($composerInstaller, file_get_contents('https://getcomposer.org/installer'))) {
        echo "Can't create tikipack/composer-setup.php. Aborting." . "\n";
        die();
    }

    if ($checksum !== hash_file('sha384', $composerInstaller)) {
        echo "Invalid composer installer checksum. Aborting." . "\n";
        unlink($composerInstaller);
        die();
    }

    $shellout = shell_exec('php ' . escapeshellarg($composerInstaller) . ' --quiet --2 --install-dir=' . $workDir . ' 2>&1');

    if ($shellout) {
        echo "Composer installer failed. Aborting." . "\n";
        unlink($composerInstaller);
        die();
    }

    // tidy up
    unlink($composerInstaller);

    echo 'Installing dependencies through composer... [may take a while]' . "\n";
    $shellout = shell_exec('php ' . escapeshellarg($workDir . '/composer.phar') . ' install -d ' . escapeshellarg($sourceDir . '/' . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH) . ' --prefer-dist --no-dev 2>&1');
    if ($options['debug-packaging']) {
        echo $shellout . "\n";
    }

    if (
        str_contains($shellout, 'Fatal error:')
        || str_contains($shellout, 'Installation failed,')
        ||
        // symfony/dependency-injection comes in quite late in the list and is required - sometimes no error is reported even though it didn't work
        ! str_contains($shellout, 'symfony/dependency-injection')
    ) {
        echo 'Vendor bundled packages installation Failed. Exiting' . "\n";
        die();
    }

    echo "Running node install and build\n";
    $npmInstall = "cd $sourceDir; npm clean-install --engine-strict";
    exec($npmInstall, $npmInstallOutput, $npmInstallExitCode);
    if ($npmInstallExitCode !== 0) {
        error("npm install failed. Exiting.", $npmInstallExitCode);
    }
    $npmBuildResult = passthru("cd $sourceDir; npm run build");
    if ($npmBuildResult === false) {
        error("npm build failed. Exiting.");
    }
    echo "npm setup completed.\n";

    echo "Removing development files\n";
    //  Remove node_modules created when running "npm install" in tiki root directory and Recursively remove node_modules dynamically created alongside the package.json files in the src folder anytime we have conflicting npm version requirements.
    $shellout = removeNodeModules($sourceDir);
    if ($shellout) {
        die($shellout . "\n");
    }

    $shellout = rrmdir($sourceDir . '/' . TESTS_PATH);
    if ($shellout) {
        die($shellout . "\n");
    }
    /** @deprecated Looks like an artefact of past tiki versions, can this be removed?  benoitg - 2023-12-22 */
    $shellout = rrmdir($sourceDir . '/db/convertscripts');
    if ($shellout) {
        die($shellout . "\n");
    }

    $shellout = rrmdir($sourceDir . '/' . DEPRECATED_DEVTOOLS_PATH);
    if ($shellout) {
        die($shellout . "\n");
    }

    $shellout = rrmdir($sourceDir . '/' . BIN_PATH);
    if ($shellout) {
        die($shellout . "\n");
    }

    removeFiles($sourceDir, ['.gitignore']);

    echo "Removing language file comments\n";
    foreach (scandir($sourceDir . '/' . LANG_SRC_PATH) as $strip) {
        if (is_file($sourceDir . '/' . LANG_SRC_PATH . '/' . $strip . '/language.php')) {
            $shellout = shell_exec('php ' . escapeshellarg(__DIR__ . '/stripcomments.php') . ' ' . escapeshellarg($sourceDir . '/' . LANG_SRC_PATH . '/' . $strip . '/language.php') . ' 2>&1');
        }
        if ($shellout) {
            die($shellout . "\n");
        }
    }

    echo "Setting file permissions\n";
    setPermissions($sourceDir);

    $relDir = escapeshellarg($relDir);
    $isMac = (PHP_OS_FAMILY === 'Darwin');
    $macPrefix = $isMac ? 'COPYFILE_DISABLE=1 ' : '';
    // exclude the .DS_Store files which are macOS generated files
    $excludes = [
        '*.DS_Store',
        '.husky',
        '.gitpod',
        '.editorconfig',
        '.gitattributes',
        '.gitlab-ci.yml',
        '.gitlab-ci-local-env',
        '.gitlab-ci-local-variables.yml',
        '.gitpod.yml',
        '.phplint.yml',
        '.prettierrc',
        '.vimrc',
        'auto-imports.d.ts',
        'components.d.ts',
        'check_composer_exists.php',
        'eslint.config.js',
        'commitlint.config.cjs'
    ];

    $tarExcludes = implode(' ', array_map(
        fn($e) => "--exclude=" . escapeshellarg($e),
        $excludes
    ));

    $zipExcludes = implode(' ', array_map(
        fn($e) => "-x " . escapeshellarg($e),
        $excludes
    ));

    $sevenZipExcludes = implode(' ', array_map(
        fn($e) => "-xr!" . escapeshellarg($e),
        $excludes
    ));

    $archives = [
        'tar.gz'  => "{$macPrefix}tar -czp $tarExcludes -f {archive} {source}",
        'tar.bz2' => "{$macPrefix}tar -cjp $tarExcludes -f {archive} {source}",
        'tar.xz'  => "{$macPrefix}tar -cJp $tarExcludes -f {archive} {source}",
        'zip'     => "zip -ry {archive} {source} $zipExcludes -9",
        '7z'      => "7za a {archive} {source} $sevenZipExcludes -mx=9",
    ];
    foreach ($archives as $ext => $cmdTemplate) {
        $archive = escapeshellarg("$fileName.$ext");
        $source  = escapeshellarg($fileName);

        $command = str_replace(
            ['{archive}', '{source}'],
            [$archive, $source],
            $cmdTemplate
        );

        echo "Creating $fileName.$ext \n";
        $shellout = shell_exec("cd $relDir; $command 2>&1");
        if (! empty($options['debug-packaging'])) {
            echo $shellout . "\n";
        }

        if ($ext === '7z' && str_contains($shellout, 'command not found')) {
            error("7za not installed. Archive creation failed.\n");
        }
    }

    echo color("\nTo upload the 'tarballs', copy-paste and execute the following line (and change '\$SF_LOGIN' by your SF.net login):\n", 'yellow');
    echo color("    cd $relDir; scp $fileName.* \$SF_LOGIN@frs.sourceforge.net:/home/pfs/project/t/ti/tikiwiki/\$RELEASEFOLDER\$\n", 'yellow');

    info(">> Packages files have been built in ~/tikipack/$releaseVersion\n");
}

/**
 * @return array|bool
 */
function get_options()
{
    if ($_SERVER['argc'] <= 1) {
        return false;
    }

    $argv = [];
    $options = [
        'howto' => false,
        'help' => false,
        'no-commit' => false,
        'no-check-vcs' => false,
        'no-first-update' => false,
        'no-lang-update' => false,
        'no-changelog-update' => false,
        'no-copyright-update' => false,
        'no-secdb' => false,
        'no-packaging' => false,
        'no-tagging' => false,
        'force-yes' => false,
        'debug-packaging' => false,
        'only-secdb' => false,
        'devmode' => false,
        'skip' => 0,
    ];

    // Environment variables provide default values for parameter options. e.g. export TIKI_NO_SECDB=true
    $prefix = "TIKI-";
    foreach ($options as $option => $optValue) {
        $envOption = $prefix . $option;
        $envOption = str_replace("-", "_", $envOption);
        if (isset($_ENV[$envOption])) {
            $envValue = $_ENV[$envOption];
            $options[$option] = $envValue;
        }
    }

    foreach ($_SERVER['argv'] as $arg) {
        if (str_starts_with($arg, '--')) {
            if (($opt = substr($arg, 2)) != '' && isset($options[$opt])) {
                $options[$opt] = true;
            } elseif (str_contains($arg, '=')) {
                $parts = explode('=', substr($arg, 2));
                if (isset($options[$parts[0]])) {
                    $options[$parts[0]] = $parts[1];
                }
            } else {
                error("Unknown option $arg. Try using --help option.\n");
            }
        } else {
            $argv[] = $arg;
        }
    }
    $_SERVER['argv'] = $argv;
    unset($argv);

    if ($_SERVER['argc'] == 2) {
        $_SERVER['argv'][] = '';
    }

    return $options;
}

/**
 * @param $msg
 * @param bool $increment_step
 * @param bool $commit_msg
 * @return bool
 */
function important_step($msg, $increment_step = true, $commit_msg = false)
{
    global $options;
    static $step = 0;

    // Auto-Skip the step if this is a commit step and if there is nothing to commit
    if ($commit_msg && ! has_uncommited_changes('.')) {
        return false;
    }

    // Increment step number if needed
    if ($increment_step) {
        $step++;
    }

    if ($step <= $options['skip']) {
        print "Skipping step $step\n";
        return false;
    }

    if ($commit_msg && $options['no-commit']) {
        print "Skipping actual commit ('$commit_msg') because no-commit = true\n";
        return false;
    }

    if ($options['force-yes']) {
        important("\n$step) $msg...");
        $do_step = true;
    } else {
        important("\n$step) $msg?");

        $prompt = '[Y/n/q/?] ';
        if (function_exists('readline')) {
            // readline function requires php readline extension...
            $c = readline($prompt);
        } else {
            echo $prompt;
            $c = rtrim(fgets(STDIN), "\n");
        }

        switch (strtolower($c)) {
            case 'y':
            case '':
                $do_step = true;
                break;
            case 'n':
                info(">> Skipping step $step.");
                $do_step = false;
                break;
            case 'q':
                die;
                break;
            default:
                if ($c != '?') {
                    info(color(">> Unknown answer '$c'.", 'red'));
                }
                info(">> You have to type 'y' (Yes), 'n' (No) or 'q' (Quit) and press Enter.");
                return important_step($msg, false);
        }
    }

    if ($commit_msg && $do_step && ($revision = commit($commit_msg))) {
        info(">> Commited revision $revision.");
    }

    return $do_step;
}


function display_usage()
{
    echo "Usage: php doc/devtools/release.php [ Options ] <version-number> [ <subrelease> ]
Examples:
    php doc/devtools/release.php 2.0 preRC3
    php doc/devtools/release.php 2.0 RC3
    php doc/devtools/release.php 2.0

Options:
    --howto                   : display the Tiki release HOWTO
    --help                    : display this help
    --no-commit               : do not commit any changes back to GIT
    --no-check-vcs            : do not check if there are uncommitted changes on the checkout used for the release
    --no-first-update         : do not vcs update the checkout used for the release as the first step
    --no-lang-update          : do not update lang/*/language.php files
    --no-changelog-update     : do not update the '" . CHANGELOG_FILENAME . "' file
    --no-copyright-update     : do not update the '" . COPYRIGHTS_FILENAME . "' file
    --no-secdb                : do not update SecDB footprints
    --only-secdb              : only generate a secdb database
    --no-packaging            : do not build packages files
    --no-tagging              : do not tag the release on the remote vcs repository
    --force-yes               : disable the interactive mode (same as replying 'y' to all steps)
    --debug-packaging         : display debug output while in packaging step
    --devmode                 : equivalent to no-commit + no-check-vcs + no-first-update
    --skip=0                  : number of steps to skip when debugging (for use with -- devmode)
Notes:
    Subreleases begining with 'pre' will not be tagged.
";
    die;
}

function display_howto()
{
    echo <<<EOS
--------------------------
   HOWTO release Tiki
--------------------------

Please see: https://dev.tiki.org/How+to+release

EOS;
    shell_exec('open ' . escapeshellarg('https://dev.tiki.org/How+to+release'));
    exit;
}
