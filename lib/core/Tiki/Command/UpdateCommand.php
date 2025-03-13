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
use Tiki\Installer\Installer;
use Tiki\Installer\Patch;
use TikiMail;

#[AsCommand(
    name: 'database:update',
    description: 'Update the database to the latest schema',
)]
class UpdateCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'auto-register',
                'a',
                InputOption::VALUE_NONE,
                'Record any failed patch as applied.'
            )
            ->addOption(
                'check-if-updated',
                null,
                InputOption::VALUE_NONE,
                'Check if a database update is needed without performing the upgrade'
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_OPTIONAL,
                'Email address to send an alert to if the database needs an update (used together with --check-if-updated)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $autoRegister = $input->getOption('auto-register');
        $checkIfUpdated = $input->getOption('check-if-updated');
        $userEmail = $input->getOption('email');
        $installer = Installer::getInstance();
        $installed = $installer->tableExists('users_users');

        if ($installed) {
            // tiki-setup.php may not have been run yet, so load the minimum required libs to be able process the schema updates
            require_once('lib/tikilib.php');

            if ($checkIfUpdated) {
                $notAppliedPatches = Patch::getPatches([Patch::NOT_APPLIED]);
                if (count($notAppliedPatches) > 0) {
                    $subject = 'Database update required';
                    $mail_data = "Database update is required. Please run command to update the database [php console.php database:update]";
                    if ($userEmail) {
                        $mail = new TikiMail();
                        $mail->setUser($userEmail);
                        $mail->setSubject($subject);
                        $mail->setHtml($mail_data);
                        $isEmailSent = $mail->send([$userEmail]);
                        if (! $isEmailSent) {
                            $msg = 'Unable to send mail';
                            $mailerrors = print_r($mail->errors, true);
                            $msg .= $mailerrors;
                            $output->writeln('<error>' . $msg . '</error>');
                        }
                    } else {
                        $output->writeln('<info>' . $mail_data . '</info>');
                    }

                    return Command::FAILURE;
                } else {
                    $output->writeln('<info>Database is up to date.</info>');
                    return Command::SUCCESS;
                }
            }

            $result = $installer->update();
            if ($result) {
                $output->writeln('Update completed.');
            } else {
                $output->writeln('<error>Update interrupted as a patch failed to complete. Please fix the errors below and try again.</error>');
            }
            foreach (array_keys(Patch::getPatches([Patch::NEWLY_APPLIED])) as $patch) {
                $output->writeln("<info>Installed: $patch</info>");
            }
            foreach (array_keys(Patch::getPatches([Patch::NOT_APPLIED])) as $patch) {
                $output->writeln("<error>Failed: $patch</error>");

                if ($autoRegister) {
                    Patch::$list[$patch]->record();
                }
            }

            if (count($installer->executed)) {
                foreach ($installer->executed as $script) {
                    $output->writeln("<info>Executed: $script</info>");
                }
            }

            $output->writeln('<info>Queries executed successfully: ' . count($installer->queries['successful']) . '</info>');

            if (count($installer->queries['failed']) > 0) {
                $output->writeln('<warning>Queries executed unsuccessfully: ' . count($installer->queries['failed']) . '</warning>');
                foreach ($installer->queries['failed'] as $error) {
                    list( $query, $message, $patch ) = $error;
                    if (! $patch) {
                        // Installer::query() does not set a meaningful third element when the error is caused by a PHP script. Needs some architectural work to solve properly
                        $patch = 'unknown patch script';
                    }
                    $output->writeln("<error>Error in $patch\n\t$query\n\t$message</error>");
                }
            }

            // tiki-setup.php may not have been run yet, so load the minimum required libs to be able to clear the caches
            require_once('lib/cache/cachelib.php');
            $cachelib = new \Cachelib();
            $cachelib->empty_cache();
        } else {
            $output->writeln('<error>Database not found.</error>');
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
