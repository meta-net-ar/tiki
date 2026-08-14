<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use TikiLib;

#[AsCommand(
    name: 'scheduler:stats',
    description: 'Output a table with scheduler tasks statistics'
)]
class SchedulerStatsCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'csv',
                null,
                InputOption::VALUE_NONE,
                'Output data in CSV format'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;
        $schedLib = TikiLib::lib('scheduler');
        $lastDays = 7;
        $header = [
            'Scheduler Tasks Statistics',
            'Values'
        ];
        $rowDataValues = [
            ['Minutes since last run', $schedLib->getMinutesLastRun()],
            ['Tasks executed in the last hour', $schedLib->getExecutedTasksbyTime(1)],
            ['Tasks executed in the last ' . $lastDays . ' days', $schedLib->getExecutedTasksbyTime($lastDays * 24)]
        ];
        if (! empty($prefs['scheduledTasksReport'])) {
            $tasksEnabled = $schedLib->getSchedulerFromLogs();
            $countTaskFailures = [];
            foreach ($tasksEnabled as $task) {
                $tableRow = [
                    'Last Execution: ' . $task['name'],
                    TikiLib::date_format("%Y-%m-%d %H:%i:%s", $task['start_time']) . ' - ' . $task['status'],
                ];
                array_push($rowDataValues, $tableRow);

                $tableRow = [
                    'Task has failures: ' . $task['name'],
                ];
                if ($prefs['scheduledTasksReport'] === 'do_not_report') {
                    array_push($tableRow, '');
                } elseif ($prefs['scheduledTasksReport'] === 'last_number_of_hours') {
                    if (! isset($countTaskFailures[$task['scheduler_id']])) {
                        $countTaskFailures[$task['scheduler_id']] = 0;
                    }
                    if ($task['status'] === 'failed') {
                        $countTaskFailures[$task['scheduler_id']]++;
                    }
                    array_push($tableRow, $countTaskFailures[$task['scheduler_id']]);
                } else {
                    array_push($tableRow, $task['status'] === 'failed' ? 1 : 0);
                }
                array_push($rowDataValues, $tableRow);
            }
        }

        if ($input->getOption('csv')) {
            $tikiLib = TikiLib::lib('tiki');
            $csv = $tikiLib->str_putcsv($header) . PHP_EOL;
            foreach ($rowDataValues as $row) {
                $csv .= $tikiLib->str_putcsv($row) . PHP_EOL;
            }
            $output->writeln($csv);
        } else {
            $table = new Table($output);
            $table->setHeaders($header);
            $table->setRows($rowDataValues);
            $table->render();
        }

        return Command::SUCCESS;
    }
}
