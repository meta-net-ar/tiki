<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use TikiLib;

class InlineAudioPlayer extends \Smarty\FunctionHandler\Base
{
    public function handle($params, \Smarty\Template $template)
    {
        TikiLib::lib('header')->add_js_module('import "@jquery-tiki/tracker-fields/files";');
        $icon = smarty_function_icon(['name' => 'play'], $template);
        $pauseIcon = smarty_function_icon(['name' => 'pause', 'size' => 1], $template);
        $stopIcon = smarty_function_icon(['name' => 'xmark', 'size' => 1], $template);
        $src = 'tiki-download_file.php?fileId=' . $params['fileId'];

        return <<<HTML
        <button class="play-audio btn btn-sm btn-link bg-secondary-subtle rounded-circle" data-src="{$src}">
            {$icon}
        </button>
        <button class="pause-audio btn btn-sm btn-link bg-secondary-subtle rounded-circle d-none">
            {$pauseIcon}
        </button>
        <div class="audio-timer">
            <div class="end-line rounded-pill bg-secondary-subtle">
                <div class="progress-bar rounded-pill bg-secondary"></div>
            </div>
            <div class="time d-none fw-lighter"></div>
        </div>
        <button class="stop-audio btn btn-sm btn-link rounded-circle d-none">
            {$stopIcon}
        </button>
        HTML;
    }
}
