<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
$inputConfiguration = [
    [
        'staticKeyFilters'                    => [
                'action'                      => 'alpha',          //post
                'tokenId'                     => 'int',            //post
        ],
    ],
];
require_once('tiki-setup.php');
require_once('lib/auth/tokens.php');

$access->check_feature('auth_token_access');
$access->check_permission('tiki_p_admin');

$tokenlib = AuthTokens::build($prefs);
global $base_url;
$action = '';
$tokenId = 0;
$smarty->assign('tokenCreated', false);

if (isset($_REQUEST['action'])) {
    $action = $_REQUEST['action'];
}

if (isset($_REQUEST['tokenId']) && is_numeric($_REQUEST['tokenId'])) {
    $tokenId = $_REQUEST['tokenId'];
}

if ($action == 'delete' && $tokenId > 0) {
    $tokenlib->deleteToken($_REQUEST['tokenId']);
}

if ($action == 'add') {
    $url = filter_input(INPUT_POST, 'entry', FILTER_SANITIZE_URL);
    $entry = parse_url($url, PHP_URL_PATH);
    $groups = $_POST['groups'];
    $sanitizedGroups = array_map(function ($group) {
        return filter_var($group, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    }, $groups);
    $parameters = [];
    $query = parse_url($url, PHP_URL_QUERY);

    if (! empty($query)) {
        parse_str($query, $parameters); // Automatically handles arrays and key-value pairs
    }

    $arguments = [];
    $arguments['timeout'] = filter_input(INPUT_POST, 'timeout', FILTER_SANITIZE_NUMBER_INT);
    $arguments['hits'] = filter_input(INPUT_POST, 'maxhits', FILTER_SANITIZE_NUMBER_INT);

    if (! empty($entry) && ! empty($groups)) {
        $token = $tokenlib->createToken($entry, $parameters, $groups, $arguments);

        if (! empty($token)) {
            $smarty->assign('tokenCreated', true);
        }
    }
}

$tokens = $tokenlib->getTokens();
$groups = $userlib->list_all_groups();

foreach ($tokens as $key => $token) {
    $tokens[$key]['groups'] = join(', ', json_decode($token['groups']));
    $tokens[$key]['parameters'] = (array) json_decode($token['parameters']);
    if ($token['timeout'] == -1) {
        $tokens[$key]['expires'] = '';
    } else {
        $tokens[$key]['expires'] = date('c', strtotime($token['creation']) + $token['timeout']);
    }
    $tokens[$key]['entry'] = preg_replace('#^' . preg_quote($tikiroot) . '#', '', $token['entry']);
    $queryParams = http_build_query(array_merge($tokens[$key]['parameters'], ['TOKEN' => $token['token']]));
    $tokenUrl = $tokenlib->convertToStandardUrl($tokens[$key]['entry'] . '?' . $queryParams);
    $tokens[$key]['token_url'] = $tokenUrl;
    $tokens[$key]['full_url'] = $base_url . $tokenUrl;
}
krsort($tokens);

$smarty->assign('tokens', $tokens);
$smarty->assign('groups', $groups);
$smarty->assign('mid', 'tiki-admin_tokens.tpl');
$smarty->display('tiki.tpl');
