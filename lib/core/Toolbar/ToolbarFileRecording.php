<?php

namespace Tiki\Lib\core\Toolbar;

use TikiLib;

class ToolbarFileRecording extends ToolbarUtilityItem
{
    private $id;

    public function __construct($domElementId, $isWysiwyg = false)
    {
        $this->domElementId = $domElementId;
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

        TikiLib::lib('header')->add_js_module('import { handRecordingTypeInputsState } from "@tiki/editor-toolbar/fileRecording"; window.handRecordingTypeInputsState = handRecordingTypeInputsState;');
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
        return <<<HTML
        <div class="recording-type d-flex gap-2 flex-wrap justify-content-center">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="recordMicrophone" name="microphone">
                <label class="form-check-label" for="recordMicrophone">Microphone</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="recordScreen" name="screen">
                <label class="form-check-label" for="recordScreen">Screen</label>
            </div>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="recordCamera" name="camera">
                <label class="form-check-label" for="recordCamera">Camera</label>
            </div>
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
        global $prefs;
        $galleryId = $prefs['fgal_use_record_rtc_screen_gallery_id'];

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
                        'data-textarea-filerecording': 'true',
                        'data-gallery-id': "$galleryId",
                        'data-area-id': '{$this->domElementId}'
                    },
                    onClick: function() {}
                },
                {
                    text: tr('Stop recording'),
                    type: 'secondary stop-recording d-none',
                    onClick: function() {}
                }
            ],
            open: function() {
                handRecordingTypeInputsState();
            }
        })
        JS;
    }
}
