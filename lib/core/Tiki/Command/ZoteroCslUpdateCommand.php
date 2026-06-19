<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Command;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Zotero\CslStyleManager;

class ZoteroCslUpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('zotero:csl:update')
            ->setDescription(tra('Download and update CSL styles for Zotero Pandoc citations'))
            ->addOption('debug', null, InputOption::VALUE_NONE, tra('Show detailed download and extraction information'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manager = new CslStyleManager();
        $debugEnabled = (bool) $input->getOption('debug');

        $output->writeln('Updating CSL styles...');
        $output->writeln('Target directory: ' . $manager->getCslDir());

        $progress = static function (string $message) use ($output): void {
            $output->writeln($message);
        };
        $debug = $debugEnabled ? static function (string $message) use ($output): void {
            $output->writeln('[DEBUG] ' . $message);
        } : null;

        try {
            $totalStyles = $manager->updateStyles($progress, $debug);
        } catch (RuntimeException $e) {
            $output->writeln('<error>ERROR: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('Building style index...');
        $output->writeln('Found ' . $totalStyles . ' styles');
        $output->writeln('');
        $output->writeln('Done! CSL styles updated successfully.');
        $output->writeln('Total styles available: ' . $totalStyles);

        return Command::SUCCESS;
    }
}
