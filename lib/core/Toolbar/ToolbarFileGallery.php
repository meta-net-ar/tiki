<?php

namespace Tiki\Lib\core\Toolbar;

use Smarty_Tiki;
use TikiLib;

class ToolbarFileGallery extends ToolbarUtilityItem
{
    public function __construct()
    {
        $this->setLabel(tra('Choose or upload images'))
            ->setIconName('images')
            ->setIcon(tra('img/icons/pictures.png'))
            ->setWysiwygToken('tikiimage')
            ->setMarkdownSyntax('tikiimage')
            ->setMarkdownWysiwyg('tikiimage')
            ->setType('FileGallery')
            ->setClass('qt-filegal')
            ->addRequiredPreference('feature_filegals_manager');
    }

    public function getOnClick(): string
    {
        global $prefs;
        /** @var Smarty_Tiki $smarty */
        $smarty = TikiLib::lib('smarty');
        if ($prefs['fgal_elfinder_feature'] !== 'y' || $prefs['fgal_elfinder_on_toolbar'] !== 'y') {
            return 'openFgalsWindow(\''
                . smarty_function_filegal_manager_url(['area_id' => $this->domElementId, 'allowedMimeTypes' => ['image/*']], $smarty->getEmptyInternalTemplate())
                . '\', true);';
        } else {
            include_once 'lib/jquery_tiki/elfinder/tikiElFinder.php';
            \tikiElFinder::loadJSCSS();
            TikiLib::lib('header')->add_jq_onready(
                'window.handleFinderInsertAt = function (files, elfinder, area_id) {
                    const hashes = files.map(function (file) {
                        return file.hash;
                    });
                    $.getJSON($.service("file_finder", "finder"), { cmd: "tikiFileFromHash", hash: hashes },
                        function (data) {
                            bootstrap.Modal.getInstance($(window).data("elFinderDialog")).hide();
                            $(window).data("elFinderDialog", null);
                            let wiki_syntax = "";
                            for (var i = 0; i < data.length; i++) {
                                if (i > 0) {
                                    wiki_syntax += "\n";
                                }
                                wiki_syntax += data[i].wiki_syntax;
                            }
                            window.insertAt(area_id, wiki_syntax);
                            return false;
                        }
                    );
                };'
            );
            return '
            var area_id = (typeof editor === \'undefined\' ?  \'' . $this->domElementId . '\' : editor.name);
            openElFinderDialog(
                this,
                {
                    defaultGalleryId: ' . (empty($prefs['home_file_gallery']) ? $prefs['fgal_root_id'] : $prefs['home_file_gallery']) . ',
                    deepGallerySearch: true,
                    ticket: \'' . smarty_function_ticket(['mode' => 'get'], $smarty->getEmptyInternalTemplate()) . '\',
                    getFileCallback: function(file,elfinder) {
                            window.handleFinderInsertAt(file,elfinder,area_id);
                        },
                    eventOrigin:this,
                    uploadCallback: function (data) {
                            if (data.data.added.length === 1 && confirm(tr(\'Do you want to use this file in your page?\'))) {
                                window.handleFinderInsertAt(data.data.added[0],window.elFinder,area_id);
                            }
                        }
                }
            );';
        }
    }

    public function getWysiwygToken(): string
    {
        if (! empty($this->wysiwyg)) {
            $exec_js = str_replace('&amp;', '&', $this->getOnClick());

            $this->setupCKEditorTool($exec_js);
        }
        return $this->wysiwyg;
    }
}
