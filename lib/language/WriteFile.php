<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once('Language.php');

/**
 * @package   Tiki
 * @subpackage    Language
 * Class to update language.php file with new
 * collected strings.
 */
class Language_WriteFile
{
    /**
     * Representation of the language file.
     * @var Language_File
     */
    protected $parseFile;

    /**
     * Path to a language.php file
     * @var string
     */
    protected $filePath;

    /**
     * Path to temporary language file.
     * @var string
     */
    protected $tmpFilePath;

    /**
     * Current language translations.
     * @var array
     */
    protected $translations;

    public function __construct(Language_File $parseFile)
    {
        $this->parseFile = $parseFile;
        $this->filePath = $parseFile->filePath;
        $this->tmpFilePath = $this->filePath . '.tmp';

        if (! is_writable($this->filePath)) {
            throw new Language_Exception("Can't write to file $this->filePath.");
        }
    }

    /**
     * Update language.php file with new strings.
     *
     * @param array $strings English strings collected from source files
     * @param bool $outputFiles whether file paths were string was found should be included or not in the output
     * @param string $language current language being processed
     * @param string|null $targetDir directory where language files should be written (null means same as the current working directory)
     * @param bool $skipRemove whether to skip removing the old or unused strings from the language files. To skip, set to true. The existing translations in the destination file will not be overwritten or removed from the file if not found in the incoming translations.
     * @return null
     */
    public function writeStringsToFile(array $strings, $outputFiles = false, string $language = "", $targetDir = null, $skipRemove = false)
    {
        $lang = [];
        if (empty($strings)) {
            return false;
        }
        if (empty($targetDir)) {
            $targetDir = getcwd();
        }

        $backupTranslations = [];
        if ($skipRemove) {
            // If the language file is not empty, we need to backup the translations
            // to restore them later
            include($this->filePath);
            $backupTranslations = $lang ?? [];
        }

        // backup original language file
        copy($this->filePath, $this->filePath . '.old');

        $this->translations = $this->parseFile->getTranslations();

        $entries = [];
        foreach ($strings as $string) {
            if (isset($this->translations[$string['name']])) {
                $string['translation'] = $this->translations[$string['name']];
            } else {
                // Handle punctuations at the end of the string (cf. comments in lib/init/tra.php)
                // For example, if the string is 'Login:', we put 'Login' for translation instead
                // (except if we already have an explicit translation for 'Login:', in which case we don't reach this else)
                $stringLength = strlen($string['name']);
                $stringLastChar = $string['name'][$stringLength - 1];

                if (in_array($stringLastChar, Language::PUNCTUATIONS)) {
                    $trimmedString = substr($string['name'], 0, $stringLength - 1);
                    $string['name'] = $trimmedString;
                    if (isset($this->translations[$trimmedString])) {
                        $string['translation'] = $this->translations[$trimmedString];
                    }
                }
            }
            $entries[$string['name']] = $string;
        }

        foreach ($backupTranslations as $key => $value) {
            if (array_key_exists($key, $entries)) {
                $entries[$key]['translation'] = $value;
            } else {
                $entries[$key] = [
                    'name' => $key,
                    'translation' => $value,
                ];
            }
        }

        $handle = fopen($this->tmpFilePath, 'w');

        if ($handle) {
            fwrite($handle, "<?php\n");

            $isCustomDir = self::isCustomDir($targetDir);

            $this->writeLanguageFile($handle, $entries, $outputFiles, $language, $isCustomDir);

            fclose($handle);
        }

        rename($this->tmpFilePath, $this->filePath);
    }

    /**
     * Return the text used for language.php header
     * @return string
     */
    protected function fileHeader()
    {
        $header = <<<TXT
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// 
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// The original strings (English) are case-sensitive.

/* Note for translators about translation of text ending with punctuation
 *
 * The current list of concerned punctuation can be found in 'lib/init/tra.php'
 * On 2009-03-02, it is: (':', '!', ';', '.', ',', '?')
 * For clarity, we explain here only for colons: ':' but it is the same for the rest
 *
 * Short version: it is not a problem that string "Login:" has no translation. Only "Login" needs to be translated.
 *
 * Technical justification:
 * If a string ending with colon needs translating (like "{tr}Login:{/tr}")
 * then Tiki tries to translate 'Login' and ':' separately.
 * This allows to have only one translation for "{tr}Login{/tr}" and "{tr}Login:{/tr}"
 * and it still allows to translate ":" as " :" for languages that
 * need it (like French)
 * Note: the difference is invisible but " :" has an UTF-8 non-breaking-space, not a regular space, but the UTF-8 equivalent of the HTML &nbsp;.
 * This allows correctly displaying emails and JavaScript messages, not only web pages as would happen with &nbsp;.
 */

TXT;

        return $header;
    }

    /**
     * Format a pair source and translation as
     * a string to be written to a language.php file
     *
     * @param array $entry an array with the English source string and the translation if any
     * @param bool $outputFiles whether file paths were string was found should be included or not in the output
     * @param string $language current language being processed
     * @return string
     */
    protected function formatString(array $entry, $outputFiles = false, string $language = "")
    {
        // final formated string
        $string = '';

        if ($outputFiles && (isset($entry['files']) && ! empty($entry['files']))) {
            $string .= '/* ' . join(', ', $entry['files']) . " */\n";
        }

        if ($language == 'en' && ! isset($entry['translation']) && preg_match('/(.*)_C\(.*\)$/', $entry['name'], $match)) {
            $entry['translation'] = $match[1];
        }

        $source = Language::addPhpSlashes($entry['name']);

        if (isset($entry['translation'])) {
            $trans = Language::addPhpSlashes($entry['translation']);
            $string .= "\"$source\" => \"$trans\",\n";
        } else {
            $string .= "// \"$source\" => \"$source\",\n";
        }

        return $string;
    }

    /**
     * Write language strings to file, handling both standard and custom directories
     *
     * @param resource $handle File handle to write to
     * @param array $entries Language entries to write
     * @param bool $outputFiles Whether to include file paths in output
     * @param string $language Current language code
     * @param bool $isCustomDir Whether this is a custom directory
     */
    protected function writeLanguageFile($handle, array $entries, bool $outputFiles, string $language, bool $isCustomDir): void
    {
        if ($isCustomDir) {
            $this->writeLanguageArray($handle, $entries, $outputFiles, $language, '$lang_custom');
            fwrite($handle, "\$lang = array_merge(\$lang, \$lang_custom);\n");
        } else {
            fwrite($handle, $this->fileHeader());

            if ($language != "en") {
                fwrite($handle, "include('lang/en/language.php'); // Needed for providing a sensible default text for untranslated strings with context like : \"edit_C(verb)\"\n");
                $this->writeLanguageArray($handle, $entries, $outputFiles, $language, '$lang_current');
                fwrite($handle, "\$lang = array_replace(\$lang, \$lang_current);\n");
            } else {
                $this->writeLanguageArray($handle, $entries, $outputFiles, $language, '$lang');
            }
        }
    }

    private function writeLanguageArray($handle, array $entries, bool $outputFiles, string $language, string $variableName): void
    {
        fwrite($handle, "{$variableName} = array(\n"); // do not use short array syntax here yet for Transifex.com translation resource import

        foreach ($entries as $entry) {
            fwrite($handle, $this->formatString($entry, $outputFiles, $language));
        }

        fwrite($handle, ");\n");
    }

    /**
     * Checks if the directory is a custom theme directory.
     *
     * @param string $dir The directory path to check
     * @return bool Returns true if it's a custom theme directory, false otherwise
     */
    public static function isCustomDir($dir): bool
    {
        global $tikipath;
        $resolvedDir = realpath($dir);
        if ($resolvedDir === false) {
            $resolvedDir = $dir;
        }
        // Ensure trailing slash for root
        $root = rtrim($tikipath, '/') . '/';

        // Match paths where the first directory after $root is "_custom"
        return preg_match('#^' . preg_quote($root, '#') . '_custom(?:/|$)#', $resolvedDir) === 1;
    }
}
