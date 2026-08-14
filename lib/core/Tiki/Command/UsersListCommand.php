<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'users:list',
    description: 'Display the list of users in the system'
)]
class UsersListCommand extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'find',
                null,
                InputOption::VALUE_REQUIRED,
                'Find user in list'
            )
            ->addOption(
                'email',
                null,
                InputOption::VALUE_REQUIRED,
                'Filter list by email'
            )
            ->addOption(
                'not-confirmed',
                null,
                InputOption::VALUE_NONE,
                'List users with email not confirmed'
            )
            ->addOption(
                'not-validated',
                null,
                InputOption::VALUE_NONE,
                'List users not validated'
            )
            ->addOption(
                'never-logged-in',
                null,
                InputOption::VALUE_NONE,
                'List users that never logged in'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $find = $input->getOption('find');
        $email = $input->getOption('email');
        $notConfirmed = $input->getOption('not-confirmed');
        $notValidated = $input->getOption('not-validated');
        $neverLoggedIn = $input->getOption('never-logged-in');

        $userlib = \TikiLib::lib('user');
        $users = $userlib->get_users(
            null,
            -1, // if fails if null is passed
            null,
            $find,
            null,
            null,
            null,
            $email,
            $notConfirmed,
            $notValidated,
            $neverLoggedIn
        );

        $table = new Table($output);
        $table->setHeaders(['User', 'Email', 'Last Login', 'Registered', 'Groups']);
        $rows = [];
        foreach ($users['data'] as $user) {
            $rows[] = [
                $user['login'],
                $user['email'] ?? '',
                ! empty($user['lastLogin']) ? \TikiLib::date_format('%Y-%m-%d %H:%m', $user['lastLogin']) : 'Never',
                \TikiLib::date_format('%Y-%m-%d %H:%m', $user['registrationDate']),
                implode(', ', $user['groups'])
            ];
        }

        $table->setRows($rows);
        $table->render();
        return Command::SUCCESS;
    }
}
