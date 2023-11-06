<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// Smarty translation prefilter. This prefilter tries to offload the tr block from as much work as possible to keep
// the performance penalty of translation limited to compilation. It does not intervene if an argument is given (lang)
// and in some cases when translation may only be possible at runtime.

function smarty_prefilter_tr($source, \Smarty\Template $template)
{
    $smartyPrefilterTr = new \SmartyTiki\Filter\Pre\Tr();
    return $smartyPrefilterTr->filter($source, $template);
}
