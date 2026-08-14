<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InstallCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('database:install')
            ->setDescription(tr('Clean Tiki install'))
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                tr('Force installation. Overwrite any current database.')
            )
            ->addOption(
                'useInnoDB',
                'i',
                InputOption::VALUE_REQUIRED,
                tr('Use InnoDb as storage engine: 1 - InnoDb, 0 - MyISAM.'),
                1
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $force = $input->getOption('force');
        $installer = \Tiki\Installer\Installer::getInstance();
        $installed = $installer->tableExists('users_users');

        $optionUseInnoDB = $input->getOption('useInnoDB');
        if ($optionUseInnoDB !== null) {
            $installer->useInnoDB = $optionUseInnoDB == 1;
        }

        if (! $installed || $force) {
            $installer->cleanInstall();
            $output->writeln('<info>' . tr('Installed from files:') . '</info>');

            foreach ($installer->queries['files'] as $file) {
                $output->writeln('<info>' . $file . '</info>');
            }
            $output->writeln('<info>' . tr('Queries executed successfully: %0', count($installer->queries['successful'])) . '</info>');

            if (count($installer->queries['failed'])) {
                $output->writeln('<error>' . tr('Queries executed unsuccessfully: %0', count($installer->queries['failed'])) . '</error>');
                foreach ($installer->queries['failed'] as $key => $error) {
                    list($query, $message, $patch) = $error;

                    $output->writeln("<error>" . tr('Error %0 in', $key) . " $patch\n\t$query\n\t$message</error>");
                }
                return Command::FAILURE;
            } else {
                $output->writeln('<fg=cyan>' . tr('Installation completed successfully.') . '</>');
            }

            if (! DB_STATUS) { // see console.php  DB_STATUS: Database connected, but tiki not installed.
                return Command::SUCCESS;
            }
            include_once 'tiki-setup.php';
            $output->writeln('<info>' . tr('Clearing cache.') . '</info>');
            \TikiLib::lib('cache')->empty_cache();
            $output->writeln('<info>' . tr('Initializing prefs.') . '</info>');
            initialize_prefs(true);
            $output->writeln('<info>' . tr('Rebuilding indexes.') . '</info>');
            \TikiLib::lib('unifiedsearch')->rebuild();
            \TikiLib::lib('prefs')->rebuildIndex();
        } else {
            $output->writeln('<error>' . tr('Database already exists.') . '</error>');
            return Command::INVALID;
        }
        return Command::SUCCESS;
    }
}
