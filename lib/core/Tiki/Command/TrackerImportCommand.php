<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Lib\Logs\LogsLib;
use TikiLib;

#[AsCommand(
    name: 'tracker:import',
    description: 'Import a CSV file into a tracker using a tracker import-export format or initiate an ODBC import'
)]
class TrackerImportCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'tabularId',
                InputArgument::REQUIRED,
                'ID of tracker import-export format to use'
            )
            ->addArgument(
                'filename',
                InputArgument::OPTIONAL,
                'Location of CSV file to import (not used if import-export is ODBC-configured)'
            )
            ->addOption(
                'placeholders',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Specify the placedholer values of an API tabular containing placeholders in the LIST endpoint URL (you can use multiple times, once for each placeholder)'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('Importing tracker...');

        /** @var LogsLib $logslib */
        $logslib = TikiLib::lib('logs');
        $lib = TikiLib::lib('tabular');
        $info = $lib->getInfo($input->getArgument('tabularId'));

        $perms = \Perms::get('tabular', $info['tabularId']);
        if (! $info || ! $perms->tabular_import) {
            throw new \Exception('Tracker Import: Import-Export Format not found');
        }

        // from \Services_Tracker_TabularController::getSchema TODO refactor?
        $tracker = \Tracker_Definition::get($info['trackerId']);

        if (! $tracker) {
            throw new \Exception('Tracker Import: Tracker not found');
        }

        $schema = new \Tracker\Tabular\Schema($tracker);
        $schema->loadFormatDescriptor($info['format_descriptor']);
        $schema->loadFilterDescriptor($info['filter_descriptor']);
        $schema->loadConfig($info['config']);

        $schema->validate();

        if (! $schema->getPrimaryKey()) {
            throw new \Exception(tr('Primary Key required'));
        }

        if ($info['odbc_config']) {
            $source = new \Tracker\Tabular\Source\ODBCSource($schema, $info['odbc_config']);
            $writer = new \Tracker\Tabular\Writer\TrackerWriter();
            $writer->write($source);
        } elseif ($info['api_config']) {
            $placeholders = $input->getOption('placeholders');
            $params = [];
            if (preg_match_all('/%([^%]+)%/', $info['api_config']['list_url'], $matches)) {
                foreach ($matches[1] as $key => $field) {
                    $params[$field] = $placeholders[$key] ?? '';
                }
            } elseif (preg_match_all('/%([^%]+)%/', $info['api_config']['list_parameters'], $matches)) {
                foreach ($matches[1] as $key => $field) {
                    $params[$field] = $placeholders[$key] ?? '';
                }
            }
            $source = new \Tracker\Tabular\Source\APISource($schema, $info['api_config'], $params);
            $writer = new \Tracker\Tabular\Writer\TrackerWriter();
            $writer->write($source);
        } else {
            $fileName = $input->getArgument('filename');
            if (! file_exists($fileName)) {
                throw new \Exception('Tracker Import: File not found');
            }

            // this will throw exceptions and not return if there's a problem
            $source = $schema->getSource($fileName);
            $writer = new \Tracker\Tabular\Writer\TrackerWriter();
            $writer->write($source);
        }

        \Feedback::printToConsole($output);

        $output->writeln(tra('Import done'));
        $logslib->add_action('tracker import', 'system', 'system', 'tracker #' . $info['trackerId'] . ' imported.');

        return Command::SUCCESS;
    }
}
