<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/***
 *
 * @var \TikiAccessLib  $access
 *
 * @var \AccountingLib  $accountinglib
 *
 *
 * @var \Smarty_Tiki    $smarty
 *
 * Define the current section
 * @var string $section
 */
$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'bookId' => 'int',        //get
            'journalLimit' => 'int',  //post
            'action' => 'striptags',  //get
        ],
    ],
];
$section = 'accounting';
require_once('tiki-setup.php');

// Feature available?
if ($prefs['feature_accounting'] != 'y') {
    $smarty->assign('msg', tra("This feature is disabled") . ": feature_accounting");
    $smarty->assign('required_preferences', ['feature_accounting']);
    $smarty->assign('errortype', \Laminas\Http\Response::STATUS_CODE_401);
    $smarty->display("error.tpl");
    die;
}

if (! isset($_REQUEST['bookId'])) {
    Feedback::errorAndDie(tra("Missing book id"), \Laminas\Http\Response::STATUS_CODE_400);
}
$bookId = $_REQUEST['bookId'];

$globalperms = Perms::get();
$objectperms = Perms::get([ 'type' => 'accounting book', 'object' => $bookId ]);
if (! ($globalperms->acct_view or $objectperms->acct_view)) {
    Feedback::errorAndDie(tra("You do not have the right view this page"), \Laminas\Http\Response::STATUS_CODE_403);
}
$accountinglib = TikiLib::lib('accounting');
try {
    $book = $accountinglib->getBook($bookId);
} catch (Exception $e) {
    Feedback::errorAndDie(tra($e->getMessage()), \Laminas\Http\Response::STATUS_CODE_400);
}
$smarty->assign('book', $book);
$smarty->assign('bookId', $bookId);
$accounts = $accountinglib->getExtendedAccounts($bookId, true);
$smarty->assign('accounts', $accounts);

if (! isset($_REQUEST['journalLimit'])) {
    $_REQUEST['journalLimit'] = -25;
}
$journal = $accountinglib->getJournal($bookId, '%', '`journalId` DESC', $_REQUEST['journalLimit']);
$smarty->assign('journal', $journal);

if ($globalperms->acct_book or $objectperms->acct_book) {
    $smarty->assign('canBook', true);
} else {
    $smarty->assign('canBook', false);
}
if ($globalperms->acct_book_stack or $objectperms->acct_book_stack) {
    $smarty->assign('canStack', true);
} else {
    $smarty->assign('canStack', false);
}

$smarty->assign('mid', 'tiki-accounting.tpl');
$smarty->display("tiki.tpl");
