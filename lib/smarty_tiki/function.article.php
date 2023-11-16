<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/* inserts the content of an rss feed into a module */
function smarty_function_article($params, \Smarty\Template $template)
{
    $smartyFunctionArticleHandler = new \SmartyTiki\FunctionHandler\Article();
    return $smartyFunctionArticleHandler->handle($params, $template);
}
