<?php

/**
 * Gets Category Id from the Category name
 */

function smarty_modifier_categid($category)
{
    $categIdModifier = new \SmartyTiki\Modifier\CategId();
    return $categIdModifier->handle($category);
}
