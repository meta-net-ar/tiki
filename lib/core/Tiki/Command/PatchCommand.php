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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:patch',
    description: 'Apply a specific database schema patch',
)]
class PatchCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Name of the patch applied')
            ->addOption(
                'force-application',
                null,
                InputOption::VALUE_NONE,
                tra('Force application, even if the patch was already applied')
            )->addOption(
                'force-mark',
                null,
                InputOption::VALUE_NONE,
                tra('Forcibly mark the patch as applied, regardless of errors')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');
        $forceApplication = $input->getOption('force-application');
        $forceMark = $input->getOption('force-mark');

        $installer = \Tiki\Installer\Installer::getInstance();
        if (! $installer->isInstalled()) {
            $output->writeln('<error>' . tra('Database not found') . '</error>');
            return (int) false;
        }
        try {
            $installer->installPatch($name, $forceApplication);
            $output->writeln(tra('Patch applied'));
        } catch (\Exception $e) {
            switch ($e->getCode()) {
                case 1:
                    $output->writeln("<error>" . tra('Unknown patch') . "</error>");
                    return Command::FAILURE;
                case 2:
                    $output->writeln("<error>" . tra('Application failed') . "</error>");
                    foreach ($installer->queries['failed'] as $key => $error) {
                        list( $query, $message, $patch ) = $error;
                        $output->writeln("<error>Error $key in $patch\n\t$query\n\t$message</error>");
                    }
                    if ($forceMark) {
                        $output->writeln(tra("Patch forcibly marked as applied"));
                        \Tiki\Installer\Patch::$list[$name]->record();
                    }
                    break;
                case 3:
                    $output->writeln(tra('Already applied patch; not applying'));
                    break;
                default:
                    throw $e;
            }
        }
        $output->writeln('<info>' . tra('Queries executed successfully: ') . count($installer->queries['successful']) . '</info>');

        \TikiLib::lib('cache')->empty_cache();
        return Command::SUCCESS;
    }
}
