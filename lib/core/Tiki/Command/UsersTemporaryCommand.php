<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TikiLib;

class UsersTemporaryCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('users:temporary')
            ->setDescription('Create temporary user(s) with specific privileges')
            ->addOption(
                'emails',
                null,
                InputOption::VALUE_REQUIRED,
                'Emails of the user(s) to be invited temporarily, separated by comma.',
            )
            ->addOption(
                'groups',
                null,
                InputOption::VALUE_REQUIRED,
                'Group(s) to be assigned to the users, separated by comma.',
            )
            ->addOption(
                'expiry',
                null,
                InputOption::VALUE_REQUIRED,
                'How long the temporary user will be valid, in seconds. Use \'-1\' to prevent it to expire.',
            )
            ->addOption(
                'prefix',
                null,
                InputOption::VALUE_OPTIONAL,
                'Prefix of the username that will be created for this temporary user.',
                'guest'
            )
            ->addOption(
                'path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Users will have to autologin using this path on the site using the token.',
                'index.php'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        global $prefs;

        $io = new SymfonyStyle($input, $output);

        if ($prefs['auth_token_access'] !== 'y') {
            $io->error(tr('The token access feature (preference "auth_token_access") is needed to create temporary users.'));
            return Command::FAILURE;
        }
        if (empty($prefs['fallbackBaseUrl'])) {
            $io->error(tr('Unable to get base url. To fix this issue set the fallbackBaseUrl preference with a valid URL.'));
            return Command::FAILURE;
        }
        if (empty($input->getOption('emails'))) {
            $io->error(tr('Insert valid e-mail(s) to receive the invitation (comma-separated).'));
            return Command::FAILURE;
        }
        if (empty($input->getOption('groups'))) {
            $io->error(tr('Insert valid user groups to be added to each temporary user (comma-separated).'));
            return Command::FAILURE;
        }
        if (! is_numeric($input->getOption('expiry'))) {
            $io->error(tr('Insert a valid expiration value, it must be numeric.'));
            return Command::FAILURE;
        }

        $emails = explode(',', $input->getOption('emails'));
        foreach ($emails as $email) {
            if (! validate_email($email)) {
                $io->error(tr('Invalid e-mail') . ' ' . $email);
                return Command::FAILURE;
            }
        }

        $groups = explode(',', $input->getOption('groups'));
        foreach ($groups as $group) {
            if (! TikiLib::lib('user')->group_exists($group)) {
                $io->error($group . ' ' . tr('group does not exist'));
                return Command::FAILURE;
            }
        }

        $tokens = TikiLib::lib('user')->invite_tempuser($emails, $groups, $input->getOption('expiry'), $input->getOption('prefix'), $input->getOption('path'));

        $io->success(tr('Temporary user(s) have been created. Invitations have been sent via e-mail or you can use the following links'));
        $invitations = [];
        foreach ($tokens as $email => $url) {
            $invitations[] = [$email, $url];
        }

        $table = new Table($output);
        $table->setHeaders([tr('Email'), tr('URL')]);
        $table->setRows($invitations);
        $table->render();

        return Command::SUCCESS;
    }
}
