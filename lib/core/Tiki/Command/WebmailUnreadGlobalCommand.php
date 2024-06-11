<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;
use Hm_IMAP_List;
use Tiki_Hm_Functions;
use Tiki_Hm_User_Config;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Runs periodically to check for unread emails
 * This command checks for emails in tiki-webmail.php page
 * Which is particular for each user and alerts him
 *
 * @package Tiki\Command
 */
#[AsCommand(
    name: 'webmail:unread:global',
    description: 'Notify unread emails in tiki-webmail.php page'
)]
class WebmailUnreadGlobalCommand extends Command
{
    protected function configure()
    {
        $this
            ->setHelp(
                'Run periodically to execute check and alert from new emails in inbox to the tiki notifications system. This is specific to each user and alerts the corresponding user.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs, $tikipath, $user;
        // The user running this command is admin
        // This prevents him from deleting his IMAP servers
        $user = null;

        if ($prefs['monitor_enabled'] != 'y') {
            $output->writeln(tr('Preference monitor_enabled not enabled'));
            return Command::FAILURE;
        }

        require_once $tikipath . '/lib/cypht/integration/Tiki_Hm_Functions.php';

        $init = Tiki_Hm_Functions::initCyphtForBackend('servers');
        extract($init, EXTR_PREFIX_ALL, 'cypht');

        $userlib = TikiLib::lib('user');
        $monitorlib = TikiLib::lib('monitor');
        $activitylib = TikiLib::lib('activity');
        // $monitormail = TikiLib::lib('monitormail');

        $userPreferences = TikiLib::lib('tiki')->table('tiki_user_preferences');
        $configs = $userPreferences->fetchAll([], [
            'prefName' => $userPreferences->like('cypht_user_config%'),
        ]);

        foreach ($configs as $user_config) {
            $config = json_decode($user_config['value'], true);

            // Check if conditions are filled to continue the process
            $userId = $userlib->get_user_id($user_config['user']);
            $userMonitor = $monitorlib->monitoredEvent($userId, 'tiki.webmail.email.received');
            if (! $userMonitor) {
                continue;
            }
            $userGroups = $userlib->get_user_groups($user_config['user']);

            $last_timestamp = Tiki_Hm_Functions::lastTimestampCheck($config);

            $userPreferences->update(['value' => json_encode($config)], ['user' => $user_config['user'], 'prefName' => $user_config['prefName']]);

            foreach ($config['imap_servers'] as $idx => $mailbox) {
                $output->writeln(tr('Checking account %0', $mailbox['name']));
                Hm_IMAP_List::add($mailbox, true);

                $output->writeln(tr('Retrieving messages'));
                $since = date('j-M-Y', $last_timestamp);
                list($status, $msg_list) = merge_imap_search_results([$idx], 'ALL', $cypht_session, $cypht_cache, ['INBOX'], 1000, [['SINCE', $since]]);

                Hm_IMAP_List::del($idx);

                if (empty($msg_list)) {
                    continue;
                }

                $output->writeln(tr('New messages found'));
                foreach ($msg_list as $msg) {
                    if (strtotime($msg['date']) < $last_timestamp) {
                        continue;
                    }
                    $path = sprintf('imap_%s_%s', $msg['server_id'], $msg['folder']);

                    $args = [
                        'type' => 'email',
                        'object' => json_encode([
                            'uid' => $msg['uid'],
                            'title' => $msg['subject'],
                            'list_path' => $path,
                            'list_parent' => $path,
                        ]),
                        'user' => $user_config['user'],
                        'stream' => [$userMonitor['priority'] . $userId],
                        'allowed_groups' => $userGroups,
                    ];

                    // if ($userMonitor['priority'] == 'critical') {
                    //     $monitormail->queue($item['event'], $args, [$userId]);
                    // }
                    $activitylib->recordEvent('tiki.webmail.email.received', $args);
                }
            }
        }
        // $monitormail->sendQueue();

        return Command::SUCCESS;
    }
}
