<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'         => [
             'rule_id'             => 'int',          //post
             'rule'                => 'int',          //get
             'new_rule'            => 'bool',         //post
             'rule_regex'          => 'text',         //post
             'editrule'            => 'bool',         //post
             'rule_description'    => 'text',         //post
             'rule_tags'           => 'text',         //post
             'rule_impact'         => 'text',         //post
             'add'                 => 'bool',         //post
        ],
    ],
];
require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN;
Sections::setCurrentSection($section);

if ($prefs['ids_enabled'] == 'n') {
    $access->display_error('', tra("Tiki IDS is not enabled"), '403', false);
}
$ruleinfo = [];

$access->check_permission('tiki_p_admin');

if (isset($_POST['new_rule']) && $access->checkCsrf()) {
    $id = $_POST['rule_id'];
    $rule = new IDS_Rule($id);

    $rule->setRegex($_POST['rule_regex']);
    $rule->setDescription($_POST['rule_description']);
    $rule->setTags($_POST['rule_tags']);
    $rule->setImpact($_POST['rule_impact']);

    //Check if a custom rule with the same ID already existes
    $conflictRule = IDS_Rule::getRule($_POST['rule_id']);
    if (empty($conflictRule)) {
        $rule->save();
        $cookietab = 1;
    } else {
        Feedback::error(tra('A custom rule with the same ID already exists.'));
        $ruleinfo = [
            'id' => $rule->getId(),
            'regex' => $rule->getRegex(),
            'description' => $rule->getDescription(),
            'tags' => implode(', ', $rule->getTags()),
            'impact' => $rule->getImpact(),
            'error' => 1,
        ];
    }
} elseif (isset($_POST['editrule']) && isset($_POST['rule_id']) && $access->checkCsrf()) {
    $rule = IDS_Rule::getRule($_POST['rule_id']);

    $rule->setRegex($_POST['rule_regex']);
    $rule->setDescription($_POST['rule_description']);
    $rule->setTags($_POST['rule_tags']);
    $rule->setImpact($_POST['rule_impact']);
    $rule->save();

    $cookietab = '1';
} elseif (isset($_REQUEST['rule']) && $_REQUEST['rule']) {
    $rule = IDS_Rule::getRule($_REQUEST['rule']);

    if (! empty($rule)) {
        $ruleinfo = [
            'id' => $rule->getId(),
            'regex' => $rule->getRegex(),
            'description' => $rule->getDescription(),
            'tags' => implode(', ', $rule->getTags()),
            'impact' => $rule->getImpact(),
        ];
    } else {
        $ruleinfo = [
            'id' => '',
            'regex' => '',
            'description' => '',
            'tags' => '',
            'impact' => '',
        ];
    }

    $cookietab = 2;
} else {
    $_REQUEST['rule'] = 0;
}

if (isset($_REQUEST['add'])) {
    $cookietab = '2';
}

$idsRules = [];

foreach (IDS_Rule::getAllRules() as $rule) {
    $idsRules[] = [
        'id' => $rule->getId(),
        'regex' => $rule->getRegex(),
        'description' => $rule->getDescription(),
        'tags' => implode(', ', $rule->getTags()),
        'impact' => $rule->getImpact(),
    ];
}

$smarty->assign('ids_rules', $idsRules);
$smarty->assign('ruleinfo', $ruleinfo);
$smarty->assign('ruleId', $_REQUEST['rule']);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');
$smarty->assign('mid', 'tiki-admin_ids.tpl');
$smarty->display('tiki.tpl');
