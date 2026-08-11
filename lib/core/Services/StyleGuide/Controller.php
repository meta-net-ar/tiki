<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// phpcs:disable PSR1.Methods.CamelCapsMethodName.NotCamelCaps
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
            ->add_cssfile('themes/base_files/css/theme-customizer.css')
            ->add_jsfile('lib/jquery_tiki/theme-customizer.js')
        ;

        return [
            'title' => tr('Theme Customizer'),
            'sections' => $sections,
        ];
    }

    public function action_save_generated_css($input)
    {
        Services_Exception_Denied::checkGlobal('admin');
        $customCss = $input->header_custom_generated_css->text();
        $tikilib = TikiLib::lib('tiki');

        try {
            $updated = $tikilib->set_preference('header_custom_generated_css', $customCss);
            if ($updated) {
                Feedback::success(tr('The custom generated CSS preference has been updated'));
            } else {
                Feedback::warning(tr('An error occurred while updating the custom generated CSS preference'));
            }
        } catch (Exception $e) {
            Feedback::error(tr("Couldn't update the custom generated CSS preference: %0", $e->getMessage()));
        }
        return true;
    }
}
