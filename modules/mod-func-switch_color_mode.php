<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Return module information
 *
 * @return array
 */
function module_switch_color_mode_info()
{
    return [
        'name' => tra('Switch Color Mode'),
        'description' => tra('Switch between light, dark, and browser color scheme preference'),
        'params' => [],
    ];
}

/**
 * *TODO* Collect information about admin defined themes and apply value
 * on smarty template engine
 *
 * @param $mod_reference
 * @param $module_params
 */
function module_switch_color_mode($mod_reference, $module_params)
{
    global $tiki_p_admin;
    $smarty = TikiLib::lib('smarty');
    $default_themes_mode = [];
    $custom_themes_mode = [];
    try {
         $default_themes_mode = TikiDb::get()->fetchAll("SELECT icon,name,css_variables FROM tiki_custom_color_modes WHERE custom='n'", null, -1, -1, 'exception');
         $custom_themes_mode = TikiDb::get()->fetchAll("SELECT icon,name,css_variables FROM tiki_custom_color_modes WHERE custom='y'", null, -1, -1, 'exception');
    } catch (Exception $e) {
        $smarty->assign('error', true);
        $message = '';
        if ($tiki_p_admin == 'y') {
            $message = '<span title="' . tra("You need to update your database to start using color modes on your website") . '">' . tra("Your database needs to be updated") . '<i class="bi bi-question"></i></span>';
        }
        $smarty->assign('message', $message);
    }

    $smarty->assign('default_mode', $default_themes_mode);
    $smarty->assign('custom_mode', $custom_themes_mode);
    $smarty->assign('default_icon', 'sun'); //will come from same value as on light icon name
    $headerlib = TikiLib::lib('header');
    $color_modes = json_encode(array_merge($default_themes_mode, $custom_themes_mode));
    $jqCode = ' 
    /*!
    * Color mode toggler for Bootstraps docs (https://getbootstrap.com/)
    * Copyright 2011-2023 The Bootstrap Authors
    * Licensed under the Creative Commons Attribution 3.0 Unported License.
    * This code was derived from the original code.
    * Attribution for the original code goes to The Bootstrap Authors.
    */
   const setup_color_mode = function() {
       "use strict";
   
       const getStoredTheme = () => localStorage.getItem("theme");
       const setStoredTheme = (theme) => localStorage.setItem("theme", theme);
   
       const getPreferredTheme = () => {
           const storedTheme = getStoredTheme();
           if (storedTheme) {
               return storedTheme;
           }
   
           return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
       };
   
       const setTheme = (theme) => {
           if (theme === "auto" && window.matchMedia("(prefers-color-scheme: dark)").matches) {
               document.documentElement.setAttribute("data-bs-theme", "dark");
           } else {
               document.documentElement.setAttribute("data-bs-theme", theme);
           }
       };
   
       setTheme(getPreferredTheme());
   
       window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => {
           const storedTheme = getStoredTheme();
           if (storedTheme !== "light" && storedTheme !== "dark") {
               setTheme(getPreferredTheme());
           }
       });
       const modes = ' . $color_modes . ';
       $("button[data-bs-theme-value]").click(function (e) {
           const theme = $(this).attr("data-bs-theme-value");
           setStoredTheme(theme);
           setTheme(theme);
           $(this).addClass("active");
           $(this).attr("aria-clicked", true);
           let current_icon = $(this).find(".theme_icon").html();
           $("#bd-theme").addClass("loading");
           setTimeout(function(){
                $("#bd-theme").html(current_icon);
                $("#bd-theme").removeClass("loading");
           },100); //small transition when updating the icon
           $("button[data-bs-theme-value]").not(this).removeClass("active").attr("aria-clicked", false);
       });
       if($("button[data-bs-theme-value=\'" + getPreferredTheme() + "\']").length > 0){
            $("button[data-bs-theme-value=\'" + getPreferredTheme() + "\']").trigger("click");
       } else{ //display default icon in case color mode has been deleted
            $("#bd-theme").removeClass("loading");
       }
    };
    setup_color_mode();';
    $headerlib->add_jq_onready($jqCode);
}
