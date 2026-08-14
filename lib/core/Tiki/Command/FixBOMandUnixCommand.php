<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use BOMChecker_Scanner;
use Tiki\LineEnding\Converter;

/**
 * Fix BOM encoding and line endings in files using native PHP implementation.
 * No longer requires dos2unix - uses native PHP for cross-platform compatibility.
 *
 * @package Tiki\Command
 */
#[AsCommand(
    name: 'dev:fixbom',
    description: 'Fix BOM and line endings for all files',
)]
class FixBOMandUnixCommand extends Command
{
    protected function configure()
    {
        $this
            ->setHelp('Fixes BOM encoding, converts windows to Unix line endings and fixes other invisible weirdness in all Tiki files.')
            ->addOption(
                'report-only',
                null,
                InputOption::VALUE_NONE,
                'Only report issues without fixing them'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Get report-only flag from command option
        $reportOnly = $input->getOption('report-only');

        // Get files to process (reuse existing globRecursive logic)
        $files = $this->globRecursive(
            '*',
            GLOB_BRACE,
            '',
            ['vendor_', 'vendor/', 'node_modules/', 'bin/', 'temp/', 'lib/cypht', '.png', '.jpg', '.gif']
        );

        $output->writeln(sprintf(
            '<info>%s %d files...</info>',
            $reportOnly ? 'Scanning' : 'Processing',
            count($files)
        ));

        // Instantiate BOMChecker_Scanner
        $bomChecker = new BOMChecker_Scanner();

        // Instantiate LineEnding_Converter
        $lineEndingConverter = new Converter();

        // Setup progress bar
        $progress = new ProgressBar($output, count($files));
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $progress->setOverwrite(false);
        }
        $progress::setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message%');
        $progress->setFormat('custom');

        $progress->start();
        $progress->setMessage('Checking for BOM...');

        // Use the same flag for both operations
        $bomAffected = $bomChecker->fix($files, $reportOnly);

        $progress->setMessage('Checking line endings...');
        $lineEndingAffected = $lineEndingConverter->fix($files, $reportOnly);

        $progress->finish();
        $output->writeln(''); // New line after progress bar

        // Display results
        if ($reportOnly) {
            $output->writeln(sprintf(
                '<comment>Found BOM in %d file(s)</comment>',
                count($bomAffected)
            ));

            if (count($bomAffected) > 0 && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                foreach ($bomAffected as $file) {
                    $output->writeln("  <info>BOM:</info> $file");
                }
            }

            $output->writeln(sprintf(
                '<comment>Found incorrect line endings in %d file(s)</comment>',
                count($lineEndingAffected)
            ));

            if (count($lineEndingAffected) > 0 && $output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                foreach ($lineEndingAffected as $file) {
                    $output->writeln("  <info>Line endings:</info> $file");
                }
            }

            $totalIssues = count($bomAffected) + count($lineEndingAffected);
            if ($totalIssues > 0) {
                $output->writeln('');
                $output->writeln('<comment>Run without --report-only to fix these issues.</comment>');
            } else {
                $output->writeln('');
                $output->writeln('<info>All files look good, no issues found.</info>');
            }
        } else {
            $output->writeln(sprintf(
                '<info>Fixed BOM in %d file(s)</info>',
                count($bomAffected)
            ));

            $output->writeln(sprintf(
                '<info>Fixed line endings in %d file(s)</info>',
                count($lineEndingAffected)
            ));

            $totalFixed = count($bomAffected) + count($lineEndingAffected);
            if ($totalFixed > 0) {
                $output->writeln('');
                $output->writeln("<comment>$totalFixed file(s) updated, you may now review and commit.</comment>");
            } else {
                $output->writeln('');
                $output->writeln('<info>All files look good, no changes made.</info>');
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Recursively calls, glob()
     *
     * @param string $pattern
     * @param int    $flags
     * @param string $startdir
     * @param array  $excludes  If this string is found within a directory name, it wont be included
     *
     * @return array
     */

    private function globRecursive($pattern = '*', $flags = 0, $startdir = '', $excludes = [])
    {
        $files = glob($startdir . $pattern, $flags);
        foreach ($files as $key => $fileName) {
            foreach ($excludes as $exclude) {
                if (str_contains($fileName, $exclude)) {
                    unset($files[$key]);
                    break;
                }
            }
        }

        foreach (glob($startdir . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK) as $dir) {
            $include = true;
            /** If the directory has not been excluded from processing */
            foreach ($excludes as $exclude) {
                if (str_contains($dir, $exclude)) {
                    $include = false;
                    break;
                }
            }
            if ($include) {
                $files = array_merge($files, $this->globRecursive($pattern, $flags, $dir, $excludes));
            }
        }
        return $files;
    }
}
