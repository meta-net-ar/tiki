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
            'bookId'       => 'int',          //post
            'what'         => 'striptags',    //post
            'action'       => 'striptags',    //post
            'separator'    => 'striptags',    //post
            'quote'        => 'striptags',    //post
            'eol'          => 'striptags',    //post
            'accountId'    => 'int',          //post
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

if (! isset($_REQUEST['what'])) {
    Feedback::errorAndDie(tra("Don't know what to export"), \Laminas\Http\Response::STATUS_CODE_409);
}
$bookId = $_REQUEST['bookId'];
$smarty->assign('bookId', $bookId);
$what = $_REQUEST['what'];
$smarty->assign('what', $what);

$globalperms = Perms::get();
$objectperms = Perms::get([ 'type' => 'accounting book', 'object' => $bookId ]);
if (! ($globalperms->acct_view or $objectperms->acct_view)) {
    Feedback::errorAndDie(tra("You do not have the right export/view this data"), \Laminas\Http\Response::STATUS_CODE_403);
}

$accountinglib = TikiLib::lib('accounting');
$book = $accountinglib->getBook($bookId);
$smarty->assign('book', $book);

if (! isset($_REQUEST['action'])) {
    $_REQUEST['action'] = 'settings';
}

if ($_REQUEST['action'] == 'export') {
    $prefs['log_tpl'] = 'n'; // Necessary to get a working css
    $separator = (isset($_REQUEST['separator']) ? $_REQUEST['separator'] : ';');
    $smarty->assign('separator', $separator);
    $eol = (isset($_REQUEST['eol']) ? $_REQUEST['eol'] : "\n");
    $smarty->assign('eol', preg_replace(["/CR/","/LF/"], ["\r","\n"], $eol));
    $quote = (isset($_REQUEST['quote']) ? $_REQUEST['quote'] : '"');
    $smarty->assign('quote', $quote);
    header('Content-type: text/plain');
    switch ($what) {
        case "accounts":
            header('Content-disposition: attachment; filename="accounts.csv"');
                            $accounts = $accountinglib->getExtendedAccounts($bookId, true);
                            $smarty->assign('accounts', $accounts);
                            $smarty->display("tiki-accounting_accounts_csv.tpl");
            die();
        case "journal":
            header('Content-disposition: attachment; filename="journal.csv"');
            if (isset($_REQUEST['accountId'])) {
                $accountId = $_REQUEST['accountId'];
            } else {
                $accountId = '%';
            }
                            $journal = $accountinglib->getJournal($bookId, $accountId, '`journalId` ASC');
                            $smarty->assign('journal', $journal);
                            $smarty->display("tiki-accounting_journal_csv.tpl");
            die();
    }
} else {
    $smarty->assign('mid', 'tiki-accounting_export.tpl');
    $smarty->display("tiki.tpl");
}
