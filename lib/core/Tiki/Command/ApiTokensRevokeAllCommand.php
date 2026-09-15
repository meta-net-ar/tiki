<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'api-tokens:revoke-all',
    description: 'Revoke all API and OAuth bearer tokens'
)]
class ApiTokensRevokeAllCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Revoke without asking for confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (! $input->getOption('force') && ! $io->confirm('Revoke all API and OAuth tokens?', false)) {
            $io->note('No tokens were revoked.');
            return Command::SUCCESS;
        }

        $tokenlib = \TikiLib::lib('api_token');
        $count = count($tokenlib->getTokens());
        $tokenlib->deleteAllTokens();

        $io->success(tr('%0 API and OAuth tokens revoked.', $count));
        return Command::SUCCESS;
    }
}
