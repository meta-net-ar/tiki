<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class InteractiveTranslation extends Base
{
    public function handle($params, Template $template)
    {
        $headerlib = \TikiLib::lib('header');
        $smarty = \TikiLib::lib('smarty');

        $strings = get_collected_strings();
        if (count($strings) == 0) {
            return;
        }

        usort($strings, [$this, 'sortStringsByLength']);

        $strings = json_encode($strings);

        // add wrench icon link
        $help .= smarty_block_self_link(
            [
                '_icon' => 'wrench',
                '_script' => 'tiki-edit_languages.php',
                '_title' => tra('Click here to go to Edit Languages')
            ],
            '',
            $template
        );

        $jq = <<<JS
    var data = $strings;
JS;

        $headerlib->add_jq_onready($jq);
        $headerlib->add_jq_onready(file_get_contents('lib/language/js/interactive_translation.js'));

        return $smarty->fetch('interactive_translation_box.tpl');
    }

    private function sortStringsByLength($a, $b)
    {
        $a = strlen($a[1]);
        $b = strlen($b[1]);

        if ($a == $b) {
            return 0;
        } elseif ($a > $b) {
            return -1;
        } else {
            return 1;
        }
    }
}
