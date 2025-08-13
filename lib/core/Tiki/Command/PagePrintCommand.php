<?php

/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 * $Id$
 */

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\PagePdfExporter;

class PagePrintCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('page:print')
            ->setDescription('Export a PDF of a wiki page')
            ->addArgument(
                'identifier',
                InputArgument::REQUIRED,
                'Page ID or name'
            )
            ->addOption(
                'output',
                'o',
                InputOption::VALUE_OPTIONAL,
                'Path to save the exported PDF (optional, defaults to temp/public directory if not specified)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $identifier = $input->getArgument('identifier');
        $out        = $input->getOption('output');

        if (empty($out)) {
            $output->writeln(
                '<comment>Warning: The exported PDF will be saved in the temp/public directory which is publicly accessible. "' .
                'For private pages, consider specifying a secure output path using the --output option.</comment>'
            );
        }

        $exporter = new PagePdfExporter();

        try {
            $filePath = $exporter->exportToFile($identifier, $out ?: null);

            $stat          = stat($filePath);
            $stat['file']  = $filePath;
            $output->writeln(json_encode($stat, JSON_PRETTY_PRINT));
        } catch (\Throwable $e) {
            $output->writeln($e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
