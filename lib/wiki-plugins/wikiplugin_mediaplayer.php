<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

const AUDIO_ACCEPTED_FORMATS = ['mp3', 'ogg', 'wav', 'aac', 'flac', 'opus'];
const VIDEO_ACCEPTED_FORMATS = ['mp4', 'ogv', 'webm', '3gp', '3g2', 'mov', 'avi', 'mpg', 'mpeg', 'wmv', 'flv'];
const DOCUMENT_ACCEPTED_FORMATS = ['pdf', 'odt', 'ods', 'odp'];
$ALL_ACCEPTED_FORMATS = array_merge(AUDIO_ACCEPTED_FORMATS, VIDEO_ACCEPTED_FORMATS, DOCUMENT_ACCEPTED_FORMATS);
define('ALL_ACCEPTED_FORMATS', $ALL_ACCEPTED_FORMATS);

function wikiplugin_mediaplayer_info()
{
    return [
        'name' => tra('Media Player'),
        'documentation' => 'PluginMediaplayer',
        'description' => tra('Add a media player to a page'),
        'extraparams' => true,
        'prefs' => [ 'wikiplugin_mediaplayer' ],
        'iconname' => 'play',
        'introduced' => 3,
        'tags' => [ 'basic' ],
        'params' => [
            // The following param needs an URL with an extension (ex.: example.wmv works but not tiki-download_file.php?fileId=4&display)
            'src' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tra("Complete URL to the media to include, which has the appropriate extension.
                    If your URL doesn't have an extension, use the File type parameter below."),
                'since' => '6.0',
                'accepted' => array_merge(AUDIO_ACCEPTED_FORMATS, VIDEO_ACCEPTED_FORMATS, DOCUMENT_ACCEPTED_FORMATS),
                'filter' => 'url',
                'default' => '',
            ],

            'mp3' => [
                'required' => false,
                'name' => tra('URL'),
                'description' => tra("Complete URL to the media to include, which has the appropriate extension."),
                'since' => '27.0',
                'accepted' => AUDIO_ACCEPTED_FORMATS,
                'filter' => 'url',
                'default' => '',
            ],

            // The type parameter is verified for Quicktime, Windows Media Player, Real Player, iframe (PDF)
            'type' => [
                'required' => false,
                'name' => tra('File type'),
                'description' => tr('File type for source URL, e.g. %0mp4%1, %0pdf%1 or %0odp%1. Specify one of the supported file types when
                    the URL of the file is missing the file extension. This is the case for File Gallery files which
                    have a URL such as %0tiki-download_file.php?fileId=4&display%1 or %0display4%1 if you have Clean URLs
                    enabled.', '<code>', '</code>'),
                'since' => '10.0',
                'filter' => 'url',
                'default' => '',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Player width in px or %'),
                'since' => '10.0',
                'default' => '',
                ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                    'description' => tra('Player height in px or %'),
                'since' => '10.0',
                'default' => '',
                ],
            'mediatype' => [
                'required' => false,
                'name' => tra('Media Type'),
                'description' => tra('Media type for HTML5'),
                'since' => '13.2',
                'filter' => 'word',
                'options' => [
                    [
                        'text' => '', 'value' => ''
                    ],
                    [
                        'text' => tra('Audio'), 'value' => 'audio'
                    ],
                    [
                        'text' => tra('Video'), 'value' => 'video'
                    ]
                ]
            ],
        ],
    ];
}
function wikiplugin_mediaplayer($data, $params)
{
    global $prefs;
    $access = TikiLib::lib('access');
    static $iMEDIAPLAYER = 0;
    $id = 'mediaplayer' . ++$iMEDIAPLAYER;
    $params['type'] = strtolower(isset($params['type']) ? $params['type'] : '');
    $extension = '';
    if ((empty($params['src']) && empty($params['mp3']))) {
        Feedback::error(['mes' => "PluginMediaPlayer : src and mp3 cannot both be empty"]);
        return '';
    } elseif (! empty($params['src'])) {
        $extension = pathinfo($params['src'], PATHINFO_EXTENSION);
        if (! in_array($extension, ALL_ACCEPTED_FORMATS)) {
            Feedback::error("PluginMediaPlayer : Media format not supported. Here are the supported formats : " . ALL_ACCEPTED_FORMATS);
            return '';
        }
    } elseif (empty($params['src']) && ! empty($params['mp3'])) {
        $extension = pathinfo($params['mp3'], PATHINFO_EXTENSION);
        if (! in_array($extension, AUDIO_ACCEPTED_FORMATS)) {
            Feedback::error("PluginMediaPlayer : Media format not supported. Here are the audio supported formats : " . AUDIO_ACCEPTED_FORMATS);
            return '';
        }
        $params['src'] = $params['mp3'];
    }

    if (empty($params['mediatype'])) {
        if (in_array($extension, AUDIO_ACCEPTED_FORMATS)) {
            $params['mediatype'] = 'audio';
        } elseif (in_array($extension, VIDEO_ACCEPTED_FORMATS)) {
            $params['mediatype'] = 'audio';
        } elseif (in_array($extension, DOCUMENT_ACCEPTED_FORMATS)) {
            $params['type'] = $extension;
        }
    }

    //checking if pdf generation request
    if (in_array($params['type'], ['pdf']) && isset($_GET['display']) && strstr($_GET['display'], 'pdf') != '') {
        return "<pdfpage>.<pdfinclude src='" . TikiLib::lib('access')->absoluteUrl($params['src']) . "' /></pdfpage>";
    }
    $defaults_html5 = [
        'width' => '',
        'height' => '',
    ];
    $defaults = [
        'width' => 320,
        'height' => 240,
    ];

    if (empty($params['type'])) {
        preg_match('/(?:dl|display|fileId=)(\d*)/', $params['src'], $matches);
        if (! empty($matches[1])) { // fileId 0 is also invalid
            $fileId = $matches[1];
            $filegallib = TikiLib::lib('filegal');
            $file = $filegallib->get_file_info($fileId);
            if (! empty($file['filetype']) && $file['fileId'] == $fileId) {
                $fileExtension = pathinfo($file['filename'], PATHINFO_EXTENSION);
                $params['type'] = $fileExtension;
                if (! in_array($fileExtension, DOCUMENT_ACCEPTED_FORMATS)) {
                    $params['style'] = ! empty($params['style']) ? $params['style'] : 'native';
                    $params['type'] = $file['filetype'];
                }
            }
        }
    }

    if (in_array($params['type'], DOCUMENT_ACCEPTED_FORMATS)) {
        $headerlib = TikiLib::lib('header');
        if ($prefs['fgal_pdfjs_feature'] === 'n') {
            return "<p>" . tr('PDF.js feature is disabled. If you do not have permission to enable, ask the site administrator.') . "</p>";
        }
        if ($prefs['fgal_pdfjs_feature'] === 'y') {
            $smarty = TikiLib::lib('smarty');

            $url = TikiLib::lib('access')->absoluteUrl($params['src']);
            $smarty->assign('url', $url);
            $smarty->assign('mediaplayerId', $iMEDIAPLAYER);
            $oldPdfJsFile = VendorHelper::getAvailableVendorPath('pdfjs', '/npm-asset/pdfjs-dist/build/pdf.js');
            $oldPdfJsFileAvailable = file_exists($oldPdfJsFile);
            $smarty->assign('oldPdfJsFileAvailable', $oldPdfJsFileAvailable);

            $pdfJsfile = VendorHelper::getAvailableVendorPath('pdfjsviewer', '/npm-asset/pdfjs-dist-viewer-min/build/minified/build/pdf.js');
            $pdfJsAvailable = file_exists($pdfJsfile);
            $smarty->assign('pdfJsAvailable', $pdfJsAvailable);

            $headerlib = TikiLib::lib('header');
            $headerlib->add_css("
                .iframe-container {
                    overflow: hidden;
                    padding-top: 56.25%;
                    position: relative;
                    height: 900px;
                }
                
                .iframe-container iframe {
                    border: 0;
                    height: 100%;
                    left: 0;
                    position: absolute;
                    top: 0;
                    width: 100%;
                }
                
                @media (max-width: 767px) {
                    .iframe-container {
                        height: 500px;
                    } 
                }
                
                @media (min-width: 768px) AND (max-width: 991px) {
                    .iframe-container {
                        height: 600px;
                    }
                }
                
                @media (min-width: 992px) AND (max-width: 1209px){
                    .iframe-container {
                        height: 700px;
                    }
                }
            ");

            $fileId = '';
            $sourceLink = '';

            $parts = parse_url($params['src'], PHP_URL_QUERY);
            if ($parts) {
                parse_str($parts, $query);
                if (! empty($query['fileId'])) {
                    $fileId = $query['fileId'];
                }
            } else {
                preg_match('/(display|dl)(.*)$/', $params['src'], $matches);
                if (! empty($matches[2])) {
                    $fileId = $matches[2];
                }
            }

            if (! empty($fileId)) {
                $sourceLink = smarty_modifier_sefurl($fileId, 'display');
            } else {
                global $base_url;
                $sourceLink = TikiLib::lib('access')->absoluteUrl($params['src']);

                // Not an internal link, lets set a security token.
                if (strrpos($sourceLink, $base_url) === false) {
                    $data = Tiki_Security::get()->encode(['url' => $params['src']]);
                    $sourceLink = TikiLib::tikiUrl('tiki-download_file.php', [
                        'data'   => $data,
                    ]);
                }
            }

            if (! empty($sourceLink)) {
                $htmlViewFile = VendorHelper::getAvailableVendorPath('pdfjsviewer', '/npm-asset/pdfjs-dist-viewer-min/build/minified/web/viewer.html') . '?file=';
                $sourceLink = $htmlViewFile . urlencode(TikiLib::lib('access')->absoluteUrl($sourceLink));
            }

            $smarty->assign('source_link', $sourceLink);
            return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_mediaplayer_pdfjs.tpl') . '~/np~';
        } elseif ($params['type'] === 'pdf') {
            $js = '
var found = false;
$.each(navigator.plugins, function(i, plugins) { // navigator.plugins is unspecified according to https://developer.mozilla.org/fr/docs/Web/API/NavigatorPlugins/plugins . Something other in NavigatorPlugins may be standard. 
    $.each(plugins, function(i, plugin) {
        if (plugin.type === "application/pdf") {
            found = true;
            return;
        }
    });
});
if (!found) {
    // IE doesnt bother using the plugins array (sometimes?), plus ActiveXObject is hidden now so just try and catch... :(
    try {
        var oAcro7 = new ActiveXObject("AcroPDF.PDF.1");
        if (oAcro7) {
            found = true;
        }
    } catch (e) {
    }
}
if (found) {
    ' . $js . '
} else {
    // no pdf plugin
    $("#' . $id . '").text(tr("Download file:") + " " + "' . $params['src'] . '");
}';
        }
        $headerlib->add_jq_onready($js);

        return "<a href=\"" . $params['src'] . "\" id=\"$id\"></a>";
    }

    if ((! empty($params['mediatype']) && ($params['mediatype'] == 'audio' || $params['mediatype'] == 'video'))) {
        $code = '<' . $params['mediatype'];
        if (! empty($params['height'])) {
            $code .= ' height="' . $params['height'] . '"';
        }
        if (! empty($params['width'])) {
            $code .= ' width="' . $params['width'] . '"';
        }
        $code .= ' style="max-width: 100%" controls>';
        $code .= '    <source src="' . $params['src'] . '" type=\'' . $params['type'] . '\'>'; // type can be e.g. 'video/webm; codecs="vp8, vorbis"'
        $code .= '</' . $params['mediatype'] . '>';

        return "~np~$code~/np~";
    }
}
