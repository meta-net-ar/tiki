<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_File_Utilities
{
    public function checkTargetGallery($galleryId)
    {
        if (! $gal_info = $this->getGallery($galleryId)) {
            throw new Services_Exception(tr('Requested gallery does not exist.'), 404);
        }

        $canUpload = TikiLib::lib('filegal')->can_upload_to($gal_info);

        if (! $canUpload) {
            throw new Services_Exception(tr('Permission denied.'), 403);
        }

        return $gal_info;
    }

    public function getGallery($galleryId)
    {
        $filegallib = TikiLib::lib('filegal');
        return $filegallib->get_file_gallery_info($galleryId);
    }

    public function uploadFile($gal_info, $name, $size, $type, $data, $asuser = null, $image_x = null, $image_y = null, $description = '', $created = '', $title = '', $directoryPattern = '')
    {
        $filegallib = TikiLib::lib('filegal');
        return $filegallib->upload_single_file($gal_info, $name, $size, $type, $data, $asuser, $image_x, $image_y, $description, $created, $title, $directoryPattern);
    }

    public function updateFile($gal_info, $name, $size, $type, $data, $fileId, $asuser = null, $title = '', $description = '')
    {
        $filegallib = TikiLib::lib('filegal');
        return $filegallib->update_single_file($gal_info, $name, $size, $type, $data, $fileId, $asuser, $title, $description);
    }

    /**
     * Used by directory drop function to automatically create subdirectories (child file galleries)
     * where the files will be uploaded to.
     */
    public function findOrCreateDirectoryHierarchy(int $parentGalleryId, string $directory): array
    {
        global $prefs;

        $filegallib = TikiLib::lib('filegal');

        $dirs = [];
        while (basename($directory)) {
            $dirs[] = basename($directory);
            $directory = dirname($directory);
        }

        $dirs = array_reverse($dirs);
        foreach ($dirs as $dir) {
            $galleryId = $filegallib->getGalleryId($dir, $parentGalleryId);
            if (! $galleryId) {
                if ($parentGalleryId == $prefs['fgal_root_id']) {
                    $galleryId = $filegallib->replace_file_gallery(['name' => $dir]);
                } else {
                    $galleryId = $filegallib->duplicate_file_gallery($parentGalleryId, $dir, '', $parentGalleryId);
                }
                // also copy any direct permissions of the parent gallery
                $this->copyParentPermissions($parentGalleryId, $galleryId);
            }
            $parentGalleryId = $galleryId;
        }
        return $this->checkTargetGallery($parentGalleryId);
    }

    public function copyParentPermissions(int $parentGalleryId, int $galleryId): void
    {
        $objectFactory = Perms_Reflection_Factory::getDefaultFactory();
        $parentObject = $objectFactory->get('file gallery', $parentGalleryId);
        $perms = $parentObject->getDirectPermissions();
        if ($perms->getPermissionArray()) {
            $object = $objectFactory->get('file gallery', $galleryId);
            $permissionApplier = new Perms_Applier();
            $permissionApplier->addObject($object);
            $permissionApplier->apply($perms);
        }
    }
}
