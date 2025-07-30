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
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Search\Elastic\ElasticSearchIndexManager;
use Tiki\Search\Manticore\ManticoreSearchIndexManager;
use Tiki\Search\MySql\MysqlSearchIndexManager;
use TikiLib;

#[AsCommand(
    name: 'index:cleanup',
    description: 'Deletes unused search indexes to free up space and maintain optimal search performance.'
)]
class IndexCleanupCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'index-to-remove',
                'i',
                InputOption::VALUE_REQUIRED,
                'Specify a single index to remove'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Delete all indexes, ignoring prefix'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'List indexes that would be removed, without actually deleting them'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(tr('Index Cleanup Process...'));

        $unifiedSearchLib = TikiLib::lib('unifiedsearch');
        $currentIndexDetails = $unifiedSearchLib->getCurrentEngineDetails();
        list($engine, $version, $currentIndex) = $currentIndexDetails;

        $options = [
            'index-to-remove' => $input->getOption('index-to-remove'),
            'all' => $input->getOption('all'),
            'dry-run' => $input->getOption('dry-run')
        ];

        switch ($engine) {
            case 'Elastic':
                $this->cleanupElasticsearch($currentIndex, $io, $options);
                break;
            case 'MySQL':
                $this->cleanupMySQL($currentIndex, $io, $options);
                break;
            case 'Manticore':
                $this->cleanupManticore($currentIndex, $io, $options);
                break;
            default:
                $io->error(tr('Unsupported search backend: %0', $engine));
                return Command::FAILURE;
        }

        $io->success(tr('Index cleanup process completed.'));
        return Command::SUCCESS;
    }

    private function cleanupElasticsearch(string $currentIndex, SymfonyStyle $io, array $options)
    {
        global $prefs;

        if (empty($prefs['unified_elastic_url']) || empty($prefs['unified_elastic_index_prefix'])) {
            $io->error(tr('Elasticsearch preferences are not properly defined.'));
            return;
        }

        try {
            $indexPrefix = $prefs['unified_elastic_index_prefix'];
            $connUrl = $prefs['unified_elastic_url'];
            $manager = new ElasticSearchIndexManager($currentIndex, $indexPrefix, $connUrl);

            $indexToRemove = $options['index-to-remove'];
            $dryRun = $options['dry-run'];
            $removeAll = $options['all'];

            // If a specific index is requested to be removed
            if (! empty($indexToRemove)) {
                if (! $manager->indexExists($indexToRemove)) {
                    $io->error(tr('The specified Elasticsearch index does not exist: %0', $indexToRemove));
                    return;
                }
                $manager->removeIndex($indexToRemove);
                $io->note(tr('Deleted specified Elasticsearch index: %0', $indexToRemove));
                return;
            }

            // Get either all indexes or just the unused ones
            $indexesToDelete = $manager->getIndexes(! empty($removeAll) ? false : true);

            if (empty($indexesToDelete)) {
                $io->note(tr('No Elastic Indexes To Delete.'));
                return;
            }

            foreach ($indexesToDelete as $indexName) {
                if (! empty($dryRun)) {
                    $io->note(tr('Would delete index: %0', $indexName));
                } else {
                    $manager->removeIndex($indexName);
                    $io->note(tr('Deleted unused Elasticsearch index: %0', $indexName));
                }
            }
        } catch (\Exception $e) {
            $io->error(tr('An error occurred during Elasticsearch index cleanup: %0', $e->getMessage()));
        }
    }

    private function cleanupMySQL(string $currentIndex, SymfonyStyle $io, array $options)
    {
        try {
            $mysqlManager = new MysqlSearchIndexManager($currentIndex);
            $indexToRemove = $options['index-to-remove'];
            $dryRun = $options['dry-run'];
            $removeAll = $options['all'];

            // If a specific index is requested to be removed
            if (! empty($indexToRemove)) {
                if (! $mysqlManager->indexExists($indexToRemove)) {
                    $io->error(tr('The specified MYSQL index does not exist: %0', $indexToRemove));
                    return;
                }
                $mysqlManager->removeIndex($indexToRemove);
                $io->note(tr('Deleted specified MYSQL index: %0', $indexToRemove));
                return;
            }

            // Get either all indexes or just the unused ones
            $indexesToDelete = $mysqlManager->getIndexes(! empty($removeAll) ? false : true);

            if (empty($indexesToDelete)) {
                $io->note(tr('No MySQL Indexes To Delete.'));
                return;
            }

            foreach ($indexesToDelete as $indexName) {
                if (! empty($dryRun)) {
                    $io->note(tr('Would delete MySQL index: %0', $indexName));
                } else {
                    $mysqlManager->removeIndex($indexName);
                    $io->note(tr('Deleted unused MySQL index: %0', $indexName));
                }
            }
        } catch (\Exception $e) {
            $io->error(tr('An error occurred during MySQL index cleanup: %0', $e->getMessage()));
        }
    }

    private function cleanupManticore(string $currentIndex, SymfonyStyle $io, array $options)
    {
        global $prefs;

        if (empty($prefs['unified_manticore_url']) || empty($prefs['unified_manticore_index_prefix'])) {
            $io->error(tr('Manticoresearch preferences are not properly defined.'));
            return;
        }

        try {
            $indexPrefix = $prefs['unified_manticore_index_prefix'];
            $dsn = $prefs['unified_manticore_url'];
            $pdoPort = $prefs['unified_manticore_mysql_port'] ?: 9306;
            $manticoreManager = new ManticoreSearchIndexManager($currentIndex, $indexPrefix, $dsn, $pdoPort);
            $indexToRemove = $options['index-to-remove'];
            $dryRun = $options['dry-run'];
            $removeAll = $options['all'];

            // If a specific index is requested to be removed
            if (! empty($indexToRemove)) {
                if (! $manticoreManager->indexExists($indexToRemove)) {
                    $io->error(tr('The specified Manticore index does not exist: %0', $indexToRemove));
                    return;
                }
                $manticoreManager->removeIndex($indexToRemove);
                $io->note(tr('Deleted specified Manticore index: %0', $indexToRemove));
                return;
            }

            // Get either all indexes or just the unused ones
            $indexesToDelete = $manticoreManager->getIndexes(! empty($removeAll) ? false : true);

            if (empty($indexesToDelete)) {
                $io->note(tr('No Manticore Indexes To Delete.'));
                return;
            }

            foreach ($indexesToDelete as $indexName) {
                if (! empty($dryRun)) {
                    $io->note(tr('Would delete Manticore index: %0', $indexName));
                } else {
                    $manticoreManager->removeIndex($indexName);
                    $io->note(tr('Deleted Manticore index: %0', $indexName));
                }
            }
        } catch (\Exception $e) {
            $io->error(tr('An error occurred during Manticore index cleanup: %0', $e->getMessage()));
        }
    }
}
