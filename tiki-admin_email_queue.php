<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\EmailQueue\EmailQueueLib;

$inputConfiguration = [
    [
        'staticKeyFilters' => [
            'offset' => 'digits',
            'maxRecords' => 'digits',
            'removeevent' => 'digits',
            'find' => 'striptags',
        ],
        'staticKeyFiltersForArrays' => [
            'checked' => 'alnum',
        ],
    ]
];

require_once('tiki-setup.php');
use Tiki\Sections;
$section = Sections::SECTION_ADMIN;
Sections::setCurrentSection($section);
$access->check_permission(['tiki_p_admin']);

$emailQueuelib = new EmailQueueLib();

$auto_query_args = [
    'offset',
    'sort_mode',
    'find',
    'maxRecords'
];

if (! empty($_REQUEST['remove']) && $access->checkCsrf(true)) {
    $result = $emailQueuelib->deleteEmailQueue($_REQUEST['remove']);
    if ($result && $result->numRows()) {
        Feedback::success(tr('Mail queue event deleted'));
    } else {
        Feedback::error(tr('Mail queue event not deleted'));
    }
}
if (! empty($_REQUEST['redeliver']) && $access->checkCsrf(true)) {
    $result = $emailQueuelib->resetAttemptsOfEmailQueue($_REQUEST['redeliver']);
    if ($result && $result->numRows()) {
        Feedback::success(tr('One mail queue has been scheduled to redeliver.'));
    } else {
        Feedback::error(tr('One mail queue has not been scheduled to redeliver.'));
    }
}

if (
    isset($_REQUEST['action'])
    && $_REQUEST['action'] === 'redeliver'
    && isset($_REQUEST['checked'])
    && $access->checkCsrf(true)
) {
    $i = 0;
    foreach ($_REQUEST['checked'] as $id) {
        $result = $emailQueuelib->resetAttemptsOfEmailQueue($id);
        if ($result && $result->numRows()) {
            $i++;
        }
    }
    $checkedCount = count($_REQUEST['checked']);
    if ($checkedCount === $i) {
        $msg = $i == 1 ? tr('One mail queue has been scheduled to redeliver.') : tr('%0 mail queues has been scheduled to redeliver.', $i);
        Feedback::success($msg);
    } elseif ($i < $checkedCount) {
        Feedback::error(tr('%0 of %1 selected mail queue has been scheduled to redeliver', $i, $checkedCount));
    }
}

if (
    isset($_REQUEST['action'])
    && $_REQUEST['action'] === 'delete'
    && isset($_REQUEST['checked'])
    && $access->checkCsrf(true)
) {
    $i = 0;
    foreach ($_REQUEST['checked'] as $id) {
        $result = $emailQueuelib->deleteEmailQueue($id);
        if ($result && $result->numRows()) {
            $i++;
        }
    }
    $checkedCount = count($_REQUEST['checked']);
    if ($checkedCount === $i) {
        $msg = $i == 1 ? tr('One mail queue events deleted') : tr('%0 mail queues deleted', $i);
        Feedback::success($msg);
    } elseif ($i < $checkedCount) {
        Feedback::error(tr('%0 of %1 selected mail queue deleted', $i, $checkedCount));
    }
}

if (! isset($_REQUEST['offset'])) {
    $offset = 0;
} else {
    $offset = $_REQUEST['offset'];
}
$smarty->assign_by_ref('offset', $offset);
if (! empty($prefs['zend_email_queue_max_retries'])) {
    $find = $prefs['zend_email_queue_max_retries'];
} else {
    $find = '';
}
$smarty->assign_by_ref('find', $find);
if (! empty($_REQUEST['maxRecords'])) {
    $maxRecords = $_REQUEST['maxRecords'];
} else {
    $maxRecords = 100;
}
$sort_mode = 'messageId_desc';
$smarty->assign_by_ref('maxRecords', $maxRecords);
$mailQueues = $emailQueuelib->listStalledEmailQueues($offset, $maxRecords, $sort_mode, $find);
$smarty->assign_by_ref('cant', $mailQueues['cant']);
$smarty->assign_by_ref('total_cant', $mailQueues['total_cant']);
$smarty->assign_by_ref('max_retries', $mailQueues['max_retries']);

foreach ($mailQueues['data'] as $key => $message) {
    try {
        $mail = unserialize($message['message']);
        $headers = $mail->getHeaders();
        $date = '';
        $subject = '';
        $destinations = [];
        foreach ($mail->getTo() as $destination) {
            $destinations[] = $destination->getAddress();
        }
        if ($headers->has('date')) {
            $date = date('Y-m-d H:i:s', strtotime($headers->get('date')->getFieldValue()));
        }
        if ($headers->has('subject')) {
            $subject = $mail->getSubject();
        }

        // Ensure body is a string for template truncate modifier
        $textBody = '';
        $htmlBody = '';
        if (method_exists($mail, 'getTextBody')) {
            $textBody = $mail->getTextBody() ?: '';
        }
        if (method_exists($mail, 'getHtmlBody')) {
            $htmlBody = ! empty($mail->getHtmlBody()) ? trim(strip_tags($mail->getHtmlBody())) : '';
        }
        $message['date'] = $date;
        $message['destination'] = implode(',', $destinations);
        $message['subject'] = $subject;
        $message['body'] = $textBody . $htmlBody;
        $mailQueues['data'][$key] = $message;
    } catch (Exception $e) {
        Feedback::error($e->getMessage(), $i, $checkedCount);
    }
}

$smarty->assign_by_ref('mailQueues', $mailQueues['data']);

// disallow robots to index page:
$smarty->assign('metatag_robots', 'NOINDEX, NOFOLLOW');

// Display the template
$smarty->assign('mid', 'tiki-admin_email_queue.tpl');
$smarty->display('tiki.tpl');
