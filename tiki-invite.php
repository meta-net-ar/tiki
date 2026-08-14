<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Lib\TikiDate;

$inputConfiguration = [
    [
        'staticKeyFilters'                => [
        'loadprevious'                    => 'int',              //post
        'emailslist'                      => 'xss',              //post
        'emailslist_format'               => 'word',              //post
        'emailsubject'                    => 'text',              //post
        'emailcontent'                    => 'xss',               //post
        'wikicontent'                     => 'xss',               //post
        'wikipageafter'                   => 'pagename',          //post
        'send'                            => 'bool',              //post
        'confirm'                         => 'bool',              //post
        ],'staticKeyFiltersForArrays' => [
        'invitegroups'                    => 'string',            //post
        ],
    ],
];
require_once('tiki-setup.php');
$access->check_feature('feature_invite');
$access->check_permission('tiki_p_invite');

require_once('lib/webmail/tikimaillib.php');

@ini_set('max_execution_time', 0);
$prefs['feature_wiki_protect_email'] = 'n'; //not to alter the email

/* csv format: lastname,firstname,mail */
/**
 * @param $bloc
 * @return array
 */
function parsemails_csv($bloc)
{
    $results = [];
    $ignored = [];
    $lines = preg_split('/[\n\r]+/', $bloc);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $l = array_map('trim', explode(',', $line));

        // Require lastname, firstname and email to safely access indexes 0–2
        if (count($l) < 3) {
            $ignored[] = $line;
            continue;
        }

        $r = [];
        $r['lastname'] = trim($l[0]);
        $r['firstname'] = trim($l[1]);
        $r['email'] = trim($l[2]);
        if (str_contains($r['email'], '@')) {
            $results[] = $r;
        }
    }
    return [$results, $ignored];
}

/* everything format */
/**
 * @param $bloc
 * @return array
 */
function parsemails_all($bloc)
{
    $bloc = str_replace("\r\n", "\n", $bloc);
    $bloc = str_replace("\n\r", "\n", $bloc);
    $bloc = str_replace("\r", "\n", $bloc);
    $mails = preg_split('/[^a-zA-Z0-9@._-]/', $bloc);
    $results = [];
    foreach ($mails as $m) {
        $m = trim($m);
        if (! str_contains($m, '@')) {
            continue;
        }
        if (! str_contains($m, '.')) {
            continue;
        }
        $r = [];
        $r['lastname'] = '';
        $r['firstname'] = '';
        $r['email'] = $m;
        $results[] = $r;
    }
    return $results;
}

$previous = [];
$res = $tikilib->query("SELECT * FROM `tiki_invite` ORDER BY `ts` DESC");
$tikidate = new TikiDate();
while (is_array($row = $res->fetchRow())) {
    $tikidate->setDate($row['ts']);
    $row['datetime'] = $tikidate->format('%c');
    $previous[$row['id']] = $row;
}
$smarty->assign('previous', $previous);

if (! empty($_REQUEST['loadprevious']) && isset($previous[(int)$_REQUEST['loadprevious']])) {
    $prev = $previous[(int)$_REQUEST['loadprevious']];
    $res = $tikilib->query("SELECT * FROM `tiki_invited` WHERE id_invite=?", [(int)$_REQUEST['loadprevious']]);
    $prev_invited = "";
    while (is_array($row = $res->fetchRow())) {
        $prev_invited .= $row['lastname'] . ',' . $row['firstname'] . ',' . $row['email'] . "\n";
    }

    $_REQUEST['emailslist'] = $prev_invited;
    $_REQUEST['emailslist_format'] = 'csv';
    $_REQUEST['emailsubject'] = $prev['emailsubject'];
    $_REQUEST['emailcontent'] = $prev['emailcontent'];
    $_REQUEST['wikicontent'] = $prev['wikicontent'];
    $_REQUEST['wikipageafter'] = $prev['wikipageafter'];
    $_REQUEST['invitegroups'] = explode(',', $prev['groups']);
}

$user_details = $userlib->get_user_details($user);
$allgroups = $userlib->get_groups(0, -1, 'groupName_desc', '', '', 'n');
$invitegroups = [];
foreach ($allgroups['data'] as $agroup) {
    $invitegroups[$agroup['groupName']] = $agroup['groupDesc'];
}
$smarty->assign("invitegroups", $invitegroups);
$smarty->assign("usergroups", $user_details['groups']);


if (isset($_REQUEST['send'])) {
    $_text = $_REQUEST["emailcontent"];
    $_text = str_replace("\r\n", "\n", $_text);
    $_text = str_replace("\n\r", "\n", $_text);
    $_text = str_replace("\r", "\n", $_text);

    if (strpos($_REQUEST['emailcontent'], '{link}') === false) {
        Feedback::error(tra("The email content must include the {link} placeholder for the invitation link."));
        $_REQUEST['send'] = false;
    }

    $mails = $_REQUEST["emailslist"];
    $ignoredLines = [];
    switch ($_REQUEST['emailslist_format']) {
        case 'all':
            $emails = parsemails_all($mails);
            break;
        case 'csv':
            $parsed = parsemails_csv($mails);
            $emails = $parsed[0];
            $ignoredLines = $parsed[1];
            break;
        default:
            $emails = [];
    }
    if (! empty($ignoredLines) && empty($_REQUEST['confirm'])) {
        Feedback::warning(
            tra('Some CSV lines were ignored because they are not in the expected format (lastname, firstname, email):')
            . '<br><pre>' . htmlspecialchars(implode("\n", $ignoredLines)) . '</pre>'
        );
    }

    $igroups = $_REQUEST['invitegroups'] ?? [];

    if (! empty($_REQUEST['confirm'])) {
        $tikilib->query(
            "INSERT INTO `tiki_invite` (inviter, `groups`, ts, emailsubject,emailcontent,wikicontent,wikipageafter) VALUES (?,?,?,?,?,?,?)",
            [
                $user,
                count($igroups) ? implode(',', $igroups) : null,
                $tikilib->now,
                $_REQUEST['emailsubject'],
                $_REQUEST['emailcontent'],
                $_REQUEST['wikicontent'],
                empty($_REQUEST['wikipageafter']) ? null : $_REQUEST['wikipageafter'],
            ]
        );

        $id = $tikilib->lastInsertId();
        $values = [];
        $bindvars = [];

        foreach ($emails as $m) {
            $values[] = "(?, ?, ?, ?, ?)";
            array_push($bindvars, $id, $m['email'], $m['firstname'], $m['lastname'], "no");
        }

        $query = "INSERT INTO `tiki_invited` (id_invite, email, firstname, lastname, used) VALUES " . implode(',', $values);
        $tikilib->query($query, $bindvars);

        foreach ($emails as $m) {
            $mail = new TikiMail();
            $mail->setFrom($prefs['sender_email']);
            $mail->setSubject($_REQUEST["emailsubject"]);
            $url = TikiLib::tikiUrl('tiki-invited.php', ['invite' => $id, 'email' => $m['email']]);
            $text = $_text;
            $text = str_replace('{link}', $url, $text);
            $text = str_replace('{email}', $m['email'], $text);
            $text = str_replace('{firstname}', $m['firstname'], $text);
            $text = str_replace('{lastname}', $m['lastname'], $text);
            $mail->setText($text);
            $mail->send([$m['email']]);
        }

        $smarty->assign('sentresult', true);
    }
    $smarty->assign('emails', $emails);
}


$smarty->assign('mid', 'tiki-invite.tpl');
$smarty->display("tiki.tpl");
