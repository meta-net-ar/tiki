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
    name: 'cache:clear',
    description: 'Clear Tiki caches',
)]
class CacheClearCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'cache',
                InputArgument::OPTIONAL,
                'Type of cache to clear (public, private, templates, modules, all)',
                'all'
            )
            ->addOption(
                'all',
                null,
                InputOption::VALUE_NONE,
                'Clear all caches and rebuild the index'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $all = $input->getOption('all');
        $type = $input->getArgument('cache');

        require_once('lib/cache/cachelib.php');
        require_once('lib/tikilib.php');
        $cachelib = new \Cachelib();

        if ($all) {
            // Probably there for historical reasons, this ignores the command argument - benoitg 2023-05-08
            $output->writeln('Clearing all caches');
            $cachelib->empty_cache();

            if (DB_STATUS) { // we have a functional db connection
                $output->writeln('Rebuilding admin index');
                \TikiLib::lib('prefs')->rebuildIndex();
            }
        } else {
            switch ($type) {
                case 'public':
                    $output->writeln('Clearing public caches');
                    $cachelib->empty_cache('temp_public');
                    break;
                case 'private':
                    $output->writeln('Clearing private caches');
                    $cachelib->empty_cache('temp_cache');
                    break;
                case 'templates':
                    $output->writeln('Clearing template caches');
                    $cachelib->empty_cache('templates_c');
                    break;
                case 'modules':
                    $output->writeln('Clearing module caches');
                    $cachelib->empty_cache('modules_cache');
                    break;
                case 'all':
                    $output->writeln('Clearing all caches');
                    $cachelib->empty_cache();
                    break;
                case '':
                    return (int) $output->writeln('<error>Missing "cache" parameter.</error>');
                    return Command::INVALID;
                default:
                    $output->writeln('<error>Invalid cache requested.</error>');
                    return Command::INVALID;
            }
        }
        return Command::SUCCESS;
    }
}
