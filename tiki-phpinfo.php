<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('tiki-setup.php');
$access->check_permission('tiki_p_admin');
// Display the template
//$smarty->assign('mid','tiki-phpinfo.tpl');
//$smarty->display("tiki.tpl");
define('BUTTON_HTML', '
    <div style="text-align: left; margin: 0 auto; width: fit-content; padding-bottom: 10px;">
        <a href="tiki-index.php" style=" text-decoration: none; padding: 10px 20px; font-weight: bold; color: #E2E4EF; background-color: #4F5B93; border: none; cursor: pointer; display: flex; align-items: center; gap: 10px;">
            <i class="fa fa-arrow-left" style="font-size: 13px;"></i>
            Tiki Homepage
        </a>
    </div>
');

ob_start();
phpinfo();
$doc = new DOMDocument();
$doc->loadHTML(ob_get_clean());
$htmlOutput = $doc->saveHTML();
$htmlOutput = preg_replace(
    '/<!DOCTYPE html PUBLIC "-\/\/W3C\/\/DTD XHTML 1\.0 Transitional\/\/EN" "DTD\/xhtml1-transitional\.dtd">/',
    '<!DOCTYPE html>',
    $htmlOutput
);

define('STYLE_CONTENT', 'body { font-family: Arial, sans-serif; margin: 20px; }');
$styleElement = $doc->createElement('style', STYLE_CONTENT);
$linkElement = $doc->createElement('link');
$linkElement->setAttribute('rel', 'stylesheet');
$linkElement->setAttribute('href', FONTAWESOME_CSS_PATH . '/all.css');
$linkElement->setAttribute('type', 'text/css');
$xpath = new DOMXPath($doc);
$headNode = $xpath->query('//head')->item(0);
if ($headNode) {
    $headNode->insertBefore($linkElement, $headNode->firstChild);
    $headNode->appendChild($styleElement);
}

$newDiv = $doc->createDocumentFragment();
$newDiv->appendXML(BUTTON_HTML);
$targetNode = $xpath->query('//div[@class="center"]/table')->item(0);

if ($targetNode) {
    $targetNode->parentNode->insertBefore($newDiv, $targetNode);
}

$modifiedHtml = $doc->saveHTML();
$finalOutput = preg_replace(
    '/<!DOCTYPE html PUBLIC "-\/\/W3C\/\/DTD XHTML 1\.0 Transitional\/\/EN" "DTD\/xhtml1-transitional\.dtd">/',
    '<!DOCTYPE html>',
    $modifiedHtml
);
echo $finalOutput;
