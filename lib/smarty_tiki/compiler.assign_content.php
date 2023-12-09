<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function smarty_compiler_assign_content($tag_attrs, \Smarty\Compiler\Template $compiler, $parameter = [], $tag = null, $function = null)
{
    $smartyCompilerAssignContent = new \SmartyTiki\Compile\Tag\AssignContent();
    return $smartyCompilerAssignContent->compile($tag_attrs, $compiler, $parameter, $tag, $function);
}
