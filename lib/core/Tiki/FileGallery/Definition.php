<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\FileGallery;

use League\Flysystem\FilesystemException;
use Feedback;
use TikiLib;

class Definition
{
    public $handler;
    private $info;

    public function __construct($info)
    {
        $this->info = $info;
        if (isset($this->info['id']) && ! isset($this->info['galleryId'])) {
            $this->info['galleryId'] = $this->info['id'];
        }
        $this->handler = $this->getHandler($info);
    }

    /**
     * Get file wrapper object responsible for accessing the underlying storage.
     * @see FileWrapper\WrapperInterface for supported methods.
     */
    public function getFileWrapper($file)
    {
        return $this->handler->getFileWrapper($file);
    }

    /**
     * @see Handler\HandlerInterface
     */
    public function delete($file)
    {
        $this->handler->delete($file);
    }

    /**
     * @see Handler\HandlerInterface
     */
    public function uniquePath($file)
    {
        return $this->handler->uniquePath($file);
    }

    /**
     * @see Handler\HandlerInterface
     */
    public function isWritable()
    {
        return $this->handler->isWritable();
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getId()
    {
        return $this->info['galleryId'];
    }

    public function isDirect()
    {
        return $this->info['type'] == 'direct';
    }

    public function getFilesystem()
    {
        return $this->handler->getFilesystem();
    }

    /**
     * Updates file contents based on chosen underlying storage.
     * Currently, we have: db storage or filesystem storage.
     * This method updates the file contents to be in the right place.
     */
    public function fixFileLocation($file)
    {
        global $prefs;

        if ($this->isDirect()) {
            return;
        }

        if ($file->path) {
            $handler = new Handler\FileSystem($prefs['fgal_use_dir']);
        } else {
            $handler = new Handler\Preloaded();
        }

        $data = $handler->getFileWrapper($file)->getContents();
        $orig = $file->clone();

        if ($file->replaceContents($data)) {
            if ($handler->getFileWrapper($file) != $file->getWrapper()) {
                $handler->delete($orig);
            }
        }
    }

    /**
     * Fully synchronize remote directory with local file gallery/sub-gallery and containing files.
     * Only for direct mapping mode.
     */
    public function sync()
    {
        if ($this->info['type'] != 'direct') {
            return;
        }

        $filesystem = $this->handler->getFilesystem();
        $root = DirectMapping\Utilities::getRoot($this->info);
        $dms = new DirectMapping\Synchronizer(new Definition($root), $filesystem);
        $prefix = DirectMapping\Utilities::getVirtualPath($this->info);

        try {
            $total = 0;
            $listing = $filesystem->listContents($prefix, true);
            foreach ($listing as $item) {
                try {
                    $dms->handle($item);
                } catch (\League\Flysystem\CorruptedPathDetected $e) {
                    Feedback::error(tr("Error syncing direct mapped file: %0", $e->getMessage()));
                }
                $total++;
            }
            try {
                $lastModif = $filesystem->lastModified('');
                TikiLib::lib('filegal')->table('tiki_file_galleries')->update(['lastModif' => $lastModif], ['galleryId' => $this->info['galleryId']]);
            } catch (\League\Flysystem\UnableToRetrieveMetadata $e) {
            }
            TikiLib::lib('logs')->add_action(
                'Sync',
                $this->info['galleryId'],
                'file gallery',
                'Synchronized direct mapping file gallery. Total items: ' . $total
            );
        } catch (FilesystemException $exception) {
            // handle the error
            Feedback::error(tr("Error handling direct mapping gallery sync: %0", $exception->getMessage()));
        }
    }

    /**
     * Ensure subdirectories in a file path exist as file galleries locally.
     * Only for direct mapping mode.
     * @param $path string the file path to synchronize
     * @return int $galleryId the (possibly new) gallery that files must reside in
     */
    public function syncDirectoryStructure($path)
    {
        if ($this->info['type'] != 'direct') {
            return;
        }

        $filesystem = $this->handler->getFilesystem();
        $root = DirectMapping\Utilities::getRoot($this->info);
        $dms = new DirectMapping\Synchronizer(new Definition($root), $filesystem);
        return $dms->syncDirectoryStructure($path);
    }

    private function getHandler($info)
    {
        switch ($info['type']) {
            case 'podcast':
            case 'vidcast':
                return new Handler\Podcast();
            case 'direct':
                return new Handler\Flysystem(DirectMapping\Utilities::getRootConfig($info), DirectMapping\Utilities::getVirtualPath($info));
            case 'system':
            default:
                return new Handler\System();
        }
    }
}
