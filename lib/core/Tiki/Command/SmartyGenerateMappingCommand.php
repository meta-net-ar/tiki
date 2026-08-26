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
use Tiki\Composer\SmartyExtensionMapper;

#[AsCommand(
    name: 'smarty:generate-mapping',
    description: 'Generate Smarty extension mapping file from PSR-4 classes',
)]
class SmartyGenerateMappingCommand extends Command
{
    protected function configure(): void
    {
        $this->addOption(
            'check',
            null,
            InputOption::VALUE_NONE,
            'Check if mapping is up-to-date without regenerating (for CI)'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tikiRoot = defined('TIKI_PATH') ? TIKI_PATH : realpath(__DIR__ . '/../../../../');
        $mapPath = SmartyExtensionMapper::SMARTY_MAP_FILE;

        if ($input->getOption('check')) {
            $diffs = SmartyExtensionMapper::checkMapping($tikiRoot, $mapPath);
            if (! empty($diffs)) {
                $output->writeln('<error>' . tr("Smarty extension mapping is outdated:") . '</error>');
                foreach ($diffs as $diff) {
                    $output->writeln("  - $diff");
                }
                $output->writeln('<comment>' . tr("Run 'php console smarty:generate-mapping' to update the mapping file.") . '</comment>');
                return Command::FAILURE;
            }
            $output->writeln('<info>' . tr("Smarty extension mapping is up-to-date.") . '</info>');
            return Command::SUCCESS;
        }

        $map = SmartyExtensionMapper::generateMapping($tikiRoot, $mapPath);

        $total = 0;
        foreach ($map as $type => $entries) {
            $count = count($entries);
            $total += $count;
            if ($count > 0) {
                $output->writeln(sprintf('  %s: %d', $type, $count));
            }
        }

        $output->writeln("<info>" . tr("Smarty extension mapping generated: %0 extensions.", $total) . "</info>");

        return Command::SUCCESS;
    }
}
