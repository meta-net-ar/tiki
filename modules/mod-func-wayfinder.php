<?php

//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

function module_wayfinder_info()
{
    return [
        'name' => tra('Structure Tree Navigation'),
        'description' => tra('Navigation panel showing topics available in the immediate structure'),
        'prefs' => [],
        'params' => [],
        ];
}

function module_wayfinder()
{

    global $page_ref_id;

    $smarty = TikiLib::lib('smarty');
    $structlib = TikiLib::lib('struct');

    // NOTE custom Smarty assignments for next and previous sibling were added to renderlib.php
    // because they are used outside of this plugin as well.

    $children = $structlib->s_get_pages($page_ref_id);
    //s_get_pages only lists children, no grandchildren, so it's convenient to use it here.

    $smarty->assign('children', $children);
}
