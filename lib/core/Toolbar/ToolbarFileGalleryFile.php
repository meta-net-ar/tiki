<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarFileGalleryFile extends ToolbarFileGallery
{
    public function __construct()
    {
        parent::__construct();

        $this->setLabel(tra('Choose or upload files'))
            ->setIconName('upload')
            ->setIcon(tra('img/icons/upload.png'))
            ->setWysiwygToken('tikifile')
            ->setMarkdownSyntax('tikifile')
            ->setMarkdownWysiwyg('tikifile')
            ->setType('FileGallery')
            ->setClass('qt-filegal')
            ->addRequiredPreference('feature_filegals_manager');
    }
    public function getOnClick(): string
    {
        $smarty = TikiLib::lib('smarty');
        return 'openFgalsWindow(\''
            . \SmartyTiki\FunctionHandler\FileGalManagerUrl::render(['area_id' => $this->domElementId], $smarty->getEmptyInternalTemplate())
            . '&insertion_syntax=file\', true);';
    }
}
