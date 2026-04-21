<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_File_Utilities
{
    public function checkTargetGallery($galleryId)
    {
        global $prefs;

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

    public function updateFile($gal_info, $name, $size, $type, $data, $fileId, $asuser = null, $title = '')
    {
        $filegallib = TikiLib::lib('filegal');
        return $filegallib->update_single_file($gal_info, $name, $size, $type, $data, $fileId, $asuser, $title);
    }

    /**
     * Enforces the full permission policy for displaying or downloading a file:
     * - bypasses all checks when the request carries a valid auth token
     * - honors `backlinkPerms` together with `hasOnlyPrivateBacklinks`
     * - grants access via a viewable backlinked tracker item
     * - enforces user-file-gallery privacy
     * - verifies download perms on `?thumbnail=<id>` too, when present
     *
     * @param array  $file
     * @param bool   $zip
     */
    public function enforceFileDownloadPermissions(array $file, bool $zip = false): void
    {
        global $user, $is_token_access, $prefs;

        if ($prefs['auth_token_access'] == 'y' && $is_token_access) {
            return;
        }

        $access = TikiLib::lib('access');
        $filegallib = TikiLib::lib('filegal');
        $userlib = TikiLib::lib('user');

        $can_admin_file_galleries = Perms::get()->admin_file_galleries;

        if (! $can_admin_file_galleries && $file['backlinkPerms'] == 'y' && $filegallib->hasOnlyPrivateBacklinks($file['fileId'])) {
            $this->rememberLoginReferer();
            $access->display_error('', tra('Permission denied'), 401);
        }

        if (
            ! $zip
            && ! $can_admin_file_galleries
            && ! $userlib->user_has_perm_on_object($user, $file['fileId'], 'file', 'tiki_p_download_files')
            && ! $filegallib->isBacklinkedFromAViewableTrackerItem($file['fileId'])
        ) {
            $this->rememberLoginReferer();
            $access->display_error('', tra('Permission denied'), 401);
        }

        // When a thumbnail is requested via ?thumbnail=<fileId>, verify download perms on the thumb too
        if (isset($_GET['thumbnail']) && is_numeric($_GET['thumbnail'])) {
            $info_thumb = $filegallib->get_file($_GET['thumbnail']);
            if (
                ! $zip
                && ! $can_admin_file_galleries
                && ! $userlib->user_has_perm_on_object($user, $info_thumb['fileId'], 'file', 'tiki_p_download_files')
            ) {
                $this->rememberLoginReferer();
                $access->display_error('', tra('Permission denied'), 401);
            }
        }

        if ($prefs['feature_use_fgal_for_user_files'] === 'y' && ! $can_admin_file_galleries && $prefs['userfiles_private'] === 'y') {
            $gal_info = $filegallib->get_file_gallery_info($file['galleryId']);
            if ($gal_info['type'] === 'user' && $gal_info['visible'] !== 'y' && $gal_info['user'] !== $user) {
                $access->display_error('', tra('Permission denied'), 401);
            }
        }
    }

    /**
     * Stash the referer so that, after login, the user is returned to the page
     * they were originally trying to reach.
     */
    private function rememberLoginReferer(): void
    {
        global $user, $prefs;

        if (! $user && $prefs['permission_denied_login_box'] === 'y' && empty($_SESSION['loginfrom'])) {
            $_SESSION['loginfrom'] = $_SERVER['HTTP_REFERER'] ?? '';
        }
    }
}
