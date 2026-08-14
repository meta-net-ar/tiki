<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'scheduler:heal',
    description: 'Heal scheduled tasks'
)]
class SchedulerHealCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'schedulerId',
                InputArgument::OPTIONAL,
                tra('Scheduler Id to be healed')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schedulerId = $input->getArgument('schedulerId');

        $verbosityLevelMap = [
            LogLevel::ERROR => OutputInterface::OUTPUT_NORMAL,
            LogLevel::NOTICE => OutputInterface::OUTPUT_NORMAL,
            LogLevel::INFO => OutputInterface::VERBOSITY_VERY_VERBOSE,
            LogLevel::DEBUG => OutputInterface::VERBOSITY_DEBUG,
        ];

        $logger = new ConsoleLogger($output, $verbosityLevelMap);

        $manager = new \Scheduler_Manager($logger);
        $manager->heal($schedulerId);
        return Command::SUCCESS;
    }
}
