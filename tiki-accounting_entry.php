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
            'bookId' => 'int',                  //get
            'book' => 'bool',                   //post
            'journal_Year' => 'digits',         //post
            'journal_Month' => 'digits',        //post
            'journal_Day' => 'digits',          //post
            'journalDescription' => 'striptags',//post
            'debitAmount' => 'float',           //post
            'creditAmount' => 'float',          //post
            'debitText' => 'striptags',         //post
            'creditText' => 'striptags',        //post
        ],
    ],
];
$section = 'accounting';
require_once('tiki-setup.php');

// Feature available?
if ($prefs['feature_accounting'] != 'y') {
    Feedback::errorAndDie(tra('This feature is disabled') . ': feature_accounting', \Laminas\Http\Response::STATUS_CODE_403);
}
$bookId = $_REQUEST['bookId'];
$globalperms = Perms::get();
$objectperms = Perms::get([ 'type' => 'accounting book', 'object' => $bookId ]);

if (! ($globalperms->acct_book or $objectperms->acct_book)) {
    Feedback::errorAndDie(tra('You do not have the right to book'), \Laminas\Http\Response::STATUS_CODE_403);
}
if (! isset($_REQUEST['bookId'])) {
    Feedback::errorAndDie(tra('Missing book id'), \Laminas\Http\Response::STATUS_CODE_400);
}
$accountinglib = TikiLib::lib('accounting');
try {
    $book = $accountinglib->getBook($bookId);
} catch (Exception $e) {
    Feedback::errorAndDie(tra($e->getMessage()), \Laminas\Http\Response::STATUS_CODE_400);
}
$smarty->assign('bookId', $bookId);
$smarty->assign('book', $book);

$accounts = $accountinglib->getAccounts($bookId, $all = true);
$smarty->assign('accounts', $accounts);

if (! empty($_POST['journal_Year'])) {
    $journalDate = new DateTime();
    $journalDate->setDate(
        $_POST['journal_Year'],
        $_POST['journal_Month'],
        $_POST['journal_Day']
    );
}

if (isset($_POST['book']) && $access->checkCsrf(true)) {
    $result = $accountinglib->book(
        $bookId,
        $journalDate,
        $_POST['journalDescription'],
        $_POST['debitAccount'],
        $_POST['creditAccount'],
        $_POST['debitAmount'],
        $_POST['creditAmount'],
        $_POST['debitText'],
        $_POST['creditText']
    );
    if (is_numeric($result)) {
        if (isset($_POST['statementId'])) {
            $accountinglib->updateStatement($bookId, $_POST['statementId']);
        }
    }
} else {
    $result = 0;
}

if (is_array($result)) {
    Feedback::error(['mes' => $result]);
    $smarty->assign('journalDate', $journalDate);
    $smarty->assign('journalDescription', $_POST['journalDescription']);
    $smarty->assign('debitAccount', $_POST['debitAccount']);
    $smarty->assign('creditAccount', $_POST['creditAccount']);
    $smarty->assign('debitAmount', $_POST['debitAmount']);
    $smarty->assign('creditAmount', $_POST['creditAmount']);
    $smarty->assign('debitText', $_POST['debitText']);
    $smarty->assign('creditText', $_POST['creditText']);
    if (isset($_POST['statementId'])) {
        $smarty->assign('statementId', $_POST['statementId']);
    }
} else {
    if (is_numeric($result) && $result > 0) {
        Feedback::success(tr('Journal %0 successfully recorded in book %1', $result, $book['bookName']));
    }
    $smarty->assign('debitAccount', ['']);
    $smarty->assign('creditAccount', ['']);
    $smarty->assign('debitAmount', ['']);
    $smarty->assign('creditAmount', ['']);
    $smarty->assign('debitText', ['']);
    $smarty->assign('creditText', ['']);
}

$journal = $accountinglib->getJournal($bookId, '%', '`journalId` DESC', 5);
$smarty->assign('journal', $journal);

$smarty->assign('req_url', $_SERVER['REQUEST_URI']);
$smarty->assign('mid', 'tiki-accounting_entry.tpl');
$smarty->display('tiki.tpl');
