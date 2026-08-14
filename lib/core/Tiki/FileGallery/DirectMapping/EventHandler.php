<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\FileGallery\DirectMapping;

use League\Flysystem\Config;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToCreateDirectory;
use League\Flysystem\UnableToMoveFile;
use League\Flysystem\UnableToDeleteFile;
use Feedback;
use TikiLib;
use Tiki\FileGallery\Definition;
use Tiki\FileGallery\Handler\AdapterNotFoundException;

class EventHandler
{
    public function bindEvents(\Tiki_Event_Manager $manager)
    {
        $manager->bind(
            'tiki.filegallery.create',
            [$this, 'handleSave']
        );
        $manager->bind(
            'tiki.filegallery.update',
            [$this, 'handleSave']
        );
        $manager->bind(
            'tiki.filegallery.delete',
            [$this, 'handleDelete']
        );
    }

    public function handleSave($args)
    {
        if (! $this->isDirectFgal($args)) {
            return;
        }

        if (isset($args['old_info']['name']) && $args['old_info']['name'] != $args['info']['name']) {
            $this->renameDirectory($args['old_info'], $args['info']);
        } else {
            if (empty($args['info']['galleryId'])) {
                $args['info']['galleryId'] = $args['object'];
            }
            $this->createDirectory($args['info']);
        }
    }

    public function handleDelete($args)
    {
        if (! $this->isDirectFgal($args)) {
            return;
        }

        $this->deleteDirectory($args['info']);
    }

    protected function isDirectFgal($args)
    {
        if (! isset($args['info']['type']) || $args['info']['type'] !== 'direct') {
            return false;
        }
        if (! empty($args['info']['direct'])) {
            $config = json_decode($args['info']['direct'], true);
            if (! isset($config['adapter']) || $config['adapter'] !== 'inherit') {
                return false;
            }
        }
        return true;
    }

    protected function getFilesystem($info)
    {
        try {
            $gallery = new Definition($info);
            return $gallery->getFilesystem();
        } catch (AdapterNotFoundException $e) {
            // could be trying to delete (recursively) already deleted subdirectory (file gallery)
            // that cannot initialize its remote filesystem and must be ignored
            return null;
        }
    }

    protected function createDirectory($info)
    {
        $path = Utilities::getVirtualPath($info);
        if (! $path) {
            return;
        }

        $fs = $this->getFilesystem($info);
        if (! $fs) {
            return;
        }

        try {
            $fs->createDirectory($path, [
                Config::OPTION_DIRECTORY_VISIBILITY => 'public',
            ]);
        } catch (FilesystemException | UnableToCreateDirectory $exception) {
            Feedback::error(tr("Error creating direct mapping directory %0: %1", $path, $exception->getMessage()));
        }
    }

    protected function renameDirectory($old_info, $info)
    {
        $path = Utilities::getVirtualPath($info);
        if (! $path) {
            return;
        }

        $old_path = preg_replace('/' . $info['name'] . '$/', $old_info['name'], $path);

        $fs = $this->getFilesystem($info);
        if (! $fs) {
            return;
        }

        try {
            $fs->move($old_path, $path, [
                Config::OPTION_DIRECTORY_VISIBILITY => 'public',
            ]);
        } catch (FilesystemException | UnableToMoveFile $exception) {
            Feedback::error(tr("Error renaming direct mapping directory %0: %1", $path, $exception->getMessage()));
        }
    }

    protected function deleteDirectory($info)
    {
        $path = Utilities::getVirtualPath($info);
        if (! $path) {
            return;
        }

        $fs = $this->getFilesystem($info);
        if (! $fs) {
            return;
        }

        try {
            $fs->deleteDirectory($path);
        } catch (FilesystemException | UnableToDeleteFile $exception) {
            Feedback::error(tr("Error deleting direct mapping directory %0: %1", $path, $exception->getMessage()));
        }
    }
}
