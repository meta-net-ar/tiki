<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// initially from https://github.com/Studio-42/elFinder/wiki/Adding-file-description-to-Properties-dialog

class tikiElFinder extends elFinder
{
    public static function loadJSCSS()
    {
        global $prefs;
        $headerlib = TikiLib::lib('header');
            $str = $prefs['tiki_minify_javascript'] === 'y' ? 'min' : 'full';
    // elfinder is sensitive to js compression - problem is inside elfinder
    // see http://stackoverflow.com/questions/11174170/js-invalid-left-hand-side-expression-in-postfix-operation for more general details
        $headerlib->add_jsfile('vendor_bundled/vendor/studio-42/elfinder/js/elfinder.' . $str . '.js', true)
            ->add_jsfile('lib/jquery_tiki/elfinder/tiki-elfinder.js');

        $elFinderLang = str_replace(['cn', 'pt-br'], ['zh_CN', 'pt_BR'], Language::getCurrentLanguage());

        if (file_exists('vendor_bundled/vendor/studio-42/elfinder/js/i18n/elfinder.' . $elFinderLang . '.js')) {
            $headerlib->add_jsfile('vendor_bundled/vendor/studio-42/elfinder/js/i18n/elfinder.' . $elFinderLang . '.js');
        }
    }
    public function __construct($opts)
    {
        parent::__construct($opts);
        /* Adding new command */
        $this->commands['info'] = ['target' => true, 'content' => false];
    }

    protected function info($args)
    {
        $target = $args['target'];
        $newDesc = $args['content'];
        $error = [self::ERROR_UNKNOWN, '#' . $target];

        if (
            ($volume = $this->volume($target)) == false
            || ($file = $volume->file($target)) == false
        ) {
            return ['error' => $this->error($error, self::ERROR_FILE_NOT_FOUND)];
        }

        $error[1] = $file['name'];

        if ($volume->commandDisabled('info')) {
            return ['error' => $this->error($error, self::ERROR_ACCESS_DENIED)];
        }

        if (($info = $volume->info($target, $newDesc)) == -1) {
            return ['error' => $this->error($error, $volume->error())];
        }

        return ['info' => $info];
    }
}
