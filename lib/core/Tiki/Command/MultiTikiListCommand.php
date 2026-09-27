<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'multitiki:list',
    description: 'List MultiTikis in a path',
)]
class MultiTikiListCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'path to the Tiki instance to list (defaults to this one if absent)'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');

        if (! $path) {
            $path = getcwd();
        }

        $virtuals = $path . '/db/virtuals.inc';

        $list = [];

        if (is_file($virtuals)) {
            $list = file($virtuals);
        }
        if ($list) {
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln("<info>Multitikis in $path</info>");
            }
            foreach ($list as $multi) {
                $output->writeln(trim($multi, " \n\r\t\v\x00"));
            }
        } else {
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln("<info>No multitikis found in $path</info>");
            }
        }
        return Command::SUCCESS;
    }
}
