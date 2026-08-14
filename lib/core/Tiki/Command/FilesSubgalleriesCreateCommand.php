<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;
use Tracker_Definition;

class FilesSubgalleriesCreateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('files:subgalleries-create')
            ->setDescription(tra('Create the subgalleries and migrate existing files stored in Files field to them'))
            ->addArgument(
                'trackerId',
                InputArgument::REQUIRED,
                tra('Tracker to migrate files from')
            )
            ->addArgument(
                'parentGalleryId',
                InputArgument::OPTIONAL,
                tra('Parent gallery ID in which to create sub-galleries')
            )
            ->addArgument(
                'fieldId',
                InputArgument::OPTIONAL,
                tra('Files field ID for which files to migrate')
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        $trackerLib = TikiLib::lib('trk');
        $filegallib = TikiLib::lib('filegal');
        $trklib = TikiLib::lib('trk');
        if ($prefs['feature_file_galleries'] != 'y') {
            throw new \Exception(tra('Feature Galleries not set up'));
        }

        $trackerId = (int) $input->getArgument('trackerId');
        $definition = Tracker_Definition::get($trackerId);
        if (! $definition) {
            throw new \Exception(tra('File subgalleries create: Tracker not found'));
        }

        $parentGalleryId = (int) $input->getArgument('parentGalleryId');

        if ($parentGalleryId) {
            $gal_info = $filegallib->get_file_gallery($parentGalleryId);
            if (! $gal_info || empty($gal_info['name'])) {
                throw new \Exception(tr('File subgalleries create: Parent Gallery #%0 not found', $parentGalleryId));
            }
        }

        $fieldId = (int) $input->getArgument('fieldId');

        if ($fieldId) {
            $fieldDefinition = $definition->getField($fieldId);
            if ($fieldDefinition['type'] === 'FG') {
                $trackerFileFields[] = $definition->getField($fieldId);
            } else {
                $output->writeln('<comment>' . tra('The indicated field is not a Files type') . '</comment>');
                return Command::INVALID;
            }
        } else {
            // Get all Files field in the indicated Tracker
            $trackerFileFields = $definition->getFileFields();
        }

        if (empty($trackerFileFields)) {
            $output->writeln('<comment>' . tra('Files field not found') . '</comment>');
            return Command::INVALID;
        }

        if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $output->writeln('<comment>' . tra('File Move starting...') . '</comment>');
        }

        // Get all items from indicated Tracker
        $trackerItems = $trackerLib->get_all_tracker_items($trackerId);

        $countFiles = 0;
        foreach ($trackerItems as $trackerItem) {
            foreach ($trackerFileFields as $fieldInfo) {
                // Get all files attached to specific Files field of an item
                $files = $trackerLib->get_item_value($trackerId, $trackerItem, $fieldInfo['fieldId']);
                if (! empty($files)) {
                    $countFiles = 1;
                }
                $options = json_decode($fieldInfo['options']);
                $options->galleryId = $parentGalleryId;
                $fieldInfo['options'] = json_encode($options);
                $fieldInfo['value'] = $files;
                $filesField['data'][$fieldInfo['fieldId']] = $fieldInfo;
            }

            $trklib->replace_item($trackerId, $trackerItem, $filesField);
        }

        $output->writeln('<comment>' . tr("Create sub-galleries and migrate files successfully") . '</comment>');
        //If there are no files attached to the indicated tracker
        if ($countFiles == 0) {
            $output->writeln('<comment>' . tra('No files to migrate') . '</comment>');
            return Command::INVALID;
        }
        return true;
    }
}
