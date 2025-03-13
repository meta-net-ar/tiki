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
use TikiLib;

#[AsCommand(
    name: '2fa:disable',
    description: 'Disable two-factor authentication'
)]
class Disable2FACommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            TikiLib::lib('tiki')->set_preference('twoFactorAuth', 'n');
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to disable two-factor authentication: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Two-factor authentication has been disabled.</info>');
        return Command::SUCCESS;
    }
}
