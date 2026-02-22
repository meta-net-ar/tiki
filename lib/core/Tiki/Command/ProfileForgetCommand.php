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
use Tiki\Lib\Logs\LogsLib;
use TikiLib;

#[AsCommand(
    name: 'profile:forget',
    description: 'Forget a profile installation'
)]
class ProfileForgetCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'profile',
                InputArgument::REQUIRED,
                tra('Profile name')
            )
            ->addArgument(
                'repository',
                InputArgument::OPTIONAL,
                tra('Repository'),
                'profiles.tiki.org'
            )->addOption(
                'revert',
                null,
                InputOption::VALUE_NONE,
                tra('Rollback profile changes')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $profileName = $input->getArgument('profile');
        $repository = $input->getArgument('repository');

        $profile = \Tiki_Profile::fromNames($repository, $profileName);

        if (! $profile) {
            $output->writeln('<error>' . tra('Profile not found.') . '</error>');
            return Command::FAILURE;
        }

        $tikilib = TikiLib::lib('tiki');
        /** @var LogsLib $logslib */
        $logslib = TikiLib::lib('logs');

        $installer = new \Tiki_Profile_Installer();
        $isInstalled = $installer->isInstalled($profile);

        if ($isInstalled) {
            $transaction = $tikilib->begin();

            if ($input->getOption('revert')) {
                $query = "SELECT * FROM tiki_actionlog where action = 'profile apply' and object=? ORDER BY actionId DESC LIMIT 1";
                $result = $logslib->query($query, [$profileName]);
                if ($logResult = $result->fetchRow()) {
                    $revertInfo = unserialize($logResult['log']);
                    if (! isset($revertInfo['reverted'])) {
                        $logslib->revert_action($logResult['actionId'], $logResult['object'], 'profiles', $revertInfo);
                        $installer->revert($profile, $revertInfo);
                    }
                } else {
                    $output->writeln(tra('No changes were found in logs to revert.'));
                }
            }

            $installer->forget($profile);
            $transaction->commit();
            $output->writeln(tra('Profile forgotten.'));
            $logslib->add_action('profile forget', 'system', 'system', $profileName . ' profile forgotten.');
        } else {
            $output->writeln('<info>' . tra('Profile was not installed or did not create any objects.') . '</info>');
        }
        return Command::SUCCESS;
    }
}
