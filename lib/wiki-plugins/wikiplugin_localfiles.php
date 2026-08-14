<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_localfiles_info()
{
    return [
        'name' => tra('Local Files'),
        'documentation' => 'PluginLocalFiles',
        'description' => tra('Show a link to local files and directories. Access is denied unless system preference wikiplugin_fileaccess_allowed_paths defines allowed base paths.'),
        'prefs' => ['wikiplugin_localfiles'],
        'iconname' => 'file',
        'introduced' => 12,
        'tags' => [ 'experimental' ],
        'format' => 'html',
        'validate' => 'all',
        'params' => [
            'path' => [
                'required' => false,
                'name' => tra('Path'),
                'description' => tra('Absolute path to a local file or directory. Must be within one of the allowed base paths configured in preference wikiplugin_fileaccess_allowed_paths.'),
                'since' => '12.0',
                'default' => '',
                'filter' => 'text',
            ],
            'list' => [
                'required' => false,
                'name' => tra('List Directory'),
                'description' => tra('If the path above is a directory, list its contents. The entries "." and ".." are always excluded.'),
                'since' => '12.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'icons' => [
                'required' => false,
                'name' => tra('Show Icons'),
                'description' => tra('Show MIME file-type icons.'),
                'since' => '12.0',
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
    ];
}


function wikiplugin_localfiles($data, $params)
{
    // TODO refactor: defaults for plugins?
    $smartylib = TikiLib::lib('smarty');
    $files = [];

    $fileaccess = \Tiki\WikiPlugin\FileaccessAllowlist::fromPreference();

    if (! $fileaccess->isConfigured()) {
        return $fileaccess->getDeniedHtml('no_roots');
    }

    $requestedPath = $params['path'] ?? '';
    $resolvedPath = $fileaccess->resolvePath($requestedPath);

    if ($resolvedPath === false) {
        return $fileaccess->getDeniedHtml('outside_path');
    }
    $params['path'] = $resolvedPath;

    if (! is_array($params['path'])) {
        if ($params['list'] === 'y' && file_exists($params['path']) && is_dir($params['path'])) {
            // Filter out . and .. to avoid exposing parent-directory entries.
            $params['path'] = array_values(array_filter(
                scandir($params['path']),
                fn($entry) => $entry !== '.' && $entry !== '..'
            ));
        } else {
            $params['path'] = [$params['path']];
        }
    }
    foreach ($params['path'] as $path) {
        $info = pathinfo($path);
        if (! $info || $info['basename'] === $path) {   // windows file but non-windows server
            preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im', $path, $m);
            if (! empty($m[1])) {
                $info['dirname'] = $m[1];
            }
            if (! empty($m[2])) {
                $info['basename'] = $m[2];
            }
            if (! empty($m[5])) {
                $info['extension'] = $m[5];
            }
            if (! empty($m[3])) {
                $info['filename'] = $m[3];
            }
            // thanks http://www.php.net/manual/en/function.pathinfo.php#107461
        }
        if ($params['icons'] === 'y') {
            $iconhtml = smarty_modifier_iconify($info['basename']);
        }

        $files[] = [
            'path' => $path,
            'name' => $info['basename'],
            'icon' => $iconhtml,
        ];
    }

    $smartylib->assign('files', $files);
    $smartylib->assign('isIE', strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false);

    return $smartylib->fetch('wiki-plugins/wikiplugin_localfiles.tpl');
}
