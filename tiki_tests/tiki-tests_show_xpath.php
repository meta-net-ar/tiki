<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('../tiki-setup.php');

if ($prefs['feature_tikitests'] != 'y') {
    Feedback::errorAndDie(tra('This feature is disabled') . ': feature_tikitests', \Laminas\Http\Response::STATUS_CODE_403);
}

if ($tiki_p_admin_tikitests != 'y' and $tiki_p_edit_tikitests != 'y') {
    Feedback::errorAndDie(tra('You do not have permission to do that'), \Laminas\Http\Response::STATUS_CODE_400);
}

$smarty->assign('tidy', extension_loaded('tidy'));
$smarty->assign('http', extension_loaded('http'));
$smarty->assign('curl', extension_loaded('curl'));

/**
 * @param $element
 * @return array|null
 */
function get_from_dom($element)
{
    if ($element === null) {
        return null;
    }
    $es = $element->getElementsByTagName('*');
    $a = [];
    foreach ($es as $e) {
        $a[$e->tagName] = $e->nodeValue;
    }
    return $a;
}

/**
 * @param $url
 * @param $xpath
 * @return mixed|string
 */
function enlight_xpath($url, $xpath)
{
    global $cookies, $base_url;
    static $purifier;
    static $loaded = false;
    $smarty = TikiLib::lib('smarty');

    $result = [];
    $data = $url->getElementsByTagName('data')->item(0)->textContent;
    if (trim($data) == '') {
        return tra('The page is empty');
    }

    if (extension_loaded('tidy')) {
        $data = tidy_parse_string($data, [], 'utf8');
        tidy_diagnose($data);
    } else {
        if (! $loaded) {
            require_once('lib/htmlpurifier_tiki/HTMLPurifier.tiki.php');
            $config = getHTMLPurifierTikiConfig();
            $config->set('Attr.EnableID', true);
            $purifier = new HTMLPurifier($config);
            $loaded = true;
        }
        if ($purifier) {
            $data = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body>' . $purifier->purify($data) . '</body></html>';
            //$data = $purifier->purify($data);
        }
    }

    $dom_ref = DOMDocument::loadHTML($data);
    $xp_ref = new DomXPath($dom_ref);
    $res_ref = $xp_ref->query('//head');
    $base = $dom_ref->createElement('base');
    $base->setAttribute('href', $base_url);
    $res_ref->item(0)->insertBefore($base, $res_ref->item(0)->firstChild);
    $res_ref = $xp_ref->query($xpath);
    foreach ($res_ref as $ref) {
        $ref->setAttribute('style', 'background-color: red;');
    }

    return $dom_ref->saveHTML();
}


$xml = file_get_contents(TIKI_TESTS_PATH . "/" . basename($_REQUEST['filename']));
if ($xml == '') {
    Feedback::errorAndDie(tra('The TikiTests Replay File is empty'), \Laminas\Http\Response::STATUS_CODE_409);
} else {
    $dom = DOMDocument::loadXML($xml);
    $element_test = $dom->getElementsByTagName('test')->item(0);
    if ($element_test == null) {
        Feedback::errorAndDie(tra('The TikiTests Replay File is empty'), \Laminas\Http\Response::STATUS_CODE_409);
    }
}

$result = [];
$urls = $dom->getElementsByTagName('url');

$count = 0;
foreach ($urls as $url) {
    if ($count == $_REQUEST['index']) {
        echo enlight_xpath($url, $_REQUEST['xpath']);
    }
    $count++;
}
