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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Package\ComposerManager;
use Tiki\Package\PackageCommandHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;
use TikiLib;

#[AsCommand(
    name: 'package:install',
    description: 'Install package'
)]
class PackageInstallCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setHelp('This command allows you to install packages.')
            ->addArgument(
                'package',
                InputArgument::OPTIONAL,
                tra('Package ID')
            )
            ->addOption(
                'install-all',
                'a',
                InputOption::VALUE_NONE,
                tra('Install all available packages')
            );
    }

    private function installAll(OutputInterface $output): void
    {
        global $tikipath;
        $composerManager = new ComposerManager($tikipath);
        $availableComposerPackages = $composerManager->getAvailable(true, true);
        $packageCount = count($availableComposerPackages);
        $progress = new ProgressBar($output, $packageCount + 1);
        if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
            $progress->setOverwrite(false);
        }
        $progress->setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message%');
        $progress->setFormat('custom');
        $progress->setMessage(tra('Starting package installation'));
        $progress->start();

        // now install each package
        $finishMessage = tra('Successfully installed %count% packages', ['%count%' => $packageCount]);
        foreach ($availableComposerPackages as $package) {
            $progress->setmessage(tra('Installing %package%', ['%package%' => $package['key']]));
            $progress->advance();
            $output->writeln(shell_exec('php console.php package:install ' . $package['key'] . '  2>&1'), OutputInterface::VERBOSITY_DEBUG);
            if (! $composerManager->isInstalled($package['name'])) {
                // we remove failed packages so they won't cause issues installing the others.
                $composerManager->removePackage($package['key']);
                $output->write(' <error>' . tra('failed') . '</error>');
                $finishMessage = '<error>' . tra('Completed with errors') . '</error>';
            } else {
                $output->write(' <comment>' . tra('done') . '</comment>');
            }
        }
        $progress->setMessage($finishMessage);
        $progress->finish();
        $output->writeln('');
    }
    /**
     * Executes the current command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logslib = TikiLib::lib('logs');
        global $tikipath;
        $composerManager = new ComposerManager($tikipath);

        if ($composerManager->composerIsAvailable()) {
            $availableComposerPackages = $composerManager->getAvailable(true, true);
            if (! empty($availableComposerPackages)) {
                $io = new SymfonyStyle($input, $output);
                $io->newLine();

                $packageKey = $input->getArgument('package');
                if ($input->getOption('install-all')) {
                    $this->installAll($output);
                } elseif (isset($packageKey) && ! empty($packageKey)) {
                    if (in_array($packageKey, array_column($availableComposerPackages, 'key'))) {
                        $output->writeln('<info>' . tr('Installing package: ') . $packageKey . '</info>');
                        $result = $composerManager->installPackage($packageKey);
                        $io->newLine();
                        $output->writeln($result);
                    } else {
                        $output->writeln('<error>' . tr('Invalid Package: ') . $packageKey . '</error>');
                        return Command::FAILURE;
                    }
                } else {
                    $availablePackagesInfo = PackageCommandHelper::getAvailablePackagesInfo($availableComposerPackages);

                    $output->writeln(tra('Packages Available to Install'));
                    PackageCommandHelper::renderAvailablePackagesTable($output, $availablePackagesInfo);

                    $helper = $this->getHelper('question');
                    $question = PackageCommandHelper::getQuestion(tra('Which package do you want to install'), null, '?');
                    $question->setValidator(function ($answer) use ($availableComposerPackages) {
                        return PackageCommandHelper::validatePackageSelection($answer, $availableComposerPackages);
                    });

                    $packageKey = $helper->ask($input, $output, $question);

                    $output->writeln('<info>' . tr('Installing package: ') . $packageKey . '</info>');
                    $result = $composerManager->installPackage($packageKey);
                    $io->newLine();
                    $output->writeln($result);
                    $logslib->add_action('package install', 'system', 'system', $packageKey . ' package installed.');
                }
            } else {
                $output->writeln('<comment>' . tr('No packages available to be installed.') . '</comment>');
            }
        } else {
            $output->writeln('<error>' . tr('Composer could not be executed.') . '</error>');
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
