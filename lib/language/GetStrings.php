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
     * Directory used as base to search for strings
     * and to construct paths to language.php files.
     * @var string
     */
    protected $baseDir;

    /**
     * Indicates whether to skip the removal of the old or unused strings from the language files.
     * When set to true, the old or unused strings will not be removed.
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

        if (isset($options['outputFiles'])) {
            $this->outputFiles = true;
        }

        if (isset($options['baseDir'])) {
            if (! is_dir($options['baseDir'])) {
                throw new Language_Exception("Invalid directory {$options['baseDir']}.");
            }

            $this->baseDir = $options['baseDir'];
        } else {
            $this->baseDir = getcwd();
        }

        if (isset($options['fileName'])) {
            $this->fileName = $options['fileName'];
        }

        if (isset($options['lang'])) {
            $this->setLanguages($options['lang']);
        } else {
            $this->setLanguages();
        }

        if (isset($options['skipRemove'])) {
            $this->skipRemove = true;
        }

        if (isset($options['includeDatabase'])) {
            $this->includeDatabase = true;
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
                if (! file_exists($this->baseDir . '/lang/' . $lang)) {
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
                            'name'   => $str,
                            'source' => 'database', // to be able to distinguish from file strings
                            'files'  => [],
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
            $fileStrings = $this->collectStrings($file);
            $writeFile = new Language_WriteFile(new Language_File($file));
            $source = $writeFile->isCustomDir(dirname($file)) ? 'custom' : 'standard'; // Define the source for file-based strings: 'custom' for user overrides, 'standard' for core.

            foreach ($fileStrings as $str) {
                $this->mergeStringEntry($strings, $str, $source, [$file]);
            }
        }

        if ($this->includeDatabase) {
            $databaseStrings = $this->collectStringsFromDatabase();
            foreach ($databaseStrings as $str => $entry) {
                $this->mergeStringEntry($strings, $str, $entry['source'], $entry['files'] ?? []);
            }
        }

        return $strings;
    }

    /**
     * Merges a string entry into the provided strings array.
     *
     * If the string entry with the given name does not exist, it initializes it with the provided sources and files.
     * If it already exists, it merges the new sources and files into the existing entry, avoiding duplicates.
     *
     * @param array  &$strings Reference to the array of string entries to be updated.
     * @param string $name     The name/key of the string entry to merge.
     * @param mixed  $source   The source(s) of the string entry. Can be a string or an array of strings.
     * @param array  $files    (Optional) List of files associated with the string entry.
     *
     * @return void
     */
    private function mergeStringEntry(array &$strings, string $name, $source, array $files = []): void
    {
        $sources = ! is_array($source) ? [$source] : $source;

        if (! isset($strings[$name])) {
            $strings[$name] = [
                'name'   => $name,
                'source' => $sources,
            ];
            if ($this->outputFiles) {
                $strings[$name]['files'] = $files;
            }
            return;
        }

        // Merge Sources
        foreach ($sources as $s) {
            if (! in_array($s, $strings[$name]['source'], true)) {
                $strings[$name]['source'][] = $s;
            }
        }

        // Merge Files
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
            $filePath = $this->baseDir . '/lang/' . $lang . '/' . $this->fileName;
            $writeFile = $this->writeFileFactory->factory($filePath);
            $writeFile->writeStringsToFile($strings, $this->outputFiles, $lang, $this->baseDir, $this->skipRemove);
        }
    }

    /**
     * Return all available languages (check for the
     * existence of a language file).
     * @return array all language codes
     */
    protected function getAllLanguages()
    {
        $dirs = dir($this->baseDir . '/lang');

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
        $files = $this->collectFiles->run($this->baseDir);
        $strings = $this->scanFiles($files);
        $this->writeToFiles($strings);
    }
}
