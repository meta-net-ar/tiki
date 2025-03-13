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

error_reporting(E_ALL);
use TikiLib;

#[AsCommand(
    name: 'forum:inbound-mail',
    description: 'Process inbound mail for forums'
)]
class ForumProcessInboundMail extends Command
{
    protected function configure()
    {
        $this
            ->addOption(
                'id',
                null,
                InputOption::VALUE_OPTIONAL,
                'Forum id'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $commentslib = TikiLib::lib('comments');

        $forumId = $input->getOption('id');
        $forums = [];
        if ($forumId) {
            $forum = $commentslib->get_forum($forumId);
            if (! $forum) {
                $output->writeln("<error>Forum not found</error>");
                return Command::FAILURE;
            }
            $forums[] = $forum;
        } else {
            $forumsList = $commentslib->list_forums();
            $forums = $forumsList['data'];
            if (! $forums) {
                $output->writeln("<error>No forums found</error>");
                return Command::FAILURE;
            }
        }

        foreach ($forums as $forum) {
            $output->writeln("\n<info>Processing forum: {$forum['name']}</info>");

            if ($commentslib->process_inbound_mail($forum['forumId'], 30)) {
                $output->writeln("<info>Done</info>");
            } else {
                $output->writeln("<error>Error processing forum</error>");
            }
        }
        return Command::SUCCESS;
    }
}
