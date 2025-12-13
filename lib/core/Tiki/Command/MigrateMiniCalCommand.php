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
use Symfony\Component\Console\Helper\ProgressBar;
use MiniCalLib;

#[AsCommand(
    name: 'calendar:migrate:minical',
    description: 'Migrate events from Tiki MiniCal to Calendar',
)]
class MigrateMiniCalCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('calendar:migrate:minical')
            ->setDescription('Migrate events from Tiki MiniCal to Calendar')
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
                'Username whose events will be migrated'
            )
            ->addOption(
                'calendarId',
                'c',
                InputOption::VALUE_REQUIRED,
                'Destination calendar ID'
            )
            ->addOption(
                'keep-copy',
                'k',
                InputOption::VALUE_NONE,
                'Keep copy of events in MiniCal after migration'
            )
            ->setHelp(
                'This command migrates events from Tiki MiniCal to the new Calendar system.
                        Examples:
                        php console.php calendar:migrate:minical --user=admin --calendarId=1
                        php console.php calendar:migrate:minical -u admin -c 1 --keep-copy
                        php console.php calendar:migrate:minical --user=john --calendarId=2 -k'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $input->getOption('user');
        $calendarId = $input->getOption('calendarId');
        $keepCopy = $input->getOption('keep-copy');

        if (! $user) {
            $output->writeln('<error>The --user option is required.</error>');
            return Command::FAILURE;
        }

        if (! $calendarId) {
            $output->writeln('<error>The --calendarId option is required.</error>');
            return Command::FAILURE;
        }

        if (! is_numeric($calendarId)) {
            $output->writeln('<error>Calendar ID must be a number.</error>');
            return Command::FAILURE;
        }

        $userlib = \TikiLib::lib('user');
        $userInfo = $userlib->get_user_info($user);
        if (! $userInfo) {
            $output->writeln("<error>User '$user' not found.</error>");
            return Command::FAILURE;
        }

        $userEmail = $userlib->get_user_email($user) ?? '';

        $output->writeln("<info>Starting migration from MiniCal to Calendar...</info>");
        $output->writeln("User: <comment>$user</comment>");
        $output->writeln("Target Calendar ID: <comment>$calendarId</comment>");
        $output->writeln("Keep copy: <comment>" . ($keepCopy ? 'Yes' : 'No') . "</comment>");
        $output->writeln('');

        // Set up minimal server context required by CalDAV
        $this->setupServerContext();

        try {
            $calendarLib = \TikiLib::lib('calendar');
            $calendar = $calendarLib->get_calendar($calendarId);

            if (! $calendar) {
                $output->writeln("<error>Calendar with ID $calendarId not found.</error>");
                return Command::FAILURE;
            }

            $minicallib = new MinicalLib();
            $events = $minicallib->minical_list_events($user, 0, -1, 'start_desc', '');

            if (empty($events['data'])) {
                $output->writeln('<comment>No events found in MiniCal for user: ' . $user . '</comment>');
                return Command::SUCCESS;
            }

            $eventCount = count($events['data']);
            $output->writeln("<info>Found $eventCount events to migrate.</info>");

            $progressBar = new ProgressBar($output, $eventCount);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%%');
            $progressBar->start();

            $migratedCount = 0;
            $client = new \Tiki\SabreDav\CaldavClient();
            foreach ($events['data'] as $event) {
                try {
                    $description = $event['description'] ?? '';

                    $calitem = $this->buildCalendarItem($event, $description, $calendarId, $calendar, $user, $userEmail);
                    $client->saveCalendarObject($calitem);

                    $migratedCount++;

                    if (! $keepCopy) {
                        $minicallib->minical_remove_event($user, $event['eventId']);
                    }
                } catch (\Exception $eventError) {
                    $output->writeln('');
                    $output->writeln("<error>Failed to migrate event '{$event['title']}': " . $eventError->getMessage() . "</error>");
                    continue;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $output->writeln('');
            $output->writeln('');

            if ($migratedCount > 0) {
                $output->writeln("<info>✓ Successfully migrated $migratedCount out of $eventCount events!</info>");

                if (! $keepCopy) {
                    $output->writeln("<comment>Original events have been removed from MiniCal.</comment>");
                } else {
                    $output->writeln("<comment>Original events remain in MiniCal.</comment>");
                }
            } else {
                $output->writeln("<error>No events were migrated. Check for errors.</error>");
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $output->writeln('');
            $output->writeln("<error>Migration failed: " . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Set up minimal server context required by CalDAV operations
     */
    private function setupServerContext(): void
    {
        if (! isset($_SERVER['REQUEST_METHOD'])) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_HOST'] = 'localhost';
            $_SERVER['REQUEST_URI'] = '/console';
            $_SERVER['SCRIPT_NAME'] = '/console.php';
            $_SERVER['SERVER_NAME'] = 'localhost';
            $_SERVER['SERVER_PORT'] = '80';
            $_SERVER['HTTPS'] = 'off';
        }
    }

    /**
     * Build calendar item array for CalDAV client
     */
    private function buildCalendarItem(array $event, string $description, int $calendarId, array $calendar, string $user, string $userEmail): array
    {
        return [
            'calitemId'     => 0,
            'uid'           => 'minical-migration-' . $event['eventId'] . '-' . time() . '-' . uniqid(),
            'calendarId'    => $calendarId,
            'user'          => $user,
            'name'          => $event['title'] ?? tra("Event without name"),
            'description'   => $description,
            'start'         => $event['start'],
            'end'           => $event['end'],
            'recurrenceId'  => 0,
            'allday'        => 0, // Assume not all-day for migrated events
            'priority'      => 1,
            'status'        => $calendar['defaulteventstatus'] ?? 1,
            'locationId'    => 0,
            'categoryId'    => 0,
            'nlId'          => 0,
            'url'           => '',
            'lang'          => 'en',
            'organizers'    => [$user],
            'participants'  => [
                [
                    'username' => $user,
                    'role'     => '1',
                    'partstat' => 'ACCEPTED',
                    'email'    => $userEmail,
                ]
            ],
        ];
    }
}
