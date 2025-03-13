<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'log:delete',
    description: 'Delete system logs'
)]
class LogDeleteCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'before-months',
                '',
                InputOption::VALUE_REQUIRED,
                'This will clean all the log entries with more than (number) months'
            )
            ->addOption(
                'before-date',
                '',
                InputOption::VALUE_REQUIRED,
                'This will clean logs before this date'
            )
            ->addOption(
                'keep-entries',
                '',
                InputOption::VALUE_REQUIRED,
                'This will keep (number) of entries (the most recent ones).'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $options = [
            'handleBeforeMonthsOption' => 'before-months',
            'handleBeforeDateOption'   => 'before-date',
            'handleKeepEntriesOption'  => 'keep-entries',
        ];
        $optionsEnteredCount = 0;
        $handleFunction = null;

        foreach ($options as $key => $option) {
            $value = $input->getOption($option);

            if (isset($value)) {
                $optionsEnteredCount++;
                $handleFunction = $key;
            }
        }

        try {
            if (! $handleFunction) {
                throw new \Exception("Specify at least one parameter", 0);
            }

            if ($optionsEnteredCount > 1) {
                throw new \Exception("You can only enter one option", 1);
            }

            $this->{$handleFunction}($input, $output);
            return 0;
        } catch (\Exception $exception) {
            $output->writeln('<error>' . $exception->getMessage() . '</error>');
            return $exception->getCode();
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    private function handleBeforeMonthsOption(InputInterface $input, OutputInterface $output)
    {
        $logsLib = \TikiLib::lib('logs');
        $value = $input->getOption('before-months');
        if (! is_numeric($value) || $value < 0) {
            throw new \Exception(tr("Please enter the number of months in whole numbers that is greater than or equal to 0"), 2);
        }

        $date = strtotime("-" . $value . " months");

        if (! $date) {
            throw new \Exception(tr("Please enter valid months"), 3);
        }

        $clearedLogs = $logsLib->clean_logs($date);
        $output->writeln('<info>' . tr('%0 logs have been cleared.', $clearedLogs->numrows) . '</info>');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    private function handleBeforeDateOption(InputInterface $input, OutputInterface $output)
    {
        $logsLib = \TikiLib::lib('logs');
        $dateInput = strtotime($input->getOption('before-date'));

        if (! $dateInput) {
            throw new \Exception(tr("Please enter the valid date"), 3);
        }

        try {
            $clearedLogs = $logsLib->clean_logs($dateInput);
            $output->write('<info>' . tr('%0 logs have been cleared.', $clearedLogs->numrows) . '</info>');
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), 3);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @throws \Exception
     */
    private function handleKeepEntriesOption(InputInterface $input, OutputInterface $output)
    {
        $logsLib = \TikiLib::lib('logs');
        $count = $input->getOption('keep-entries');

        if (! is_numeric($count) || $count < 0) {
            throw new \Exception(tr("Please enter the number of entries in whole numbers that is greater than or equal to 0"), 5);
        }

        $logsAllCount = $logsLib->logsCount();
        $toDelete = $logsAllCount - $count;

        if ($toDelete <= 0) {
            $msg = tr('No logs need to be cleared. Number of entries requested to keep (%0) exceeds or equals the total logs (%1).', $count, $logsAllCount);
            $output->writeln('<info>' . $msg . '</info>');
            return;
        }

        $clearedLogs = $logsLib->cleanWithCount($toDelete);
        $output->writeln('<info>' . tr('%0 logs have been cleared.', $clearedLogs->numrows) . '</info>');
    }
}
