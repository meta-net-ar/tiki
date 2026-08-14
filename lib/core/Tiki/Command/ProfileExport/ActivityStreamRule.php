<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command\ProfileExport;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'profile:export:activity-stream-rule',
    description: 'Export an activity stream rule'
)]
class ActivityStreamRule extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addArgument(
                'rule',
                InputArgument::REQUIRED,
                tra('Rule ID')
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $rule = $input->getArgument('rule');

        $writer = $this->getProfileWriter($input);

        if (\Tiki_Profile_InstallHandler_ActivityStreamRule::export($writer, $rule)) {
            $writer->save();
        } else {
            $output->writeln("<error>'" . tra('Rule not found: %rule%', ['%rule%' => $rule]) . "'</error>");
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
