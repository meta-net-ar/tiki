<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_outputfilter_highlight($source, \Smarty\Template $template)
{
    $smartyOutputFilterHighlight = new \SmartyTiki\Filter\Output\Highlight();
    return $smartyOutputFilterHighlight->filter($source, $template);
}
