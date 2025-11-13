<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handles the processing of queued tasks in the Tiki system.
 * This command can be executed in a loop mode where it continuously processes tasks
 */
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Tiki\TaskQueue\Exception\QueueManagerException;
use Tiki\TaskQueue\QueuedTaskSettings;
use Tiki\TaskQueue\QueueManager;

#[AsCommand(
    name: 'taskqueue:process',
    description: 'Run queued tasks, optionally in a continuous loop.'
)]
class TaskQueueProcessCommand extends Command
{
    private $queueManager;

    protected function configure()
    {
        $this
            ->addOption(
                'loop',
                null,
                InputOption::VALUE_NONE,
                tra('Enable continuous processing of tasks. Without this option, all queued tasks are processed once and the command exits.')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->queueManager = new QueueManager();
            $loop = $input->getOption('loop');

            do {
                $output->writeln(tra("Fetching Pending Queued Tasks..."));
                $queuedTasks = $this->queueManager->getQueuedTasks([], ['status' => QueuedTaskSettings::PENDING]);
                if (empty($queuedTasks)) {
                    $output->writeln(tra("No pending tasks found."));
                } else {
                    $output->writeln(sprintf(tra("Found %d pending task(s)."), count($queuedTasks)));
                    foreach ($queuedTasks as $queuedTask) {
                        $output->writeln(sprintf(tra("Processing task ID %d of type %s..."), $queuedTask->getId(), $queuedTask->getType()));
                        $this->queueManager->processTask($queuedTask);
                    }
                }
                if ($loop) {
                    $output->writeln(tra("Sleeping for 0.2 seconds."));
                    usleep(200000);
                }
            } while ($loop);

            return Command::SUCCESS;
        } catch (QueueManagerException $e) {
            $output->writeln(sprintf(tra("An error occurred: %s"), $e->getMessage()));
            return Command::FAILURE;
        }
    }
}
