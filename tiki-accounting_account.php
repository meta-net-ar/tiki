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
            'bookId' => 'int',          //get
            'action' => 'alpha',        //post
            'newAccountId' => 'int',    //post
            'accountId' => 'digits',      //post
            'accountName' => 'text',    //post
            'accountNotes' => 'text',   //post
            'accountBudget' => 'float', //post
            'accountLocked' => 'int',  //post
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
$accountinglib = TikiLib::lib('accounting');

try {
    $book = $accountinglib->getBook($bookId);
} catch (Exception $e) {
    Feedback::errorAndDie(tra($e->getMessage()), \Laminas\Http\Response::STATUS_CODE_400);
}

$smarty->assign('bookId', $bookId);
$smarty->assign('book', $book);

$globalperms = Perms::get();
$objectperms = Perms::get([ 'type' => 'accounting book', 'object' => $bookId ]);

if (! isset($_REQUEST['action'])) {
    $_REQUEST['action'] = '';
}

if ($_REQUEST['action'] != 'new' and ! isset($_REQUEST['accountId'])) {
    Feedback::errorAndDie(tra("Missing account id"), \Laminas\Http\Response::STATUS_CODE_400);
}

$smarty->assign('action', $_REQUEST['action']);

if ($_REQUEST['action'] == '' or $_REQUEST['action'] == 'view') {
    if (
        ! ($globalperms->acct_view or $objectperms->acct_view or
            $globalperms->acct_book or $objectperms->acct_book)
    ) {
        Feedback::errorAndDie(tra("You do not have the rights to view this account"), \Laminas\Http\Response::STATUS_CODE_403);
    }
} else {
    if (! ($globalperms->acct_manage_accounts or $objectperms->acct_manage_accounts)) {
        Feedback::errorAndDie(tra("You do not have the rights to manage accounts"), \Laminas\Http\Response::STATUS_CODE_403);
    }
}
if (! empty($_REQUEST['accountId'])) {
    $accountId = $_REQUEST['accountId'];
    try {
        $account = $accountinglib->getAccount($bookId, $accountId);
    } catch (Exception $e) {
        Feedback::errorAndDie(tra($e->getMessage()), \Laminas\Http\Response::STATUS_CODE_400);
    }

    $smarty->assign('accountId', $accountId);
    $journal = $accountinglib->getJournal($bookId, $accountId);
    $smarty->assign('journal', $journal);
}
$template = "tiki-accounting_account_view.tpl";
if (! empty($_REQUEST['action'])) {
    /***
     * Account Notes
     * @var Ambiguous $notes
     */
    $notes = ! empty($_POST['accountNotes']) ? $_POST['accountNotes'] : '';
    switch ($_REQUEST['action']) {
        case 'edit':
            $template = "tiki-accounting_account_form.tpl";
            if (isset($_POST['accountName']) && $access->checkCsrf()) {
                if (! isset($_POST['newAccountId'])) {
                    $_POST['newAccountId'] = $accountId;
                }
                $result = $accountinglib->updateAccount(
                    $bookId,
                    $accountId,
                    $_POST['newAccountId'],
                    $_POST['accountName'],
                    $_POST['accountNotes'],
                    $_POST['accountBudget'],
                    $_POST['accountLocked'],
                    0 /*$_REQUEST['accountTax'] */
                );
                if ($result !== true) {
                    Feedback::error(['mes' => $result]);
                } else {
                    $smarty->assign('action', 'view');
                    $template = "tiki-accounting_account_view.tpl";
                    Feedback::success(tr(
                        '%0 account in book %1 modified',
                        htmlspecialchars($_POST['accountName']),
                        $bookId
                    ));
                }
            }
            try {
                $account = $accountinglib->getAccount($bookId, $accountId);
            } catch (Exception $e) {
                Feedback::errorAndDie(tra($e->getMessage()), \Laminas\Http\Response::STATUS_CODE_400);
            }
            $smarty->assign('account', $account);
            break;
        case 'new':
            $template = "tiki-accounting_account_form.tpl";
            $account = ['changeable' => true];
            try {
                if (isset($_POST['accountName']) && $access->checkCsrf()) {
                    $result = $accountinglib->createAccount(
                        $bookId,
                        $_POST['newAccountId'],
                        $_POST['accountName'],
                        $_POST['accountNotes'],
                        $_POST['accountBudget'],
                        $_POST['accountLocked'],
                    );
                    if ($result === true) {
                        $smarty->assign('action', 'view');
                        $template = "tiki-accounting_account_view.tpl";
                        Feedback::success(tr(
                            '%0 account created for book %1',
                            $_POST['accountName'],
                            $book["bookName"]
                        ));
                        // Set account details after successful creation
                        $account = [
                            'accountBookId' => $bookId,
                            'accountId' => $_POST['newAccountId'],
                            'accountName' => $_POST['accountName'],
                            'accountNotes' => $_POST['accountNotes'],
                            'accountBudget' => $_POST['accountBudget'],
                            'accountLocked' => $_POST['accountLocked'],
                            'changeable' => true // still changeable as it's newly created
                        ];
                    }
                }
            } catch (Exception $e) {
                Feedback::error(['mes' => $e->getMessage()]);
            }
            $smarty->assign('account', $account);
            break;
        case 'lock':
            if ($account['accountLocked']) {
                $successMsg = tr('Account %0 in book %1 unlocked', $account['accountName'], $bookId);
                $errorMsg = tr('Account %0 in book %1 not unlocked', $account['accountName'], $bookId);
            } else {
                $successMsg = tr('Account %0 in book %1 locked', $account['accountName'], $bookId);
                $errorMsg = tr('Account %0 in book %1 not locked', $account['accountName'], $bookId);
            }
            if ($access->checkCsrf()) {
                $result = $accountinglib->changeAccountLock($bookId, $accountId);
                if ($result) {
                    Feedback::success($successMsg);
                } else {
                    Feedback::error($errorMsg);
                }
            }
            $smarty->assign('account', $account);
            $template = "tiki-accounting_account_view.tpl";
            break;
        case 'delete':
            try {
                $smarty->assign('account', $account);
                if (! $access->checkCsrf(true)) {
                    throw new Exception(tr("CSRF check failed. Operation not allowed."));
                }
                $accountinglib->deleteAccount($bookId, $accountId);
                Feedback::success(tr('%0 account deleted from book %1', $account['accountName'], $book["bookName"]));
                $template = "tiki-accounting.tpl";
                $accounts = $accountinglib->getExtendedAccounts($bookId, true);
                $smarty->assign('accounts', $accounts);
            } catch (Exception $e) {
                Feedback::error(['mes' => $e->getMessage()]);
                $smarty->assign('action', 'new');
                $smarty->assign('account', $account);
                $template = "tiki-accounting_account_form.tpl";
            }
            break;
    }
}
$smarty->assign('account', $account);
$smarty->assign('mid', $template);
$smarty->display("tiki.tpl");
