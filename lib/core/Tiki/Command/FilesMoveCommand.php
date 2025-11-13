<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\HelpCommand;
use TikiLib;

class FilesMoveCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('files:move')
            ->setDescription(tra('Move files from file galleries to a regular directory on the file system'))
            ->addArgument(
                'galleryId',
                InputArgument::REQUIRED,
                tra('Gallery to move files from')
            )
            ->addArgument(
                'destinationPath',
                InputArgument::REQUIRED,
                tra('Path to move files to')
            )
            ->addOption(
                'confirm',
                null,
                InputOption::VALUE_NONE,
                tra('Confirm the move operation (required)')
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        $logslib = TikiLib::lib('logs');

        if ($prefs['feature_file_galleries'] != 'y') {
            throw new \Exception(tra('Feature Galleries not set up'));
        }

        $filegallib = \TikiLib::lib('filegal');
        $filegalcopylib = \TikiLib::lib('filegalcopy');

        $galleryId = (int) $input->getArgument('galleryId');

        $gal_info = $filegallib->get_file_gallery($galleryId);
        if (! $gal_info || empty($gal_info['name'])) {
            throw new \Exception(tr('File Move: Gallery #%0 not found', $galleryId));
        }

        $destinationPath = $input->getArgument('destinationPath');
        if (empty($destinationPath)) {
            throw new \Exception(tra('File Move: Destination path required'));
        }

        $destinationPath = rtrim($destinationPath, '/') . '/';

        if (! is_dir($destinationPath)) {
            $output->writeln("<error>File Move: Destination path $destinationPath is not a directory</error>");
            return Command::FAILURE;
        }

        if (! is_writable($destinationPath)) {
            $output->writeln("<error>File Move: Destination path $destinationPath is not writable</error>");
            return Command::FAILURE;
        }

        $files = $filegallib->get_files_info_from_gallery_id($galleryId);
        if (! $files) {
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln('<comment>' . tra('No files to move') . '</comment>');
            }
            return Command::SUCCESS;
        }

        $confirm = $input->getOption('confirm');

        if (! $confirm) {
            $help = new HelpCommand();
            $help->setCommand($this);
            $help->run($input, $output);
            throw new \Exception(tra('Use the --confirm option to proceed with the move operation'));
        }

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln('<comment>' . tra('File Move starting...') . '</comment>');
        }

        $feedback = $filegalcopylib->processCopy($files, $destinationPath, true);

        foreach ($feedback as $message) {
            $error = strpos($message, '<span class="text-danger">') !== false;
            $message = strip_tags(str_replace('<br>', ' : ', $message));
            if ($error) {
                $message = "<error>$message</error>";
                $output->writeln($message);
            } elseif ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $message = "<info>$message</info>";
                $output->writeln($message);
            }
        }

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln('<comment>' . tra('File Move complete') . '</comment>');
        }

        $logslib->add_action(
            tra('files move'),
            'system',
            'system',
            tra('Moving files from gallery #%0 to folder %1 completed. %2 files moved.', $galleryId, $destinationPath, count($files))
        );
        return Command::SUCCESS;
    }
}
