<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Zotero\CslStyleManager;

class ZoteroCslFindCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('zotero:csl:find')
            ->setDescription(tra('Search installed CSL styles for Zotero Pandoc citations'))
            ->addArgument('search_term', InputArgument::OPTIONAL, tra('Style name fragment to search for'))
            ->addOption('debug', null, InputOption::VALUE_NONE, tra('Show storage path information'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $manager = new CslStyleManager();
        $cslDir = $manager->getCslDir();

        if ($input->getOption('debug')) {
            $output->writeln('[DEBUG] storageBaseDir=' . $manager->getStorageBaseDir());
            $output->writeln('[DEBUG] cslDir=' . $cslDir);
        }

        if (! is_dir($cslDir)) {
            $output->writeln("<error>Directory $cslDir not found</error>");
            $output->writeln('Run: php console.php zotero:csl:update first');
            return Command::FAILURE;
        }

        $searchTerm = trim((string) $input->getArgument('search_term'));
        if ($searchTerm === '') {
            $output->writeln('Usage: php console.php zotero:csl:find [search_term]');
            $output->writeln('');
            $output->writeln('Examples:');
            $output->writeln('  php console.php zotero:csl:find chicago');
            $output->writeln('  php console.php zotero:csl:find harvard');
            $output->writeln('  php console.php zotero:csl:find apa');
            $output->writeln('  php console.php zotero:csl:find medical');
            $output->writeln('');
            $output->writeln('Total styles available: ' . count($manager->listStyles()));
            return Command::SUCCESS;
        }

        $matches = $manager->searchStyles($searchTerm);

        $output->writeln("Searching for: $searchTerm");
        $output->writeln('');
        $output->writeln('Matching styles:');
        $output->writeln('================');
        $output->writeln('');

        foreach ($matches as $match) {
            $output->writeln($match);
        }

        $output->writeln('');
        $output->writeln('================');
        $output->writeln('Found ' . count($matches) . ' matching styles');
        $output->writeln('');
        $output->writeln('Usage in Tiki: {zoterobibliography style=' . ($matches[0] ?? 'style-name') . '}');

        return Command::SUCCESS;
    }
}
