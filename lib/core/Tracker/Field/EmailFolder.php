<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tracker_Field_EmailFolder extends Tracker_Field_Files implements \Tracker\Field\ExportableInterface
{
    public static function getManagedTypesInfo(): array
    {
        global $prefs;

        $options = [
            'EF' => [
                'name' => tr('Email Folder'),
                'description' => tr('Associate email messages with tracker items.'),
                'prefs' => ['trackerfield_email_folder', 'feature_file_galleries'],
                'tags' => ['advanced'],
                'help' => 'Email-Folder-Tracker-Field',
                'default' => 'y',
                'params' => [
                    'galleryId' => [
                        'name' => tr('Gallery ID'),
                        'description' => tr('File gallery to upload new emails into.'),
                        'filter' => 'int',
                        'legacy_index' => 0,
                        'profile_reference' => 'file_gallery',
                    ],
                    'useFolders' => [
                        'name' => tr('Use Folders'),
                        'description' => tr('Use separate folders like Inbox, Sent, Trash, Archive.'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                    'inboxName' => [
                        'name' => tr('Inbox Name'),
                        'description' => tr('Name of the Inbox folder.'),
                        'filter' => 'text',
                        'default' => 'Inbox',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'sentName' => [
                        'name' => tr('Sent Name'),
                        'description' => tr('Name of the Sent folder.'),
                        'filter' => 'text',
                        'default' => 'Sent',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'trashName' => [
                        'name' => tr('Trash Name'),
                        'description' => tr('Name of the Trash folder.'),
                        'filter' => 'text',
                        'default' => 'Trash',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'archiveName' => [
                        'name' => tr('Archive Name'),
                        'description' => tr('Name of the Archive folder.'),
                        'filter' => 'text',
                        'default' => 'Archive',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'draftName' => [
                        'name' => tr('Draft Name'),
                        'description' => tr('Name of the Draft folder.'),
                        'filter' => 'text',
                        'default' => 'Draft',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'junkName' => [
                        'name' => tr('Junk Name'),
                        'description' => tr('Name of the Junk folder.'),
                        'filter' => 'text',
                        'default' => 'Junk',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'customFolders' => [
                        'name' => tr('Custom Folders'),
                        'description' => tr('Comma separated list of additional folders to use.'),
                        'filter' => 'text',
                        'default' => '',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'openedFolders' => [
                        'name' => tr('Opened Folders'),
                        'description' => tr('Comma separated list of folders to show opened by default.'),
                        'filter' => 'text',
                        'default' => '',
                        'depends' => [
                            'field' => 'useFolders',
                            'value' => '1'
                        ],
                    ],
                    'composePage' => [
                        'name' => tr('Compose Page'),
                        'description' => tr('Name of the wiki page where compose button will direct to. Leave empty for default Webmail page.'),
                        'filter' => 'text',
                        'default' => '',
                    ],
                ],
            ],
        ];
        return $options;
    }

    public function getFieldData(array $requestData = [])
    {
        global $prefs;
        $filegallib = TikiLib::lib('filegal');

        $galleryId = (int) $this->getOption('galleryId');
        $galinfo = $filegallib->get_file_gallery($galleryId, false);
        if (! $galinfo || empty($galinfo['galleryId'])) {
            Feedback::error(tr('%0 field: Gallery #%1 not found', $this->getConfiguration('name'), $galleryId));
            return [];
        }

        $value = $this->getValue();
        $fileIds = $this->sanitizeFieldData($value);

        // Obtain the information from the database for display
        $emails = [];
        foreach ($fileIds as $folder => $files) {
            $emails[$folder] = [];
            foreach ($files as $fileId) {
                if (empty($fileId)) {
                    continue;
                }
                $email = $this->getEmailFromFile($fileId);
                if (! $email) {
                    continue;
                }
                $emails[$folder][] = $email;
            }
        }

        foreach ($emails as $folder => $_) {
            usort($emails[$folder], function ($e1, $e2) {
                if ($e1['date'] > $e2['date']) {
                    return -1;
                } elseif ($e1['date'] < $e2['date']) {
                    return 1;
                } else {
                    return 0;
                }
            });
        }
        return [
            'galleryId' => $galleryId,
            'emails' => $emails,
            'count' => count($fileIds, COUNT_RECURSIVE),
            'value' => $value,
        ];
    }

    private function sanitizeFieldData($value)
    {
        $decoded = json_decode($value, true);
        if ($decoded !== null) {
            return $decoded;
        } else {
            return [
                'inbox' => array_filter(explode(',', $value))
            ];
        }
    }

    private function getEmailFromFile($fileId)
    {
        $file_object = Tiki\FileGallery\File::id($fileId);
        if (! $file_object->exists()) {
            return false;
        }
        $parsed_fields = (new Tiki\FileGallery\Manipulator\EmailParser($file_object))->run();
        $parsed_fields['fileId'] = $fileId;
        $parsed_fields['trackerId'] = $this->getTrackerDefinition()->getConfiguration('trackerId');
        $parsed_fields['itemId'] = $this->getItemId();
        $parsed_fields['fieldId'] = $this->getConfiguration('fieldId');
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
        $view_path .= "page=message&uid=" . $parsed_fields['fileId'] . "&list_path=tracker_folder_" . $parsed_fields['itemId'] . "_" . $parsed_fields['fieldId'] . "&list_parent=tracker_" . $parsed_fields['trackerId'];
        $parsed_fields['view_path'] = $view_path;
        return $parsed_fields;
    }

    public function renderInput($context = [])
    {
        return tr("Emails can be copied or moved here via the Webmail interface.");
    }

    public function renderOutput($context = [])
    {
        if (! isset($context['list_mode'])) {
            $context['list_mode'] = 'n';
        }

        $value = $this->getValue();

        if ($context['list_mode'] === 'csv') {
            return $value;
        }

        $emails = $this->getConfiguration('emails');

        if ($context['list_mode'] === 'text') {
            $folderFormatter = function ($emails) {
                return implode(
                    "\n",
                    array_map(
                        function ($email) {
                            return $email['subject'];
                        },
                        $emails
                    )
                );
            };
            if ($this->getOption('useFolders')) {
                $result = "";
                foreach ($this->getFolders() as $folder => $folderName) {
                    if (! empty($emails[$folder])) {
                        $result .= $folderName . "\n";
                        $result .= $folderFormatter($emails[$folder]);
                    }
                }
            } else {
                return $folderFormatter($emails['inbox']);
            }
        }

        if ($compose_page = $this->getOption('composePage')) {
            $compose_path = smarty_modifier_sefurl($compose_page);
            if (preg_match("/tiki-index\.php\?page=.*/", $compose_path)) {
                $compose_path = "tiki-index.php?page_id=" . TikiLib::lib('tiki')->get_page_id_from_name($compose_page);
            }
        } else {
            $compose_path = "tiki-webmail.php";
        }
        if (strstr($compose_path, '?')) {
            $compose_path .= '&';
        } else {
            $compose_path .= '?';
        }
        $compose_path .= "page=compose&list_path=tracker_folder_" . $this->getItemId() . "_" . $this->getConfiguration('fieldId') . "&list_parent=tracker_" . $this->getTrackerDefinition()->getConfiguration('trackerId');

        return $this->renderTemplate('trackeroutput/email_folder.tpl', $context, [
            'emails' => $emails,
            'count' => $this->getConfiguration('count'),
            'folders' => $this->getFolders(),
            'opened' => array_map(function ($folder) {
                return $this->folderHandle($folder);
            }, preg_split('/\s*,\s*/', $this->getOption('openedFolders'))),
            'compose_path' => $compose_path,
        ]);
    }

    public function handleSave($value, $oldValue)
    {
        $existing = json_decode($oldValue, true);
        if ($existing === null) {
            $existing = [
                'inbox' => array_filter(explode(',', $oldValue))
            ];
        }
        if (isset($value['new'])) {
            $folder = $value['folder'] ?? 'inbox';
            if ($this->getOption('useFolders') || $folder == 'inbox') {
                if (array_filter($value['new'], 'is_array') === $value['new']) {
                    foreach ($value['new'] as $new_value) {
                        $this->addEmail($existing[$folder], $new_value);
                    }
                } else {
                    $this->addEmail($existing[$folder], $value['new']);
                }
            }
        } elseif (isset($value['delete'])) {
            $this->deleteEmail($existing, $value['delete'], $value['skip_trash'] ?? false);
        } elseif (isset($value['archive'])) {
            $this->archiveEmail($existing, $value['archive']);
        }
        return [
            'value' => json_encode($existing)
        ];
    }

    public function getDocumentPart(Search_Type_Factory_Interface $typeFactory)
    {
        $value = $this->getValue();
        $baseKey = $this->getBaseKey();
        $emails = $this->getConfiguration('emails');
        if (! is_array($emails)) {
            $data = $this->getFieldData();
            $emails = $data['emails'];
        }

        $subjects = [];
        $dates = [];
        $senders = [];
        $recipients = [];
        foreach ($emails as $folder => $folder_emails) {
            foreach ($folder_emails as $email) {
                $subjects[] = $email['subject'];
                $dates[] = $email['date'];
                $senders[] = $email['sender'];
                $recipients[] = $email['recipient'];
            }
        }

        $out = [
            $baseKey => $typeFactory->identifier($value),
            "{$baseKey}_subjects" => $typeFactory->multivalue($subjects),
            "{$baseKey}_dates" => $typeFactory->multivalue($dates),
            "{$baseKey}_senders" => $typeFactory->multivalue($senders),
            "{$baseKey}_recipients" => $typeFactory->multivalue($recipients),
        ];
        return $out;
    }

    public function getProvidedFields(): array
    {
        $baseKey = $this->getBaseKey();
        $fields = [
            $baseKey,
            "{$baseKey}_subjects",
            "{$baseKey}_dates",
            "{$baseKey}_senders",
            "{$baseKey}_recipients",
        ];
        return $fields;
    }

    public function getProvidedFieldTypes(): array
    {
        $baseKey = $this->getBaseKey();
        $fields = [
            $baseKey => 'identifier',
            "{$baseKey}_subjects" => 'multivalue',
            "{$baseKey}_dates" => 'multivalue',
            "{$baseKey}_senders" => 'multivalue',
            "{$baseKey}_recipients" => 'multivalue',
        ];
        return $fields;
    }

    public function getGlobalFields(): array
    {
        $baseKey = $this->getBaseKey();
        return [$baseKey => true];
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        $schema->addNew($permName, 'default')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        return $schema;
    }

    public function watchCompare($old, $new)
    {
        $filegallib = TikiLib::lib('filegal');

        $old = $this->sanitizeFieldData($old);
        $new = $this->sanitizeFieldData($new);

        $output = "[-[" . $this->getConfiguration('name') . "]-]:\n";
        foreach ($new as $folder => $_) {
            $oldFileIds = empty($old[$folder]) ? [] : $old[$folder];
            $newFileIds = empty($new[$folder]) ? [] : $new[$folder];

            $diff = parent::watchCompareList($oldFileIds, $newFileIds, function ($item) {
                $file_object = Tiki\FileGallery\File::id($item);
                if ($file_object->exists()) {
                    $parsed_fields = (new Tiki\FileGallery\Manipulator\EmailParser($file_object))->run();
                    return ($parsed_fields['date'] ? TikiLib::lib('tiki')->get_long_date($parsed_fields['date']) . ' ' : '') . $parsed_fields['subject'];
                } else {
                    return $item;
                }
            });
            $diff = preg_replace('/^[^\n]+\n/', '', $diff);

            $output .= "  [-[" . ucfirst($folder) . "]-]:\n";
            $output .= $diff;
        }
        return $output;
    }

    public function renderDiff($context = [])
    {
        if ($context['oldValue']) {
            $old = $context['oldValue'];
        } else {
            $old = '';
        }
        if ($context['value']) {
            $new = $context['value'];
        } else {
            $new = $this->getValue('');
        }
        if (empty($context['diff_style'])) {
            $context['diff_style'] = 'sidediff';
        }

        $old = $this->sanitizeFieldData($old);
        $new = $this->sanitizeFieldData($new);

        $changes = ['removed' => [], 'added' => []];

        foreach ($new as $folder => $_) {
            $oldFileIds = empty($old[$folder]) ? [] : $old[$folder];
            $newFileIds = empty($new[$folder]) ? [] : $new[$folder];

            $removed = array_diff($oldFileIds, $newFileIds);
            $added = array_diff($newFileIds, $oldFileIds);

            if ($removed) {
                $changes['removed'][$folder] = $removed;
            }

            if ($added) {
                $changes['added'][$folder] = $added;
            }
        }

        $result = '<table class="table"><tr><td class="diffdeleted">-</td><td class="diffdeleted"><del class="diffchar deleted">';
        foreach ($changes['removed'] as $folder => $removed) {
            $result .= '</del><strong>' . ucfirst($folder) . ':</strong><br><del class="diffchar deleted">';
            foreach ($removed as $fileId) {
                $email = $this->getEmailFromFile($fileId);
                if ($email) {
                    $subject = ($email['date'] ? TikiLib::lib('tiki')->get_long_date($email['date']) . ' ' : '') . $email['subject'];
                    $result .= "<a href='{$email['view_path']}'>" . $subject . '</a><br>';
                } else {
                    $result .= $fileId . '<br>';
                }
            }
        }

        $result .= '</del></td><td class="diffadded">+</td><td class="diffadded"><ins class="diffchar inserted">';
        foreach ($changes['added'] as $folder => $added) {
            $result .= '</ins><strong>' . ucfirst($folder) . ':</strong><br><ins class="diffchar inserted">';
            foreach ($added as $fileId) {
                $email = $this->getEmailFromFile($fileId);
                if ($email) {
                    $subject = ($email['date'] ? TikiLib::lib('tiki')->get_long_date($email['date']) . ' ' : '') . $email['subject'];
                    $result .= "<a href='{$email['view_path']}'>" . $subject . '</a><br>';
                } else {
                    $result .= $fileId . '<br>';
                }
            }
        }

        $result .= '</ins></td></tr></table>';

        return $result;
    }

    public function getFolders()
    {
        $folders = [
            'inbox' => $this->getOption('inboxName'),
            'sent' => $this->getOption('sentName'),
            'trash' => $this->getOption('trashName'),
            'archive' => $this->getOption('archiveName'),
            'draft' => $this->getOption('draftName'),
            'junk' => $this->getOption('junkName'),
        ];
        $custom = preg_split('/\s*,\s*/', $this->getOption('customFolders'));
        $handles = array_map(function ($folder) {
            return $this->folderHandle($folder);
        }, $custom);
        return array_merge($folders, array_combine($handles, $custom));
    }

    protected function folderHandle($folderName)
    {
        return preg_replace("/[^a-z0-9]+/", "", strtolower($folderName));
    }

    protected function addEmail(&$existing, $file)
    {
        $filegallib = TikiLib::lib('filegal');
        $galleryId = (int) $this->getOption('galleryId');
        $galinfo = $filegallib->get_file_gallery($galleryId, false);
        if (! $galinfo || empty($galinfo['galleryId'])) {
            Feedback::error(tr('%0 field: Gallery #%1 not found', $this->getConfiguration('name'), $galleryId));
            return;
        }
        $fileId = $filegallib->upload_single_file($galinfo, $file['name'], $file['size'], $file['type'], $file['content']);
        if ($fileId) {
            $existing[] = $fileId;
        }
    }

    protected function deleteEmail(&$existing, $fileId, $skip_trash = false)
    {
        foreach ($existing as $folder => $_) {
            if (($key = array_search($fileId, $existing[$folder])) !== false) {
                unset($existing[$folder][$key]);
                $existing[$folder] = array_values($existing[$folder]);
                if ($this->getOption('useFolders') && $folder != 'trash' && $folder != 'archive' && ! $skip_trash) {
                    $existing['trash'][] = $fileId;
                } else {
                    $filegallib = TikiLib::lib('filegal');
                    $info = $filegallib->get_file_info($fileId);
                    if ($info) {
                        $filegallib->remove_file($info);
                    }
                }
                break;
            }
        }
    }

    protected function archiveEmail(&$existing, $fileId)
    {
        if (! $this->getOption('useFolders')) {
            Feedback::error(tr('%0 field: not configured to use folders but message was tried to be archived.', $this->getConfiguration('name')));
            return;
        }
        foreach ($existing as $folder => $_) {
            if (($key = array_search($fileId, $existing[$folder])) !== false) {
                unset($existing[$folder][$key]);
                $existing[$folder] = array_values($existing[$folder]);
                $existing['archive'][] = $fileId;
                break;
            }
        }
    }
}
