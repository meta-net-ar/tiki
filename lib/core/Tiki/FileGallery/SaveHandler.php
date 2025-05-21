<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\FileGallery;

use TikiLib;
use Tiki\FileGallery\File as TikiFile;

class SaveHandler
{
    private $file;
    private $filesTable;
    private $fileDraftsTable;
    private $galleriesTable;

    public function __construct($file)
    {
        $this->file = $file;
        $this->filesTable = TikiLib::lib('filegal')->table('tiki_files');
        $this->galleriesTable = TikiLib::lib('filegal')->table('tiki_file_galleries');
        $this->fileDraftsTable = TikiLib::lib('filegal')->table('tiki_file_drafts');
    }

    public function isReplacing()
    {
        global $prefs;
        if (! $this->file->exists()) {
            return false;
        } elseif (! $this->file->fileId) {
            return false;
        } elseif ($this->file->galleryDefinition()->isDirect()) {
            return true;
        } else {
            if ($prefs['feature_file_galleries_save_draft'] == 'y') {
                return false;
            } elseif ($this->file->galleryDefinition()->getInfo()['archives'] == -1) {
                return true;
            } else {
                return false;
            }
        }
    }

    public function save()
    {
        global $prefs, $user;

        $initialFileId = $this->file->fileId;

        $this->ensureNameLength();

        if (! $this->file->exists()) {
            $fileId = $this->insertFile();
            $final_event = 'tiki.file.create';
        } elseif (! $initialFileId) {
          // Edge case: when using the migration script from image galleries to file galleries,
          // the file "exists" but has no fileId and still needs to be inserted.
            $fileId = $this->insertFile();
        } elseif ($this->file->galleryDefinition()->isDirect()) {
            $this->filesTable->update($this->file->getParamsForDB(), ['fileId' => $this->file->fileId]);
            $fileId = $this->file->fileId;
            $final_event = 'tiki.file.update';
        } else {
            if ($prefs['feature_file_galleries_save_draft'] == 'y') {
                $this->insertDraft();
                $fileId = $this->file->fileId;
            } elseif ($this->file->galleryDefinition()->getInfo()['archives'] == -1) {
                $this->filesTable->update($this->file->getParamsForDB(), ['fileId' => $this->file->fileId]);
                $fileId = $this->file->fileId;
            } else {
                $fileId = $this->saveArchive();
            }
            $final_event = 'tiki.file.update';
        }

        $this->galleriesTable->update(['lastModif' => TikiLib::lib('filegal')->now], ['galleryId' => $this->file->galleryId]);

        if (isset($final_event) && $final_event) {
            $event_params = [
                'type' => 'file',
                'object' => $fileId,
                'file' => $this->file,
                'galleryId' => $this->file->galleryId,
                'filetype' => $this->file->filetype,
                'user' => $user,
            ];
            if ($initialFileId) {
                $event_params['initialFileId'] = $initialFileId;
            }
            TikiLib::events()->trigger($final_event, $event_params);
        }

        return $fileId;
    }

    public function validateDraft()
    {
        global $user;

        $archives = $this->file->galleryDefinition()->getInfo()['archives'];
        $fileId = $this->file->fileId;

        if ($archives == -1) {
          //if no archives allowed by user, then replace certain original file information with
          //information from the validated draft
            $this->filesTable->update($this->file->getParamsForDB(), ['fileId' => (int) $fileId]);

            TikiLib::events()->trigger(
                'tiki.file.update',
                [
                    'type' => 'file',
                    'object' => $this->file->fileId,
                    'file' => $this->file,
                    'galleryId' => $this->file->galleryId,
                    'initialFileId' => $this->file->fileId,
                    'filetype' => $this->file->filetype,
                    'user' => $user,
                ]
            );
        } else {
          //if archives are allowed, the validated draft becomes an archive copy with some db info
          //from the original file carried over
            $this->saveArchive();
        }
    }

    private function insertFile()
    {
        global $prefs, $user;

        $file = $this->file;

        $initialFileId = (int)$file->param['fileId'];
        $sendWatches = true;

      // Edge case: If one is migrating files from image galleries to file galleries, the file exists but has no fileId yet and needs to be inserted.
      // This is detected with $initialFileId == 0
        if ($file->exists() && $initialFileId != 0) {
            $this->filesTable->update($file->getParamsForDB(), ['fileId' => $file->fileId]);
            $fileId = $file->fileId;
        } else {
            $fileId = $this->filesTable->insert($file->getParamsForDB());
            if ($initialFileId == 0) {
              // In case of a migration from image galleries to file galleries, don't send out a huge number of useless emails.
                $sendWatches = false;
            }
        }

        if ($prefs['feature_actionlog'] == 'y') {
            $logslib = TikiLib::lib('logs');
            $logslib->add_action('Uploaded', $file->galleryId, 'file gallery', "fileId=$fileId&amp;add=" . $file->filesize);
        }

        return $fileId;
    }

    private function insertDraft()
    {
        if ($this->file->getWrapper()->getSize() == 0) {
            return $this->filesTable->update($this->file->getParamsForDB(), ['fileId' => $this->file->fileId]);
        } else {
            $fileDraft = FileDraft::fromFile($this->file);
            TikiLib::lib('filegal')->remove_draft($this->file->fileId, $this->file->user);
            return $this->fileDraftsTable->insert($fileDraft->getParamsForDB());
        }
    }

    private function saveArchive()
    {
        global $prefs;

        $file = $this->file;
        $definition = $file->galleryDefinition();
        $count_archives = $definition->getInfo()['archives'];
        $origFileId = $file->fileId;

      // fgal_keep_fileId == n means that the archive will keep the same fileId and the latest version will have a new fileId
      // fgal_keep_fileId = y the new version will keep the current fileId, the archive will have a new fileId
        if ($prefs['fgal_keep_fileId'] == 'y') {
          // create archive by inserting the old file with a new fileId and archivId field set to original fileId
            $res = $this->filesTable->fetchFullRow(['fileId' => $file->fileId]);
            if ($res) {
                $res['archiveId'] = $file->fileId;
                $res['user'] = $file->user;
                $res['lockedby'] = null;
                unset($res['fileId']);

                $newFileId = $this->filesTable->insert($res);

                $attributelib = TikiLib::lib('attribute');
                $attributes = $attributelib->get_attributes('file', $file->fileId);
                $attributelib->set_attribute('file', $file->fileId, 'tiki.content.url', '');

                if (isset($attributes['tiki.content.url'])) {
                  //we don't delete or update the attribute, so that it remains working if the user changes the fgal_keep_fileId
                    $attributelib->set_attribute('file', $newFileId, 'tiki.content.url', $attributes['tiki.content.url']);
                }
            }
        }

      // clone file object to insert as new record
        if ($prefs['fgal_keep_fileId'] != 'y') {
            $file = $this->file = $file->clone();
        }
        $idNew = $this->insertFile();

        if ($count_archives > 0) {
            $archives = TikiLib::lib('filegal')->get_archives($file->fileId, 0, -1, 'created_asc');

            if ($archives['count'] > $count_archives) {
                $toRemove = [];

                // Remove only the oldest archived versions to keep the latest ones within the archive limit
                $archivesToDelete = array_slice($archives['data'], 0, $archives['count'] - $count_archives);


                foreach ($archivesToDelete as $values) {
                    $toRemove[] = $values['fileId'];
                    $definition->delete(new TikiFile($values));
                }

                $this->filesTable->deleteMultiple(['fileId' => $this->filesTable->in($toRemove)]);

                \Feedback::success(tr('%0 archive(s) max allowed — %1 older version(s) have been deleted for file %2.', $count_archives, count($toRemove), $file->name));
            }
        }
        if ($prefs['fgal_keep_fileId'] != 'y') {
            $this->filesTable->updateMultiple(
                ['archiveId' => $idNew, 'search_data' => '', 'user' => $file->user, 'lockedby' => null],
                ['anyOf' => $this->filesTable->expr('(`archiveId` = ? OR `fileId` = ?)', [$origFileId, $origFileId])]
            );
        }

        if ($prefs['feature_categories'] == 'y') {
            $categlib = TikiLib::lib('categ');
            $categlib->uncategorize_object('file', $origFileId);
        }

        return $idNew;
    }

    private function ensureNameLength(): void
    {
        $f_len = function_exists('mb_strlen') ? 'mb_strlen' : 'strlen';
        $f_substr = function_exists('mb_substr') ? 'mb_substr' : 'substr';

        if (! empty($this->file->name) && $f_len($this->file->name) > 200) {
            $name = $f_substr($this->file->name, 0, 98) . '...' . $f_substr($this->file->name, -98);
            $this->file->setParam('name', $name);
        }

        if (! empty($this->file->filename) && $f_len($this->file->filename) > 80) {
            $filename = $f_substr($this->file->filename, 0, 38) . '...' . $f_substr($this->file->filename, -38);
            $this->file->setParam('filename', $filename);
        }
    }
}
