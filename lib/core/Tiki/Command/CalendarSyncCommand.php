<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Exception;
use TikiLib;
use Tiki\SabreDav\CaldavClient;

class CalendarSyncCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('calendar:sync')
            ->setDescription(tra('Synchronize calendar subscriptions that are due depending on last sync date and refresh rate'))
            ->addOption(
                'subscription',
                's',
                InputOption::VALUE_OPTIONAL,
                'Limit synchronization for one specific calenader subscription'
            )
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Force synchronization even if due date has not yet come'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $subscriptionId = (int) $input->getOption('subscription');
        $force = $input->getOption('force');

        $client = new CaldavClient();
        $count = 0;

        $subscriptions = TikiLib::lib('calendar')->get_subscriptions('%');
        foreach ($subscriptions['data'] as $sub) {
            if ($subscriptionId && $sub['subscriptionId'] != $subscriptionId) {
                continue;
            }
            if (! $force && ! empty($sub['last_sync'])) {
                // check due time
                $rate = $sub['refresh_rate'];
                if (empty($rate)) {
                    $rate = 'P1D';
                }
                if (preg_match('/P(\d+)([WDHM])/', $rate, $m)) {
                    switch ($m[2]) {
                        case 'W':
                            $unit = 'weeks';
                            break;
                        case 'D':
                            $unit = 'days';
                            break;
                        case 'H':
                            $unit = 'hours';
                            break;
                        case 'M':
                            $unit = 'minutes';
                            break;
                    }
                    if (strtotime("+$m[1] $unit", $sub['last_sync']) > time()) {
                        continue;
                    }
                } else {
                    $output->writeln('<error>' . tr('Calendar subscription %0 contains unrecognized refresh rate period: %1', $sub['subscriptionId'], $rate) . '</error>');
                    continue;
                }
            }
            $output->writeln('<comment>' . tr('Synchronizing subscription %0...', $sub['subscriptionId']) . '</comment>');
            try {
                $client->syncSubscription($sub);
                $count++;
            } catch (Exception $e) {
                $output->writeln('<error>' . tr('Error synchronizing calendar subscription: ' . $e->getMessage()) . '</error>');
            }
        }

        $output->writeln('<comment>' . tr('Subscriptions synchronized: %0', $count) . '</comment>');
        return Command::SUCCESS;
    }
}
