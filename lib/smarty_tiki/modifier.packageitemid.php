<?php

function smarty_modifier_packageitemid($token)
{
    $packageitemIdModifier = new \SmartyTiki\Modifier\PackageItemId();
    return $packageitemIdModifier->handle($token);
}
