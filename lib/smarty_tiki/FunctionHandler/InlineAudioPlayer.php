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
        $fileId = $params['fileId'];
        $type = $params['type'];

        $uniqueId = uniqid('audio-player-');

        $headerlib = TikiLib::lib('header');
        $headerlib->add_jsfile(NODE_PUBLIC_DIST_PATH . '/plyr/dist/plyr.min.js');
        $headerlib->add_cssfile(NODE_PUBLIC_DIST_PATH . '/plyr/dist/plyr.css');
        $headerlib->add_js(<<<JS
            new Plyr('#$uniqueId', {
                controls: ['play', 'progress', 'current-time', 'duration', 'settings']
            });
        JS);

        return <<<HTML
                <audio playsinline id="$uniqueId" data-file-ref-id="$fileId"><source src="tiki-download_file.php?fileId=$fileId&display" type="$type"></audio>
            HTML;
    }
}
