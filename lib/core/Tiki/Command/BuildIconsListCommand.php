<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Lib\IconGenerator;

#[AsCommand(
    name: 'build:generateiconlist',
    description: 'Generate theme\'s iconset'
)]

class BuildIconsListCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('build:generateiconlist')
            ->setHelp('Synchronize current iconset with the latest available icons from icon libraries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $generator = new IconGenerator($output);
            $generator->execute();
            $output->writeln("<info>Done!</info>");
            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }
}
