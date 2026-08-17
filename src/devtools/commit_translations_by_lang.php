<?php

# (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
#
# All Rights Reserved. See copyright.txt for details and a complete list of authors.
# Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
# $Id: commit_translations_by_lang.php  2019-2-22 12:28 PM Axel Mwenze $

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
$language = new Language();
$retour = $langlib->getAllDbTranslations();
$user_translations = [];

function rangeByLang($changes, $lang)
{
    $usernames = [];
    foreach ($changes['translations'] as $change) {
        if ($change['lang'] == $lang) {
            array_push($usernames, $change['user']);
        }
    }
    $usernames = array_unique($usernames, SORT_REGULAR);
    $specific_tring = join(",", $usernames);
    return $specific_tring;
}

/**
 * Generate a unique branch name based on the language
 * @param $branch string
 * @return string The branch name
 */
function generateBranchName($lang)
{
    $uniqueID = substr(md5(uniqid()), 0, 10);
    return "tra-{$lang}-{$uniqueID}";
}

$final_commit_list = [];

foreach ($retour['translations'] as $current_lang) {
    $usernames = rangeByLang($retour, $current_lang['lang']);
    array_push($final_commit_list, ['lang' => $current_lang['lang'], 'usernames' => $usernames]);
}
$final_commit_list = array_unique($final_commit_list, SORT_REGULAR);

foreach ($final_commit_list as $langToWrite) {
    try {
        $language_transl = new LanguageTranslations($langToWrite['lang']);
        $stats = $language_transl->writeLanguageFile(false, true);
    } catch (Exception $e) {
        die("{$e->getMessage()}\n");
    }
}

$langmap = $language::get_language_map();
foreach ($final_commit_list as $trans) {
    $lang_found = $langmap[$trans['lang']];
    array_push($user_translations, ["user" => $trans['usernames'], "lang" => $lang_found, "langdir" => $trans['lang']]);
}
if (has_uncommited_changes("./lang")) {
    echo "There are uncommitted changes pending \n";
    $user_transl_size = count($user_translations);

    // Group translations by language
    $translations_by_lang = [];

    if ($user_transl_size) {
        foreach ($user_translations as $translation) {
            $lang = $translation['langdir'];
            if (empty($translation['user'])) {
                $translation['user'] = 'Anonymous';
            }
            $translations_by_lang[$lang][] = $translation;
        }
    } else {
        $languages = getUncommittedChangesParentFolder("./lang");

        foreach ($languages as $lang) {
            $lang = ltrim($lang, '/');
            $lang_found = $langmap[$lang];

            $translations_by_lang[$lang][] = [
                'lang' => $lang_found,
                'langdir' => $lang,
                'user' => 'Anonymous'
            ];
        }
    }

    // Process each language group
    foreach ($translations_by_lang as $lang => $translations) {
        $description_merge = [];
        $branch_name = generateBranchName($lang);
        $output = checkoutBranch($branch_name, true);

        if ($output) {
            foreach ($translations as $translation) {
                $commit_description = "Automatic commit of {$translation['lang']} translation contributed by {$translation['user']} to https://i18n.tiki.org";
                $description_merge[] = $commit_description;
                $return_value = commit_specific_lang($translation['langdir'], $commit_description);
                echo "Commit: " . $return_value . "\n";
            }

            // Push the branch and create the merge request
            $title_merge = "[TRA] {$lang} - Automatic Merge request of translations contributed to https://i18n.tiki.org";
            push_create_merge_request($title_merge, implode(" , ", $description_merge), "master", getCurrentBranch());
            checkoutBranch("master");
        } else {
            continue;
        }
    }
} else {
    echo "There is no translation to commit";
}
