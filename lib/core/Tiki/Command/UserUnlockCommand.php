<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\Lib\Logs\LogsLib;
use TikiLib;

#[AsCommand(
    name: 'users:unlock',
    description: 'Unlock a user'
)]
class UserUnlockCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'identifiers',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Logins or emails'
            )
            ->addOption(
                'format',
                null,
                InputOption::VALUE_REQUIRED,
                tra('Output format'),
                'table'
            );
    }

    private function unlockUsers($identifiers)
    {
        /** @var LogsLib $logslib */
        $logslib = TikiLib::lib('logs');
        $userlib = TikiLib::lib('user');

        $return = [];
        foreach ($identifiers as $identifier) {
            $login = filter_var($identifier, FILTER_VALIDATE_EMAIL)
                ? $userlib->get_user_by_email($identifier)
                : $identifier;
            $user = $userlib->get_user_info($login, false, 'login');

            $row = [ 'user' => $identifier ];
            if (empty($user)) {
                $row['result'] = 'error';
                $row['message'] = 'user not found';
            } elseif (empty($user['valid']) && empty($user['waiting'])) {
                $row['result'] = 'success';
                $row['message'] = tra('user already unlocked');
            } else {
                $userlib->confirm_user($user['login']);
                $userlib->set_unsuccessful_logins($user['login'], 0);
                $row['result'] = 'success';
                $row['message'] = tra('user unlocked and unsuccessful login was reset');

                $logslib->add_action('adminusers', 'system', 'system', 'User ' . $user['login'] . ' unlocked');
            }
            $return[] = $row;
        }
        return $return;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $identifiers = $input->getArgument('identifiers');
        $format = $input->getOption('format') ?? 'table';

        $result = $this->unlockUsers($identifiers);

        if ($format === 'json') {
            $output->write(json_encode($result, JSON_PRETTY_PRINT));
        } else {
            $header = array_keys($result[0]);
            $result = array_map('array_values', $result);

            $table = new Table($output);
            $table->setHeaders($header);
            $table->setRows($result);
            $table->render();
        }
        return Command::SUCCESS;
    }
}
