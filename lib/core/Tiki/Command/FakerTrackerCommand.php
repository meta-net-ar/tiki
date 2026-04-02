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
use Symfony\Component\Console\Attribute\AsCommand;
use TikiLib;
use Tiki\TrackerFaker;
use Tracker_Definition;

/**
 * Enabled the usage of Faker as a way to load random data to trackers
 */
#[AsCommand(
    name: 'faker:tracker',
    description: 'Generate tracker fake data'
)]
class FakerTrackerCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->addArgument(
                'tracker',
                InputArgument::REQUIRED,
                'Tracker id'
            )
            ->addOption(
                'field',
                'f',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Override default faker for field. Format: field,faker[,faker_options]. Example: 1,text,30'
            )
            ->addOption(
                'items',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Number of items to generate',
                100
            )
            ->addOption(
                'random-status',
                'r',
                InputOption::VALUE_NONE,
                'Generate random item status'
            )
            ->addOption(
                'reuse-files',
                null,
                InputOption::VALUE_OPTIONAL,
                'Reuse existing files in the file gallery when possible',
                1
            );
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return null|int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        if (! class_exists('\Faker\Factory')) {
            $output->writeln('<error>' . tra('Please install Faker package') . '</error>');
            return Command::FAILURE;
        }

        $trackerId = $input->getArgument('tracker');
        $numberItems = $input->getOption('items');
        $randomizeStatus = ! empty($input->getOption('random-status'));
        $fieldOverrideDefinition = $input->getOption('field');
        $reuseFiles = ! empty($input->getOption('reuse-files'));

        if (! is_numeric($numberItems)) {
            $output->writeln('<error>' . tra('The value of items is not a number') . '</error>');
            return Command::INVALID;
        }

        $trackerDefinition = Tracker_Definition::get($trackerId);
        if (! $trackerDefinition) {
            $output->writeln('<error>' . tr('Tracker not found') . '</error>');
            return Command::FAILURE;
        }

        $fieldFakerOverride = [];
        foreach ($fieldOverrideDefinition as $fieldDefinition) {
            $arguments = array_map('trim', explode(',', $fieldDefinition));
            $fieldReference = array_shift($arguments);
            $action = array_shift($arguments);

            if (is_null($fieldReference) || is_null($action)) {
                $output->writeln('<error>' . tr('Invalid field definition: %0', $fieldDefinition) . '</error>');
                return Command::FAILURE;
            }

            if (empty($arguments)) {
                $fieldFakerOverride[$fieldReference] = $action;
            } else {
                $fieldFakerOverride[$fieldReference] = [$action, $arguments];
            }
        }

        $trackerFaker = new TrackerFaker($reuseFiles);
        $trackerFields = $trackerDefinition->getFields();

        $fieldFakerMap = [];
        foreach ($trackerFields as $field) {
            $fieldFakerMap[] = [
                'fieldId' => $field['fieldId'],
                'faker' => $trackerFaker->resolveFakerForField($field, $fieldFakerOverride),
            ];
        }

        /** @var \TrackerLib $trackerLib */
        $trackerLib = TikiLib::lib('trk');

        for ($i = 0; $i < $numberItems; $i++) {
            $fieldData = [];

            foreach ($fieldFakerMap as $fieldFaker) {
                $entry = $trackerFaker->buildFieldData($fieldFaker, $trackerDefinition);
                if ($entry) {
                    $fieldData[] = $entry;
                }
            }

            if (! empty($fieldData)) {
                $status = ($randomizeStatus) ? array_rand(\TikiLib::lib('trk')->status_types()) : '';
                $trackerLib->replace_item($trackerId, 0, ['data' => $fieldData], $status);
            }
        }
        return Command::SUCCESS;
    }
}
