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
    name: 'profile:export:plugin-alias',
    description: 'Export a plugin alias definition as a profile object'
)]
class PluginAlias extends ObjectWriter
{
    protected function configure()
    {
        $this
            ->addArgument(
                'alias',
                InputArgument::REQUIRED,
                'Plugin alias name (case-insensitive)'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('alias');

        $writer = $this->getProfileWriter($input);

        if (! \Tiki_Profile_InstallHandler_PluginAlias::export($writer, $name)) {
            $output->writeln("<error>Plugin alias not found: $name</error>");
            return Command::FAILURE;
        }

        $writer->save();
        $output->writeln("Plugin alias <info>$name</info> exported successfully.");

        return Command::SUCCESS;
    }
}
