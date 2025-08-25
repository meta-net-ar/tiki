<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Tiki calendar modules
 * @package modules
 * @subpackage tiki
 */

if (! defined('DEBUG_MODE')) {
    die();
}

require_once APP_PATH . 'modules/smtp/hm-mime-message.php';

/**
 * Parse message and check for a calendar invitation
 * @subpackage tiki/handler
 */
class Hm_Handler_check_calendar_invitations_imap extends Hm_Handler_Module
{
    public function process()
    {
        if ($this->get('msg_struct')) {
            get_calendar_part_imap($this->get('msg_struct'), $this);
        }
        if ($this->get('calendar_event_raw')) {
            $event = Tiki\SabreDav\Utilities::getDenormalizedData($this->get('calendar_event_raw'));
            $this->out('calendar_event', $event);
        } else {
            $event = null;
        }
        // get recipient from TO header
        $recipient = null;
        $headers = $this->get('msg_headers', []);
        foreach ($headers as $name => $value) {
            if (strtolower($name) == 'to') {
                $recipient = (string)$value;
            }
        }
        if (! empty($event['participants'])) {
            // try to find the recipient in the participants' list
            $found = false;
            foreach ($event['participants'] as $participant) {
                if ($participant['email'] == $recipient) {
                    $found = true;
                    break;
                }
            }
            if (! $found) {
                // might be an email sent to multiple people, try to match by imap mailbox email
                list($success, $form) = $this->process_form(['imap_server_id']);
                if ($success) {
                    $imap_details = Hm_IMAP_List::dump($form['imap_server_id']);
                    if (! empty($imap_details['user'])) {
                        foreach ($event['participants'] as $participant) {
                            if ($participant['email'] == $imap_details['user']) {
                                $found = true;
                                $recipient = $imap_details['user'];
                                break;
                            }
                        }
                    }
                }
            }
        }
        $this->out('recipient', $recipient);
    }
}

/**
 * Send a RSVP for an event
 * @subpackage tiki/handler
 */
class Hm_Handler_event_rsvp_action extends Hm_Handler_Module
{
    public $output;
    public $protected;
    public $config;
    public function process()
    {
        list($success, $form) = $this->process_form(['rsvp_action']);
        if (! $success) {
            return;
        }

        $recipient = $this->get('recipient');

        $calendardata = $this->get('calendar_event_raw');
        if (! $calendardata) {
            return;
        }

        // format answer
        $partstat = null;
        $action = "";
        switch ($form['rsvp_action']) {
            case 'accept':
                $partstat = 'ACCEPTED';
                $action = 'accepted';
                break;
            case 'maybe':
                $partstat = 'TENTATIVE';
                $action = 'tentatively accepted';
                break;
            case 'decline':
                $partstat = 'DECLINED';
                $action = 'declined';
                break;
        }

        // parse event and format response
        $vObject = Sabre\VObject\Reader::read($calendardata);
        $vObject->method  = 'REPLY';
        foreach ($vObject->getComponents() as $component) {
            if ($component->name !== 'VEVENT') {
                continue;
            }
            if (isset($component->ATTENDEE)) {
                foreach ($component->ATTENDEE as $attendee) {
                    $email = preg_replace("/MAILTO:\s*/i", "", (string)$attendee);
                    if ($email === $recipient) {
                        if ($partstat) {
                            $attendee['PARTSTAT'] = $partstat;
                            unset($attendee['RSVP']);
                        }
                        if (isset($this->request->post['rsvp_comment'])) {
                            $attendee['X-COMMENT'] = $this->request->post['rsvp_comment'];
                        }
                        $component->ATTENDEE = $attendee;
                    }
                }
            }
            $component->DTSTAMP = \DateTime::createFromFormat('U', time())->format('Ymd\THis\Z');
        }
        $event_response = $vObject->serialize();
        $vObject->destroy();

        // format reply, smtp server details and recipients
        list($to, $cc, $subject, $body, $in_reply_to) = format_reply_fields(
            $this->get('msg_text'),
            $this->get('msg_headers'),
            $this->get('msg_struct_current'),
            false,
            new Hm_Output_add_rsvp_actions($this->output, $this->protected),
            'reply'
        );

        // use specific text body for the reply
        $event = $this->get('calendar_event');
        $comment = $this->request->post['rsvp_comment'] ?? '';
        $body = "$from_name has $action the invitation to the following event: \n\n*{$event['name']}*";
        if ($comment) {
            $body .= "\n\nNote: $comment";
        }
        $body .= "\n\nWhen: " . TikiLib::lib('tiki')->get_long_datetime($event['start']) . " - " . TikiLib::lib('tiki')->get_long_datetime($event['end']) . "
        \n\nInvitees: " . implode(",\n", $event['attendees']);

        // add attachments
        $content = Hm_Crypt::ciphertext($event_response, Hm_Request_Key::generate());
        $filename = hash('sha512', $content);
        $filepath = rtrim($this->config->get('attachment_dir'), '/');
        if (@file_put_contents($filepath . '/' . $filename, $content)) {
            $file = [
                'filename' => $filepath . '/' . $filename,
                'basename' => $filename,
                'type' => 'text/calendar; method=REPLY',
                'name' => 'event.ics',
                'no_encoding' => true,
            ];
        } else {
            $file = null;
        }

        $profiles = $this->get('compose_profiles', []);
        $recip = get_primary_recipient($profiles, $this->get('msg_headers'), $this->get('smtp_servers', []));

        $result = tiki_send_email_through_cypht($to, $cc, $subject, $body, $in_reply_to, $file, $profiles, $this, $recip);

        if (! empty($file['filename'])) {
            @unlink($file['filename']);
        }

        if (! $result) {
            return;
        }

        // sync partstat for local calendar event
        global $prefs, $user;
        if ($prefs['feature_calendar'] === 'y') {
            $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
            if ($existing) {
                $event['calendarId'] = $existing['calendarId'];
                $event['calitemId'] = $existing['calitemId'];
                if (! empty($event['participants'])) {
                    foreach ($event['participants'] as &$role) {
                        if ($role['email'] === $recipient) {
                            if ($partstat) {
                                $role['partstat'] = $partstat;
                            }
                            if (isset($this->request->post['rsvp_comment'])) {
                                $role['comment'] = $this->request->post['rsvp_comment'];
                            }
                        }
                    }
                }
                $client = new \Tiki\SabreDav\CaldavClient();
                if ($existing['recurrenceId']) {
                    $rec = new CalRecurrence($existing['recurrenceId']);
                    if ($event['rec']) {
                        $event['rec']->setId($rec->getId());
                        $event['rec']->setUri($rec->getUri());
                        $rec = $event['rec'];
                    }
                    $rec->setUser($user);
                    $client->saveRecurringCalendarObject($rec);
                } else {
                    $client->saveCalendarObject($event);
                }
            }
        }
    }
}

/**
 * Add an event to Tiki calendar
 * @subpackage tiki/handler
 */
class Hm_Handler_add_to_calendar extends Hm_Handler_Module
{
    public function process()
    {
        global $prefs, $user;

        if ($prefs['feature_calendar'] !== 'y') {
            return;
        }

        list($success, $form) = $this->process_form(['calendar_id']);
        if (! $success) {
            Hm_Msgs::add("No calendar selected", "warning");
            return;
        }

        $calendar = TikiLib::lib('calendar')->get_calendar($form['calendar_id']);
        if (! $calendar) {
            Hm_Msgs::add("Selected calendar is unavailable", "danger");
            return;
        }

        $perms = Perms::get('calendar', $form['calendar_id']);
        if (! $perms->add_events) {
            Hm_Msgs::add("Insufficient permissions to create the event in the selected calendar", "danger");
            return;
        }

        $data = $this->get('calendar_event');
        $data['calendarId'] = $form['calendar_id'];
        $data['user'] = $user;

        $client = new \Tiki\SabreDav\CaldavClient();
        if ($data['rec']) {
            if (empty($data['priority'])) {
                $data['priority'] = 0;
            }
            if (is_null($data['status'])) {
                $data['status'] = 1;
            }
            if (empty($data['lang'])) {
                $data['lang'] = 'en';
            }
            if (empty($data['nlId'])) {
                $data['nlId'] = 0;
            }
            $rec = $data['rec'];
            $client->saveRecurringCalendarObject($rec);
        } else {
            $client->saveCalendarObject($data);
        }

        Hm_Msgs::add("Event created");
    }
}

/**
 * Update an event in Tiki calendar
 * @subpackage tiki/handler
 */
class Hm_Handler_update_in_calendar extends Hm_Handler_Module
{
    public function process()
    {
        global $prefs, $user;

        if ($prefs['feature_calendar'] !== 'y') {
            return;
        }

        $data = $this->get('calendar_event');
        $existing = TikiLib::lib('calendar')->find_by_uid(null, $data['uid']);

        if (! $existing) {
            Hm_Msgs::add("Existing event could not be found in your calendar", "danger");
            return;
        }

        $perms = Perms::get('event', $existing['calitemId']);
        if (! $perms->change_events) {
            Hm_Msgs::add("Insufficient permissions to update the event in the calendar", "danger");
            return;
        }

        $client = new \Tiki\SabreDav\CaldavClient();
        if ($data['rec']) {
            $rec = $data['rec'];
            $rec->setId($existing['recurrenceId']);
            $rec->setCalendarId($existing['calendarId']);
            $client->saveRecurringCalendarObject($rec);
        } else {
            $data['calitemId'] = $existing['calitemId'];
            $data['calendarId'] = $existing['calendarId'];
            $client->saveCalendarObject($data);
        }

        Hm_Msgs::add("Event updated");
    }
}

/**
 * Update participant status for a Tiki calendar event
 * @subpackage tiki/handler
 */
class Hm_Handler_update_participant_status extends Hm_Handler_Module
{
    public function process()
    {
        global $prefs;

        if ($prefs['feature_calendar'] !== 'y') {
            return;
        }

        $event = $this->get('calendar_event');
        $from = null;
        $headers = $this->get('msg_headers', []);
        foreach ($headers as $name => $value) {
            if (strtolower($name) == 'from') {
                $from = (string)$value;
            }
        }

        $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
        if ($existing) {
            if (! empty($event['participants'])) {
                foreach ($event['participants'] as &$role) {
                    if ($role['email'] === $from) {
                        TikiLib::lib('calendar')->update_partstat($existing['calitemId'], $role['username'], $role['partstat']);
                    }
                }
            }
        }
        Hm_Msgs::add("Information updated");
    }
}

/**
 * Remove an event from Tiki calendar when cancelation email is received
 * @subpackage tiki/handler
 */
class Hm_Handler_remove_from_calendar extends Hm_Handler_Module
{
    public function process()
    {
        global $prefs, $user;

        if ($prefs['feature_calendar'] !== 'y') {
            return;
        }

        $event = $this->get('calendar_event');
        $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
        if ($existing) {
            $client = new \Tiki\SabreDav\CaldavClient();
            $client->deleteCalendarObject($existing);
        }

        Hm_Msgs::add("Event removed");
    }
}

/**
 * Show RSVP buttons if message contains a calendar invitation
 * @subpackage tiki/output
 */
class Hm_Output_add_rsvp_actions extends Hm_Output_Module
{
    protected function output()
    {
        global $prefs, $user;
        $method = $this->get('calendar_method');
        $event = $this->get('calendar_event');
        $headers = $this->get('msg_headers');
        if (! empty($event)) {
            $res = '';
            $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex header_event_dtstart"><div class="col-md-2"><span class="text-muted">%s</span></div><div class="col-md-10 col-12">%s</div></div>', tr('Event start'), TikiLib::lib('tiki')->get_long_datetime($event['start']));
            $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex header_event_dtend"><div class="col-md-2"><span class="text-muted">%s</span></div><div class="col-md-10 col-12">%s</div></div>', tr('Event end'), tr('Event end'), TikiLib::lib('tiki')->get_long_datetime($event['end']));
            $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex header_event_organizer"><div class="col-md-2"><span class="text-muted">%s</span></div><div class="col-md-10 col-12">%s</div></div>', tr('Organizer'), tr('Organizer'), implode(", ", $event['real_organizers']));
            if ($prefs['feature_calendar'] == 'y' && $method != 'CANCEL') {
                $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
                if (! $existing) {
                    $options = ['<option></option>'];
                    $calendars = TikiLib::lib('calendar')->list_calendars();
                    $calendars['data'] = Perms::filter([ 'type' => 'calendar' ], 'object', $calendars['data'], [ 'object' => 'calendarId' ], 'add_events');
                    foreach ($calendars['data'] as $row) {
                        $options[] = "<option value='" . $row['calendarId'] . "'>" . $row['name'] . "</option>";
                    }
                    $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex header_event_addtocal"><div class="col-md-2"><span class="text-muted">%s</span></div><div class="col-md-10 col-12"><select name="calendarId" class="event_calendar_select">%s</select></div></div>', tr('Add to calendar'), implode('', $options));
                    $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex" id="event_calendar_to_rsvp" style="display:none"><div class="col-md-12"><span class="text-muted">%s</span><span class="text-danger">*</span><select class="event_calendar_select_rsvp">%s</select></div></div>', tr('Calendar'), implode('', array_slice($options, 1)));
                } else {
                    $existing = TikiLib::lib('calendar')->get_item($existing['calitemId']);
                    $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex"><div class="col-md-2"><span class="text-muted">%s</span></div><div class="col-md-10 col-12 header_links"><a href="tiki-calendar.php?calitemId=%s" data-external="1">%s</a></div></div>', tr('Event'), $existing['calitemId'], tr('View event in my calendar'));
                    foreach (['start', 'end', 'name', 'description', 'participants'] as $field) {
                        $val1 = $existing[$field];
                        $val2 = $event[$field];
                        if ($field == 'participants') {
                            $val1 = array_map(function ($p) {
                                return $p['email'];
                            }, $val1);
                            sort($val1);
                            $val2 = array_map(function ($p) {
                                return $p['email'];
                            }, $val2);
                            sort($val2);
                        }
                        if ($val1 != $val2) {
                            $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex header_event_addtocal"><div class="col-md-2"><span class="text-muted">&nbsp;</span></div><div class="col-md-10 col-12 header_links"><a href="#" class="event_calendar_update">%s</a></div></div>', tr('Update in my calendar'));
                            break;
                        }
                    }
                }
            }
            if ($method == 'REQUEST') {
                $partstat = null;
                $comment = '';
                $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
                if ($existing) {
                    $existing = TikiLib::lib('calendar')->get_item($existing['calitemId']);
                }
                if ($existing && ! empty($existing['participants'])) {
                    foreach ($existing['participants'] as $role) {
                        if ($role['email'] == $this->get('recipient')) {
                            $partstat = $role['partstat'];
                            $comment = $role['comment'] ?? '';
                        }
                    }
                }
                $res .= '<div class="row g-0 py-0 py-sm-1 small_header d-flex"><div class="col-md-2"><button class="btn btn-light rsvp-button" data-value="' . $partstat . '" data-comment="' . $comment . '">' . tr('RSVP') . '</button></div><div class="col-md-10 col-12"></div></div>';
            }
            if ($prefs['feature_calendar'] == 'y' && $method == 'REPLY') {
                $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);

                if ($existing) {
                    $participants = TikiLib::lib('calendar')->get_participant_by_event_uid($event['uid']);
                    $participant_status_updated = true;

                    if (! empty($existing['participants'])) {
                        foreach ($event['participants'] as $role) {
                            $idx = array_search($role['username'], array_column($participants, 'username'));
                            if (! $idx) {
                                $idx = array_search($role['email'], array_column($participants, 'username'));
                            }

                            if ($idx && $role['partstat'] != $participants[$idx]['partstat']) {
                                $participant_status_updated = false;
                                break;
                            }
                        }
                    }

                    $event_update_participant_class = 'event_update_participant_status';
                    $event_update_participant_text = 'Update participant status';

                    if ($participant_status_updated) {
                        $event_update_participant_class = 'event_participant_status_updated';
                        $event_update_participant_text = 'Participant status updated';
                    }
                    $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex"><div class="col-md-2 header_links"><a href="#" class="' . $event_update_participant_class . '">%s</a></div><div class="col-md-10 col-12"></div></div>', tr($event_update_participant_text));
                }
            }
            if ($prefs['feature_calendar'] == 'y' && $method == 'CANCEL') {
                $existing = TikiLib::lib('calendar')->find_by_uid(null, $event['uid']);
                if ($existing) {
                    $res .= sprintf('<div class="row g-0 py-0 py-sm-1 small_header d-flex"><div class="col-md-2 header_links"><a href="#" class="event_remove_from_calendar">%s</a></div><div class="col-md-10 col-12"></div></div>', tr('Remove from calendar'));
                }
            }
            $headers = preg_replace("#<div[^>]*event_calendar_section[^>]*>.*?</div>#", $res . "\\0", $headers);
        }
        $this->out('msg_headers', $headers, false);
    }
}

/**
 * Search imap message structure for text/calendar parts
 * @subpackage tiki/functions
 * @param array $struct message structure
 * @param object $mod Hm_Handler_Module
 * @return string
 */
if (! hm_exists('get_calendar_part_imap')) {
    function get_calendar_part_imap($struct, $mod)
    {
        $event = $method = null;
        $part = false;
        foreach ($struct as $id => $vals) {
            if (is_array($vals) && isset($vals['type'])) {
                if ($vals['type'] . '/' . $vals['subtype'] == 'text/calendar') {
                    $part = $id;
                    $method = $vals['attributes']['method'];
                }
                if (isset($vals['subs'])) {
                    return get_calendar_part_imap($vals['subs'], $mod);
                }
            } else {
                if (is_array($vals) && count($vals) == 1 && isset($vals['subs'])) {
                    return get_calendar_part_imap($vals['subs'], $mod);
                }
            }
        }
        if (! $part) {
            return;
        }
        list($success, $form) = $mod->process_form(['imap_server_id', 'imap_msg_uid', 'folder']);
        if ($success) {
            $mailbox = Hm_IMAP_List::get_connected_mailbox($form['imap_server_id'], $mod->cache);
            if ($mailbox->authed()) {
                $event = $mailbox->get_structured_message(hex2bin($form['folder']), $form['imap_msg_uid'], $part, true)[2];
            }
        }
        $mod->out('calendar_method', $method);
        $mod->out('calendar_event_raw', $event);
    }
}
