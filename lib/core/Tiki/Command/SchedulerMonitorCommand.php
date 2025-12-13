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
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'scheduler:monitor',
    description: 'Monitor scheduler jobs'
)]
class SchedulerMonitorCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'minutes-back',
                'm',
                InputOption::VALUE_REQUIRED,
                tra('Number of minutes back to check for failed tasks')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        if ($prefs['feature_scheduler'] != 'y') {
            $output->writeln("<error>Scheduler feature is not enabled.</error>");
            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $minutesBack = (int) $input->getOption('minutes-back') ?: 0;

        if ($minutesBack === 0) {
            $output->writeln(tr('Option "minutes-back" is not valid. Insert a valid numeric number greater than 0'));
            return Command::FAILURE;
        }

        $timestampBack = time() - ($minutesBack * 60);
        $schedLib = \TikiLib::lib('scheduler');
        $results = $schedLib->getFailedRuns($timestampBack);

        $message = "";
        foreach ($results as $key => $run) {
            $seconds = $run['end_time'] - $run['start_time'];
            $scheduler = $schedLib->get_scheduler($run['scheduler_id']);
            if ($key != 0) {
                $message .= "\n";
            }
            $message .= "Scheduler $scheduler[name] failed after $seconds seconds";
            $message .= "\nReason: $run[output]";
        }

        if (count($results) == 0) {
            return Command::SUCCESS;
        }

        $output->writeln($message);
        return Command::FAILURE;
    }
}
