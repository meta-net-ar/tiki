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
            'journalId' => 'int',     //get
        ],
    ],
];
$section = 'accounting';
require_once('tiki-setup.php');

// Feature available?
if ($prefs['feature_accounting'] != 'y') {
    Feedback::errorAndDie(tra("This feature is disabled") . ": feature_accounting", \Laminas\Http\Response::STATUS_CODE_403);
}

if (! isset($_REQUEST['bookId'])) {
    Feedback::errorAndDie(tra("Missing book id"), \Laminas\Http\Response::STATUS_CODE_400);
}
$bookId = $_REQUEST['bookId'];
$smarty->assign('bookId', $bookId);

if (! isset($_REQUEST['journalId'])) {
    Feedback::errorAndDie(tra("Missing journal id."), \Laminas\Http\Response::STATUS_CODE_400);
}
$journalId = $_REQUEST['journalId'];
$smarty->assign('journalId', $journalId);

$globalperms = Perms::get();
$objectperms = Perms::get([ 'type' => 'accounting book', 'object' => $bookId ]);
if (! ($globalperms->acct_view or $objectperms->acct_book)) {
    Feedback::errorAndDie(tra("You do not have the right to cancel transactions"), \Laminas\Http\Response::STATUS_CODE_403);
}

$accountinglib = TikiLib::lib('accounting');
$book = $accountinglib->getBook($bookId);
$smarty->assign('book', $book);

$entry = $accountinglib->getTransaction($bookId, $journalId);
if ($entry === false) {
    Feedback::errorAndDie(tra("Error retrieving data from journal."), \Laminas\Http\Response::STATUS_CODE_500);
}
$smarty->assign('entry', $entry);

if ($access->checkCsrf(true)) {
    $accountinglib->cancelTransaction($bookId, $journalId);
    if (! empty($errors)) {
        Feedback::error(['mes' => $errors]);
    } else {
        Feedback::success(tr('Journal shown below successfully canceled'));
    }
}

$smarty->assign('mid', 'tiki-accounting_cancel.tpl');
$smarty->display("tiki.tpl");
