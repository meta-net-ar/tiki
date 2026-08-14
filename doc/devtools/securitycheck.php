<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// analyse_file_path groups files by type, e.g. library, etc.

/**
 * Security Static Checker for Tiki
 *
 * Usage:
 *   From the Tiki root directory, run:
 *       php doc/devtools/securitycheck.php
 *
 * Purpose:
 *   This script scans all PHP files in the Tiki codebase to detect potentially unsafe files.
 *   Each PHP file in Tiki should start with a feature check (or an equivalent mechanism such as
 *   an include-only check, web access block, or permission check). If the feature is deactivated,
 *   the file is considered inactive. Files that cannot have a feature check by design must be
 *   audited manually.
 *
 * Behavior:
 *   - If all scanned files are safe, the script prints:
 *         "All scanned files are safe ✅"
 *   - If unsafe files are detected, the script prints a table listing each unsafe file
 *
 * Exit Codes:
 *   - 0 => all scanned files are safe
 *   - 1 => unsafe file found
*/

if (PHP_SAPI !== 'cli') {
    die("This script must be run from the command line.\n");
}

require __DIR__ . '/../../path_constants.php';

// Add the imported libraries located in lib/
$thirdpartyLibs = [
    '\./lib.*', /* as per NKO 4:18 19-MAY-09 */
                /* jb 110715 Tiki 7.1 - so everything in lib is protected by the .htaccess file, right? */
];

/*

The following do actually have features, but the fix check checker
needs to be changed to accept access->check_permissions() so that also that it loads tikisetup.php
 ./tiki-orphan_pages.php
 ./tiki-plugins.php
 ./tiki-switch_perspective.php
*/
// Skip entire folders that are known safe
$skipDirs = [
    '.git',
    '.gitignore',
    './node_modules', // generated files
    './' . TEMP_PATH, // generated files
    './' . TIKI_VENDOR_CUSTOM_PATH,
    './' . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . '/vendor', // generated files
    './' . TIKI_VENDOR_NONBUNDLED_PATH, // generated files
    './' . TIKI_CUSTOMIZATIONS_SRC_PATH,
    './' . DEPRECATED_DEVTOOLS_PATH,
    './' . TIKI_CUSTOMIZATIONS_SRC_DIST_PATH,
    './' . BIN_PATH,
    './' . PUBLIC_GENERATED_PATH, // generated files
    './src/php/external_lib_sources' // External php codebases whose source code have been included in tiki.
];

$safePaths = [
    /* Not in build */
    '\./' . TIKI_CONFIG_FILE_PATH,
    '\./' . TIKI_CONFIG_PATH . '/virtuals.inc',

    /* The following are DELIBERATELY PUBLIC. */
    '\./tiki-cookie-jar.php',
    '\./tiki-error_simple.php',
    '\./tiki-information.php',
    '\./tiki-install.php',  // does its own check
    '\./tiki-live_support_chat_frame.php',
    '\./tiki-login_scr.php',
    '\./tiki-channel.php',            // does its own checks
    /* This file is just comments */
    './about.php',
    './db/preconfiguration.php', // contains comments
    './lang/ca/language_r.php', // contains comments
    './lang/en/language_r.php', // contains comments

    '/lib/Sheet/include/org/apicnet/io/OOo/objOOo/OOoCadre.php', // empty file
    '\./lib/test/.*/fixtures/.*\.php', // contains predefined data used in tests.
    '\./lib/test/api/tiki-api-wrapper.php', // API wrapper for testing.
    '\./lib/test/local.php', // contains database credentials for testing.
    '/lib/cypht/modules/tiki/modules.php', // bootstrap includes file.
    '/lib/cypht/modules/tiki/setup.php', // configuration file.

    /* exception files */
    '\./tiki-admin_trackers.php', // perform redirection and a target file has its own check.
    '\./tiki-list_users.php', // perform redirection and a target file has its own check.
    '\./tiki-mods.php', // template file (no executable code)
    '\./tiki-monitor.php', // shows status info
    '\./tiki-wikiplugin_edit.php', // deprecated file
    '\./get_strings.php', // deprecated file
    '\./installer/shell.php', // deprecated file
    '\./permissioncheck/create_new_htaccess.php', // create a new_htaccess file with password protection in ./permissioncheck
    '\./xmlrpc.php', // redirect to tiki-xmlrpc_services.php which has its own check
    '\./tiki-download_forum_attachment.php', // no need to attach permission for download and has its own checks

    /* language files */
    '\./lang/.*/language_.*\.php', // language files are just definitions
    '\./lang/.*/.*\.php_example',  // language example files are just definitions

    /* The following need to be refactored to a lib */
    '\./tiki-testGD.php',
];

/**
 * @param $filename
 * @return bool|string
 */
function get_content($filename)
{
    static $last, $content;

    if ($filename == $last) {
        return $content;
    }
    $content = file_get_contents($last = $filename);
    return $content;
}

/**
 * @param $featureNameIndex
 * @return string
 */
function feature_pattern(&$featureNameIndex)
{
    $q = "[\"']";
    $featureNameIndex = 1;
    return "/\\\$prefs\s*\[$q(\w+)$q\]\s*(!=|==)=?\s*$q(y|n)$q/";
}


/**
 * @param $permissionNameIndex
 * @return string
 */
function permission_pattern(&$permissionNameIndex)
{
    $permissionNameIndex = 1;
    return "/->check_permission\s*\(\s*['\"](tiki_p_\w+)['\"]\s*\)/";
}

/**
 * @return string
 */
function includeonly_pattern() // {{{
{
    return "/strpos\s*\(\s*\\\$_SERVER\s*\[\s*[\"']SCRIPT_NAME[\"']\s*\]\s*,\s*basename\s*\(\s*__FILE__\s*\)\s*\)\s*!==\s*(false|FALSE)/";
}
// }}}

/**
 * @return string
 */
function includeonly_pattern3() // {{{
{
    return "/basename\s*\(\s*\\\$_SERVER\s*\[\s*[\"']SCRIPT_NAME[\"']\s*\]\s*\)\s*===?\s*basename\s*\(\s*__FILE__\s*\)\s*\)/";
}
// }}}


/**
 * @return string
 */
function includeonly_pattern2() // {{{
{
    return "/\\\$access\s*->\s*check_script\s*\(\s*\\\$_SERVER\s*\[\s*[\"']SCRIPT_NAME[\"']\s*\]\s*,\s*basename\s*\(\s*__FILE__\s*\)\s*\)/s";
}
// }}}

/**
 * @return string
 */
function includeonly_pattern4() // {{{
{
    return "/!\s*str_contains\s*\(\s*\\\$_SERVER\s*\[\s*[\"']SCRIPT_NAME[\"']\s*\]\s*,\s*basename\s*\(\s*__FILE__\s*\)\s*\)/";
}
// }}}

/**
 * @return string
 */
function includeonly_pattern5() // {{{
{
    return "/str_contains\s*\(\s*\\\$_SERVER\s*\[\s*[\"']SCRIPT_NAME[\"']\s*\]\s*,\s*basename\s*\(\s*__FILE__\s*\)\s*\)/";
}
// }}}

/**
 * @return string
 */
function noweb_pattern() // {{{
{
    return "/if\s*\(\s*isset\s*\(\s*\\\$_SERVER\[\s*[\"']REQUEST_METHOD[\"']\]\s*\)\s*\)\s*die/";
}
// }}}

/**
 * @return string
 */
function cli_sapi_pattern() // {{{
{
    return "/if\s*\(\s*PHP_SAPI\s*!==\s*'cli'\s*\)\s*{\s*(die(\s*\(\s*(\".*?\"|'.*?')?\s*\))?|return)\s*;/";
}
// }}}

/**
 * @return string
 */
function httpResponseCode_pattern() // {{{
{
    return "/if\s*\(http_response_code\(\)\s*!==\s*false\)\s*{\s*die\s*\(/";
}
// }}}

/**
 * @return string
 */
function tikisetup_pattern() // {{{
{
    return "/(require(_once)?|include(_once)?)\s*\(?\s*['\"]tiki-setup.php['\"]/";
}
// }}}

/**
 * @param $folder
 * @param $files
 * @param $filesHash
 */
function scanfiles($folder, &$files, &$filesHash)
{
    global $safePaths, $skipDirs;

    try {
        $iterator = new FilesystemIterator($folder, FilesystemIterator::SKIP_DOTS);
    } catch (UnexpectedValueException $e) {
        printf("Could not open folder: %s\n", $folder);
        return;
    }

    foreach ($iterator as $fileInfo) {
        $path = $fileInfo->getPathname();

        if ($fileInfo->isDir()) {
            // Check if directory should be skipped
            if (in_array($path, $skipDirs)) {
                continue;
            }

            scanfiles($path, $files, $filesHash);
        } else {
            // Skip files that match safe paths
            if (regex_match($path, $safePaths) || str_starts_with($path, '.')) {
                continue;
            }

            $analysis = analyse_file_path($path);
            $files[] = $analysis;
            $filesHash[$path] = $analysis;
        }
    }
}

// }}}

// TODO This is an inefficient function, but more flexible than in_array
/**
 * @param $path
 * @param $regex_possibles
 * @return bool
 */
function regex_match($path, $regex_possibles)
{
    foreach ($regex_possibles as $possible) {
        if (preg_match('%' . $possible . '%', $path)) {
            return true;
        }
    }
    return false;
}

/**
 * @param $path
 * @return array
 */
function analyse_file_path($path) // {{{
{
    global $thirdpartyLibs, $safePaths;

    $type = 'unknown';
    $name = basename($path);
    if (str_contains($name, '.')) {
        $extension = substr($name, strrpos($name, '.') + 1);
    } else {
        $extension = false;
    }

    if (str_contains($path, '/CVS/')) {
        $type = 'cvs';
    } elseif ($extension == 'php' || $extension == 'inc') {
        if ($name == 'index.php') {
            $type = 'blocker';
        } elseif ($name == 'language.php') {
            $type = 'lang';
        } elseif ($path == './lib/wiki-plugins') {
            $type = 'wikiplugin';
        } elseif ($path == './lib/') {
            if (regex_match($path, $thirdpartyLibs)) {
                $type = '3rdparty';
            } else {
                $type = 'lib';
            }
        } elseif ($path == './tiki-') {
            $type = 'public';
        } elseif ($path == './modules/') {
            $type = 'module';
        } else {
            $type = "include";
        }
    } elseif (in_array($extension, ['txt', 'png', 'jpg', 'html', 'css', 'sql', 'gif', 'afm', 'js'])) {
        $type = 'static';
    } elseif ($path == './files/') {
        $type = 'user';
    } elseif ($extension == 'sh' || str_contains($path, '_htaccess')) {
        $type = 'system';
    } elseif (in_array(basename($path), ['INSTALL', 'README'])) {
        $type = 'doc';
    } elseif ($extension == 'tpl') {
        $type = 'template';
    }

    return [
        'filename' => basename($path),
        'path' => $path,
        'type' => $type,
        'extension' => $extension,
        'features' => [],
        'permissions' => [],
        'includeonce' => false,
        'noweb' => false,
        'tikisetup' => false,
        'unsafeextract' => false,
    ];
}
// }}}

/**
 * @param $file
 * @return array
 */
function perform_feature_check(&$file) // {{{
{
    global $features;
    $index = 0;
    $feature_pattern = feature_pattern($index);
    $path = $file['path'];

    preg_match_all($feature_pattern, get_content($path), $parts);

    $featuresInFile = [];
    if ($index === 1) {
        $featuresInFile = array_merge($features, $parts[$index]);
    }

    $featuresInFile = array_merge($featuresInFile, access_check_call($path, 'check_feature'));
    $featuresInFile = array_unique($featuresInFile);
    $file['features'] = $featuresInFile;
    return $featuresInFile;
}
// }}}

/**
 * @param $file
 */
function perform_permission_check(&$file) // {{{
{
    $index = 0;

    $permission_pattern = permission_pattern($index);

    preg_match_all($permission_pattern, get_content($file['path']), $parts);

    $permissions = array_unique(
        array_merge(
            access_check_call($file['path'], 'check_permission'),
            permission_check_accessors($file['path']),
            $parts[$index]
        )
    );

    $file['permissions'] = $permissions;
}
// }}}

/**
 * @param $file
 */
function perform_includeonly_check(&$file) // {{{
{
    $index = 0;
    $pattern = includeonly_pattern($index);

    preg_match_all($pattern, get_content($file['path']), $parts);

    $pattern = includeonly_pattern2($index);
    preg_match_all($pattern, get_content($file['path']), $parts2);

    $pattern = includeonly_pattern3($index);
    preg_match_all($pattern, get_content($file['path']), $parts3);

    $pattern = includeonly_pattern4($index);
    preg_match_all($pattern, get_content($file['path']), $parts4);

    $pattern = includeonly_pattern5($index);
    preg_match_all($pattern, get_content($file['path']), $parts5);

    $file['includeonly'] = count($parts[0]) > 0 || count($parts2[0]) > 0 || count($parts3[0]) > 0 || count($parts4[0]) > 0 || count($parts5[0]) > 0;
}
// }}}

/**
 * @param $file
 */
function is_function_declaration_file(&$file) // {{{
{
    // Initially assume the file is not safe
    $file['safe_fn_declaration'] = false;

    // Read the file content
    $content = file_get_contents($file['path']);

    // Get all tokens in the file
    $tokens = token_get_all($content);

    $isInFunctionDeclaration = false;
    $hasFunctionDeclaration = false;
    $functionBraceCount = 0;
    $topLevelBraceCount = 0;
    $allowedChars = ['(', ')', '{', '}', ';', '=', '[', ']', '!', ':', ',', '?', '.'];

    $allowedTokens = [
        T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_USE, T_NAME_QUALIFIED,
        T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE, T_DIR,
        T_OPEN_TAG, T_IF, T_STRING, T_NS_SEPARATOR, T_CONSTANT_ENCAPSED_STRING, T_EXIT,
    ];

    foreach ($tokens as $token) {
        if (is_array($token)) {
            list($type, $value) = $token;

            if ($type == T_FUNCTION) {
                $isInFunctionDeclaration = true;
                $hasFunctionDeclaration = true;
                continue;
            }

            if ($isInFunctionDeclaration) {
                if ($value === '{') {
                    $functionBraceCount++;
                    continue;
                }
                if ($value === '}') {
                    $functionBraceCount--;
                    if ($functionBraceCount === 0) {
                        $isInFunctionDeclaration = false;
                    }
                    continue;
                }

                // skip everything inside function body
                continue;
            }

            // Outside function: track top-level braces
            if ($value === '{') {
                $topLevelBraceCount++;
                continue;
            }
            if ($value === '}') {
                $topLevelBraceCount--;
                if ($topLevelBraceCount === 0) {
                    $isInFunctionDeclaration = false;
                }
                continue;
            }

            if (! $isInFunctionDeclaration && ! in_array($type, $allowedTokens)) {
                return;
            }
        } else {
            if (! $isInFunctionDeclaration && ! in_array(trim($token), $allowedChars) && trim($token) !== '') {
                return; // fail
            }
        }
    }

    if ($hasFunctionDeclaration) {
        $file['safe_fn_declaration'] = true;
    }
}
// }}}

/**
 * @param $file
 */
function is_class_declaration_file(&$file)
{
    // Initially assume the file is not safe
    $file['safe_class_declaration'] = false;

    // Read the file content
    $content = file_get_contents($file['path']);

    // Get all tokens in the file
    $tokens = token_get_all($content);

    $foundClassDeclaration = false;
    $inClassDeclaration = false;
    $foundNonClassTokenOutsideClass = false;
    $braceCount = 0;

    $allowedTokens = [
        T_OPEN_TAG,
        T_WHITESPACE,
        T_COMMENT,
        T_DOC_COMMENT,

        // namespace / imports
        T_NAMESPACE,
        T_USE,
        T_AS,
        T_FUNCTION, // for "use function" like in TikiInit.php

        // names
        T_STRING, // for defined()
        T_IF, // top-level guards like in lib/cypht/modules/tiki/tracker_modules.php and other cypht class
        T_NAME_QUALIFIED,
        T_NAME_FULLY_QUALIFIED,
        T_NAME_RELATIVE,
        T_NS_SEPARATOR,

        // includes
        T_REQUIRE,
        T_REQUIRE_ONCE,
        T_INCLUDE,
        T_INCLUDE_ONCE,

        // constants used in include paths
        T_DIR,
        T_CONSTANT_ENCAPSED_STRING,
        T_LNUMBER, // Numerical constants like in lib/Sheet/excel/writer/validator.php
        T_DNUMBER,

        T_FINAL, // like in TikiPsr18Client.php
        T_ABSTRACT,
        T_READONLY,
        T_CONST,
        T_DECLARE,
        T_ATTRIBUTE, // like in PackageInstallCommand.php
        T_EXIT, // die(), exit(), etc.
    ];
    foreach ($tokens as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM])) {
                $inClassDeclaration = true;
                $foundClassDeclaration = true;
                continue;
            }
            if ($inClassDeclaration) {
                if ($token[0] == T_CURLY_OPEN || $token[1] == '{') {
                    $braceCount++;
                } elseif ($token[1] == '}') {
                    $braceCount--;
                    if ($braceCount === 0) {
                        $inClassDeclaration = false;
                    }
                }
                continue;
            }
            if (! in_array($token[0], $allowedTokens)) {
                $foundNonClassTokenOutsideClass = true;
                break;
            }
        }
    }

    if (! $foundNonClassTokenOutsideClass && $foundClassDeclaration) {
        $file['safe_class_declaration'] = true;
    }
}

/**
 * @param $file
 */
function perform_noweb_check(&$file) // {{{
{
    $pattern = noweb_pattern();

    preg_match_all($pattern, get_content($file['path']), $parts);

    $pattern = cli_sapi_pattern();
    preg_match_all($pattern, get_content($file['path']), $parts1);

    $pattern = httpResponseCode_pattern();
    preg_match_all($pattern, get_content($file['path']), $parts2);

    $file['noweb'] = count($parts[0]) > 0 || count($parts1[0]) > 0 || count($parts2[0]) > 0;
}
// }}}

/**
 * @param $file
 */
function perform_tikisetup_check(&$file) // {{{
{
    $index = 0;

    $pattern = tikisetup_pattern($index);

    preg_match_all($pattern, get_content($file['path']), $parts);

    $file['tikisetup'] = count($parts[0]) > 0;
}
// }}}

/**
 * @param $file
 */
function perform_extract_skip_check(&$file) // {{{
{
    $pattern = "/extract\s*\(([\s\S]*?)(?=\);)/";

    preg_match_all($pattern, get_content($file['path']), $parts);

    foreach ($parts[0] as $extract) {
        if (! str_contains($extract, 'EXTR_SKIP')) {
            $file['unsafeextract'] = true;
        }
    }
}
// }}}

/**
 * @param $file
 * @param $type
 * @return array
 */
function access_check_call($file, $type) // {{{
{
    $content = get_content($file);
    $tokens = token_get_all($content);

    $checks = [];

    foreach ($tokens as $key => $token) {
        if (is_array($token)) {
            if ($token[0] == T_VARIABLE && ($token[1] == '$access' || $token[1] == '$accesslib')) {
                if (
                    $tokens[$key + 1][0] == T_OBJECT_OPERATOR
                    && $tokens[$key + 2][0] == T_STRING && $tokens[$key + 2][1] == $type
                ) {
                    $checks = array_merge($checks, access_checks($tokens, $key + 2));
                }
            }
        }
    }

    return $checks;
}
// }}}

/**
 * @param $tokens
 * @param $from
 * @return array
 */
function access_checks($tokens, $from) // {{{
{
    $end = count($tokens);

    $features = [];

    for ($i = $from; $end > $i; ++$i) {
        $token = $tokens[$i];

        if (is_string($token) && $token == ';') {
            break;
        }

        if (is_array($token) && $token[0] == T_CONSTANT_ENCAPSED_STRING) {
            $features[] = trim($token[1], "\"'");
        }
    }

    return $features;
}
// }}}

/**
 * @param $file
 * @return array
 */
function permission_check_accessors($file) // {{{
{
    $tokens = token_get_all(get_content($file));

    $perms = [];

    foreach ($tokens as $key => $token) {
        if (is_array($token) && ($token[0] == T_IF || $token[0] == T_ELSEIF)) {
            $subset = tokenizer_get_subset($tokens, $key);
            $perms = array_merge($perms, permission_check_condition($subset));
        }
    }

    return $perms;
}
// }}}

/**
 * @param $tokens
 * @param $from
 * @return array
 */
function tokenizer_get_subset($tokens, $from) // {{{
{
    $out = [];

    $started = false;
    $count = 0;
    $end = count($tokens);

    for ($i = $from; $end > $i && (! $started || $count > 0); ++$i) {
        $t = $tokens[$i];

        if (is_string($t)) {
            if ($t == '(') {
                $started = true;
                $count++;
            } elseif ($t == ')') {
                $count--;
            }
        }

        $out[] = $t;
    }

    return $out;
}
// }}}

/**
 * @param $tokens
 * @return array
 */
function permission_check_condition($tokens) // {{{
{
    $permissions = [];

    foreach ($tokens as $i => $t) {
        if ($t[0] == T_VARIABLE) {
            if (str_ends_with($t[1], 'perms')) {
                if ($tokens[$i + 1][0] == T_OBJECT_OPERATOR && $tokens[$i + 2][0] == T_STRING) {
                    $perm = $tokens[$i + 2][1];

                    if (! str_starts_with($perm, 'tiki_p_')) {
                        $perm = 'tiki_p_' . $perm;
                    }

                    $permissions[] = $perm;
                }
            }
        }
    }

    return $permissions;
}
// }}}


include_once('lib/setup/twversion.class.php');
$TWV = new TWVersion();
$major = explode('.', $TWV->version)[0];

function normalize_input_file($path)
{
    $path = ltrim($path, './');

    return './' . $path;
}

function add_specified_files(array $inputFiles, array &$files, array &$filesHash)
{
    global $safePaths, $skipDirs;

    foreach ($inputFiles as $path) {
        $path = normalize_input_file($path);

        if (! is_file($path) || is_link($path)) {
            continue;
        }

        if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
            continue;
        }

        foreach ($skipDirs as $skipDir) {
            if (str_starts_with($path, $skipDir)) {
                continue 2; // skip this directory entirely
            }
        }

        if (regex_match($path, $safePaths)) {
            continue;
        }

        $analysis = analyse_file_path($path);
        $files[] = $analysis;
        $filesHash[$path] = $analysis;
    }
}

/* Build Files structures */
// a hash of filenames, each element is a hash of attributes of that file
$filesHash = [];

// a hash of features, each element is a hash of filenames that use that feature
$features = [];

// note: the files[0..N] is intended to be replaced by the above hash.
$files = [];

// build these two files structures
// If files are passed as arguments, scan only those files.
// Otherwise, scan the full repository.
$inputFiles = array_slice($argv, 1);

if (! empty($inputFiles)) {
    add_specified_files($inputFiles, $files, $filesHash);
} else {
    scanfiles('.', $files, $filesHash);
}

/* Iterate each file, and perform checks */
$unsafe = [];
foreach ($files as $key => $dummy) {
    $file = &$files[$key];

    switch ($file['type']) {
        case 'wikiplugin':
            perform_extract_skip_check($file);

            if ($file['unsafeextract']) {
                $unsafe[] = $file;
            }

            break;
        case 'public':
        case 'include':
        case 'script':
        case 'module':
        case 'lib':
        case '3rdparty':
            perform_feature_check($file);
            perform_permission_check($file);
            perform_includeonly_check($file);
            perform_noweb_check($file);
            perform_tikisetup_check($file);
            is_function_declaration_file($file);
            is_class_declaration_file($file);

            if (
                ! $file['noweb']
                && ! $file['includeonly']
                && ! count($file['features']) && ! count($file['permissions'])
                && ! $file['safe_fn_declaration']
                && ! $file['safe_class_declaration']
            ) {
                $unsafe[] = $file;
            }

            break;
    }
}
if (empty($unsafe)) {
    echo "'All scanned files are safe ✅'.\n";
    exit(0);
}

// Table header
$lineWidth = 70;
echo str_repeat('-', $lineWidth) . "\n";
echo str_pad('#', 5) . str_pad('File Path', $lineWidth - 5) . "\n";
echo str_repeat('-', $lineWidth) . "\n";

// Print unsafe files
foreach ($unsafe as $index => $file) {
    $lineNumber = $index + 1;
    $path = $file['path'];
    echo str_pad($lineNumber, 5) . str_pad($path, $lineWidth - 5) . "\n";
}

echo str_repeat('-', $lineWidth) . "\n";

exit(1);
