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
use Tiki\Process\Process;

#[AsCommand(
    name: 'dev:run-signaturepdf',
    description: 'Run SignaturePDF development server',
)]
class DevRunSignaturePDF extends Command
{
    protected function configure()
    {
        $this
            ->setHelp('Runs the SignaturePDF development server on the specified port. This is useful for development and testing purposes.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;
        $port = $prefs['signaturepdf_dev_port'];
        
        $output->writeln("<info>Starting Signature PDF development server on port $port...</info>");

        // Run the development server using PHP's built-in server
        $process = new Process(['php', '-S', "localhost:$port", '-t', __DIR__ . '/../../../../vendor/24eme/signaturepdf/public']);
        $process->setTimeout(null);
        $process->setTty(true);
        $process->run();

        return Command::SUCCESS;
    }
}
