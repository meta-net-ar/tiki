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
    name: 'tokens:clear',
    description: 'Remove expired tokens'
)]
class TokensClearCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        $tokenlib = Tokens::build($prefs);
        $affectedRows = $tokenlib->deleteExpired();

        $output->writeln(tr('%0 tokens deleted.', $affectedRows->numrows), OutputInterface::VERBOSITY_VERBOSE);
        $output->writeln('<comment>' . tr('Expired tokens were removed.') . '</comment>');
        return Command::SUCCESS;
    }
}
