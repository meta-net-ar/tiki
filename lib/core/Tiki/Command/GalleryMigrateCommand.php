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
use Tiki\Installer\Installer;

/**
 * Allows the migration of images from the Image Gallery (deprecated) to the File Gallery
 */
class GalleryMigrateCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('gallery:migrate')
            ->setDescription(tra('Migrate images from the Image Gallery (deprecated) to the File Gallery'));
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;

        $logslib = TikiLib::lib('logs');

        $fileGalLib = \TikiLib::lib('filegal');
        $installer = Installer::getInstance();

        if (! $installer->tableExists('tiki_images') && ! $installer->tableExists('zzz_unused_tiki_images')) {
            $output->writeln('<info>' . tr('This Tiki installation uses File Gallery only.') . '</info>');
            $output->writeln('<comment>' . tr('Note: The Image Gallery feature was removed in Tiki 24.') . '</comment>');
            return Command::FAILURE;
        }

        if ($fileGalLib->is_default_gallery_writable()) {
            $containerGalleryId = $fileGalLib->migrateFilesFromImageGalleries();

            if ($containerGalleryId > 0) {
                $output->writeln('<info>' . tr('All image galleries and files migrated to file gallery #%0', $containerGalleryId) . '</info>');
                $logslib->add_action(
                    'gallery migrate',
                    'system',
                    'system',
                    'All image galleries and files migrated to file gallery #' . $containerGalleryId
                );
                if ($prefs['file_galleries_redirect_from_image_gallery'] !== 'y') {
                    $output->writeln(
                        '<comment>' . tr(
                            "To continue using image gallery id's in wiki pages you should enable the preference 'file_galleries_redirect_from_image_gallery'. \nYou can use the command: `php console.php preferences:set file_galleries_redirect_from_image_gallery y`"
                        ) . '</comment>'
                    );
                }
            } else {
                $output->writeln('<info>' . tr('No image files found to migrate.') . '</info>');
            }
        } else {
            $output->writeln('<error>' . tr('No files migrated, default file gallery path is not writable.') . '</error>');
        }
        return Command::SUCCESS;
    }
}
