<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'tracker:export',
    description: 'Export a CSV file from a tracker using a tracker import-export format'
)]
class TrackerExportCommand extends Command
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
                'Location (full path) and/or a file name to export - depending on the import-export format, this could be csv, json, ndjson or ical format.'
            )
            ->addOption(
                'filter',
                null,
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Query string formatted name=value key pairs of filters applied to the corresponding import-export format. Inspect the web UI partial export of this format in order to get the available filter names and possible values.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $output->writeln('Exporting tracker...');

        $lib = \TikiLib::lib('tabular');
        $info = $lib->getInfo($input->getArgument('tabularId'));

        $perms = \Perms::get('tabular', $info['tabularId']);
        if (! $info || ! $perms->tabular_export) {
            throw new \Exception('Tracker Export: Import-Export Format not found');
        }

        $fileName = $input->getArgument('filename');

        $tracker = \Tracker_Definition::get($info['trackerId']);

        if (! $tracker) {
            throw new \Exception('Tracker Export: Tracker not found');
        }

        $schema = new \Tracker\Tabular\Schema($tracker);
        $schema->loadFormatDescriptor($info['format_descriptor']);
        $schema->loadFilterDescriptor($info['filter_descriptor']);
        $schema->loadConfig($info['config']);

        $schema->validate();

        if (! $schema->getPrimaryKey()) {
            throw new \Exception(tr('Primary Key required'));
        }

        if ($filter = $input->getOption('filter')) {
            $qs = implode('&', $filter);
            parse_str($qs, $data);
            $inp = new \JitFilter($data);
            $collection = $schema->getFilterCollection();
            $collection->applyInput($inp);
            $search = \TikiLib::lib('unifiedsearch');
            $query = $search->buildQuery([
                'type' => 'trackeritem',
                'tracker_id' => $info['trackerId'],
            ]);
            $collection->applyConditions($query);
            $source = new \Tracker\Tabular\Source\QuerySource($schema, $query);
        } else {
            // this will throw exceptions and not return if there's a problem
            $source = $schema->getDefaultFilterSource();
        }

        if (! empty($fileName)) {
            $name = '';
            $writer = $schema->getWriter($name, '', $fileName);
        } elseif (! empty($info['odbc_config'])) {
            $writer = new \Tracker\Tabular\Writer\ODBCWriter($info['odbc_config']);
        } elseif (! empty($info['api_config'])) {
            $writer = new \Tracker\Tabular\Writer\APIWriter($info['api_config'], $info['config']);
        } else {
            throw new \Exception(tr('Tracker Export: No filename or remote import-export synchronization settings provided.'));
        }

        $result = $writer->write($source);

        \Feedback::printToConsole($output);

        if ($result) {
            $output->writeln(tr("Items succeeded: %0", $result['succeeded']));
            $output->writeln(tr("Items failed: %0", $result['failed']));
            $output->writeln(tr("Items skipped: %0", $result['skipped']));
            foreach ($result['errors'] as $error) {
                $output->writeln('<error>' . $error . '</error>');
            }
        }

        $output->writeln('Export done');

        return Command::SUCCESS;
    }
}
