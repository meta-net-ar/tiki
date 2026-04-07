<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_vimeo_info()
{
    global $prefs;

    return [
        'name' => tra('Vimeo'),
        'documentation' => 'PluginVimeo',
        'description' => tra('Embed a Vimeo video'),
        'prefs' => [ 'wikiplugin_vimeo' ],
        'iconname' => 'vimeo',
        'introduced' => 6.1,
        'format' => 'html',
        'params' => [
            'url' => [
                'required' => $prefs['vimeo_upload'] !== 'y',
                'name' => tra('URL'),
                'description' => tra('Complete URL to the Vimeo video. Example:') . ' <code>http://vimeo.com/3319966</code>'
                    . ($prefs['vimeo_upload'] === 'y' ? ' ' . tra('or leave blank to upload one.') : ''),
                'since' => '6.1',
                'filter' => 'url',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels (e.g., 560). Leave empty to use provider dimensions.'),
                'filter' => 'digits',
                'default' => '',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels (e.g., 315). Leave empty to use provider dimensions.'),
                'filter' => 'digits',
                'default' => '',
            ],
            'bg' => [
                'required' => false,
                'name' => tra('Background'),
                'description' => tra('Object background color. Example:') . ' <code>#ffffff</code>',
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
            ],
            'border' => [
                'required' => false,
                'name' => tra('Border Color'),
                'description' => tra('Object border color. Example:') . ' <code>#ffffff</code>',
                'filter' => 'text',
                'default' => '',
                'advanced' => true,
            ],
            'borderRadius' => [
                'required' => false,
                'name' => tra('Border Radius'),
                'description' => tra('Apply rounded corners. Default: Yes'),
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true,
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start Time'),
                'description' => tra('Start time offset in seconds'),
                'filter' => 'digits',
                'default' => 0,
            ],
            'allowFullScreen' => [
                'required' => false,
                'name' => tra('Allow Full-Screen'),
                'description' => tra('Enlarge video to full screen size'),
                'default' => 'y',
                'filter' => 'alpha',
                'options' => [
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'advanced' => true,
            ],
            'fileId' => [
                'required' => false,
                'name' => tra('File ID'),
                'description' => tr(
                    'Numeric ID of a Vimeo file in a File Gallery (or list separated by commas or %0).',
                    '<code>|</code>'
                ),
                'since' => '12.0',
                'filter' => 'text',
                'advanced' => true
            ],
            'fromFieldId' => [
                'required' => false,
                'name' => tra('Field ID'),
                'description' => tra('Numeric ID of a Tracker Files field, using Vimeo displayMode.'),
                'since' => '12.0',
                'filter' => 'int',
                'advanced' => true
            ],
            'fromItemId' => [
                'required' => false,
                'name' => tra('Item ID'),
                'description' => tra('Numeric ID of a Tracker item, using Vimeo displayMode.'),
                'since' => '12.0',
                'filter' => 'int',
                'advanced' => true
            ],
            'galleryId' => [
                'required' => false,
                'name' => tra('Gallery ID'),
                'description' => tra('Gallery ID to upload to.'),
                'since' => '12.0',
                'filter' => 'int',
                'advanced' => true
            ],
        ],
    ];
}

function vimeo_iframe($data, $params)
{
    $hasCustomDimensions = ! empty($params['width']) || ! empty($params['height']);
    $height = $params['height'] ?? '';
    $width = $params['width'] ?? '';

    $url = $params['vimeo'];

    // Extract video ID, and optionally the unlisted hash.
    $parsed = parse_url($url);
    $path = trim($parsed['path'] ?? '', '/');

    if (preg_match('/^(\d+)(?:\/([a-zA-Z0-9]+))?/', $path, $matches)) {
        $vimeoId = $matches[1];
        $hash = $matches[2] ?? null;
    } elseif (preg_match('/video\/(\d+)(?:\/([a-zA-Z0-9]+))?/', $path, $matches)) {
        $vimeoId = $matches[1];
        $hash = $matches[2] ?? null;
    } elseif (ctype_digit($url)) {
        $vimeoId = $url;
        $hash = null;
    }

    if (empty($vimeoId)) {
        return '';
    }

    $embedUrl = "https://player.vimeo.com/video/" . $vimeoId;
    $args = [];
    if (! empty($hash)) {
        $args['h'] = $hash;
    }
    if (! empty($params['showTitle']) && $params['showTitle'] === 'false') {
        $args['title'] = 0;
    }
    if (! empty($params['showByline']) && $params['showByline'] === 'false') {
        $args['byline'] = 0;
    }
    if (! empty($params['showPortrait']) && $params['showPortrait'] === 'false') {
        $args['portrait'] = 0;
    }
    if (! empty($args)) {
        $embedUrl .= '?' . http_build_query($args);
    }
    if (! empty($params['start'])) {
        $embedUrl .= '#t=' . (int)$params['start'] . 's';
    }

    $iframeAttrs = [
        'src' => $embedUrl,
        'frameborder' => '0',
    ];

    if ($hasCustomDimensions) {
        $iframeAttrs['width'] = $width ?: 560;
        $iframeAttrs['height'] = $height ?: 315;
    }

    // Default to true if not explicitly "n"
    if (! isset($params['allowFullScreen']) || $params['allowFullScreen'] !== 'n') {
        $iframeAttrs['allow'] = 'autoplay; fullscreen; picture-in-picture; clipboard-write';
        $iframeAttrs['allowfullscreen'] = true;
        $iframeAttrs['webkitallowfullscreen'] = true;
        $iframeAttrs['mozallowfullscreen'] = true;
    }

    $htmlAttrs = '';
    foreach ($iframeAttrs as $k => $v) {
        if ($v === true) {
            $htmlAttrs .= " $k";
        } else {
            $htmlAttrs .= " $k=\"" . htmlspecialchars((string)$v) . "\"";
        }
    }

    $inlineStyles = [];
    if (! $hasCustomDimensions) {
        $inlineStyles[] = 'aspect-ratio: 16/9';
        $inlineStyles[] = 'width: 100%';
        $inlineStyles[] = 'max-width: 100%';
    }
    if (! empty($params['borderRadius']) && $params['borderRadius'] === 'y') {
        $inlineStyles[] = 'border-radius: 8px';
    }

    $iframeStyleAttr = '';
    if (! empty($inlineStyles)) {
        $iframeStyleAttr = ' style="' . implode('; ', $inlineStyles) . ';"';
    }

    $output = "<iframe data-fileid=\"" . (! empty($params['vimeo_fileId']) ? htmlspecialchars($params['vimeo_fileId']) : '0') . "\" id=\"" . (! empty($params['player_id']) ? htmlspecialchars($params['player_id']) : '') . "\" $htmlAttrs$iframeStyleAttr></iframe>";

    // Apply container background/border styles
    $containerStyle = [];
    if (! empty($params['bg'])) {
        $containerStyle[] = 'background-color:' . htmlspecialchars($params['bg']);
    }
    if (! empty($params['border'])) {
        $containerStyle[] = 'border: 1px solid ' . htmlspecialchars($params['border']);
    }
    if (! empty($params['borderRadius']) && $params['borderRadius'] === 'y') {
        $containerStyle[] = 'border-radius: 8px; overflow: hidden;';
    }

    if (! empty($containerStyle)) {
        $output = '<div style="' . implode(' ', $containerStyle) . '; display:inline-block; max-width: 100%;' . (! $hasCustomDimensions ? ' width: 100%;' : '') . '">' . $output . '</div>';
    }

    return $output;
}

function wikiplugin_vimeo($data, $params)
{
    global $prefs;
    static $instance = 0;
    $instance++;

    if (! is_null($params['url']) && ! empty($params['url'])) {
        $params['vimeo'] = $params['url'];
        $params['player_id'] = "pid_" . uniqid();
        $params['vimeo_fileId'] = 0;
        unset($params['url']);

        return vimeo_iframe($data, $params);
    } elseif (! is_null($params['fileId']) && ! empty($params['fileId'])) {
        $fileIds = preg_split('/\D+/', $params['fileId'], -1, PREG_SPLIT_NO_EMPTY);
        unset($params['fileId']);

        $out = '';
        foreach ($fileIds as $fileId) {
            $attributelib = TikiLib::lib('attribute');
            $attributes = $attributelib->get_attributes('file', $fileId);
            if (! empty($attributes['tiki.content.url'])) {
                $videoParams = $params;
                $videoParams['url'] = $attributes['tiki.content.url'];

                $videoParams['vimeo'] = $videoParams['url'];
                $videoParams['player_id'] = "pid_" . uniqid();
                $videoParams['vimeo_fileId'] = $fileId;
                unset($videoParams['url']);

                $out .= vimeo_iframe($data, $videoParams);
            } else {
                Feedback::error(tr('Vimeo video not found for file #%0', $fileId));
            }
        }

        return $out;
    } else {
        global $page;
        $smarty = TikiLib::lib('smarty');
        if ($prefs['vimeo_upload'] !== 'y') {
            $repeat = false;
            return smarty_block_remarksbox(
                ['type' => 'error', 'title' => tra('Feature required')],
                tra('Feature "vimeo_upload" is required to be able to add videos here.'),
                $smarty->getEmptyInternalTemplate(),
                $repeat
            );
        }

        // old perms access to get "special" gallery perms to handle user gals etc
        $perms = TikiLib::lib('tiki')->get_perm_object(
            ! is_null($params['galleryId']) ? $params['galleryId'] : $prefs['vimeo_default_gallery'],
            'file gallery',
            TikiLib::lib('filegal')->get_file_gallery_info($prefs['vimeo_default_gallery']),
            false
        );
        if ($perms['tiki_p_upload_files'] !== 'y') {
            return '';      //$permMessage = tra('You do not have permsission to add files here.');
        } elseif (! is_null($params['fromFieldId'])) {
            $fieldInfo = TikiLib::lib('trk')->get_tracker_field($params['fromFieldId']);
            if (is_null($params['fromItemId'])) {
                $item = Tracker_Item::newItem($fieldInfo['trackerId']);
            } else {
                $item = Tracker_Item::fromId($params['fromItemId']);
            }
            if (! $item->canModify()) {
                return '';      //$permMessage = tra('You do not have permsission modify this tracker item.');
            }
        } elseif ($page) {
            $pagePerms = Perms::get([ 'type' => 'wiki page', 'object' => $page ])->edit;
            if (! $pagePerms) {
                return '';      //$permMessage = tra('You do not have permsission modify this page.');
            }
        }

        // set up for an upload
        $html = smarty_function_button(
            [
                '_keepall' => 'y',
                '_class' => 'vimeo dialog',
                'href' => smarty_function_service(
                    [
                        'controller' => 'vimeo',
                        'action' => 'upload',
                    ],
                    $smarty->getEmptyInternalTemplate()
                ),
                '_text' => tra('Upload Video'),
            ],
            $smarty->getEmptyInternalTemplate()
        );

        if (! empty($page) && is_null($params['fromFieldId'])) {
            // Wikiplugin used within Wiki page
            $access = TikiLib::lib('access');
            $access->checkAuthenticity();
            $ticket = $access->getTicket();
            $js = '
                $("body").on("vimeo_uploaded", ".vimeo_upload", function(event, data) {
                    var params = {
                        daconfirm: "y",
                        ticket: "' . $ticket . '",
                        page: ' . json_encode($page) . ',
                        content: "",
                        index: ' . $instance . ',
                        type: "vimeo",
                        params: {
                            url: data.url
                        }
                    };
                    $.post("tiki-wikiplugin_edit.php", params, function() {
                        $.get($.service("wiki", "get_page", {page:' . json_encode($page) . '}), function (data) {
                            if (data) {
                                $("#page-data").html(data);
                            }
                        });
                    });
                    $.closeModal();
                });
            ';
        } else {
            // Tracker edit, whether in tracker UI or tracker plugin within wiki page
            $js = '
                $("body").on("vimeo_uploaded", ".vimeo_upload", function(event, data) {
                    handleVimeoFile($(".vimeo.dialog"), data);
                    $.closeModal();
                });
            ';
        }
        $js .= '
            $(".vimeo.dialog").on("click", function () {
                vimeoUploadModal = $.openModal({
                    remote: $.service(
                        "vimeo",
                        "upload",
                        {
                            title: tr("Upload Video")' .
                (! is_null($params['galleryId']) ? ',galleryId:' . $params['galleryId'] : '') .
                (! is_null($params['fromFieldId']) ? ',fieldId:' . $params['fromFieldId'] : '') .
                (! is_null($params['fromItemId']) ? ',itemId:' . $params['fromItemId'] : '') . '
                        }
                    )
                });
                return false;
            });
        ';

        TikiLib::lib('header')->add_jq_onready($js);

        return $html;
    }
}
