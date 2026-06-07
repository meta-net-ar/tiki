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
use Tiki\Lib\Auth\Tokens;

#[AsCommand(
    name: 'tokens:rotate-secret',
    description: 'Rotate the token signing secret and revoke existing tokens'
)]
class TokensRotateSecretCommand extends Command
{
    protected function configure(): void
    {
        $this->setHelp(
            tr('Run this command after cloning a Tiki database so copied auth tokens cannot be reused between installations.')
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        $tokenlib = Tokens::build($prefs);
        $revokedCount = $tokenlib->rotateSigningSecret();

        $output->writeln(
            '<comment>' . tr('Token signing secret rotated. %0 existing token(s) were revoked and must be recreated.', $revokedCount) . '</comment>'
        );

        return Command::SUCCESS;
    }
}
