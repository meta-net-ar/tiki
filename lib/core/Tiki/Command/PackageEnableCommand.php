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
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Package\ExtensionManager;
use TikiLib;

#[AsCommand(
    name: 'package:enable',
    description: 'Enable a Tiki Package',
)]
class PackageEnableCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'package',
                InputArgument::REQUIRED,
                'Tiki package name'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logslib = TikiLib::lib('logs');
        $io = new SymfonyStyle($input, $output);

        $packageName = $input->getArgument('package');

        $path = ExtensionManager::locatePackage($packageName);

        if (empty($path)) {
            $io->error('Package was not found. Did you forget to install the package?');
            return Command::FAILURE;
        }

        $extensionPackage = ExtensionManager::get($packageName);
        $update = isset($extensionPackage) && $extensionPackage->hasUpdate();

        $success = ExtensionManager::enableExtension($packageName, $path);
        $messages = ExtensionManager::getMessages();
        $io->writeln(implode(PHP_EOL, $messages));
        $logslib->add_action('package enable', 'system', 'system', $packageName . ' package enabled');

        if ($success && $update) {
            $io->success(tr('Extension %0 was updated', $packageName));
            return Command::SUCCESS;
        }

        if ($success) {
            $io->success(tr('Extension %0 is now enabled', $packageName));
            return Command::SUCCESS;
        }

        $io->error(tr('Extension %0 was not enabled.', $packageName));
        return Command::FAILURE;
    }
}
