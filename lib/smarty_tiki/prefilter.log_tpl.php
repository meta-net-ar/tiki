<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// To disable for certain templates where this would break, temporarily set a log_tpl template variable to false.
function smarty_prefilter_log_tpl($source, \Smarty\Template $template)
{
    $prefilterLogTpl = new \SmartyTiki\Filter\Pre\LogTpl();
    return $prefilterLogTpl->filter($source, $template);
}
