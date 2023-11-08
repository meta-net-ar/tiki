<?php

use Tiki\Package\VendorHelper;

require_once('tiki-setup.php');

$exportImageCache = (int)($prefs['fgal_export_diagram_on_image_save'] == 'y');

$xmlContent = isset($_POST['xml']) ? $_POST['xml'] : false;
$page = isset($_POST['page']) ? $_POST['page'] : false;
$index = isset($_POST['index']) ? $_POST['index'] : null;
$compressXml = ($prefs['fgal_use_diagram_compression_by_default'] !== 'y') ? false : true;

if (! empty($_POST['compressXmlParam']) && ! empty($_POST['compressXml']) && $_POST['compressXml'] === 'false') {
    $compressXml = false;
}

$galleryId = $_REQUEST['galleryId'] ? $_REQUEST['galleryId'] : 0;
$backLocation = '';

if ($xmlContent) {
    $xmlContent = base64_decode($xmlContent);

    $xmlContent = str_replace('<mxfile compressed="false"', '<mxfile', $xmlContent);
    if (! $compressXml) {
        $xmlContent = str_replace('<mxfile', '<mxfile compressed="false"', $xmlContent);
    }
}

$newDiagram = isset($_REQUEST['newDiagram']) ?: false;
if ($newDiagram && ! $xmlContent) {
    $xmlContent = '<mxGraphModel dx="1190" dy="789" grid="1" gridSize="10" guides="1" tooltips="1" connect="1" arrows="1" fold="1" page="1" pageScale="1" pageWidth="827" pageHeight="1169" math="0" shadow="0"><root><mxCell id="0"/><mxCell id="1" parent="0"/></root></mxGraphModel>';
}

if ($newDiagram) {
    $smarty = TikiLib::lib('smarty');
    $backLocation = smarty_modifier_sefurl($page ?: $galleryId, $page ? 'wikipage' : 'filegallery');
}
$fileId = null;
$template = null;
$fileName = $_REQUEST['fileName'] ?? 0;

if (isset($_REQUEST['fileId']) && is_numeric($_REQUEST['fileId'])) {
    $fileId = (int) $_REQUEST['fileId'];
}

if (isset($_REQUEST['template']) && is_numeric($_REQUEST['template'])) {
    $template = (int)$_REQUEST['template'];
    if ($fileId == null) {
        $fileId = $template;
        $newDiagram = true;
    }
}

if (! empty($fileId) && ! $template) {
    $userLib = TikiLib::lib('user');
    $file = \Tiki\FileGallery\File::id($fileId);
    if (! $file->exists() || ! $userLib->user_has_perm_on_object($user, $file->fileId, 'file', 'tiki_p_download_files')) {
        Feedback::error(tr('Forbidden'));
        $smarty->display('tiki.tpl');
        exit();
    }

    $xmlContent = $file->getContents();
    $xmlContent = preg_replace('/\s+/', ' ', $xmlContent);
    $fileName = $file->getParam('name');
}

if (empty($xmlContent)) {
    Feedback::error(tr('Invalid request'));
    $smarty->display('tiki.tpl');
    exit();
}

$xmlDiagram = $xmlContent;
$access->setTicket();
$tickets[] = $access->getTicket();

if ($page && $galleryId) {
    $access->setTicket();
    $tickets[] = $access->getTicket();
}

if ($exportImageCache) {
    $access->setTicket();
    $tickets[] = $access->getTicket();
}

$saveModal = $smarty->fetch('mxgraph/save_modal.tpl');
$saveModal = preg_replace('/\s+/', ' ', $saveModal);

$headerlib = TikiLib::lib('header');

$oldVendorPath = VendorHelper::getAvailableVendorPath('mxgraph', 'xorti/mxgraph-editor', false);
if ($oldVendorPath) {
    $errorMessageToAppend = 'Previous xorti/mxgraph-editor package has been deprecated.<br/>';
}

$vendorPath = VendorHelper::getAvailableVendorPath('diagram', 'tikiwiki/diagram', false);
if (! $vendorPath) {
    $accesslib = TikiLib::lib('access');
    $accesslib->display_error('tiki-display.php', tr($errorMessageToAppend . 'To edit diagrams Tiki needs the tikiwiki/diagram package. If you do not have permission to install this package, ask the site administrator.'));
}

$headerlib->add_js_config("var diagramVendorPath = '{$vendorPath}';");
$headerlib->add_jsfile('lib/jquery_tiki/tiki-mxgraph.js', true);
$headerlib->add_jsfile('lib/jquery_tiki/tiki-editdiagram.js', true);

// Clear Tiki CSS files (just use drawio css)
$headerlib->unsafeClearAllCss();
$headerlib->add_css(".geMenubar a.geStatus { display: none;}");
$headerlib->add_cssfile($vendorPath . '/tikiwiki/diagram/styles/grapheditor.css');
$headerlib->add_jsfile($vendorPath . '/tikiwiki/diagram/js/app.min.js', true);

$js = sprintf(
    ';initializeEditorUI(%s);',
    json_encode(
        [
            'tickets'          => $tickets,
            'fileId'           => $template == $fileId ? null : $fileId,
            'template'         => $template,
            'backLocation'     => $backLocation,
            'newDiagram'       => $newDiagram,
            'compressXml'      => $compressXml,
            'galleryId'        => $galleryId,
            'saveModal'        => $saveModal,
            'index'            => $index,
            'fileName'         => $fileName,
            'page'             => $page,
            'exportImageCache' => $exportImageCache,
            'xmlDiagram'       => $xmlDiagram,
        ],
        JSON_PRETTY_PRINT
        | JSON_UNESCAPED_SLASHES
        | JSON_UNESCAPED_UNICODE
    )
);

$headerlib->add_js($js);
$title = $newDiagram ? tr('New diagram') : tr('Edit diagram');
$smarty->assign('title', $title);
$smarty->display('mxgraph/editor.tpl');
