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
use Tiki\FileGallery\Definition;

class FileGallerySyncCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('files:sync')
            ->setDescription(tra('Synchronize direct mapping mode file galleries from source'))
            ->addOption(
                'gallery',
                'g',
                InputOption::VALUE_OPTIONAL,
                tra('Gallery to synchronize')
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        if ($prefs['feature_file_galleries'] != 'y') {
            throw new Exception(tra('Feature Galleries not set up'));
        }

        $filegallib = TikiLib::lib('filegal');

        $galleryId = (int) $input->getOption('gallery');
        if ($galleryId) {
            $galleries = [$filegallib->get_file_gallery($galleryId)];
        } else {
            $galleries = $filegallib->get_direct_mapping_file_galleries();
        }

        foreach ($galleries as $gal_info) {
            $output->writeln('<comment>' . tr('Processing %0...', $gal_info['name']) . '</comment>');
            $def = new Definition($gal_info);
            $def->sync();
        }
        return Command::SUCCESS;
    }
}
