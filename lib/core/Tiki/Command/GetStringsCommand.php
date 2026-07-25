<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Language_CollectFiles;
use Language_FileType_Php;
use Language_FileType_Tpl;
use Tiki\Lib\Language\FileType\Js as Language_FileType_Js;
use Language_GetStrings;
use Language_WriteFile_Factory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Tiki\Profiling\Timer;

/**
 * @package Tiki\Command
 *
 * Update lang/xx/language.php files
 *
 * Scans a directory (its files) and a set of (individual) files
 * By default, the directory scanned is the Tiki root, excluding $excludeDirs. By default, the individual files scanned are files in these otherwise excluded directories.
 * If a _custom directory exists, its content (including wiki pages in the database) is also scanned automatically and written to _custom/shared/lang/<lang>/custom.php.
 *
 * Examples:
 *      - http://localhost/pathToTiki/get_strings.php -> update all language.php files
 *      - http://localhost/pathToTiki/get_strings.php?lang=fr -> update just lang/fr/language.php file
 *      - http://localhost/pathToTiki/get_strings.php?lang[]=fr&lang[]=pt-br&outputFiles -> update both French
 *        and Brazilian Portuguese language.php files and for each string add a line with
 *        the file where it was found.
 *
 * Command line examples:
 *      - php get_strings.php
 *      - php get_strings.php lang=pt-br outputFiles=true
 *
 *      Only scan lib/, and only part of lib/ (exclude lib/core/Zend and lib/captcha), but still include captchalib.php and index.php
 *      This FAILS as of 2017-09-15, since the language files (for output) are looked for in baseDir.
 *      - php get_strings.php baseDir=lib/ excludeDirs=lib/core/Zend,lib/captcha includeFiles=captchalib.php,index.php fileName=language_r.php
 *
 *
 */
#[AsCommand(
    name: 'translation:getstrings',
    description: 'Update language.php files with new strings'
)]
class GetStringsCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp(tra('Scans all Tiki files and adds new English strings to language files. Also reorganizes existing strings.') . "\n"
                . 'If the ' . TIKI_CUSTOMIZATIONS_SRC_PATH . ' directory exists, its content (and wiki pages in the database) is also scanned automatically, and '
                . TIKI_CUSTOMIZATIONS_SHARED_PATH . '/' . LANG_PATH_FRAGMENT . '/<lang>/' . LANG_CUSTOM_PHP_BASENAME . ' is updated. Use --custom to update only the customizations, skipping the core scan.')
            ->addOption(
                'lang',
                'l',
                InputOption::VALUE_OPTIONAL,
                tra('Language code to process eg. lang=pt-br')
            )
            ->addOption(
                'outputfiles',
                null,
                InputOption::VALUE_NONE,
                tra('For each string add a line with the file where it was found')
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_OPTIONAL,
                tra('Directories that should be excluded from searching')
            )
            ->addOption(
                'custom',
                null,
                InputOption::VALUE_NONE,
                'Only extract strings from the ' . TIKI_CUSTOMIZATIONS_SRC_PATH . ' directory (and wiki pages) and write them to '
                    . TIKI_CUSTOMIZATIONS_SHARED_PATH . '/' . LANG_PATH_FRAGMENT . '/<lang>/' . LANG_CUSTOM_PHP_BASENAME
                    . ', skipping the core scan. A normal run already does this automatically too; use --custom to update only the customizations.'
            )
            ->addOption(
                'include',
                null,
                InputOption::VALUE_OPTIONAL,
                tra('Individual files that should be included in otherwise excluded directories')
            )
            ->addOption(
                'basedir',
                null,
                InputOption::VALUE_OPTIONAL,
                tra('The base directory to use. Will invalidate default exclude and include parameters')
            )
            ->addOption(
                'filename',
                null,
                InputOption::VALUE_OPTIONAL,
                'eg. filename=language_r.php'
            )
            ->addOption(
                'skip-remove',
                null,
                InputOption::VALUE_NONE,
                'Keep strings no longer found by the scan instead of removing them. Without this option, any string missing from the current scan is always removed. eg. --skip-remove'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $timer = new Timer();
        $timer->start();

        $baseOptions = [
            'lang' => $input->getOption('lang') ?: null,
            'outputFiles' => $input->getOption('outputfiles') ?: null,
            'fileName' => $input->getOption('filename') ?: null,
            'skipRemove' => $input->getOption('skip-remove') ?: null,
        ];

        $exclude = $input->getOption('exclude') ? explode(',', $input->getOption('exclude')) : null;
        $include = $input->getOption('include') ? explode(',', $input->getOption('include')) : null;

        if ($input->getOption('custom') && $input->getOption('basedir')) {
            $output->writeln('<error>--custom and --basedir cannot be combined: --custom already defines where to scan and where to write.</error>');
            return Command::INVALID;
        }

        if ($input->getOption('basedir')) {
            $baseDir = $input->getOption('basedir');

            $resolvedBaseDir = realpath($baseDir) ?: $baseDir;
            $customRoot = realpath(TIKI_CUSTOMIZATIONS_SRC_PATH);
            if ($customRoot && str_starts_with($resolvedBaseDir, $customRoot)) {
                $output->writeln('<error>To extract strings from ' . TIKI_CUSTOMIZATIONS_SRC_PATH . ', use --custom instead of --basedir: it scans all the customizations and writes the language files where Tiki reads them from.</error>');
                return Command::INVALID;
            }

            $options = $baseOptions;
            $options['baseDir'] = $baseDir;

            $result = $this->runPass($output, $options, $exclude ?? [], $include ?? [], true);
            if ($result !== Command::SUCCESS) {
                return $result;
            }
        } else {
            $onlyCustom = (bool) $input->getOption('custom');

            if (! $onlyCustom) {
                $excludeDirs = $exclude ?? array_filter([
                    TIKI_CUSTOMIZATIONS_SRC_PATH, EXPORT_DUMP_PATH, STATIC_IMG_PATH, LANG_SRC_PATH, BIN_PATH,
                    TIKI_UPGRADE_SQL_SCHEMA_PATH, TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH, TIKI_VENDOR_NONBUNDLED_PATH,
                    TIKI_VENDOR_CUSTOM_PATH, 'lib/test', TEMP_PATH, PERMISSIONCHECK_PATH,
                    DEPRECATED_STORAGE_PATH, TIKI_TESTS_PATH, DEPRECATED_DEVTOOLS_PATH, TIKI_CONFIG_PATH, 'lib/openlayers', TESTS_PATH
                ], 'is_dir');
                // Files are processed after the base directory, so adding a file here allows to scan it even if its directory was excluded.
                $includeFiles = $include ?? ['./' . LANG_PATH_FRAGMENT . '/langmapping.php', './' . IMG_FLAGNAMES_FILE];

                $result = $this->runPass($output, $baseOptions, $excludeDirs, $includeFiles, true);
                if ($result !== Command::SUCCESS) {
                    return $result;
                }
            }

            // Customizations are scanned automatically as part of a normal run (or on their own with --custom)
            if (is_dir(TIKI_CUSTOMIZATIONS_SRC_PATH)) {
                $customOptions = $baseOptions;
                $customOptions['scanDir'] = TIKI_CUSTOMIZATIONS_SRC_PATH;
                $customOptions['langDir'] = TIKI_CUSTOMIZATIONS_SHARED_PATH . '/' . LANG_PATH_FRAGMENT;
                $customOptions['fileName'] = $input->getOption('filename') ?: LANG_CUSTOM_PHP_BASENAME;
                $customOptions['langVariable'] = '$lang_custom';
                $customOptions['mergeFunction'] = 'array_merge';
                $customOptions['withHeader'] = false;
                $customOptions['baseEnglishFile'] = null;
                // strings from wiki pages (database) are always included in the custom language files.
                $customOptions['includeDatabase'] = true;

                // Do not scan the generated language files themselves
                $customExcludeDirs = $exclude ?? [$customOptions['langDir']];
                $customIncludeFiles = $include ?? [];

                $result = $this->runPass($output, $customOptions, $customExcludeDirs, $customIncludeFiles, $onlyCustom);
                if ($result !== Command::SUCCESS) {
                    return $result;
                }
            } elseif ($onlyCustom) {
                $output->writeln('<error>' . TIKI_CUSTOMIZATIONS_SRC_PATH . ' directory not found.</error>');
                return Command::FAILURE;
            }
        }

        $output->writeln(tr('Total time spent: %0 seconds', $timer->stop()));
        $output->writeln('<comment>' . tra('You may now review and commit') . '</comment>');
        $output->writeln(
            '<info>' . tra('Warning: Committing the results of getstrings will prevent identifying broken strings with translation:englishupdate so englishupdate should be run first to prevent gradual translation loss. See englishupdate help for details.') . '</info>'
        );
        return Command::SUCCESS;
    }

    /**
     * Run a single scan+write pass with the given options.
     *
     * @param OutputInterface $output
     * @param array $options options passed to Language_GetStrings
     * @param array $excludeDirs directories excluded from the scan
     * @param array $includeFiles individual files scanned even if their directory is excluded
     * @param bool $failIfNoLanguages when true, a pass finding no <lang> directories to update is
     *             reported as an error; when false, it is silently skipped (used for the customizations
     *             pass when it runs automatically alongside the core pass, since by default Tiki does
     *             not have _custom/shared/lang/<lang>/custom.php, and that is expected, not an error)
     * @return int Command::SUCCESS, Command::FAILURE or Command::INVALID
     */
    private function runPass(OutputInterface $output, array $options, array $excludeDirs, array $includeFiles, bool $failIfNoLanguages): int
    {
        try {
            $getStrings = new Language_GetStrings(new Language_CollectFiles(), new Language_WriteFile_Factory(), $options);

            $getStrings->addFileType(new Language_FileType_Php());
            $getStrings->addFileType(new Language_FileType_Tpl());
            $getStrings->addFileType(new Language_FileType_Js());

            // skip the following directories
            $getStrings->collectFiles->setExcludeDirs($excludeDirs);

            // manually add the following files from skipped directories
            $getStrings->collectFiles->setIncludeFiles($includeFiles);

            $langs = $getStrings->getLanguages();
        } catch (\Language_Exception $e) {
            // Thrown when the requested --lang has no matching <lang> dir for this pass
            // (e.g. no _custom/shared/lang/<lang> yet). Handled exactly like the empty $langs
            // case just below: skip quietly if optional, fail with a message otherwise.
            if (! $failIfNoLanguages) {
                return Command::SUCCESS;
            }

            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        if (empty($langs)) {
            if (! $failIfNoLanguages) {
                return Command::SUCCESS;
            }

            $fileName = $options['fileName'] ?: 'language.php';
            $langDir = $options['langDir'] ?? ($options['baseDir'] ?? '.') . '/' . LANG_PATH_FRAGMENT;
            $output->writeln("<error>No language files to update: no <lang> directory containing $fileName was found in $langDir. Create the file(s) first, e.g. $langDir/fr/$fileName.</error>");
            return Command::FAILURE;
        }
        sort($langs);
        $output->writeln(count($langs) . ' Languages: ' . implode(' ', $langs));

        $getStrings->run();

        return Command::SUCCESS;
    }
}
