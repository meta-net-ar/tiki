<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Action_EmailAction implements Search_Action_Action
{
    public function getValues()
    {
        return [
            'object_type' => true,
            'object_id' => true,
            'replyto' => false,
            'to+' => true,
            'cc+' => false,
            'bcc+' => false,
            'from' => false,
            'subject' => true,
            'content' => true,
            'is_html' => false,
            'pdf_page_attachment' => false,
            'file_attachments+' => false,
            'file_attachment_field' => false,
            'file_attachment_gal' => false,
        ];
    }

    public function validate(JitFilter $data)
    {
        return true;
    }

    public function execute(JitFilter $data)
    {
        try {
            $mail = new TikiMail();

            if ($replyto = $this->dereference($data->replyto->raw())) {
                $mail->setReplyTo($replyto[0]);
            }

            $recipients = [];
            foreach ($data->to->raw() as $to) {
                if (is_array($to)) {
                    foreach ($to as $user_email) {
                        if ($user_email = $this->dereference($user_email)) {
                            foreach ($user_email as $name => $email) {
                                $recipients[] = $email;
                            }
                        }
                    }
                } else {
                    if ($to = $this->dereference($to)) {
                        foreach ($to as $name => $email) {
                            $recipients[] = $email;
                        }
                    }
                }
            }

            foreach ($data->cc->raw() as $cc) {
                if ($cc = $this->dereference($cc)) {
                    foreach ($cc as $email) {
                        $recipients[] = $email;
                    }
                }
            }

            foreach ($data->bcc->raw() as $bcc) {
                if ($bcc = $this->dereference($bcc)) {
                    foreach ($bcc as $email) {
                        $recipients[] = $email;
                    }
                }
            }

            if ($from = $data->from->raw()) {
                $fromEmail = $this->dereference($from);
                $fromName = $this->dereferenceName($from);
                if (! empty($fromEmail[0])) {
                    $mail->setFrom($fromEmail[0], $fromName);
                    $mail->setSender($fromEmail[0], $fromName);
                }
            }

            $content = $this->parse($data->content->none(), $data->is_html->int());
            $subject = $this->parse($data->subject->text());

            $mail->setSubject(strip_tags($subject));

            $mail->setHtml($content);

            if (! empty($data->pdf_page_attachment->text())) {
                $pageName = $data->pdf_page_attachment->text();
                $fileName = $pageName . ".pdf";
                $pdfContent = $this->getPDFAttachment($pageName);

                if ($pdfContent) {
                    $mail->addAttachment($pdfContent, $fileName, 'application/pdf');
                } else {
                    return false;
                }
            }

            $fileIds = [];
            if (! empty($data->file_attachment_field->text()) || ! empty($data->file_attachment_gal->text())) {
                $objectType = $data->object_type->text();
                if ($objectType !== 'trackeritem') {
                    Feedback::error(tr('Parameters file_attachment_field and file_attachment_gal can only be used with trackeritem'));
                    return false;
                }

                // get fileIds from Files field
                if (! empty($data->file_attachment_field->text())) {
                    // get the contents of indicated Files field, i.e., the file ids
                    $object_id = $data->object_id->int();
                    $trklib = TikiLib::lib('trk');
                    $info = $trklib->get_tracker_item($object_id);
                    $definition = Tracker_Definition::get($info['trackerId']);
                    $field = str_replace('tracker_field_', '', $data->file_attachment_field->word());
                    $fieldInfo = $definition->getField($field);
                    $handler = $definition->getFieldFactory()->getHandler($fieldInfo, $info);
                    $values = $handler->getFieldData();
                    $filesFieldValue = $values['value'];
                    $fileIds = explode(',', $filesFieldValue);
                }

                // get fileIds of all files in a Gallery
                if (! empty($data->file_attachment_gal->text())) {
                    $fileGal = TikiLib::lib('filegal');
                    $files = $fileGal->get_files_info_from_gallery_id($data->file_attachment_gal->text());
                    foreach ($files as $file) {
                        $fileId = $file['fileId'];
                        if (! in_array($fileId, $fileIds)) {
                            $fileIds[] = $fileId;
                        }
                    }
                }
            }
            foreach ($data->file_attachments->text() as $fileAttIds) {
                if (is_string($fileAttIds)) {
                    $fileAttIds = preg_split('/\s*,\s*/', trim($fileAttIds));
                }
                if (is_array($fileAttIds)) {
                    $fileIds = array_merge($fileIds, $fileAttIds);
                }
            }

            $fileIds = array_filter(array_unique($fileIds));
            foreach ($fileIds as $fileId) {
                $file = $this->getFileAttachment($fileId);
                if ($file) {
                    $mail->addAttachment($file['contents'], $file['filename'], $file['filetype']);
                } else {
                    return false;
                }
            }

            $isSent = $mail->send($recipients);
            return $isSent;
        } catch (Exception $e) {
            throw new Search_Action_Exception(tr('Error sending email: %0', $e->getMessage()));
        }
    }

    public function inputType(): string
    {
        return "text";
    }

    public function requiresInput(JitFilter $data)
    {
        return false;
    }

    public function requiresConfirm()
    {
        return false;
    }

    private function parse($content, $is_html = null)
    {
        $content = "~np~$content~/np~";

        $parserlib = TikiLib::lib('parser');

        $options = [
            'protect_email' => false,
            'absolute_links' => true,   // force links to be absolute
            'suppress_icons' => true,   // don't render plugin and section edit icons
        ];

        if ($is_html) {
            $options['is_html'] = true;
        }

        return trim($parserlib->parse_data($content, $options));
    }

    private function stripNp($content)
    {
        return str_replace(['~np~', '~/np~'], '', $content);
    }

    private function dereference($email_or_username)
    {
        if (empty($email_or_username)) {
            return [];
        }
        if (strpbrk($email_or_username, ',; ') !== false) {
            $list = preg_split('/\s*[,;\s]\s*/', $email_or_username);
            $res = [];
            foreach ($list as $email_or_username) {
                $res = array_merge($res, $this->dereference($email_or_username));
            }
            return array_filter($res);
        }
        $email_or_username = trim($this->stripNp($email_or_username));
        if (preg_match_all('/([^<]*?)<([^@>]+@[^>]+)>/', $email_or_username, $m)) {
            $emails = [];
            foreach ($m[0] as $key => $_) {
                $name = trim($m[1][$key], ",;\n\r\t ");
                $emails[$name] = $m[2][$key];
            }
            return $emails;
        } elseif (preg_match_all('/[^@]+@[^,;]+/', $email_or_username, $m)) {
            return array_map(function ($email) {
                return trim($email, ",;\n\r\t ");
            }, $m[0]);
        } elseif (str_contains($email_or_username, '@')) {
            return [$email_or_username];
        } else {
            $users = TikiLib::lib('trk')->parse_user_field($email_or_username);
            return array_filter(array_map(function ($username) {
                return TikiLib::lib('user')->get_user_email($username);
            }, $users));
        }
    }

    private function dereferenceName($email_or_username)
    {
        if (empty($email_or_username)) {
            return null;
        }
        $email_or_username = $this->stripNp($email_or_username);
        if (str_contains($email_or_username, '@')) {
            return null;
        } else {
            $users = TikiLib::lib('trk')->parse_user_field($email_or_username);
            if ($users) {
                return TikiLib::lib('user')->clean_user($users[0]);
            } else {
                return null;
            }
        }
    }

    private function getPDFAttachment($pageName)
    {
        if (! Perms::get('wiki page', $pageName)->view) {
            return [];
        }

        try {
            $exporter = new \Tiki\PagePdfExporter();
            $pageInfo = $exporter->validatePageAccess($pageName);
            $pageInfo['pageName'] = $pageName; // Ensure pageName is set
            return $exporter->generatePdfFromWikiPage($pageInfo);
        } catch (\Exception $e) {
            Feedback::error($e->getMessage());
            return false;
        }
    }

    private function getFileAttachment($fileId)
    {

        // TODO: keep in mind that list execute might be run with default admin user or without a user when running via cron.
        $file = \Tiki\FileGallery\File::id($fileId);
        if (! $file->exists() || ! Perms::get('file', $fileId)->download_files) {
            return;
        }

        $contents = $file->getContents();

        if (empty($contents)) {
            Feedback::error(sprintf(tra('File id %s cannot be found'), $fileId));
            return false;
        }
        $filetype = $file->filetype;
        $filename = $file->filename;

        return(['contents' => $contents, 'filetype' => $filetype, 'filename' => $filename]);
    }
}
