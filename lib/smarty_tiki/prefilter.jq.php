<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_prefilter_jq($source, \Smarty\Template $template)
{
    $smartyPrefilterJq = new \SmartyTiki\Filter\Pre\Jq();
    return $smartyPrefilterJq->filter($source, $template);
}
