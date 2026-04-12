<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_StyleGuide_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('theme_customizer');
    }

    /**
     * Display the theme customizer tool
     *
     * @param JitFilter $input
     *
     * @return array
     */
    public function action_show($input)
    {
        Services_Exception_Denied::checkGlobal('admin');
        $sections = $input->sections->text();

        if (empty($sections)) {
            $sections = [
                'alerts',
                'buttons',
                'colors',
                'dropdowns',
                'fonts',
                'forms',
                'icons',
                'headings',
                'lists',
                'navbars',
                'tables',
                'tabs',
            ];
        } else {
            $sections = explode(',', $sections);
        }

        TikiLib::lib('header')
            ->add_cssfile('lib/jsfontpicker/fontpicker.css')
            ->add_cssfile('themes/base_files/css/theme-customizer.css')
            ->add_jsfile('lib/jquery_tiki/theme-customizer.js')
            ->add_js_module(<<<'JS'
                import initFontPicker from "@jquery-tiki/tiki-fontpicker_init";
                initFontPicker(".tiki-fontpicker", { customizer: true });
            JS)
        ;

        return [
            'title' => tr('Theme Customizer'),
            'sections' => $sections,
        ];
    }
}
