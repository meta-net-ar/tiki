<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_ContentSource_CalendarItemSource implements Search_ContentSource_Interface, Tiki_Profile_Writer_ReferenceProvider
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getReferenceMap()
    {
        return [
            'calendar_id' => 'calendar',
        ];
    }

    public function getDocuments()
    {
        $files = $this->db->table('tiki_calendar_items');
        return $files->fetchColumn(
            'calitemId',
            [],
            -1,
            -1,
            'ASC'
        );
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        $lib = TikiLib::lib('calendar');

        $item = $lib->get_item($objectId);

        if (! $item) {
            return false;
        }

        $allday = (bool) $item['allday'];

        if ($item['status'] == 0) {
            $status_text = tr('Tentative');
        } elseif ($item['status'] == 1) {
            $status_text = tr('Confirmed');
        } elseif ($item['status'] == 2) {
            $status_text = tr('Cancelled');
        }

        $trackerItems = $lib->getAttachedTrackerItems($objectId);

        $data = [
            'title' => $typeFactory->sortable($item['name']),
            'language' => $typeFactory->identifier(empty($item['lang']) ? 'unknown' : $item['lang']),
            'creation_date' => $typeFactory->timestamp($item['created']),
            'modification_date' => $typeFactory->timestamp($item['lastModif']),
            'contributors' => $typeFactory->multivalue([$item['user']]),
            // Index object of all Participants including username, email, role, partstat. This is array of associative arrays, we keep that for the future.
            'participants' => $typeFactory->multivalue([$item['participants']]),
            // Index just participant emails. The value can be a username and not an email if 'login_is_email' pref is enabled.
            'participant_emails' => $typeFactory->multivalue(array_column($item['participants'], 'email')),
            'description' => $typeFactory->plaintext($item['description']),
            'date' => $typeFactory->timestamp($item['start'], $allday),

            'calendar_id' => $typeFactory->identifier($item['calendarId']),
            'start_date' => $typeFactory->timestamp($item['start'], $allday),
            'end_date' => $typeFactory->timestamp($item['end'], $allday),
            'priority' => $typeFactory->numeric($item['priority']),
            'status' => $typeFactory->numeric($item['status']),
            'status_text' => $typeFactory->identifier($status_text),
            'url' => $typeFactory->identifier($item['url']),
            'recurrence_id' => $typeFactory->identifier($item['recurrenceId']),
            // TODO index recurrences too here?

            'view_permission' => $typeFactory->identifier('tiki_p_view_events'),
            'parent_object_type' => $typeFactory->identifier('calendar'),
            'parent_object_id' => $typeFactory->identifier($item['calendarId']),

            'trackeritems' => $typeFactory->multivalue($trackerItems),

        ];

        return $data;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'language',
            'creation_date',
            'modification_date',
            'date',
            'contributors',
            'participants',
            'participant_emails',
            'description',

            'calendar_id',
            'start_date',
            'end_date',
            'priority',
            'status',
            'status_text',
            'url',
            'recurrence_id',

            'view_permission',
            'parent_object_id',
            'parent_object_type',

            'trackeritems',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'sortable',
            'language' => 'identifier',
            'creation_date' => 'timestamp',
            'modification_date' => 'timestamp',
            'date' => 'timestamp',
            'contributors' => 'multivalue',
            'participants' => 'multivalue',
            'participant_emails' => 'multivalue',
            'description' => 'plaintext',

            'calendar_id' => 'identifier',
            'start_date' => 'timestamp',
            'end_date' => 'timestamp',
            'priority' => 'numeric',
            'status' => 'numeric',
            'status_text' => 'identifier',
            'url' => 'identifier',
            'recurrence_id' => 'identifier',

            'view_permission' => 'identifier',
            'parent_object_id' => 'identifier',
            'parent_object_type' => 'identifier',

            'trackeritems' => 'multivalue',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'description' => true,
            'date' => true,
        ];
    }
}
