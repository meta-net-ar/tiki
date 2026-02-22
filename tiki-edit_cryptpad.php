<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

$section = 'cryptpad_docs';
$inputConfiguration = [
    [
        'staticKeyFilters'                => [
            'fileId'                      => 'int',           //post
            'galleryId'                   => 'int',           //post
            'name'                        => 'string',        //post
            'data'                        => 'none',          //post
            'description'                 => 'xss',           //post
            'edit'                        => 'bool',          //post
            'format'                      => 'string',        //post
        ],
    ],
];
require_once('tiki-setup.php');
$filegallib = TikiLib::lib('filegal');
require_once('lib/mime/mimetypes.php');
global $mimetypes;

$auto_query_args = [
    'fileId',
    'edit',
    'format'
];

$access->check_feature(['cryptpad_feature', 'feature_file_galleries']);

$fileId = (int)$_REQUEST['fileId'];
$smarty->assign('fileId', $fileId);

if ($fileId > 0) {
    $fileInfo = $filegallib->get_file_info($fileId);
} else {
    $fileInfo = [];
}

if (! empty($fileInfo['archiveId']) && $fileInfo['archiveId'] > 0) {
    $fileId = $fileInfo['archiveId'];
    $fileInfo = $filegallib->get_file_info($fileId);
}

$cat_type = 'file';
$cat_objid = (int) $fileId;
$cat_object_exists = ! empty($fileInfo);
include_once('categorize_list.php');
include_once('tiki-section_options.php');

$gal_info = $filegallib->get_file_gallery($_REQUEST['galleryId']);

$fileTypeParts = explode(';', $fileInfo['filetype']);
$fileType = reset($fileTypeParts);

$extensionParts = explode('.', $fileInfo['filename']);
$extension = end($extensionParts);

$supportedExtensions = ['docx', 'xlsx', 'pptx', 'odt', 'ods', 'odp'];
$supportedTypes = array_map(function ($type) use ($mimetypes) {
    return $mimetypes[$type];
}, $supportedExtensions);

if (! in_array($extension, $supportedExtensions) && ! in_array($fileType, $supportedTypes)) {
    Feedback::errorAndDie(tr('Wrong file type, expected one of %0', implode(', ', $supportedTypes)), 500);
}

$globalperms = Perms::get(['type' => 'file', 'object' => $fileInfo['fileId']]);

if (! ($globalperms->admin_file_galleries == 'y' || $globalperms->view_file_gallery == 'y')) {
    Feedback::errorAndDie(tra('You do not have permission to view/edit this file'), 401);
}

// CryptPad editing requires an authenticated user.
// The user identity is passed to CryptPad for collaborative editing session tracking.
// Anonymous users are not permitted to edit documents with CryptPad.
if (empty($user)) {
    Feedback::errorAndDie(tra('You must be logged in to edit documents with CryptPad'), 401);
}

$_REQUEST['name'] = ! empty($_REQUEST['name']) ? $_REQUEST['name'] : (! empty($fileInfo['name']) ? $fileInfo['name'] : 'New Document');
$_REQUEST['name'] = htmlspecialchars(preg_replace('/\.(docx|xlsx|pptx|odt|ods|odp)$/', '', $_REQUEST['name']));

$documentTypeMap = [
    'xlsx' => 'sheet', 'ods' => 'sheet',
    'pptx' => 'presentation', 'odp' => 'presentation',
    'docx' => 'doc', 'odt' => 'doc'
];
$documentType = $documentTypeMap[$extension] ?? 'doc';

$smarty->assign('documentType', $documentType);
$smarty->assign('fileExtension', $extension);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['data'])) {
    $_REQUEST['galleryId'] = (int)$_REQUEST['galleryId'];
    $_REQUEST['description'] = htmlspecialchars($_REQUEST['description'] ?? $_REQUEST['name']);
    $format = $_REQUEST['format'] ?? $extension;
    $_REQUEST['data'] = base64_decode($_REQUEST['data']);
    $mimeType = $mimetypes[$format] ?? 'application/octet-stream';

    $file = Tiki\FileGallery\File::id($fileId);
    if (! $file->exists()) {
        $file->init([
            'galleryId' => $_REQUEST['galleryId'],
            'description' => $_REQUEST['description'],
            'user' => $user
        ]);
    }
    $file->replace($_REQUEST['data'], $mimeType, $_REQUEST['name'], $_REQUEST['name'] . '.' . $format);
    echo $fileId;
    die;
}

$smarty->assign('page', $page);
$smarty->assign('isFromPage', isset($page));
$smarty->assign('fileId', $fileId);

$cryptpadBaseUrl = trim($prefs['cryptpad_base_url'] ?? '');
$cryptpadAvailable = ! empty($cryptpadBaseUrl);
$smarty->assign('cryptpadAvailable', $cryptpadAvailable);
$smarty->assign('cryptpadBaseUrl', $cryptpadBaseUrl);
$smarty->assign('padUrl', isset($_REQUEST['pad']) ? (string)$_REQUEST['pad'] : '');

$importUrl = '';
if ($cryptpadAvailable && $fileId > 0) {
    $payload = [
        'fileId' => (int) $fileId,
        'exp' => time() + 7200,
    ];
    $encoded = Tiki_Security::get()->encode($payload);
    global $base_url;
    $importUrl = $base_url . 'tiki-cryptpad-import.php?data=' . rawurlencode($encoded);
}
$smarty->assign('cryptpadImportUrl', $importUrl);
$smarty->assign('fileName', $fileInfo['name'] ?? 'document');

if ($cryptpadAvailable) {
    // Load CryptPad API from the configured CryptPad instance (external URL)
    $cryptpadApiUrl = rtrim($cryptpadBaseUrl, '/') . '/cryptpad-api.js';
    $headerlib->add_jsfile_cdn($cryptpadApiUrl);

    $smarty->assign('edit', 'true');
    $headerlib->add_jq_onready("
(function() {
    var fileId = " . json_encode($fileId) . ";
    var importUrl = " . json_encode($importUrl) . ";
    var documentType = " . json_encode($documentType) . ";
    var fileExtension = " . json_encode($extension) . ";
    var cryptpadBaseUrl = " . json_encode(rtrim($cryptpadBaseUrl, '/')) . ";
    var fileName = " . json_encode($fileInfo['name'] ?? 'document') . ";
    var galleryId = " . json_encode($fileInfo['galleryId'] ?? $_REQUEST['galleryId'] ?? 0) . ";
    var hasUnsavedChanges = false;

    /**
     * CryptPad Concurrent Editing Model:
     * - The session key is derived deterministically from the fileId ('tiki_file_' + fileId).
     * - This ensures all users editing the same file join the same CryptPad session.
     * - CryptPad handles operational transformation (OT) for real-time collaboration.
     * - Saves are serialized: users click 'Save' -> sends base64 content to Tiki -> updates file.
     * - Unsaved changes are tracked to prevent accidental navigation away from the page.
     */
    
    // Use a shared key so multiple users edit the same session
    function getSessionKey(fileId) {
        return 'tiki_file_' + fileId; 
    }
    
    var apiWaitStart = Date.now();
    var apiWaitTimeout = 10000;
    
    function initCryptPadEditor() {
        if (typeof CryptPadAPI === 'undefined' || typeof CryptPadAPI !== 'function') {
            var elapsed = Date.now() - apiWaitStart;
            if (elapsed > apiWaitTimeout) {
                console.error('CryptPad API failed to load after ' + (apiWaitTimeout/1000) + ' seconds');
                alert('CryptPad API failed to load. Please check: 1. cryptpad-api.js exists at: ' + cryptpadBaseUrl + '/cryptpad-api.js 2. CryptPad instance is accessible 3. Check browser console for errors');
                fallbackToIframe();
                return;
            }
            setTimeout(initCryptPadEditor, 100);
            return;
        }
        
        var sessionKey = getSessionKey(fileId);
        var docConfig = {
            url: importUrl,
            fileType: fileExtension
        };
        
        if (sessionKey && typeof sessionKey === 'string' && sessionKey.length > 0) {
            docConfig.key = sessionKey;
        }
        
        var config = {
            document: docConfig,
            documentType: documentType,
            width: '100%',
            height: '800px',
            events: {
                onSave: function(blob, callback) {
                    var reader = new FileReader();
                    reader.onloadend = function() {
                        var base64 = reader.result.split(',')[1];
                        if (!base64) {
                            callback({error: 'Failed to convert file to base64'});
                            alert('Failed to prepare file for upload');
                            return;
                        }
                        
                        var savingMsg = " . json_encode(tr('Saving...'), JSON_HEX_APOS | JSON_HEX_QUOT) . ";
                        if (typeof $.tikiModal === 'function') {
                            $.tikiModal(savingMsg);
                        }
                        
                        $.post('tiki-edit_cryptpad.php', {
                            fileId: fileId,
                            data: base64,
                            name: fileName.replace(/\\.[^.]+$/, ''),
                            format: fileExtension,
                            galleryId: galleryId
                        })
                        .done(function() {
                            if (typeof $.tikiModal === 'function') {
                                $.tikiModal();
                            }
                            callback();
                        })
                        .fail(function(xhr, status, error) {
                            if (typeof $.tikiModal === 'function') {
                                $.tikiModal();
                            }
                            var errorMsg = 'Save failed: ' + (xhr.responseText || error);
                            callback({error: errorMsg});
                            alert('Save failed: ' + errorMsg);
                        });
                    };
                    reader.onerror = function() {
                        callback({error: 'Failed to read file'});
                        alert('Failed to read file for upload');
                    };
                    reader.readAsDataURL(blob);
                },
                onReady: function() {
                    console.log('CryptPad editor ready');
                },
                onDocumentReady: function() {
                    console.log('Document loaded in editor');
                },
                onHasUnsavedChanges: function(hasChanges) {
                    console.log('Unsaved changes:', hasChanges);
                    hasUnsavedChanges = hasChanges;
                }
            },
            editorConfig: {
                user: {
                    id: " . json_encode($user) . ",
                    name: " . json_encode($user) . "
                }
            }
        };
        
        try {
            var editorPromise = CryptPadAPI(cryptpadBaseUrl, 'tiki_cryptpad', config);
            
            if (editorPromise && typeof editorPromise.then === 'function') {
                editorPromise
                    .then(function(editor) {
                        window.cryptpadEditor = editor;
                    })
                    .catch(function(error) {
                        alert('Failed to initialize editor: ' + (error.message || error));
                        fallbackToIframe();
                    });
            } else {
                window.cryptpadEditor = editorPromise;
            }
        } catch (error) {
            alert('Failed to initialize editor: ' + error.message);
            fallbackToIframe();
        }
    }
    
    function fallbackToIframe() {
        var msg = 'Failed to load CryptPad editor. Please check: CryptPad instance is accessible, cryptpad-api.js exists, OnlyOffice is installed.';
        $('#tiki_cryptpad').html('<div class=\"alert alert-danger\">' + msg + '</div>');
    }
    
    $(document).on('click', '.cancelButton', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        var galId = $('input[name=galleryId]').val() || galleryId;
        var galleryUrl = 'tiki-list_file_gallery.php';
        if (galId && galId > 0) {
            galleryUrl += '?galleryId=' + galId;
        }
        window.location.href = galleryUrl;
        return false;
    });
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCryptPadEditor);
    } else {
        initCryptPadEditor();
    }

    // Warn before leaving if there are unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();
");
} else {
    $smarty->assign('missingPackage', true);
}

$smarty->assign('mid', 'tiki-edit_cryptpad.tpl');
$smarty->display('tiki.tpl');
