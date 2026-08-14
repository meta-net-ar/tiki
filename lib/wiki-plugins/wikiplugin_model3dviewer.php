<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_model3dviewer_info()
{
    global $prefs;
    $info = [
        'name' => tra('3D Model Viewer'),
        'documentation' => 'PluginModel3DViewer',
        'description' => tra('Display one or more custom-formatted 3D models'),
        'prefs' => ['wikiplugin_model3dviewer'],
        'iconname' => 'cube',
        'tags' => [ 'basic' ],
        'introduced' => 30,
        'params' => [
            'type' => [
                'required' => true,
                'name' => tra('3D Model Source'),
                'description' => tra('Choose where to get the 3D model from'),
                'since' => '30.0',
                'doctype' => 'id',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['text' => tra('Select an option'), 'value' => ''],
                    ['text' => tra('A 3D model in the file galleries'), 'value' => 'fileId'],
                    ['text' => tra('A 3D model anywhere on the Internet'), 'value' => 'src'],
                ],
            ],
            'fileId' => [
                'required' => true,
                'name' => tra('File ID'),
                'type' => 'image',
                'area' => 'fgal_picker_id',
                'description' => tr(
                    'Numeric ID of a 3D model in a file gallery (or a comma- or %0-separated list of IDs).',
                    '<code>|</code>'
                ),
                'since' => '30.0',
                'doctype' => 'id',
                'filter' => 'text',
                'default' => '',
                'accepted' => tra('Valid file IDs separated by commas or |'),
                'parentparam' => ['name' => 'type', 'value' => 'fileId'],
                'profile_reference' => 'file',
            ],
            'src' => [
                'required' => true,
                'name' => tra('Image Source'),
                'description' => tra('Full URL to the 3D Model to display.'),
                'since' => '30.0',
                'doctype' => 'id',
                'filter' => 'url',
                'default' => '',
                'parentparam' => ['name' => 'type', 'value' => 'src'],
            ],
            'camera_position' => [
                'required' => false,
                'name' => tra('3D Camera Position'),
                'description' => tra('Optional camera position for 3D viewer, as X,Y,Z coordinates (e.g. "0,1,5"). Only for supported viewers.'),
                'since' => '30.0',
                'doctype' => 'text',
                'filter' => 'text',
                'default' => '',
            ],
            'camera_type' => [
               'required' => false,
                'name' => tra('Camera Type'),
                'description' => tra('Type of camera to use , Perspective mimics human eye, Orthographic is a parallel projection.'),
                'since' => '30.0',
                'doctype' => 'text',
                'filter' => 'text',
                'default' => 'Perspective',
                'options' => [
                    ['value' => 'perspective', 'text' => tra('Perspective, Human oeil like')],
                    ['value' => 'orthographic', 'text' => tra('Orthographic')],
                ],
            ],
            'autorotate' => [
                'required' => false,
                'name' => tra('Auto-rotate'),
                'description' => tra('Enable or disable auto-rotation of the 3D model. Supported by some viewers.'),
                'since' => '30.0',
                'doctype' => 'flag',
                'filter' => 'word',
                'default' => 'n',
                'options' => [
                    ['value' => 'y', 'text' => tra('Yes')],
                    ['value' => 'n', 'text' => tra('No')],
                ],
            ],
            'backgroundColor' => [
                'required' => false,
                'name' => tra('Viewer Background Color'),
                'description' => tra('Hex background color for the viewer (e.g., "#ffffff"). Leave blank for transparent.'),
                'since' => '30.0',
                'doctype' => 'color',
                'filter' => 'text',
                'default' => '',
            ],
            'height' => [
                'required' => false,
                'name' => tra('Image Height'),
                'description' => tr('Height in pixels or percent. Syntax: %0100%1 or %0100px%1 means 100 pixels;
                    %050%%1 means 50 percent. Default is 400px.', '<code>', '</code>'),
                'since' => '30.0',
                'doctype' => 'size',
                'filter' => 'text',
                'default' => '400px',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Image Width'),
                'description' => tr('Width in pixels or percent. Syntax: %0100%1 or %0100px%1 means 100 pixels;
                    %050%%1 means 50 percent. Default is 100%.', '<code>', '</code>'),
                'since' => '30.0',
                'doctype' => 'size',
                'filter' => 'text',
                'default' => '100',
            ],
            'desc' => [
                'required' => false,
                'name' => tra('Caption'),
                'since' => '30.0',
                'doctype' => 'text',
                'filter' => 'text',
                'description' => tr('Image caption. Use %0name%1 or %0desc%1 or %0namedesc%1 for Tiki name and
                    description properties, %0idesc%1 or %0ititle%1 for metadata from the image itself, otherwise
                    enter your own description.', '<code>', '</code>'),
                'default' => '',
            ],
            'controls' => [
                'required' => false,
                'name' => tra('Enable Controls'),
                'description' => tra('Allow mouse/touch camera control (orbit, zoom).'),
                'since' => '30.0',
                'doctype' => 'flag',
                'filter' => 'word',
                'default' => 'y',
                'options' => [
                    ['value' => 'y', 'text' => tra('Yes')],
                    ['value' => 'n', 'text' => tra('No')],
                ],
            ],
            'shadow' => [
                'required' => false,
                'name' => tra('Enable Shadows'),
                'description' => tra('Render model shadows (if supported by viewer).'),
                'since' => '30.0',
                'doctype' => 'flag',
                'filter' => 'word',
                'default' => 'n',
                'options' => [
                    ['value' => 'y', 'text' => tra('Yes')],
                    ['value' => 'n', 'text' => tra('No')],
                ],
            ],
            'light_type' => [
                'required' => false,
                'name' => tra('Lighting Preset'),
                'description' => tra('Lighting mode (e.g., "soft", "rembrandt"). Applies mainly to Model Viewer.'),
                'since' => '30.0',
                'doctype' => 'selector',
                'default' => '',
                'filter' => 'word',
                'options' => [
                    ['value' => 'default', 'text' => tra('Default')],
                    ['value' => 'studio', 'text' => tra('Studio')],
                    ['value' => 'rembrandt', 'text' => tra('Rembrandt')],
                    ['value' => 'portrait', 'text' => tra('Portrait')],
                    ['value' => 'soft', 'text' => tra('Soft')],
                ],
            ],
            'exposure' => [
                'required' => false,
                'name' => tra('Lighting Exposure'),
                'description' => tra('Adjust scene brightness. Applies to some 3D viewers.'),
                'since' => '30.0',
                'type' => 'range',
                'filter' => 'float',
                'default' => '1.0',
                'min' => '0.1',
                'max' => '5.0',
                'step' => '0.1',
            ],
            'autoplay' => [
                'required' => false,
                'name' => tra('Autoplay Animation'),
                'description' => tra('Automatically start animation on model load if any.'),
                'since' => '30.0',
                'doctype' => 'flag',
                'filter' => 'word',
                'default' => 'n',
                'options' => [
                    ['value' => 'y', 'text' => tra('Yes')],
                    ['value' => 'n', 'text' => tra('No')],
                ],
            ],
            'animation_loop' => [
                'required' => false,
                'name' => tra('Loop Animation'),
                'description' => tra('Enable or disable looping of the animation.'),
                'since' => '30.0',
                'doctype' => 'flag',
                'filter' => 'word',
                'default' => 'n',
                'options' => [
                    ['value' => 'y', 'text' => tra('Yes')],
                    ['value' => 'n', 'text' => tra('No')],
                ],
            ],
        ]
    ];
    return $info;
}

function wikiplugin_model3dviewer($data, $params)
{
    global $prefs, $user;
    $userlib = TikiLib::lib('user');
    $smarty = TikiLib::lib('smarty');
    $headerlib = TikiLib::lib('header');
    $filegallib = TikiLib::lib('filegal');

    $params['uniqueId'] = 'viewer_' . ($params['fileId'] ?? '') . uniqid();
    $params['height'] = $params['height'] ?? '400px';
    $params['width'] = $params['width'] ?? '100%';
    $params['model3dviewer_mimetypes'] = '.gltf,.glb,.stl,.fbx,.obj,.dae,.ply,.3ds,.vrml,.x3d';
    $bgColor = trim($params['backgroundColor'] ?? '') !== ''
        ? $params['backgroundColor']
        : ($prefs['theme_model3dviewer_default_background'] ?? '#ffffff');

    $fileInfo = [];

    if (! empty($params['fileId'])) {
        $fileData = $filegallib->get_file($params['fileId']);
        $fileInfo = [
            'fileId' => $fileData['fileId'],
            'src' => $fileData['fileUrl'],
            'mimeType' => $fileData['filetype'],
            'basename' => $fileData['filename'],
            'description' => $fileData['description'] ?? '',
        ];

        if (! $userlib->user_has_perm_on_object($user, $params['fileId'], 'file', 'tiki_p_download_files') && $params['type'] === 'fileId') {
            return '<div class="alert alert-warning">' . tra('You do not have permission to view this 3D model.') . '</div>';
        }
    } elseif (! empty($params['src']) && empty($params['fileId'])) {
        $src = $params['src'];

        // Option 1: Guess from URL extension
        $pathInfo = pathinfo(parse_url($src, PHP_URL_PATH));

        // Option 2: Use HTTP headers (best effort)
        $headers = @get_headers($src, 1);
        $mimeType = $headers['Content-Type'] ?? '';

        $fileInfo = [
            'src' => $src,
            'fileId' => 0, // No file ID since it's not from the file gallery
            'mimeType' => $mimeType,
            'basename' => $pathInfo['basename'] ?? basename($src),
        ];
    }

    $absolute_links = false;
    if (isset($params['absoluteLinks'])) {
        $absolute_links = true;
    }

    $params['model3dviewer_src'] = resolve_model3dviewer_src($params, $absolute_links, $fileInfo['basename'] ?? 'model.stl');

    if (empty($params['model3dviewer_src'])) {
        return WikiParser_PluginOutput::error(tr('Plugin Model3DViewer'), tr('Missing or invalid source.'));
    }

    $headerlib->add_js_module("
        (async function() {
            const {initViewer} = await import('@tiki-3d-model-viewer/model3dviewer');
            initViewer('" . addslashes($params['uniqueId']) . "',
                {
                    modelUrl: \"" . addslashes($params['model3dviewer_src']) . "\",
                    controls: " . ($params['controls'] === 'y' ? 'true' : 'false') . ",
                    backgroundColor: \"" . $bgColor . "\",
                    autoRotate: " . (($params['autorotate'] ?? 'n') === 'y' ? 'true' : 'false') . ",
                    camera: \"" . addslashes($params['camera_position']) . "\",
                    cameraType: \"" . addslashes($params['camera_type']) . "\",
                    shadow: " . ($params['shadow'] === 'y' ? 'true' : 'false') . ",
                    lightType: \"" . addslashes($params['light_type']) . "\",
                    exposure: " . (floatval($params['exposure']) ?: 1.0) . ",
                    autoplay: " . ($params['autoplay'] === 'y' ? 'true' : 'false') . ",
                    loop: " . ($params['animation_loop'] === 'y' ? 'true' : 'false') . ",
                    uid: \"" . addslashes($params['uniqueId']) . "\"
                }
            );
        })();
    ");

    $smarty->assign('fileInfo', $fileInfo);
    $smarty->assign('params', $params);

    return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_model3dviewer.tpl') . '~/np~';
}

function resolve_model3dviewer_src($params, $absolute_links = false, $filename = 'model.stl')
{
    global $tikidomain;

    if (! empty($params['fileId'])) {
        $src = smarty_modifier_sefurl($params['fileId'], 'file');
        $src = TikiLib::tikiUrl($src);
        $separator = str_contains($src, '?') ? '&' : '?';
        $src .= $separator . 'filename=' . rawurlencode($filename);
    } elseif (! empty($params['src'])) {
        $src = trim($params['src']);
        $src = str_replace(' ', '', $src);

        if (stripos($src, 'javascript:') !== false) {
            $src = ''; // sanitize
        } elseif ($absolute_links && ! preg_match('|^[a-zA-Z]+:\/\/|', $src)) {
            global $base_host, $url_path;
            $src = $base_host . ($src[0] == '/' ? '' : $url_path) . $src;
        } elseif ($tikidomain && ! preg_match('|^https?:|', $src)) {
            $src = preg_replace("~" . DEPRECATED_IMG_WIKI_UP_PATH . " /~", DEPRECATED_IMG_WIKI_UP_PATH . "/$tikidomain/", $src);
        }
    } else {
        return false; // No valid source
    }

    return $src;
}
