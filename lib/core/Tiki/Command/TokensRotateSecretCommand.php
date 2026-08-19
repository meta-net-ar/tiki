<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TokensRotateSecretCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('tokens:rotate-secret')
            ->setDescription('Rotate the token signing secret and revoke existing tokens')
            ->setHelp(
                tra('Run this command after cloning a Tiki database so copied auth tokens cannot be reused between installations.')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $prefs;
        require_once 'lib/auth/tokens.php';

        $tokenlib = \AuthTokens::build($prefs);
        $revokedCount = $tokenlib->rotateSigningSecret();

        $output->writeln(
            '<comment>' . tr('Token signing secret rotated. %0 existing token(s) were revoked and must be recreated.', $revokedCount) . '</comment>'
        );
    }
}
