<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TikiLib;

class ReportCacheClearCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('reportcache:clear')
            ->setDescription(tra('Clean user reports cache'))
            ->addArgument(
                'days',
                InputArgument::REQUIRED,
                tra('Number of days to clean older report cache')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int)$input->getArgument('days') ?: 0;

        if ($days === 0) {
            $io->error(tr('Argument "days" is not valid. Insert a valid numeric number'));
            return 1;
        }

        $result = TikiLib::lib('tiki')->query(
            'DELETE FROM tiki_user_reports_cache
                WHERE UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)) > UNIX_TIMESTAMP(time);'
        );

        $io->success(tr('%0 reports cache deleted.', $result->numRows()));
        return 0;
    }
}
