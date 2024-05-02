<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace I18n;

use Language_Exception;

require_once('lib/tikilib.php');
require_once('lib/init/typography.php');

/**
 * The code necessary for the tr and tra functions.   Must be kept small, performance critical and included very early
 */
class LanguageTranslator
{
    private static array $instances = [];
    private array $interactiveCollectedStrings = [];
    private array $translations = [];
    /** Language code */
    private string $lang;

    public static function getInstance(string $lang, array $options = []): LanguageTranslator
    {
        $hash = md5(serialize(func_get_args()));
        if (! isset(self::$instances[$hash])) {
            self::$instances[$hash] = new self($lang, $options);
        }
        return self::$instances[$hash];
    }

    public static function getLanguageFromPrefs()
    {
        global $user, $user_preferences;
        if (isset($user) && isset($user_preferences[$user]['language'])) {
            $retval = $user_preferences[$user]['language'];
        } else {
            global $prefs;
            $retval = $prefs['language'];
        }
        if (! $retval) {
            throw new Language_Exception("Unable to find a language in preferences.  This isn't supposed to happen");
        }
        return $retval;
    }

    private function __construct(string $lang, array $options)
    {
        global $prefs;

        $this->lang = $lang;
        $this->initLanguageFromFiles($lang);
        $this->loadThemeOverrides();
        if (empty($options['skipDb']) && isset($prefs['lang_use_db']) && $prefs['lang_use_db'] == 'y') {
            $this->initLanguageFromDb();
        }
    }

    public function translate(string $content, array $args = [])
    {
        list($content, $out, $wasTranslated) = $this->traImpl($content, $this->lang, $args);
        $out = typography($out, $this->lang, true);

        $this->populateCollectedTranslations($content, $out, $wasTranslated);
        return $out;
    }

    private function initLanguageFromDb()
    {
        $translations = [];
        $tikilib = \TikiLib::lib('tiki');
        if (isset($tikilib)) {
            $query = "select `source`, `tran` from `tiki_language` where `lang`=?";
            $result = $tikilib->fetchAll($query, [$this->lang]);

            foreach ($result as $row) {
                $translations[ $row['source'] ] = $row['tran'];
            }
        }
        $this->translations = array_merge($this->translations, $translations);
    }

    /**
     * initialize language $lg
     * @param string $lg short language code, such as en-uk
     */
    private function initLanguageFromFiles($lg)
    {
        global $tikidomain, $prefs;
        if (is_file(LANG_SRC_PATH . "/$lg/language.php")) {//Base language must exist
            $lang = [];
            require_once(LANG_SRC_PATH . "/$lg/language.php");

            // include mods language files if any
            $files = glob(LANG_SRC_PATH . "/$lg/language_*.php");
            if (is_array($files)) {
                global $lang_mod;
                foreach ($files as $file) {
                    require_once($file);
                    $lang = array_merge($lang, $lang_mod);
                }
            }

            //Which _custom multitiki site matches
            $sitePath = \Tiki\Paths\Customization::getCurrentSitePublicPath(LANG_PATH_FRAGMENT . "/$lg/" . LANG_CUSTOM_PHP_BASENAME);

            $customFileLookupPaths = [
                LANG_SRC_PATH . "/$lg/" . LANG_CUSTOM_PHP_BASENAME, //Legacy
                LANG_SRC_PATH . "/$lg/$tikidomain/" . LANG_CUSTOM_PHP_BASENAME, //Legacy
                TIKI_CUSTOMIZATIONS_SHARED_PATH . '/' . LANG_PATH_FRAGMENT . "/$lg/" . LANG_CUSTOM_PHP_BASENAME
            ];
            if ($sitePath) {
                $customFileLookupPaths[] = $sitePath;
            }

            foreach ($customFileLookupPaths as $customfile) {
                if (is_file($customfile)) {
                    if (! self::checkFileBOM($customfile)) {
                        require_once($customfile);
                    }
                }
            }

            try {
                $languageLib = \TikiLib::lib('language');
                $lang = array_merge($lang, $languageLib::loadExtensions($lg));
            } catch (\Exception $e) {
                // ignore
            }

            $this->translations = $lang;
        }
    }
    /**
     * Load additional language files that are located in theme folder
     *
     * @param $lang
     * @param $themeName
     * @throws Exception
     */
    private function loadThemeOverrides()
    {
        //This is just because the class isn't fully migrated, we need to load it as as side effect - benoitg - 2024-04-10
        \TikiLib::lib('theme');
        $themePath = \ThemeLib::getThemePath(null, null, null, true);
        $themeLangFragment = "lang/$this->lang/language.php";
        $themeLangPath = $themePath . '/' . $themeLangFragment;
        if (file_exists($themeLangPath)) {
            $lang = $this->translations;
            require_once $themeLangPath;

            if (isset($lang) && is_array($lang)) {
                $this->translations = array_merge($this->translations, $lang);
            } else {
                trigger_error("Language file $themeLangPath is not in the right format.", E_USER_WARNING);
            }
        }
    }

    private function traImpl($content, $lg = '', $args = []): array
    {
        global $prefs, $tikilib;
        if (empty($content) && $content !== '0') {
            return ['', false, false];
        }

        $lang = $this->translations;

        if ($lg and isset($lang[$content])) {
            return [$content, $this->argReplace($lang[$content], $args), true];
        }

        if (! is_null($lang) and $lg and $key = array_search($content, $lang)) {
            return [$key, $this->argReplace($content, $args), true];
        }

        // If no translation has been found and if the string ends with a punctuation,
        //   try to translate punctuation separately (e.g. if the content is 'Login:' or 'Login :',
        //   then it will try to translate 'Log In' and ':' separately).
        // This should avoid duplicated strings like 'Log In' and 'Log In:' that were needed before
        //   (because there is no space before ':' in english, but there is one in others like french)
        $lastCharacter = $content[strlen($content) - 1];
        if (in_array($lastCharacter, \Language::PUNCTUATIONS)) { // Should stay synchronized with Language_WriteFile::writeStringsToFile()
            $new_content = substr($content, 0, -1);
            if (isset($lang[$new_content])) {
                return [
                $content,
                $this->argReplace(
                    $lang[$new_content] . ( isset($lang[$lastCharacter])
                        ? $lang[$lastCharacter]
                        : $lastCharacter ),
                    $args
                ),
                    true
                ];
            }
        }

        // ### Trebly:B00624-01:added test on tikilib existence : on the first launch of tra tikilib is not yet set
        if (isset($prefs['record_untranslated']) && $prefs['record_untranslated'] == 'y' && $lg != 'en' && isset($tikilib)) {
            $query = 'select `id` from `tiki_untranslated` where `source`=? and `lang`=?';
            if (! $tikilib->getOne($query, [$content, $lg])) {
                $query = "insert into `tiki_untranslated` (`source`,`lang`) values (?,?)";
                $tikilib->query($query, [$content, $lg], -1, -1, false);
            }
        }

        return [$content, $this->argReplace($content, $args), false];
    }

    private function argReplace(string $content, array $args): string
    {
        if (! count($args)) {
            $out = $content;
        } else {
            $needles = [];
            // reverse makes sure %11, %12, etc. are translated
            $replacements = array_reverse($args);
            $keys = array_reverse(array_keys($args));
            foreach ($keys as $num) {
                $needles[] = "%$num";
            }

            $out = str_replace($needles, $replacements, $content);
        }

        return $out;
    }

    /**
     * Populate the collected strings global variable with information related with the translation
     * @param $original
     * @param $printed
     * @param $isTranslated
     */
    private function populateCollectedTranslations($original, $printed, $isTranslated)
    {
        if (self::inInteractiveMode()) {
            $this->interactiveCollectedStrings[md5($original . '___' . $printed)] = [$original, html_entity_decode($printed), $isTranslated];
        }
    }

    private static function inInteractiveMode(): bool
    {
        return isset($_SESSION['interactive_translation_mode']) && $_SESSION['interactive_translation_mode'] != 'off';
    }

    public function getInteractiveCollectedStrings(): array
    {
        return $this->interactiveCollectedStrings;
    }

    /**
     * Checks a php file for a Byte Order Mark (BOM) and trigger error (and report error for admin)
     *
     * @param string $filename      full path of file to check
     * @param bool $try_to_fix      if file perms allow remove BOM if found
     *
     * @return bool                 true if file still has a BOM
     */
    private static function checkFileBOM($filename, $try_to_fix = true)
    {
        $BOM_found = false;

        if (is_readable($filename)) {
            $file = @fopen($filename, "r");
            $BOM_found = (fread($file, 3) === "\xEF\xBB\xBF");

            if ($try_to_fix && $BOM_found && is_writable($filename)) {
                $content = fread($file, filesize($filename));
                fclose($file);
                file_put_contents($filename, $content);
                trigger_error('File "' . $filename . '" contained a BOM which has been fixed.');
                $BOM_found = false;
            } else {
                fclose($file);
            }
        }
        if ($BOM_found) {
            $message = 'Warning: File "' . $filename . '" contains a BOM which cannot be fixed. Please re-edit and save as "UTF-8 without BOM"';
            if (\Perms::get()->admin) {
                \Feedback::error($message);
            }
            trigger_error($message);
        }

        return $BOM_found;
    }
}
