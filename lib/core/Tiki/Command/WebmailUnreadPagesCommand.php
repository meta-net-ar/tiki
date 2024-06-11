<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use TikiLib;
use Hm_IMAP_List;
use Tiki_Hm_User_Config;
use Tiki_Hm_Functions;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Runs periodically to check for unread emails
 * This only checks for messages pages with plugin cypht
 * And alert users who chose to receive email notification on those pages
 *
 * @package Tiki\Command
 */
#[AsCommand(
    name: 'webmail:unread:pages',
    description: 'Notify unread emails from plugin cypht in pages'
)]
class WebmailUnreadPagesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setHelp(
                'Runs periodically to check for unread emails. This only checks for messages pages with plugin cypht and alert users who chose to receive email notification on those pages'
            )
            ->addArgument(
                'pages',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'List the pages you want to monitor (separate multiples with space)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $tikipath, $prefs;

        if ($prefs['monitor_enabled'] != 'y') {
            $output->writeln(tr('Preference monitor_enabled not enabled'));
            return Command::FAILURE;
        }

        require_once $tikipath . '/lib/cypht/integration/Tiki_Hm_Functions.php';

        $cypht = Tiki_Hm_Functions::initCyphtForBackend('servers');

        $tikilib = TikiLib::lib('tiki');
        $parserlib = TikiLib::lib('parser');
        $activitylib = TikiLib::lib('activity');
        $monitorlib = TikiLib::lib('monitor');
        $userlib = TikiLib::lib('user');

        $pages = $input->getArgument('pages');

        foreach ($pages as $pageName) {
            $pageInfo = $tikilib->get_page_info($pageName);
            if (! $pageInfo) {
                $output->writeln(tr('Page %0 does not exist', $pageName));
                continue;
            }

            $output->writeln(tr('Checking page %0', $pageName));
            $plugins = $parserlib->find_plugins($pageInfo['data'], 'cypht');

            // Only the first one is executed
            $body = json_decode($plugins[0]['body'], true);
            if (! empty($body['imap_servers'])) {
                $users = $monitorlib->getMonitoringUsers(
                    ['tiki.webmail.email.received'],
                    ["wiki page:{$pageInfo['page_id']}"]
                );

                // Check if users want to get email notifications from this page
                if (! $users) {
                    continue;
                }

                $last_timestamp = Tiki_Hm_Functions::lastTimestampCheck($body);

                // Store last timestamp in plugin body?

                foreach ($body['imap_servers'] as $idx => $mailbox) {
                    Hm_IMAP_List::add($mailbox, true);

                    $output->writeln(tr('Retrieving messages'));
                    $since = date('j-M-Y', $last_timestamp);
                    list($status, $msg_list) = merge_imap_search_results([$idx], 'ALL', $cypht['session'], $cypht['cache'], ['INBOX'], 1000, [['SINCE', $since]]);

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

                        foreach ($users as $user) {
                            $login = $userlib->get_user_login($user['userId']);
                            $userGroups = $userlib->get_user_login($login);

                            $args = [
                                'type' => 'email',
                                'object' => json_encode([
                                    'uid' => $msg['uid'],
                                    'title' => $msg['subject'],
                                    'list_path' => $path,
                                    'list_parent' => $path,
                                    'page_id' => $pageInfo['page_id'],
                                ]),
                                'user' => $login,
                                'stream' => [$user['priority'] . $user['userId']],
                                'allowed_groups' => $userGroups,
                            ];

                            $activitylib->recordEvent('tiki.webmail.email.received', $args);
                        }
                    }
                }
            }
        }

        return Command::SUCCESS;
    }
}
