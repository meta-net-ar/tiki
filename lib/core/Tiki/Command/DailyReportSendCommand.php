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

error_reporting(E_ALL);
use TikiLib;
use Reports_Factory;

#[AsCommand(
    name: 'daily-report:sendd',
    description: 'Send daily user reports'
)]
class DailyReportSendCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $access = TikiLib::lib('access');

        $access->check_feature('feature_daily_report_watches');

        $output->writeln(tra('Generating reports...'));
        $reportsManager = Reports_Factory::build('Reports_Manager');

        $output->writeln(tra('Sending...'));
        $reportsManager->send();

        $output->writeln(tra('Finished.'));
        return Command::SUCCESS;
    }
}
