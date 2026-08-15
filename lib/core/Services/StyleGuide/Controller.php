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

        $generatedCss = is_readable(THEME_CUSTOMIZER_GENERATED_CSS_PATH)
            ? file_get_contents(THEME_CUSTOMIZER_GENERATED_CSS_PATH)
            : '';

        return [
            'title' => tr('Theme Customizer'),
            'sections' => $sections,
            'generated_css' => $generatedCss,
        ];
    }

    public function action_save_generated_css($input)
    {
        Services_Exception_Denied::checkGlobal('admin');
        $customCss = $input->generated_css->text();

        $dir = dirname(THEME_CUSTOMIZER_GENERATED_CSS_PATH);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_put_contents(THEME_CUSTOMIZER_GENERATED_CSS_PATH, $customCss) !== false) {
            Feedback::success(tr('The custom generated CSS has been saved'));
        } else {
            Feedback::error(tr("Couldn't save the custom generated CSS file. Check that public/storage/custom_css/ is writable."));
        }
        return true;
    }

    public function action_reset_generated_css($input)
    {
        Services_Exception_Denied::checkGlobal('admin');

        if (! file_exists(THEME_CUSTOMIZER_GENERATED_CSS_PATH)) {
            Feedback::note(tr('No custom generated CSS file found.'));
            return true;
        }

        if (file_put_contents(THEME_CUSTOMIZER_GENERATED_CSS_PATH, '') !== false) {
            Feedback::success(tr('The custom generated CSS has been reset'));
        } else {
            Feedback::error(tr("Couldn't reset the custom generated CSS file. Check that public/storage/custom_css/ is writable."));
        }
        return true;
    }
}
