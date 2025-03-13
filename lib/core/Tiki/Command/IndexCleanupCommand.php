<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Index Cleanup Process...');

        $unifiedSearchLib = TikiLib::lib('unifiedsearch');
        $currentIndexDetails = $unifiedSearchLib->getCurrentEngineDetails();
        list($engine, $version, $currentIndex) = $currentIndexDetails;

        switch ($engine) {
            case 'Elastic':
                $this->cleanupElasticsearch($currentIndex, $io);
                break;
            case 'MySQL':
                $this->cleanupMySQL($currentIndex, $io);
                break;
            case 'Manticore':
                $this->cleanupManticore($currentIndex, $io);
                break;
            default:
                $io->error("Unsupported search backend: $engine");
                return Command::FAILURE;
        }

        $io->success('Index cleanup process completed successfully.');
        return Command::SUCCESS;
    }

    private function cleanupElasticsearch($currentIndex, SymfonyStyle $io)
    {
        global $prefs;

        if (! isset($prefs['unified_elastic_url']) || empty($prefs['unified_elastic_url']) || ! isset($prefs['unified_elastic_index_prefix']) || empty($prefs['unified_elastic_index_prefix'])) {
            $io->error('Elasticsearch preferences are not properly defined.');
            return;
        }

        try {
            $indexPrefix = $prefs['unified_elastic_index_prefix'];
            $connUrl = $prefs['unified_elastic_url'];
            $manager = new ElasticSearchIndexManager($currentIndex, $indexPrefix, $connUrl);
            $unUsedIndexes = $manager->getUnusedIndexes();
            if (! count($unUsedIndexes)) {
                $io->note('No unused indexes to delete.');
                return;
            }
            foreach ($unUsedIndexes as $indexName) {
                $manager->removeIndex($indexName);
                $io->note("Deleted unused Elasticsearch index: $indexName");
            }
        } catch (\Exception $e) {
            $io->error('An error occurred during Elasticsearch index cleanup: ' . $e->getMessage());
        }
    }

    private function cleanupMySQL($currentIndex, SymfonyStyle $io)
    {
        try {
            $mysqlManager = new MysqlSearchIndexManager($currentIndex);
            $unusedIndexes = $mysqlManager->getUnusedIndexes();
            if (! count($unusedIndexes)) {
                $io->note('No unused indexes to delete.');
                return;
            }
            foreach ($unusedIndexes as $indexName) {
                $mysqlManager->removeIndex($indexName);
                $io->note("Deleted unused MySQL index: $indexName");
            }
        } catch (\Exception $e) {
            $io->error('An error occurred during MySQL index cleanup: ' . $e->getMessage());
        }
    }

    private function cleanupManticore($currentIndex, SymfonyStyle $io)
    {
        global $prefs;

        if (! isset($prefs['unified_manticore_url']) || empty($prefs['unified_manticore_url']) || ! isset($prefs['unified_manticore_index_prefix']) || empty($prefs['unified_manticore_index_prefix'])) {
            $io->error('Manticoresearch preferences are not properly defined.');
            return;
        }

        try {
            $indexPrefix = $prefs['unified_manticore_index_prefix'] . 'main';
            $dsn = $prefs['unified_manticore_url'];
            $pdoPort = $prefs['unified_manticore_mysql_port'] ?: 9306;
            $manticoreManager = new ManticoreSearchIndexManager($currentIndex, $indexPrefix, $dsn, $pdoPort);
            $unusedIndexes = $manticoreManager->getUnusedIndexes();
            if (! count($unusedIndexes)) {
                $io->note('No unused indexes to delete.');
                return;
            }
            foreach ($unusedIndexes as $indexName) {
                $manticoreManager->removeIndex($indexName);
                $io->note("Deleted Manticore index: $indexName");
            }
        } catch (\Exception $e) {
            $io->error('An error occurred during Manticore index cleanup: ' . $e->getMessage());
        }
    }
}
