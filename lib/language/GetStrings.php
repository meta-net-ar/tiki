<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @package   Tiki
 * @subpackage    Language
 */

class Language_GetStrings
{
    /**
     * Array of file types objects.
     *
     * @var array
     */
    protected $fileTypes = [];

    /**
     * Array of valid extensions. Extracted
     * from $this->fileTypes.
     *
     * @var array
     */
    protected $extensions = [];

    /**
     * List of languages whose language.php
     * files will be updated. If empty all
     * language.php files are updated.
     *
     * @var array
     */
    protected $languages = [];

    /**
     * Name of the file that contain the
     * translations.
     * @var string
     */
    protected $fileName = 'language.php';

    /**
     * @var Language_CollectFiles
     */
    public $collectFiles;

    /**
     * @var Language_WriteFile_Factory
     */
    public $writeFileFactory;

    /**
     * Whether file paths where the string was found
     * is included or not in language.php files. Default
     * is false.
     *
     * @var bool
     */
    protected $outputFiles = false;

    /**
     * Directory scanned recursively for translatable strings.
     * @var string
     */
    protected $scanDir;

    /**
     * Directory containing the per-language subdirectories where the language
     * files are written (e.g. 'lang' or '_custom/shared/lang').
     * @var string
     */
    protected $langDir;

    /**
     * Name of the PHP array variable written to the language files.
     * When null, the standard names are used: '$lang' for English and
     * '$lang_current' for the other languages.
     * @var string|null
     */
    protected $langVariable = null;

    /**
     * Path of the base English file included in non-English language files to provide
     * defaults for untranslated strings. Set to null to write files without the include
     * (e.g. custom language files, which are merged on top of the base translations at runtime).
     * @var string|null
     */
    protected $baseEnglishFile = 'lang/en/language.php';

    /**
     * Whether to write the standard header (copyright and translator notes)
     * to the language files.
     * @var bool
     */
    protected $withHeader = true;

    /**
     * Function written at the end of the language file to merge the language array into $lang
     * ('array_replace' or 'array_merge'). When null, standard files use 'array_replace' for
     * non-English languages and no merge line for English.
     * @var string|null
     */
    protected $mergeFunction = null;

    /**
     * Indicates whether to keep strings no longer found by the scan instead of removing them
     * from the language files. When false (the default), any such string is removed.
     *
     * @var bool
     */
    protected $skipRemove = false;

    /**
     * Indicates whether to include database strings.
     * When set to true, translatable strings from the tiki_pages database table (wiki pages) will be included.
     *
     * @var bool
     */
    protected $includeDatabase = false;

    /**
     * Class construct.
     *
     * The following are valid $options:
     *   - 'outputFiles' => true: will write to language.php file the path
     *     to the files where the string was found. Default is false.
     *   - 'lang' => 'langCode' or 'lang' => array(list of lang codes):
     *     language code or list of language codes whose language.php will be
     *     updated. If empty, all language.php files are updated.
     *   - 'scanDir' => directory scanned for translatable strings.
     *     Default is the current working directory.
     *   - 'langDir' => directory containing the per-language subdirectories where
     *     the language files are written. Default is 'lang' in the current working directory.
     *   - 'baseDir' => legacy option: a single directory used both as scan directory
     *     and as parent of the lang/ directory. Overrides 'scanDir' and 'langDir'.
     *   - 'langVariable', 'baseEnglishFile', 'withHeader', 'mergeFunction': layout of the
     *     written files (see the corresponding properties). Defaults produce standard
     *     language.php files.
     *
     * @param Language_CollectFiles $collectFiles
     * @param Language_WriteFile_Factory $writeFileFactory factory to create Language_WriteFile objects
     * @param array $options list of options to control object behavior (see above)
     * @return null
     */
    public function __construct(Language_CollectFiles $collectFiles, Language_WriteFile_Factory $writeFileFactory, array|null $options = null)
    {
        $this->collectFiles = $collectFiles;
        $this->writeFileFactory = $writeFileFactory;
        $options = $options ?? [];

        if (isset($options['outputFiles'])) {
            $this->outputFiles = true;
        }

        if (isset($options['baseDir'])) {
            if (! is_dir($options['baseDir'])) {
                throw new Language_Exception("Invalid directory {$options['baseDir']}.");
            }

            $options['scanDir'] = $options['baseDir'];
            $options['langDir'] = $options['baseDir'] . '/lang';
        }

        if (isset($options['scanDir'])) {
            if (! is_dir($options['scanDir'])) {
                throw new Language_Exception("Invalid directory {$options['scanDir']}.");
            }

            $this->scanDir = $options['scanDir'];
        } else {
            $this->scanDir = getcwd();
        }

        $this->langDir = $options['langDir'] ?? getcwd() . '/lang';

        if (isset($options['fileName'])) {
            $this->fileName = $options['fileName'];
        }

        if (isset($options['lang'])) {
            $this->setLanguages($options['lang']);
        } else {
            $this->setLanguages();
        }

        if (! empty($options['skipRemove'])) {
            $this->skipRemove = true;
        }

        if (! empty($options['includeDatabase'])) {
            $this->includeDatabase = true;
        }

        if (isset($options['langVariable'])) {
            $this->langVariable = $options['langVariable'];
        }

        if (array_key_exists('baseEnglishFile', $options)) {
            $this->baseEnglishFile = $options['baseEnglishFile'];
        }

        if (isset($options['withHeader'])) {
            $this->withHeader = (bool) $options['withHeader'];
        }

        if (isset($options['mergeFunction'])) {
            $this->mergeFunction = $options['mergeFunction'];
        }
    }

    /**
     * Getter for $this->extensions
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Getter for $this->fileTypes
     * @return array
     */
    public function getFileTypes()
    {
        return $this->fileTypes;
    }

    /**
     * Add a file type object to $this->fileTypes
     * and update $this->extensions.
     *
     * @param $fileType Language_FileType
     * @return null
     * @throws Language_Exception if type being added already exists
     */
    public function addFileType(Language_FileType $fileType)
    {
        if (in_array($fileType, $this->fileTypes)) {
            $className = get_class($fileType);
            throw new Language_Exception("Type $className already added.");
        }

        $this->fileTypes[] = $fileType;
        $this->extensions = array_merge($this->extensions, $fileType->getExtensions());
    }

    /**
     * Setter method $this->languages
     * property.
     *
     * @param array|string $languages
     * @return null
     */
    public function setLanguages($languages = null)
    {
        if (is_null($languages)) {
            $languages = $this->getAllLanguages();
        } else {
            if (is_string($languages)) {
                $languages = [$languages];
            }

            foreach ($languages as $lang) {
                if (! file_exists($this->langDir . '/' . $lang)) {
                    throw new Language_Exception('Invalid language code.');
                }
            }
        }

        $this->languages = $languages;
    }

    /**
     * Getter method for $this->languages.
     *
     * @return array
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * Get English strings from a given file.
     *
     * @param string $filePath path to file
     * @return array collected strings
     */
    public function collectStrings($filePath)
    {
        if (empty($this->fileTypes)) {
            throw new Language_Exception('No Language_FileType found.');
        }

        $strings = [];
        $fileExtension = strrchr($filePath, '.');

        if (! $fileExtension || $fileExtension == '.') {
            throw new Language_Exception('Could not determine file extension.');
        }

        foreach ($this->fileTypes as $fileType) {
            if (in_array($fileExtension, $fileType->getExtensions())) {
                $file = file_get_contents($filePath);

                foreach ($fileType->getCleanupRegexes() as $regex => $replacement) {
                    $file = preg_replace($regex, $replacement, $file);
                }

                foreach ($fileType->getRegexes() as $postProcess => $regex) {
                    $matches = [];
                    preg_match_all($regex, $file, $matches);
                    $newStrings = $matches[1];

                    // $postProcess can be used to call a file type specific method for each regular expression
                    // used for PHP file type to perform different clean up for single quoted and double quoted strings
                    if (method_exists($fileType, $postProcess)) {
                        $newStrings = $fileType->$postProcess($newStrings);
                    }

                    $strings = array_merge($strings, $newStrings);
                }

                break;
            }
        }

        return array_values(array_unique($strings));
    }

    /**
     * Collects translatable strings from the database, specifically from the 'data' field of the 'tiki_pages' table.
     *
     * This method searches for strings marked for translation using specific patterns:
     *   - {tr}...{/tr} or {tr [args]}...{/tr}
     *   - {TR()}...{TR}
     * It also cleans up the content by removing or processing Smarty comments and wiki comments to avoid extracting
     * non-translatable text.
     *
     * @return array An array of unique strings extracted for translation.
     */
    public function collectStringsFromDatabase()
    {
        $regexes = [
            // Only extract {tr} ... {/tr} in tiki_pages data field
            // Also match {tr [args]} ...{/tr}
            '/\{tr(?:\s+[^\}]*)?\}(.+?)\{\/tr\}/s', // {tr} ... {/tr}
            // Only match {TR()} ... {TR}
            '/\{TR\(\)\}(.*?)\{TR\}/s',
        ];


        $cleanupRegexes = [
            // Do not translate text in Wiki comments: {* Smarty comment *}
            // except if it is an string marked {*get_strings {tr}string{/tr} *}
            '/\{\*get_strings(.*?)\*\}/s' => '$1',
            '/\{\*.*?\*\}/s' => '', // Smarty comment
            // ~tc~This is a wiki comment. ~/tc~
            '/~tc~(.*?)~\/tc~/s' => '',
        ];

        $tikilib = \TikiLib::lib('tiki');
        $query = "SELECT `data`, `pageName`, `pageSlug` FROM `tiki_pages` WHERE `data` IS NOT NULL";
        $result = $tikilib->fetchAll($query);

        global $prefs;
        $dbStrings = [];
        foreach ($result as $row) {
            $pageSlug = $row['pageSlug'];
            if (empty($pageSlug)) {
                $pageSlug = TikiLib::lib('slugmanager')->generate($prefs['wiki_url_scheme'] ?: 'dash', $row['pageName'], $prefs['url_only_ascii'] === 'y');
            }

            $file = $row['data'];
            foreach ($cleanupRegexes as $regex => $replacement) {
                $file = preg_replace($regex, $replacement, $file);
            }

            foreach ($regexes as $regex) {
                $matches = [];
                preg_match_all($regex, $file, $matches);

                foreach ($matches[1] ?? [] as $str) {
                    if (! isset($dbStrings[$str])) {
                        $dbStrings[$str] = [
                            'name'  => $str,
                            'files' => [],
                        ];
                    }

                    $dbStrings[$str]['files'][$pageSlug] = $pageSlug;
                }
            }
        }
        return $dbStrings;
    }

    /**
     * Loop through a list of files and
     * calls $this->collectStrings() for each
     * file. Return a list of translatable strings
     * found.
     *
     * @param array $files
     * @return array $strings translatable strings found in scanned files
     */
    public function scanFiles($files)
    {
        $strings = [];

        foreach ($files as $file) {
            foreach ($this->collectStrings($file) as $str) {
                $this->mergeStringEntry($strings, $str, [$file]);
            }
        }

        if ($this->includeDatabase) {
            foreach ($this->collectStringsFromDatabase() as $entry) {
                $this->mergeStringEntry($strings, $entry['name'], $entry['files']);
            }
        }

        return $strings;
    }

    /**
     * Merges a string entry into the provided strings array,
     * merging the lists of files where the string was found and avoiding duplicates.
     *
     * @param array  &$strings Reference to the array of string entries to be updated.
     * @param string $name     The name/key of the string entry to merge.
     * @param array  $files    (Optional) List of files associated with the string entry.
     *
     * @return void
     */
    private function mergeStringEntry(array &$strings, string $name, array $files = []): void
    {
        if (! isset($strings[$name])) {
            $strings[$name] = ['name' => $name];
            if ($this->outputFiles) {
                $strings[$name]['files'] = array_values($files);
            }
            return;
        }

        if ($this->outputFiles) {
            foreach ($files as $file) {
                if (! in_array($file, $strings[$name]['files'], true)) {
                    $strings[$name]['files'][] = $file;
                }
            }
        }
    }

    public function writeToFiles($strings)
    {
        foreach ($this->languages as $lang) {
            $filePath = $this->langDir . '/' . $lang . '/' . $this->fileName;
            $writeFile = $this->writeFileFactory->factory($filePath);

            // Standard language files use '$lang' with no merge line for English, and
            // '$lang_current' merged with array_replace for the other languages. Both are
            // overridden when a specific variable was configured (e.g. custom files use
            // '$lang_custom' merged with array_merge for every language).
            if ($this->langVariable !== null) {
                $langVariable = $this->langVariable;
                $mergeFunction = $this->mergeFunction;
            } elseif ($lang === 'en') {
                $langVariable = '$lang';
                $mergeFunction = null;
            } else {
                $langVariable = '$lang_current';
                $mergeFunction = $this->mergeFunction ?? 'array_replace';
            }

            // The English file needs no include of itself
            $baseEnglishFile = $lang === 'en' ? null : $this->baseEnglishFile;

            $writeFile->writeStringsToFile($strings, $this->outputFiles, $lang, $this->skipRemove, $langVariable, $baseEnglishFile, $this->withHeader, $mergeFunction);
        }
    }

    /**
     * Return all available languages (check for the
     * existence of a language file).
     * @return array all language codes
     */
    protected function getAllLanguages()
    {
        $languages = [];

        if (! is_dir($this->langDir)) {
            return $languages;
        }

        $dirs = dir($this->langDir);
        if ($dirs === false) {
            return $languages;
        }

        while (false !== ($entry = $dirs->read())) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            $path = $dirs->path . '/' . $entry;
            if (is_dir($path) && file_exists($path . '/' . $this->fileName)) {
                $languages[] = $entry;
            }
        }

        return $languages;
    }

    public function run()
    {
        if (empty($this->fileTypes)) {
            throw new Language_Exception('No Language_FileType found.');
        }

        $this->collectFiles->setExtensions($this->extensions);
        $files = $this->collectFiles->run($this->scanDir);
        $strings = $this->scanFiles($files);
        $this->writeToFiles($strings);
    }
}
