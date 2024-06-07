<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_FileSource implements Search_ContentSource_Interface, Tiki_Profile_Writer_ReferenceProvider
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getReferenceMap()
    {
        return [
            'gallery_id' => 'file_gallery',
        ];
    }

    public function getDocuments()
    {
        $files = $this->db->table('tiki_files');
        return $files->fetchColumn(
            'fileId',
            [
                'archiveId' => 0,
            ],
            -1,
            -1,
            'ASC'
        );
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        global $prefs;

        $filegallib = TikiLib::lib('filegal');

        $file = $filegallib->get_file_info($objectId, true, false);

        if (! $file) {
            return false;
        }

        if (! empty($file['name'])) {
            // Many files when uploaded have underscore in the file name and makes search difficult
            $file['name'] = str_replace('_', ' ', $file['name']);
        }

        $data = [
            'title' => $typeFactory->sortable(empty($file['name']) ? $file['filename'] : $file['name']),
            'title_unstemmed' => $typeFactory->simpletext(empty($file['name']) ? $file['filename'] : $file['name']),
            'language' => $typeFactory->identifier('unknown'),
            'creation_date' => $typeFactory->timestamp($file['created']),
            'modification_date' => $typeFactory->timestamp($file['lastModif']),
            'date' => $typeFactory->timestamp($file['created']),
            'contributors' => $typeFactory->multivalue(array_unique([$file['author'], $file['user'], $file['lastModifUser']])),
            'description' => $typeFactory->plaintext($file['description']),
            'filename' => $typeFactory->identifier($file['filename']),
            'filetype' => $typeFactory->sortable(! empty($file['filetype']) ? preg_replace('/^([\w-]+)\/([\w-]+).*$/', '$1/$2', $file['filetype']) : ''),
            'filesize' => $typeFactory->plaintext($file['filesize']),
            'hits' => $typeFactory->numeric($file['hits']),

            'gallery_id' => $typeFactory->identifier($file['galleryId']),
            'file_comment' => $typeFactory->plaintext($file['comment']),
            'file_content' => $typeFactory->plaintext($file['search_data']),
            'ocr_content' => $typeFactory->plaintext($file['ocr_data']),

            'parent_object_type' => $typeFactory->identifier('file gallery'),
            'parent_object_id' => $typeFactory->identifier($file['galleryId']),
            'view_permission' => $typeFactory->identifier('tiki_p_download_files'),
        ];

        if ($prefs['fgal_enable_email_indexing'] === 'y' && $file['filetype'] == 'message/rfc822') {
            $file_object = Tiki\FileGallery\File::id($file['fileId']);
            $parsed_fields = (new Tiki\FileGallery\Manipulator\EmailParser($file_object))->run();
            if ($parsed_fields) {
                $data += [
                    'email_subject' => $typeFactory->plaintext($parsed_fields['subject']),
                    'email_from' => $typeFactory->plaintext($parsed_fields['from']),
                    'email_sender' => $typeFactory->plaintext($parsed_fields['sender']),
                    'email_recipient' => $typeFactory->plaintext($parsed_fields['recipient']),
                    'email_date' => $typeFactory->timestamp($parsed_fields['date']),
                    'email_flags' => $typeFactory->multivalue(array_filter(array_values($parsed_fields['flags']))),
                    'email_content_type' => $typeFactory->plaintext($parsed_fields['content_type']),
                    'email_body' => $typeFactory->plainmediumtext($parsed_fields['body']),
                    'email_plaintext' => $typeFactory->plainmediumtext($parsed_fields['plaintext']),
                    'email_html' => $typeFactory->plainmediumtext($parsed_fields['html']),
                ];
                $sources = TikiLib::lib('relation')->get_relations_to('file', $objectId, 'tiki.file.attach');
                foreach ($sources as $rel) {
                    if ($rel['type'] == 'trackeritem' && ! empty($rel['fieldId'])) {
                        $view_path = 'tiki-webmail.php';
                        if (! empty($parsed_fields['source_id'])) {
                            $page_info = TikiLib::lib('tiki')->get_page_info_from_id($parsed_fields['source_id']);
                            if ($page_info && stristr($page_info['data'], "cypht")) {
                                $view_path = smarty_modifier_sefurl($page_info['pageName']);
                                if (preg_match("/tiki-index\.php\?page=.*/", $view_path)) {
                                    $view_path = "tiki-index.php?page_id=" . $parsed_fields['source_id'];
                                }
                            }
                        }
                        if (strstr($view_path, '?')) {
                            $view_path .= '&';
                        } else {
                            $view_path .= '?';
                        }
                        $view_path .= "page=message&uid=" . $file['fileId'] . "&list_path=tracker_folder_" . $rel['itemId'] . "_" . $rel['fieldId'] . "&list_parent=tracker_" . TikiLib::lib('trk')->get_tracker_for_item($rel['itemId']);
                        $data['url'] = $typeFactory->identifier($view_path);
                        break;
                    }
                }
            }
        }

        return $data;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'title_unstemmed',
            'language',
            'creation_date',
            'modification_date',
            'date',
            'contributors',
            'description',
            'filename',
            'filetype',
            'filesize',
            'hits',

            'gallery_id',
            'file_comment',
            'file_content',
            'ocr_content',

            'view_permission',
            'parent_object_id',
            'parent_object_type',

            'email_subject',
            'email_from',
            'email_sender',
            'email_recipient',
            'email_date',
            'email_flags',
            'email_content_type',
            'email_body',
            'email_plaintext',
            'email_html',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'title_unstemmed' => 'simpletext',
            'language' => 'identifier',
            'creation_date' => 'timestamp',
            'modification_date' => 'timestamp',
            'date' => 'timestamp',
            'contributors' => 'multivalue',
            'description' => 'plaintext',
            'filename' => 'identifier',
            'filetype' => 'sortable',
            'filesize' => 'plaintext',
            'hits' => 'numeric',

            'gallery_id' => 'identifier',
            'file_comment' => 'plaintext',
            'file_content' => 'plaintext',
            'ocr_content' => 'plaintext',

            'view_permission' => 'identifier',
            'parent_object_id' => 'identifier',
            'parent_object_type' => 'identifier',

            'email_subject' => 'plaintext',
            'email_from' => 'plaintext',
            'email_sender' => 'plaintext',
            'email_recipient' => 'plaintext',
            'email_date' => 'timestamp',
            'email_flags' => 'multivalue',
            'email_content_type' => 'plaintext',
            'email_body' => 'plainmediumtext',
            'email_plaintext' => 'plainmediumtext',
            'email_html' => 'plainmediumtext',
        ];
    }

    public function getGlobalFields(): array
    {
        global $prefs;

        $fields = [
            'title' => true,
            'description' => true,
            'date' => true,
            'filename' => true,

            'file_comment' => false,
            'file_content' => false,
            'ocr_content' => false,
        ];

        if ($prefs['fgal_enable_email_indexing'] === 'y') {
            $fields['email_subject'] = true;
            $fields['email_body'] = true;
            $fields['email_plaintext'] = true;
            $fields['email_html'] = true;
        }

        return $fields;
    }
}
