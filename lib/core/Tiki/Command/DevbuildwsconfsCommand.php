<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * index.php and .htaccess are designed to disallow directory listing in apache.
 *
 * This command replaces the deprecated doc/devtools/check_tiki_directories.php
 * @package Tiki\Command
 */
#[AsCommand(
    name: 'dev:buildwsconfs',
    description: 'Checks or generate .index.php & .htaccess files.'
)]
class DevbuildwsconfsCommand extends Command
{
    protected function configure()
    {
        $this
        ->setHelp('Allows checking for the presence and generating of index.php and .htaccess files to avoid unintended php code from being executed in production.')
        ->addOption(
            'generate',
            'g',
            InputOption::VALUE_NONE,
            'generate missing index.php.  Does not support .htaccess yet'
        )
        ->addOption(
            'clean',
            'c',
            InputOption::VALUE_NONE,
            'remove all index.php not part of the git repo'
        );
    }

    /**
     * Command Execution entry point
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (PHP_SAPI !== 'cli') {
            die('Only available through command-line.');
        }

        require_once(__DIR__ . '/../../../../path_constants.php');

        $generate = $input->getOption('generate');
        $verbose = $input->getOption('verbose');
        $cleanFiles = $input->getOption('clean');

        $tikiRootFolder = realpath(__DIR__ . '/../../../../');

        // get latest file "stat" info
        clearstatcache();

        $emptyDirectoriesMessage = '';
        $missingIndexMessage = '';
        $missingIndexMessageFixed = '';
        $missingHtaccessMessage = '';
        $failCreateMessage = '';


        $filter = function ($o_path) {
            $filePath = $o_path->getRealpath();
            $folders = explode(\DIRECTORY_SEPARATOR, $filePath);
            $folderName = end($folders);//This is a string...
            //var_dump($folders);
            $folder = count($folders);

            $excludeDir = [
            TEMP_PATH, //This needs to be changed once we are sure everything that needs to be served has been moved to TEMP_HTTP_PUBLIC_PATH
            TIKI_CUSTOMIZATIONS_SRC_PATH,
            //These are only here because we still include some js and css assets from composer packages
            TIKI_VENDOR_NONBUNDLED_PATH,
            TIKI_VENDOR_BUNDLED_TOPLEVEL_PATH,
            TIKI_VENDOR_CUSTOM_PATH
            ];
            $excludeDirAnywhere = [
            'node_modules'
            ];
            //'.' // Directories that are hidden (Ex: .composer)


            if ($o_path->isDir() && str_starts_with($folderName, '.')) {// UNIX directories that are hidden (Ex: .composer)
                //var_dump($folderName);
                return false;
            }

            //I know this will still exclude path fragments anywhere, didn't have time to change it.  Benoitg- 2023-11-15
            if (in_array($folderName, $excludeDir)) {
                return false;
            }
            if (in_array($folderName, $excludeDirAnywhere)) {
                return false;
            }
            return true;
        };

        $it = new \RecursiveDirectoryIterator($tikiRootFolder, \FilesystemIterator::SKIP_DOTS);
        $folder = new \RecursiveCallbackFilterIterator($it, $filter);
        $iterator = new \RecursiveIteratorIterator($folder, \RecursiveIteratorIterator::SELF_FIRST);
        if ($cleanFiles) {
            $iterator->setMaxDepth(0);  //git will take care of the recursion
        }
        foreach ($iterator as $file) {
            if (! $file->isDir()) {
                continue;
            }
            $filePath = $file->getRealpath();
            $fileName = $file->getFilename();
            //$output->writeln("<info>$filePath</info>");
            if ($fileName == '..') {
                //It seems RecursiveDirectoryIterator doesn't always obey \FilesystemIterator::SKIP_DOTS
                continue;
            }
            if ($file->islink()) {
                //It seems RecursiveDirectoryIterator sometimes follows links despite NOT passing FilesystemIterator::FOLLOW_SYMLINKS
                continue;
            }
            if ($verbose) {
                $output->writeln("<info>Checking directory: $filePath</info>");
            }
            if ($cleanFiles) {
                $command = "git clean -f -x $filePath\*\*/index.php";
                $output->writeln("<info>$command</info>");
                exec(
                    $command,
                    $raw,
                    $error
                );
                $output->writeln($raw, OutputInterface::VERBOSITY_NORMAL);
                foreach ($raw as $line) {
                    if ($error) {
                        $output->writeln(
                            "<error>$line</error>"
                        );
                    } else {
                        $output->writeln("<info>$line</info>");
                    }
                }
                continue;
            }
            if ($this->isEmptyDir($filePath)) {
                $emptyDirectoriesMessage .= '<fg=blue>' . $filePath . '</>' . PHP_EOL;
                continue;
            }

            if ($this->folderHasIndexPhpFile($filePath)) {
                if ($verbose) {
                    $output->writeln("<info>$filePath has proper index.php</info>");
                }
            } elseif ($generate) {
                $projectFolder = str_replace('doc', '', dirname(__DIR__)); //Why do we make this replacement?  benoitg - 2023-11-16
                $path = str_replace($projectFolder, '', $filePath);
                $path = str_replace('-', '', $path);
                $indexPath = preg_replace('/(\w+)/', '../', $path);
                $indexPath = str_replace('//', '/', $indexPath);
                $indexContent = '<?php' . PHP_EOL . PHP_EOL;
                $indexContent .= '//This file is generated using php console.php dev:buildwsconfs' . PHP_EOL;
                $indexContent .= '//This redirects to the sites root to prevent directory browsing' . PHP_EOL;
                $indexContent .= 'header("location: ' . $indexPath . '/");' . PHP_EOL;
                $indexContent .= 'die;' . PHP_EOL;

                $newFile = file_put_contents($filePath . '/index.php', $indexContent);

                if (! $this->folderHasIndexPhpFile($filePath)) {
                    $failCreateMessage .= '<error>' . $filePath . '</error>' . PHP_EOL;
                } else {
                    $missingIndexMessageFixed .= '<fg=blue>' . $filePath . '</>' . PHP_EOL;
                }
            } else {
                $missingIndexMessage .= '<fg=red>' . $filePath . '</>' . PHP_EOL;
            }

            if ($this->folderHasHtaccess($filePath, $tikiRootFolder)) {
                if ($verbose) {
                    $output->writeln("<info>$filePath has proper htaccess file</info>");
                }
            } else {
                $missingHtaccessMessage .= '<fg=blue>' . $filePath . '</>' . PHP_EOL;
            }
        }
        $output->writeln('<comment>Processing complete</comment>', OutputInterface::VERBOSITY_VERBOSE);
        if (! empty($emptyDirectoriesMessage) || ! empty($missingIndexMessage) || ! empty($missingHtaccessMessage)) {
            if (! empty($emptyDirectoriesMessage)) {
                $output->writeln('<comment>The following directories are empty:</comment>');
                $output->writeln($emptyDirectoriesMessage);
            }
            if (! empty($missingIndexMessage)) {
                $output->writeln('<comment>index.php file is missing in the following directories:</comment>');
                $output->writeln($missingIndexMessage);
            }
            if (! empty($missingIndexMessageFixed)) {
                $output->writeln('<info>index.php file was successfully added in the following directories:</info>');
                $output->writeln($missingIndexMessageFixed);
            }
            if (! empty($missingHtaccessMessage)) {
                $output->writeln('<comment>.htaccess file is missing in the following directories:</comment>');
                $output->writeln($missingHtaccessMessage);
            }
            if (! empty($failCreateMessage)) {
                $output->writeln('<comment>The directory where we failed to create files</comment>');
                $output->writeln($failCreateMessage);
            }
            return Command::FAILURE;
        } else {
            $output->writeln('All directories OK');
        }
        return Command::SUCCESS;
    }

    /**
    * Check if folder is empty
    *
    * @param $dir
    * @return boolean
    */
    protected function isEmptyDir($dir)
    {
        return (($files = scandir($dir)) && count($files) <= 2);
    }
    /**
     * Check if .htaccess file exist
     *
     * @param $dir
     * @return boolean
     */
    private function folderHasIndexPhpFile($filePath): bool
    {
        return ! empty(glob($filePath . '/[iI][nN][dD][eE][xX].[pP][hH][pP]'));  // index.php case-insensitive
    }

    /**
     * Check if .htaccess file exist
     *
     * @param $dir
     * @return boolean
     */
    protected function folderHasHtaccess($dir, $tikiRootFolder)
    {
        $hasHtaccess = file_exists($dir . '/.htaccess');

        // We want to ensure that first level folders have .htaccess
        if (! $hasHtaccess && dirname($dir) !== $tikiRootFolder && $dir != $tikiRootFolder) {
            return $this->folderHasHtaccess(dirname($dir), $tikiRootFolder);
        }

        return $hasHtaccess;
    }
}
