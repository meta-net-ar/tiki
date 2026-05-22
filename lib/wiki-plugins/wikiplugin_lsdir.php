<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_lsdir_info()
{
    return [
        'name' => tra('List Directory'),
        'documentation' => 'PluginLsDir',
        'description' => tra('List files in a directory. Access is denied unless system preference wikiplugin_fileaccess_allowed_paths defines allowed base paths.'),
        'prefs' => [ 'wikiplugin_lsdir' ],
        'validate' => 'all',
        'iconname' => 'file-archive',
        'introduced' => 1,
        'params' => [
            'dir' => [
                'required' => true,
                'name' => tra('Directory'),
                'description' => tra('Path to a server-local directory. Must be within one of the allowed base paths configured in preference wikiplugin_fileaccess_allowed_paths.'),
                'since' => '1',
                'default' => '',
            ],
            'urlprefix' => [
                'required' => false,
                'name' => tra('URL Prefix'),
                'description' => tra('Make the file name a link to the file by adding the URL path preceding the file
                    name. Example:') . ' <code>http://example.org/tiki/</code>',
                'since' => '1',
                'default' => null,
                'filter' => 'url',
            ],
            'sort' => [
                'required' => false,
                'name' => tra('Sort order'),
                'description' => tra('Set the sort order of the file list'),
                'since' => '1',
                'default' => 'name',
                'filter' => 'word',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('File Name'), 'value' => 'name'],
                    ['text' => tra('File Size'), 'value' => 'size'],
                    ['text' => tra('Last Access'), 'value' => 'atime'],
                    ['text' => tra('Last Metadata Change'), 'value' => 'ctime'],
                    ['text' => tra('Last modified'), 'value' => 'mtime'],
                ]
            ],
            'filter' => [
                'required' => false,
                'name' => tra('Filter'),
                'description' => tra('Only list files with file names that contain this filter. Example:')
                    . ' <code>.jpg</code>',
                'since' => '1',
            ],
            'limit' => [
                'required' => false,
                'name' => tra('Limit'),
                'description' => tra('Maximum amount of files to display. Default is no limit.'),
                'since' => '1',
                'default' => 0,
                'filter' => 'digits',
            ],
        ],
    ];
}

function wikiplugin_lsdir($data, $params)
{
    $dir = $params['dir'];
    $urlprefix = $params['urlprefix'];
    $sort = $params['sort'];
    $sortmode = 'asc';
    $filter = $params['filter'];
    $limit = $params['limit'];
    $tmp_array = [];
    $ret = '';

    extract($params, EXTR_SKIP);

    $fileaccess = \Tiki\WikiPlugin\FileaccessAllowlist::fromPreference();

    if (! $fileaccess->isConfigured()) {
        return $fileaccess->getDeniedHtml('no_roots');
    }

    $dirCandidates = [$dir];
    if (! str_starts_with($dir, '/') && ! empty($_SERVER['DOCUMENT_ROOT'])) {
        $dirCandidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\') . '/' . $dir;
    }

    $resolvedAllowedDir = $fileaccess->resolvePathFromCandidates($dirCandidates);

    if ($resolvedAllowedDir === false) {
        return $fileaccess->getDeniedHtml('outside_dir');
    }

    $dir = $resolvedAllowedDir;

    // make sure urlprefix has a trailing slash
    if (! empty($urlprefix)) {
        $tail = strlen($urlprefix) - 1;
        if (substr($urlprefix, $tail) != '/') {
            $urlprefix .= '/';
        }
    }

    if ($limit > 0) {
        $count = 0;
    } else {
        $count = -1;
    }

    // fileatime, filectime, filemtime, filesize are PHP functions
    if ($sort == 'atime') {
        $getkey = 'fileatime';
    } elseif ($sort == 'ctime') {
        $getkey = 'filectime';
    } elseif ($sort == 'mtime') {
        $getkey = 'filemtime';
    } elseif ($sort == 'size') {
        $getkey = 'filesize';
    }

    // supress the PHP error because that causes Tiki to crash
    $dh = @opendir($dir);

    if (! $dh) {
        $error = "<span class='attention'><b>" . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8') . '</b> ' . tra("could not be opened because it doesn't exist or permission was denied") . '</span>';
        return $error;
    }

    while ($file = readdir($dh)) {
        if (empty($filter) || stristr($file, $filter)) {
            //Don't list subdirectories
            if (! is_dir("$dir/$file")) {
                if ($sort == 'name') {
                    $key = "$file";
                } else {
                    $key = $getkey("$dir/$file");
                }
                $tmp_array["$key"] = "$file";
            }
        }
    }
    closedir($dh);

    if ($sortmode == 'asc') {
        ksort($tmp_array);
    } elseif ($sortmode == 'desc') {
        krsort($tmp_array);
    }

    foreach ($tmp_array as $filename) {
        if ($count >= $limit) {
            break 1;
        }
        if (! empty($urlprefix)) {
            $safeUrl  = htmlspecialchars($urlprefix . $filename, ENT_QUOTES, 'UTF-8');
            $safeName = htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
            $ret .= "<a href='$safeUrl' class='wiki'>$safeName</a><br />";
        } else {
            $ret .= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') . '<br />';
        }
        if ($limit > 0) {
            $count++;
        }
    }

    return $ret;
}
