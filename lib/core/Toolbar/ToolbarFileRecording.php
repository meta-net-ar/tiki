<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarFileRecording extends ToolbarUtilityItem
{
    private $id;
    private $recordingFieldId;
    private $recordingGalleryId;
    private $trackerItemId;

    public function __construct($objectId, $isWysiwyg = false, $recordingFieldId = 0, $recordingGalleryId = 0)
    {
        $this->trackerItemId = $objectId;
        $this->recordingFieldId = $recordingFieldId;
        $this->recordingGalleryId = $recordingGalleryId;
        $this->id = $this->domElementId . '_toolbar-file-recording';
        $this->setLabel(tra('Start recording'))
            ->setIcon('img/icons/file-manager.png')
            ->setIconName('stop-circle')
            ->setType('FileRecording')
            ->setWysiwygToken('filerecording')
            ->setMarkdownSyntax('filerecording')
            ->setMarkdownWysiwyg('filerecording')
            ->setClass('qt-file-recording')
            ->addRequiredPreference('fgal_use_record_rtc_screen');

        if (! $isWysiwyg) {
            $this->setupWikiJs();
        }

        TikiLib::lib('header')->add_js_module('import "@jquery-tiki/tracker-fields/files";');
    }

    private function setupWikiJs()
    {
        $onClickHandler = $this->getOnClick();
        TikiLib::lib('header')->add_jq_onready("
            $('#{$this->id}').on('click', function(e) {
                e.preventDefault();
                $onClickHandler
            });
        ");
    }

    private function getPlainHtml(): string
    {
        $icon = $this->getIconHtml();
        $title = $this->getLabel();
        $id = $this->id;
        return "<a title=\":$title\" class=\"toolbar btn btn-sm px-2 tips bottom\" href='#' id='$id'>$icon</a>";
    }

    private function getModalContent(): string
    {
        $options = [
            [
                'type' => 'audio',
                'label' => tra('Audio only'),
                'icon' => 'microphone',
            ],
            [
                'type' => 'screen',
                'label' => tra('Screen only'),
                'icon' => 'desktop',
            ],
            [
                'type' => 'screenandaudio',
                'label' => tra('Screen and audio'),
                'icon' => 'desktop',
            ],
            [
                'type' => 'cameraandaudio',
                'label' => tra('Camera and audio'),
                'icon' => 'camera',
            ]
        ];

        $optionsHtml = implode('', array_map(function ($option) {
            $iconHtml = smarty_function_icon(['name' => $option['icon']], TikiLib::lib('smarty')->getEmptyInternalTemplate());
            return <<<HTML
            <div class="recording-type__item bg-secondary-subtle border rounded border-3 border-white p-2 d-flex flex-column align-items-center w-25" data-type="{$option['type']}" role="button">
                <div class="fs-2">$iconHtml</div>
                <div class="fw-light text-center">{$option['label']}</div>
            </div>
            HTML;
        }, $options));

        return <<<HTML
        <div class="recording-type d-flex gap-2 flex-wrap justify-content-center">
            $optionsHtml
        </div>
        HTML;
    }

    public function getWikiHtml(): string
    {
        return $this->getPlainHtml();
    }

    public function getMarkdownHtml(): string
    {
        return $this->getPlainHtml();
    }

    public function getOnClick(): string
    {
        return <<<JS
        $.openModal({
            title: tr("What do you want to record?"),
            size: "modal-lg",
            content: `{$this->getModalContent()}`,
            buttons: [
                {
                    text: tr('Start recording'),
                    type: 'primary start-recording',
                    attrs: {
                        'data-tracker-files': 'true',
                        'data-field-id': '{$this->recordingFieldId}',
                        'data-gallery-id': '{$this->recordingGalleryId}',
                        'data-tracker-item-id': '{$this->trackerItemId}',
                    },
                    onClick: function() {}
                },
                {
                    text: tr('Stop recording'),
                    type: 'secondary stop-recording d-none',
                    onClick: function() {}
                }
            ]
        })
        JS;
    }
}
