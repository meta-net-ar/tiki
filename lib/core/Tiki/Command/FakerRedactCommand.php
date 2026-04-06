<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use TikiLib;
use Tiki\TrackerFaker;
use Tracker_Definition;

#[AsCommand(
    name: 'faker:redact',
    description: 'Replace existing tracker item field values with fake data'
)]
class FakerRedactCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'field',
                'f',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Field ID or permanent name to redact. Use -f for each field, or optionally override faker: field,faker[,faker_options]. Example: -f 42 -f myPermName -f 7,email'
            )
            ->addOption(
                'confirm',
                null,
                InputOption::VALUE_NONE,
                'Actually perform the redaction. Without this flag, runs in dry-run mode.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (! class_exists('\Faker\Factory')) {
            $output->writeln('<error>' . tra('Please install Faker package') . '</error>');
            return Command::FAILURE;
        }

        $fieldDefinitions = $input->getOption('field');
        if (empty($fieldDefinitions)) {
            $output->writeln('<error>' . tra('At least one --field (-f) option is required.') . '</error>');
            return Command::INVALID;
        }

        $confirm = $input->getOption('confirm');

        /** @var \TrackerLib $trackerLib */
        $trackerLib = TikiLib::lib('trk');
        $trackerFaker = new TrackerFaker();

        $fieldsByTracker = [];
        $fieldInfoCache = [];

        foreach ($fieldDefinitions as $fieldDefinition) {
            $arguments = array_map('trim', explode(',', $fieldDefinition));
            $fieldReference = array_shift($arguments);
            $fakerOverride = ! empty($arguments) ? array_shift($arguments) : null;
            $fakerArgs = ! empty($arguments) ? $arguments : null;

            $fieldInfo = $trackerLib->get_field_by_perm_name($fieldReference);

            if (! $fieldInfo || empty($fieldInfo['trackerId'])) {
                $output->writeln('<error>' . tr('Field not found: %0', $fieldReference) . '</error>');
                return Command::FAILURE;
            }

            $trackerId = (int) $fieldInfo['trackerId'];
            $fieldId = (int) $fieldInfo['fieldId'];

            $overrides = [];
            if ($fakerOverride !== null) {
                $overrides[$fieldId] = $fakerArgs ? [$fakerOverride, $fakerArgs] : $fakerOverride;
            }

            $fieldsByTracker[$trackerId][] = [
                'fieldId' => $fieldId,
                'faker' => $trackerFaker->resolveFakerForField($fieldInfo, $overrides, 'text'),
            ];
            $fieldInfoCache[$fieldId] = $fieldInfo;
        }

        if (! $confirm) {
            $output->writeln('<comment>' . tra('DRY-RUN MODE — no data will be modified. Use --confirm to apply changes.') . '</comment>');
            $output->writeln('');
        } else {
            $output->writeln('<fg=red;options=bold>' . tra('WARNING: This will permanently replace field values in the current database.') . '</>');
            $output->writeln('');
        }

        $totalItems = 0;
        $totalFieldValues = 0;

        foreach ($fieldsByTracker as $trackerId => $fields) {
            $trackerDefinition = Tracker_Definition::get($trackerId);
            if (! $trackerDefinition) {
                $output->writeln('<error>' . tr('Tracker %0 not found', $trackerId) . '</error>');
                return Command::FAILURE;
            }

            $itemIds = $trackerLib->get_all_tracker_items($trackerId);
            $itemCount = count($itemIds);
            $fieldCount = count($fields);
            $fieldNames = array_map(function ($f) use ($fieldInfoCache) {
                $info = $fieldInfoCache[$f['fieldId']];
                return $info['permName'] . ' (#' . $f['fieldId'] . ')';
            }, $fields);

            $output->writeln(tr(
                'Tracker #%0 (%1): %2 items × %3 fields [%4]',
                $trackerId,
                $trackerDefinition->getConfiguration('name') ?? '',
                $itemCount,
                $fieldCount,
                implode(', ', $fieldNames)
            ));

            $totalItems += $itemCount;
            $totalFieldValues += $itemCount * $fieldCount;

            if ($confirm && $itemCount > 0) {
                foreach ($itemIds as $itemId) {
                    $fieldData = [];
                    foreach ($fields as $fieldFaker) {
                        $entry = $trackerFaker->buildFieldData($fieldFaker, $trackerDefinition);
                        if ($entry) {
                            $fieldData[] = $entry;
                        }
                    }
                    if (! empty($fieldData)) {
                        $trackerLib->replace_item($trackerId, $itemId, ['data' => $fieldData], '', 0, true, true);
                    }
                }
                $output->writeln('<info>  → ' . tr('Redacted %0 items.', $itemCount) . '</info>');
            }
        }

        $output->writeln('');
        $output->writeln(tr('Total: %0 items, %1 field values to redact.', $totalItems, $totalFieldValues));

        if (! $confirm) {
            $output->writeln('');
            $output->writeln('<comment>' . tra('Run with --confirm to apply. CAUTION: This will destroy existing data in the current database.') . '</comment>');
        } else {
            $output->writeln('<info>' . tra('Redaction complete.') . '</info>');
        }

        return Command::SUCCESS;
    }
}
