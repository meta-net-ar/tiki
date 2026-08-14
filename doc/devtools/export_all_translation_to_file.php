<?php

/**
 * Created by PhpStorm.
 * User: Alexandre
 * Date: 2/22/2019
 * Time: 12:28 PM
 */

//die("REMOVE THIS LINE TO USE THE SCRIPT.\n");

//if (! isset($argv[1])) {
//    echo "\nUsage: php export_all_translations_to_file.php\n";
//    echo "Example: php export_translations_to_file.php\n";
//    die;
//}

require_once('tiki-setup.php');
require_once('lang/langmapping.php');
require_once('lib/language/Language.php');
require_once('lib/language/LanguageTranslations.php');
require_once('gittools.php');



$langlib = new LanguageTranslations();
$langguage = new Language();
$retour = $langlib->getAllDbTranslations();
$user_translations = [];
foreach ($retour['translations'] as $trans) {
    $langmap = $langguage::get_language_map();
    $lang_found = $langmap[$trans['lang']];
    array_push($user_translations, ["user" => $trans['user'], "lang" => $lang_found]);
}
$user_translations = array_unique($user_translations, SORT_REGULAR);
$final_phrase = "";

foreach ($user_translations as $all_translations) {
    $final_phrase .= "[TRA] Automatic commit of $all_translations[lang] translation contributed By $all_translations[user] to http://i18n.tiki.org \n";
}

/**
 * @param $path
 * @return object
 */
function get_info($path)
{
    $esc = escapeshellarg($path);
    $info = @simplexml_load_string(shell_exec("svn info --xml $esc 2> /dev/null"));
    return $info;
}

/**
 * This is taken from the former svntools.php.  It hasn't been functional since before then, but is added back here to the intent is known if someone wants to restore this - benoitg - 2026-01-22
 * Commit lang files
 * @param $msg
 * @param bool $displaySuccess
 * @param bool $dieOnRemainingChanges
 * @return int
 */
function commit_lang($msg, $displaySuccess = true, $dieOnRemainingChanges = true)
{
    $msg = escapeshellarg($msg);
    shell_exec('svn ci ./lang -m $msg');

    if ($dieOnRemainingChanges && has_uncommited_changes('./lang')) {
        error("Commit seems to have failed. Uncommited changes exist in the working folder.\n");
    }

    return (int)get_info('./lang')->entry->commit['revision'];
}


if (has_uncommited_changes(".")) {
    $langlib = new LanguageTranslations();
    echo "there is uncommitted changes \n";
    $retour = $langlib->getDbTranslations();
     $return_value = commit_lang($final_phrase);
     echo "commit :  " . $return_value;
} else {
    echo "There is not translation to commit";
}
