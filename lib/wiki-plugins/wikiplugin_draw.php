<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_draw_info()
{
    return [
        'name' => tra('Draw'),
        'documentation' => 'PluginDraw',
        'description' => tra('Embed a drawing in a page'),
        'prefs' => [ 'feature_draw' , 'wikiplugin_draw'],
        'iconname' => 'edit',
        'tags' => [ 'basic' ],
        'introduced' => 7.1,
        'params' => [
            'id' => [
                'required' => false,
                'name' => tra('Drawing ID'),
                'description' => tra('Internal ID of the file ID'),
                'filter' => 'digits',
                'accepted' => ' ID number',
                'since' => '7.1',
                'profile_reference' => 'file',
            ],
            'width' => [
                'required' => false,
                'name' => tra('Width'),
                'description' => tr(
                    'Width in pixels or percentage. Default value is page width, for example, %0 or %1',
                    '<code>200px</code>',
                    '<code>100%</code>'
                ),
                'filter' => 'text',
                'accepted' => 'Number of pixels followed by \'px\' or percent followed by % (e.g. "200px" or "100%").',
                'since' => '7.1'
            ],
            'height' => [
                'required' => false,
                'name' => tra('Height'),
                'description' => tra('Height in pixels or percentage. Default value is complete drawing height.'),
                'filter' => 'text',
                'accepted' => 'Number of pixels followed by \'px\' or percent followed by % (e.g. "200px" or "100%").',
                'since' => '7.1'
            ],
            'archive' => [
                'required' => false,
                'name' => tra('Force Display Archive'),
                'description' => tr('The latest revision of file is automatically shown, by setting archive to Yes (%0),
                it bypasses this check and shows the archive rather than the latest revision', '<code>y</code>'),
                'filter' => 'alpha',
                'default' => 'n',
                'since' => '8.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
    ];
}

function wikiplugin_draw($data, $params)
{
    global $tiki_p_upload_files, $user, $page;
    $smarty = TikiLib::lib('smarty');
    $filegallib = TikiLib::lib('filegal');

    extract(array_merge($params, []), EXTR_SKIP);

    static $drawIndex = 0;
    ++$drawIndex;

    if (is_null($id)) {
        //check permissions
        if ($tiki_p_upload_files != 'y') {
            return;
        }

        $label = tra('Draw New SVG Image in Gallery');
        $page = htmlentities($page, ENT_COMPAT);
        $content = htmlentities($data, ENT_COMPAT);
        $formId = "form$drawIndex";
        $gals = $filegallib->list_file_galleries(0, -1, 'name_desc', $user);

        $galHtml = "";
        $wp_draw_cmp = function ($a, $b) {
            return strcmp(strtolower($a["name"]), strtolower($b["name"]));
        };

        usort($gals['data'], $wp_draw_cmp);
        foreach ($gals['data'] as $gal) {
            if ($gal['name'] != "Wiki Attachments" && $gal['name'] != "Users File Galleries") {
                // While smarty_function_bootstrap_modal is available for such use cases, it doesn't fit in cases where the final html is generated as strings because there happens to be quotes not properly escaped.
                $galHtml .= "<li><a class='dropdown-item' href='tiki-ajax_services.php?controller=draw&action=edit&galleryId=" . $gal['id'] . "&modal=1' data-tiki-bs-toggle=\"modal\" data-bs-backdrop=\"static\" data-bs-target=\".footer-modal.fade:not(.show):first\" data-size='modal-fullscreen'>" . $gal['name'] . "</a></li>";
            }
        }

        return <<<EOF
        ~np~
        <div class="dropdown">
            <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                $label
            </button>
            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                $galHtml
            </ul>
        </div>
        ~/np~
EOF;
    }

    $fileInfo = $filegallib->get_file_info($id);

    //this sets the image to latest in a group of archives
    if ($archive != 'y') {
        if (! empty($fileInfo['archiveId']) && $fileInfo['archiveId'] > 0) {
            $id = $fileInfo['archiveId'];
            $fileInfo = $filegallib->get_file_info($id);
        }
    }

    if (! isset($fileInfo['created'])) {
        return tra("File not found.");
    } else {
        $globalperms = Perms::get([ 'type' => 'file', 'object' => $fileInfo['fileId'] ]);

        if ($globalperms->view_file_gallery != 'y') {
            return "";
        }

        $label = tra('Edit SVG Image');
        $ret = '<div type="image/svg+xml" class="svgImage pluginImg table-responsive' . $fileInfo['fileId'] . '" style="' .
            (! is_null($height) ? "height: $height;" : "" ) .
            (! is_null($width) ? "width: $width;" : "" )
        . '">' . $fileInfo['data'] . '</div>';

        if ($globalperms->upload_files == 'y') {
            $editicon = \SmartyTiki\FunctionHandler\Icon::render(['name' => 'edit'], $smarty->getEmptyInternalTemplate());
            $ret .= "<a title='$label' href='tiki-ajax_services.php?controller=draw&action=edit&modal=1&fileId=$id&page=$page&index=$drawIndex" .
                (! is_null($width) ? "&width=$width" : "") . (! is_null($height) ? "&height=$height" : "") .
                "' data-tiki-bs-toggle=\"modal\" data-bs-backdrop=\"static\" data-bs-target=\".footer-modal.fade:not(.show):first\" data-size='modal-fullscreen'  title='Edit: " . $fileInfo['filename'] . "'>" .
                $editicon . "</a>";
        }


        return '~np~' . $ret . '~/np~';
    }
}
