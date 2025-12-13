<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_viewtextfile_info()
{
    $info = [
        'name' => tra('View Text File'),
        'documentation' => 'PluginViewTextFile',
        'description' => tra('Allows a simple plain/text file stored in a File Gallery to be displayed in a wiki page'),
        'prefs' => ['wikiplugin_viewtextfile'],
        'introduced' => 29.0,
        'params' => [
            'fileId' => [
                'required' => true,
                'name' => tra('File Id'),
                'description' => tra('File Id of the .TXT file stored in a File Gallery'),
                'since' => '29.0',
                'filter' => 'digits',
            ],
            'startline' => [
                'required' => false,
                'name' => tra('Start content display line'),
                'description' => tra('Display the file content starting at this line (remembering that the first line is number 0 (zero) - which is also the default value'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => 0,
                'advanced' => true,
            ],
            'stopline' => [
                'required' => false,
                'name' => tra('Stop content display line'),
                'description' => tra('Display the file content stopping at this line (remembering that the first line is number 0 (zero)'),
                'since' => '29.0',
                'filter' => 'digits',
                'advanced' => true,
            ],
            'showfileinfo' => [
                'required' => false,
                'name' => tra('Display the file info data Yes/No'),
                'filter' => 'text',
                'description' => tr('logic flag to display the file info Yes/No'),
                'since' => '29.0',
                'advanced' => true,
                'options' => [
                    ['text' => tra(''), 'value' => 'n'],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'default' => 'n',
            ],
            'showlinenumbers' => [
                'required' => false,
                'name' => tra('Show line numbers Yes/No'),
                'filter' => 'text',
                'description' => tr('logic flag to display line numbers Yes/No'),
                'since' => '29.0',
                'advanced' => true,
                'options' => [
                    ['text' => tra(''), 'value' => 'n'],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
                'default' => 'n',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tra('Width in pixels'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => '612',
                'advanced' => true,
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels'),
                'since' => '29.0',
                'filter' => 'digits',
                'default' => '792',
                'advanced' => true,
            ],
            ]
        ];

    return $info;
}

function wikiplugin_viewtextfile($data, $params)
{
    $filegallib = TikiLib::lib('filegal');
    $fileId = $params['fileId'];
    $filedetails = [];

    $fileinfo = $filegallib->get_file_info($fileId);

    if (empty($fileinfo)) {
        return "<p>" . tr("Error: No file found.") . "</p>";
    }

    $filedescription = $fileinfo['description'];
    $filename = $fileinfo['filename'];
    $filetype = $fileinfo['filetype'];

    // convert the file size to a human-readable format
    $filesize = $filegallib->convertFileSize($fileinfo['filesize']);

    if ($filetype != 'text/plain') {
        return ("<p>" . tr("Error: the selected file is not a plain text file (text/plain)") . "</p>");
    }

    $file = Tiki\FileGallery\File::id($fileId);
    $filecontent = $file->getContents();

    $filecontent_data = explode("\n", $filecontent);
    $out = [];

    foreach ($filecontent_data as $line_num => $line) {
        if ($line_num >= $params['startline'] && (is_null($params['stopline']) || $line_num <= $params['stopline'])) {
            $out[$line_num] = $line;
        }
    }
    $filecontent_data = $out;

    if ($params['showfileinfo'] == 'y') {
        $filedetails = [
            'fileId' => $params['fileId'],
            'filename' => $filename,
            'filedescription' => $filedescription,
            'filetype' => $filetype,
            'filesize' => $filesize,
        ];
    }

    $smarty = TikiLib::lib('smarty');

    $smarty->assign('showlinenumbers', $params['showlinenumbers'] == 'y');
    $smarty->assign('width', $params['width'] . 'px');
    $smarty->assign('height', $params['height'] . 'px');
    $smarty->assign('content', $filecontent_data);
    $smarty->assign('filedetails', $filedetails);

    return '~np~' . $smarty->fetch('wiki-plugins/wikiplugin_viewtextfile.tpl') . '~/np~';
}
