<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
        'pref'                     => 'string',          //get
        'save'                     => 'bool',            //post
        ],
        'staticKeyFiltersForArrays' => [
            'new_val'               => 'text',       //post
            'additional_languages'  => 'lang',       //post
        ],
    ],
];

require_once('tiki-setup.php');

$access->check_feature('feature_multilingual');
$access->check_permission('tiki_p_admin');

$multilingualLib = TikiLib::lib('multilingual');
$languageLib     = TikiLib::lib('language');
$prefsLib        = TikiLib::lib('prefs');
$usedLanguages   = [];
$translatedVal   = [];
$defaultLanguage = $prefs['site_language'] ? $prefs['site_language'] : 'en';

if (empty($_REQUEST['pref'])) {
    Feedback::errorAndDie(tra('No preference given.'), \Laminas\Http\Response::STATUS_CODE_409);
}

$preference      = $_REQUEST['pref'];
$definition      = $prefsLib->getPreference($preference);

if (! isset($definition['translatable']) || $definition['translatable'] != 'y') {
    $smarty->assign('msg', tra('This preference is not translatable.'));
    Feedback::errorAndDie(tra('This preference is not translatable.'), \Laminas\Http\Response::STATUS_CODE_409);
}

if (isset($_POST['save']) && $access->checkCsrf()) {
    if (! empty($preference)) {
        foreach ($_POST['new_val'] as $lang => $val) {
            $prefsLib->setTranslatedPreference($preference, $lang, $val, $defaultLanguage);
        }
    }
}

$translatedVal[$defaultLanguage] = $prefsLib->getTranslatedPreference($preference, $defaultLanguage);
$preferredLanguages = $multilingualLib->preferredLangs();

foreach ($preferredLanguages as $l) {
    $usedLanguages[$l] = true;
    if ($l != $defaultLanguage) {
        $translatedVal[$l] = $prefsLib->getTranslatedPreference($preference, $l);
    }
}

if (
    array_key_exists('additional_languages', $_POST) && is_array($_POST['additional_languages'])
    && $access->checkCsrf()
) {
    foreach ($_POST['additional_languages'] as $lang) {
        if ($lang != $defaultLanguage) {
            $usedLanguages[$lang] = true;
            $translatedVal[$lang] = $prefsLib->getTranslatedPreference($preference, $lang);
        }
    }
}

$usedLanguages = array_keys($usedLanguages);
$allLanguages = $languageLib->list_languages();

$smarty->assign('pref', $preference);
$smarty->assign('languageList', $usedLanguages);
$smarty->assign('translated_val', $translatedVal);
$smarty->assign('fullLanguageList', $allLanguages);
$smarty->assign('default_language', $defaultLanguage);

// Display the template
$smarty->assign('mid', 'tiki-preference_translate.tpl');
$smarty->display("tiki.tpl");
