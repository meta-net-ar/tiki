<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_MailIn_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_mailin');

        $perms = Perms::get();
        if (! $perms->admin_mailin) {
            throw new Services_Exception_Denied(tr('Reserved for administrators.'));
        }
    }

    public function action_replace_account($input)
    {
        $mailinlib = TikiLib::lib('mailin');
        $accountId = $input->accountId->int();  // array('html' => $result);
        $trklib = TikiLib::lib('trk');
        $trackers = $trklib->list_trackers(0, -1, 'name_asc', '');
        $trackers = $trackers['list'];

        $util = new Services_Utilities();
        if ($util->isConfirmPost()) {
            $protocol = $input->protocol->word();
            $port = $input->port->int();
            $tls = $input->tls->int();
            // Adjust the port if TLS is enabled
            if ($tls && $port == 0) {
                $port = 995;
            }
            $account = [
                'protocol' => $protocol,
                'host' => $input->host->url(),
                'port' => $port,
                'username' => $input->username->text(),
                'pass' => $input->pass->none(),
            ];
            $result = $mailinlib->replace_mailin_account(
                $accountId,
                $input->account->text(),
                $protocol,
                $input->host->url(),
                $port,
                $input->username->text(),
                $input->pass->none(),
                $input->type->text(),
                $input->active->int() ? 'y' : 'n',
                $input->anonymous->int() ? 'y' : 'n',
                $input->admin->int() ? 'y' : 'n',
                $input->attachments->int() ? 'y' : 'n',
                $input->tracker_attachments->int() ? 'y' : 'n',
                $input->routing->int() ? 'y' : 'n',
                $input->article_topicId->int(),
                $input->article_type->text(),
                $input->discard_after->text(),
                $input->show_inlineImages->int() ? 'y' : 'n',
                $input->save_html->int() ? 'y' : 'n',
                $input->categoryId->int(),
                $input->namespace->pagename(),
                $input->respond_email->int() ? 'y' : 'n',
                $input->leave_email->int() ? 'y' : 'n',
                $input->galleryId->int(),
                $input->trackerId->int(),
                $input->preferences->text()
            );
            if ($result) {
                Feedback::success(tr('Account created or modified'));
                $account = $mailinlib->get_mailin_account($result);
                try {
                    if (! Tiki\MailIn\Account::test($account)) {
                        Feedback::error(tr('Failed to connect or authenticate with remote host.'));
                    }
                } catch (Exception $e) {
                    Feedback::error('<!--field[username]-->' . $e->getMessage());
                }
            } else {
                Feedback::error(tr('Account not created or modified'));
            }
        } else {
            $info = $mailinlib->get_mailin_account($accountId);
            $artlib = TikiLib::lib('art');
            return [
                'title' => $info ? tr('Modify Account') : tr('Create Account'),
                'types' => $artlib->list_types(),
                'topics' => $artlib->list_topics(),
                'galleries' => TikiLib::lib('filegal')->getSubGalleries(0, true, 'upload_files'),
                'trackers' => $trackers,
                'accountId' => $accountId,
                'mailinTypes' => $mailinlib->list_available_types(),
                'checkPackage' => $mailinlib->checkPackage(),
                'info' => $info ?: [
                    'account' => '',
                    'username' => '',
                    'pass' => '',
                    'protocol' => 'imap',
                    'host' => '',
                    'port' => 993,
                    'type' => 'wiki-put',
                    'active' => 'y',
                    'anonymous' => 'n',
                    'admin' => 'y',
                    'attachments' => 'y',
                    'routing' => 'y',
                    'article_topicId' => '',
                    'article_type' => '',
                    'show_inlineImages' => 'y',
                    'save_html' => 'y',
                    'categoryId' => 0,
                    'namespace' => '',
                    'respond_email' => 'y',
                    'leave_email' => 'y',
                    'galleryId' => '',
                    'trackerId' => '',
                    'preferences' => ''
                ],
            ];
        }
    }

    public function action_remove_account($input)
    {
        $mailinlib = TikiLib::lib('mailin');
        $accountId = $input->accountId->int();  // array('html' => $result);
        $info = $mailinlib->get_mailin_account($accountId);

        if (! $info) {
            throw new Services_Exception_NotFound();
        }

        $util = new Services_Utilities();
        if ($util->isConfirmPost()) {
            $result = $mailinlib->remove_mailin_account($accountId);
            if ($result && $result->numRows()) {
                Feedback::success(tr('Account removed'));
            } else {
                Feedback::error(tr('Account not removed'));
            }
        } else {
            return [
                'title' => tr('Remove Account'),
                'info' => $info,
            ];
        }
    }

    // take all permanent names of the fields from the selected tracker
    public function action_fields_account($input)
    {
        $result = $input->data();
        $content = $result['content'];
        $trklib = TikiLib::lib('trk');
        $util = new Services_Utilities();
        // get all tracker fields
        $fields = $trklib->list_tracker_fields($content, 0, -1, 'position_asc', '', true, '', '');
        $permNames = [];
        foreach ($fields['data'] as $field) {
            foreach ($field as $key => $value) {
                if ($key == 'type' && $value != 'q') {
                    $permNames [] = $field['permName'];
                }
            }
        }
        return $permNames;
    }
}
