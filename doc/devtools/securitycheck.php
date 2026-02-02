<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// analyse_file_path groups files by type, e.g. library, etc.

// Usage:
// From Tiki root, run:
// php doc/devtools/securitycheck.php > securityreport.html
// visit securityreport.html (where your Tiki is)
//
// Each PHP file in Tiki should start with a feature check. So if the feature is
// de-activated, the file is dead. If a particular file is discovered to be insecure,
// users can deactivate the feature until they upgrade to the release which contains
// a fix. To avoid forgetting to add this feature check on new files, a feature check
// script has been created. Some files, by design, can't have a feature check and
// these files should be audited manually.
//
//
// Related script:  doc/devtools/prefreport.php
//

if (PHP_SAPI !== 'cli') {
    die;
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

$safePaths = [

    /* Not in build */
    '\./' . DEPRECATED_DEVTOOLS_PATH . '/.*',
    '\./' . TIKI_CONFIG_FILE_PATH,
    '\./' . TIKI_CONFIG_PATH . '/virtuals.inc',
    '\./node_modules/.*',
    '\./_custom_dist/.*',
    '\./_custom/.*',

    '\./public/generated/.*', // generated files
    '\./src/php/external_lib_sources.*', // External php codebases whose source code have been included in tiki.


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

    /* exception files */
    '\./tiki-admin_trackers.php', // perform redirection and target file has its own check.
    '\./tiki-list_users.php', // perform redirection and target file has its own check.
    '\./tiki-mods.php', // template file (no executable code)
    '\./tiki-monitor.php', // shows status info
    '\./tiki-wikiplugin_edit.php', // deprecated file
    '\./get_strings.php', // deprecated file
    '\./installer/shell.php', // deprecated file
    '\./permissioncheck/create_new_htaccess.php', // create new_htaccess file with passwod protection in ./permissioncheck
    '\./xmlrpc.php', // redirect to tiki-xmlrpc_services.php which has its own check
    '\./tiki-download_forum_attachment.php', // no need attach permission for download and has its own checks

    /* language files */
    '\./lang/.*/language_.*\.php', // language files are just definitions
    '\./lang/.*/.*\.php_example',  // language example files are just definitions

    /* The following need to be refactored to a lib */
    '\./tiki-testGD.php',

    /* vendor and vendor_bundled dirs, not tiki files*/
    '\./' . TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH . '/*',
    '\./' . TIKI_VENDOR_NONBUNDLED_PATH . '/*',
];

if (! file_exists('tiki-setup.php')) {
    die("Please run this script from tiki root.\n");
}

include_once('lib/setup/twversion.class.php');
$TWV = new TWVersion();

if (! $TWV->version) {
    die("Could not find version information.\n");
}

$ver = explode('.', $TWV->version);
$major = (count($ver) >= 1) ? $ver[0] : '?';
$minor = (count($ver) >= 2) ? $ver[1] : '?';
$revision = (count($ver) >= 3) ? $ver[2] : '?';

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
function feature_pattern(&$featureNameIndex) // {{{
{
    global $major, $minor, $revision;
    $featureName = "((feature_\w+)|lang_use_db|allowRegister|validateUsers|cachepages)";
    $q = "[\"']";
    if ($major == 1 && $minor == 9) {
        $featureNameIndex = [2, 7];
        $tl = '\\$tikilib->get_preference';
        return "/(\\\${$featureName}\s*(!=|==)=?\s*$q(y|n)[\"'])|($tl\s*\(\s*$q{$featureName}$q\s*(,\s*{$q}n?$q)?\s*\)\s*(==|!=)=?\s*$q(y|n)$q)/";
    } elseif (($major == 1 && $minor == 10) || $major >= 2) {
        $featureNameIndex = 1;
        return "/\\\$prefs\s*\[$q(\w+)$q\]\s*(!=|==)=?\s*$q(y|n)$q/";
    }

    return '';
}
// }}}

/**
 * @param $permissionNameIndex
 * @return string
 */
function permission_pattern(&$permissionNameIndex) // {{{
{
    global $major, $minor, $revision;
    $permissionNameIndex = 1;
    return "/->check_permission\s*\(\s*['\"](tiki_p_\w+)['\"]\s*\)/";
}
// }}}

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
 */
function scanfiles($folder, &$files) // {{{
{
    global $filesHash;
    $handle = opendir($folder);
    if (! $handle) {
        printf("Could not open folder: %s\n", $folder);
        return;
    }

    while (false !== $file = readdir($handle)) {
        // Skip self and parent
        if ($file[0] == '.' || $file[0] == '..') {
            continue;
        }

        $path = "$folder/$file";

        if (is_dir($path)) {
            scanfiles($path, $files);
        } else {
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
        //    print "Matching $path against $possible\n";
        if (preg_match('%' . $possible . '%', $path)) {
            //print "Matches $possible\n\n";
            print "<!-- Found $path in " . join(",", $regex_possibles) . "-->\n";
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
    } elseif (str_starts_with($path, "./" . SMARTY_COMPILED_TEMPLATES_PATH . "/")) {
        $type = 'cache';
    } elseif (regex_match($path, $safePaths)) {
        $type = 'safe';
    } elseif ($extension == 'php' || $extension == 'inc') {
        if ($name == 'index.php') {
            $type = 'blocker';
        } elseif ($name == 'language.php') {
            $type = 'lang';
        } elseif (str_starts_with($path, './lib/wiki-plugins')) {
            $type = 'wikiplugin';
        } elseif (str_starts_with($path, './lib/')) {
            if (regex_match($path, $thirdpartyLibs)) {
                $type = '3dparty';
            } else {
                $type = 'lib';
            }
        } elseif (str_starts_with($path, './tiki-')) {
            $type = 'public';
        } elseif (str_starts_with($path, './modules/')) {
            $type = 'module';
        } else {
            $type = "include";
        }
    } elseif (in_array($extension, ['txt', 'png', 'jpg', 'html', 'css', 'sql', 'gif', 'afm', 'js'])) {
        $type = 'static';
    } elseif (str_starts_with($path, './' . DEPRECATED_DEVTOOLS_PATH . '/')) {
        $type = 'script';
    } elseif (str_starts_with($path, './files/')) {
        $type = 'user';
    } elseif ($extension == 'sh') {
        $type = 'system';
    } elseif (str_contains($path, '_htaccess')) {
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
    $index = [];
    $feature_pattern = feature_pattern($index);
    $index = (array)$index;
    $path = $file['path'];

    preg_match_all($feature_pattern, get_content($path), $parts);

    $featuresInFile = [];
    foreach ($index as $i) {
        $featuresInFile = array_merge($features, $parts[$i]);
    }

    $featuresInFile = array_merge($featuresInFile, access_check_call($path, 'check_feature'));
    $featuresInFile = array_unique($featuresInFile);
    $file['features'] = $featuresInFile;
    //  var_dump($featuresInFile);
    /*
     This data structure seems to be typical, and very confusing.
     An array of 3, with the zeroth element being a named element whose value is an array of one element.
     other elements being named, not numbered

     1array(3) {
     2  ["feature_directory"]=>
     3  array(1) {
     4    [0]=>
     5    string(28) "./tiki-directory_ranking.php"
     6  }
     7  [0]=>
     8  string(18) "feature_html_pages"
     9  [1]=>
     10  string(21) "feature_theme_control"
     11}
    */
    /*
    // store, for each feature, which files are involved
    foreach ($featuresInFile as $feature) {
      if (is_string($feature)) {
        if (preg_match('/feature/', $feature)) {
          // SMELL sure to be a better way to do this.
          //print "Listing as feature $feature\n";
          $featuresListed = (array) $features[$feature];
          array_push($featuresListed, $path);
          $features[$feature] = $featuresListed;
        }
      // TODO SMELL: this regex should not be necessary, it should only contain features at this point.
      // SMELL: it will also miss some vital elements.
      }
    }
    */
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
    $allowedTokens = [
        T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_USE, T_NAME_QUALIFIED,
        T_REQUIRE, T_REQUIRE_ONCE, T_INCLUDE, T_INCLUDE_ONCE, T_OPEN_TAG
    ];

    foreach ($tokens as $token) {
        if (is_array($token)) {
            list($type, $value) = $token;

            if ($type == T_FUNCTION) {
                $isInFunctionDeclaration = true;
                $hasFunctionDeclaration = true;
                continue;
            }

            if ($isInFunctionDeclaration && $value == '{') {
                continue; // Skip tokens inside function declarations
            }

            if ($isInFunctionDeclaration && $value == '}') {
                $isInFunctionDeclaration = false; // End of function declaration
                continue;
            }

            if (! $isInFunctionDeclaration && ! in_array($type, $allowedTokens)) {
                // Log the token that caused the file to be marked as unsafe
                // error_log("Non-allowed token in file {$file['path']}: " . token_name($type) . " - " . $value);
                return;
            }
        } else {
            if (! $isInFunctionDeclaration && trim($token) !== '' && trim($token) !== ';') {
                // Log the non-allowed character found outside of a function declaration
                // error_log("Non-allowed character outside function declaration in file {$file['path']}: " . $token);
                return;
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
function is_class_declaration_file(&$file) // {{{
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
    $allowedTokens = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_NAMESPACE, T_NAME_QUALIFIED, T_USE, T_STRING];

    foreach ($tokens as $token) {
        if (is_array($token)) {
            if (in_array($token[0], [T_CLASS, T_INTERFACE, T_TRAIT])) {
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
                // error_log("Found non-class token outside of class context at token: " . token_name($token[0]) . " - " . $token[1]);
                break;
            }
        }
    }

    if (! $foundNonClassTokenOutsideClass && $foundClassDeclaration) {
        $file['safe_class_declaration'] = true;
    }
}
// }}}

/**
 * @param $file
 */
function perform_noweb_check(&$file) // {{{
{
    $index = 0;
    $pattern = noweb_pattern($index);

    preg_match_all($pattern, get_content($file['path']), $parts);

    $pattern = cli_sapi_pattern($index);
    preg_match_all($pattern, get_content($file['path']), $parts1);

    $pattern = httpResponseCode_pattern($index);
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
            if ('perms' == substr($t[1], -5)) {
                if ($tokens[$i + 1][0] == T_OBJECT_OPERATOR && $tokens[$i + 2][0] == T_STRING) {
                    $perm = $tokens[$i + 2][1];

                    if ('tiki_p_' != substr($perm, 0, 7)) {
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

/* Build Files structures */
// a hash of filenames, each element is a hash of attributes of that file
$filesHash = [];

// a hash of features, each element is a hash of filenames that use that feature
$features = [];

// note: the files[0..N] is intended to be replaced by the above hash.
$files = [];

// build these two files structures
scanfiles('.', $files);
error_reporting(E_ALL);

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

/**
 * @param $a
 * @param $b
 * @return int
 */
function sort_cb($a, $b)
{
    return strcmp($a['path'], $b['path']);
}

usort($files, 'sort_cb');
usort($unsafe, 'sort_cb');

?>
<html>
<head><title>Security Static Checker Output</title></head>
<body>
<p>Tiki Version: <?php echo "$major.$minor.$revision" ?></p>
<p>Audit Date: <?php echo date('Y-m-d H:i:s') ?></p>
<h1>Potentially unsafe files</h1>
<p>
    To be safe, files must have either an include only check, block web access, have a feature check or have a
    permission check. </p>
<ol>
    <?php foreach ($unsafe as $unsafeUrlAndFile) :
        $pathname = $unsafeUrlAndFile['path'];
        $url = substr($unsafeUrlAndFile['path'], 2);
        $fileRecord = $filesHash[$pathname];
        $fileType = $fileRecord['type'];
        ?>
        <li>
            <?php echo $fileType; ?>
            <a href="<?php echo htmlentities($url, ENT_COMPAT) ?>"><?php echo htmlentities($pathname, ENT_COMPAT) ?></a>
        </li>
    <?php endforeach; ?>
</ol>
<h1>All files</h1>
<table border="1">
    <thead>
    <tr style="font-size:x-small">
        <th>File</th>
        <th>Include only check</th>
        <th>Not web accessible</th>
        <th>Includes tiki-setup</th>
        <th>Unsafe extract</th>
        <th>File only declares symbols (functions or classes) do not execute code</th>
        <th>Permissions checked</th>
        <th>Features checked</th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($files as $file) {
        if (in_array($file['type'], ['script', 'module', 'include', 'public', 'lib', '3rdparty', 'wikiplugin'])) : ?>
            <tr>
                <td><a href="<?php echo htmlentities(substr($file['path'], 2), ENT_COMPAT) ?>"><?php echo htmlentities($file['path'], ENT_COMPAT) ?></a></td>
                <td>
                    <?php
                    if (isset($file['includeonly']) && $file['includeonly']) {
                        echo 'X';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if ($file['noweb']) {
                        echo 'X';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if ($file['tikisetup']) {
                        echo 'X';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if ($file['unsafeextract']) {
                        echo 'X';
                    }
                    ?>
                </td>
                <td>
                    <?php
                    if ((isset($file['safe_fn_declaration']) && $file['safe_fn_declaration']) || (isset($file['safe_class_declaration']) && $file['safe_class_declaration'])) {
                        echo 'X';
                    }
                    ?>
                </td>
                <td>
                    <?php foreach ($file['permissions'] as $perm) : ?>
                        <div><?php echo $perm ?></div>
                    <?php endforeach; ?>
                </td>
                <td>
                    <?php foreach ($file['features'] as $feature) : ?>
                        <div><?php echo $feature ?></div>
                    <?php endforeach; ?>
                </td>
            </tr>
        <?php endif;
    };
    ?>
    </tbody>
</table>

<?php
foreach ($features as $featureKey => $featureValue) {
    print "$featureKey :\n";
    foreach ($featureValue as $file) {
        print "<li>$file</li>";
    }
    print "<br/><br/>\n";
} ?>

</body>
</html>

<!-- If you see this in your terminal window it's because you didn't read the usage. See the start of the file. -->
