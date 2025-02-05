<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'save'                        => 'bool',           //post
            'code'                        => 'string',         //post
            'value'                       => 'digits',         //post
            'percent'                     => 'word',           //post
            'id'                          => 'digits',         //post
            'del'                         => 'digits',         //get
            'offset'                      => 'digits',         //get
            'max'                         => 'digits',         //post
        ],
    ],
];
include_once('tiki-setup.php');
global $discountlib;
include_once('lib/payment/discountlib.php');
$access->check_permission(['tiki_p_admin']);

$auto_query_args = [];
$tab = 1;
if (! empty($_REQUEST['save']) && ! empty($_REQUEST['code'])) {
    $access->checkCsrf();
    if (empty($_REQUEST['value']) && ! empty($_REQUEST['percent'])) {
        $_REQUEST['percent'] = min(100, (int)$_REQUEST['percent']);
        $_REQUEST['value'] = $_REQUEST['percent'] . '%';
    } elseif (! empty($_REQUEST['value'])) {
        $_REQUEST['value'] = (int)$_REQUEST['value'];
    }
    if (! empty($_REQUEST['value'])) {
        $default = ['id' => 0];
        $_REQUEST = array_merge($default, $_REQUEST);
        if (! $discountlib->replace_discount($_REQUEST)) {
            Feedback::error(tra('Discount code already exists'));
            $smarty->assign_by_ref('info', $_REQUEST);
            $tab = 2;
        } else {
            unset($_REQUEST['id']);
            $tab = 1;
        }
    } else {
        Feedback::error(tra('Please provide a discount value'));
        $smarty->assign_by_ref('info', $_REQUEST);
        $tab = 2;
    }
}
if (! empty($_REQUEST['del'])) {
    $access->checkCsrf(false);
    $discountlib->del_discount($_REQUEST['del']);
    $tab = 1;
}

if (! empty($_REQUEST['id'])) {
    if ($info = $discountlib->get_discount($_REQUEST['id'])) {
        if (strstr($info['value'], '%')) {
            $info['percent'] = (int)$info['value'];
        }
        $smarty->assign_by_ref('info', $info);
        $tab = 1;
    }
}

$offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0;
$max = $prefs['maxRecords'];
$discounts = $discountlib->list_discounts($offset, $max);
$discounts['offset'] = $offset;
$discounts['max'] = $max;
$smarty->assign_by_ref('discounts', $discounts);

setcookie('tab', $tab);
$smarty->assign_by_ref('cookietab', $tab);
$smarty->assign('mid', 'tiki-discount.tpl');
$smarty->display('tiki.tpl');
