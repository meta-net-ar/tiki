<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_ContentSource_SurveySource implements Search_ContentSource_Interface
{
    private $db;

    public function __construct()
    {
        $this->db = TikiDb::get();
    }

    public function getDocuments()
    {
        global $prefs;

        $surveys = $this->db->table('tiki_surveys')->fetchColumn('surveyId', []);
        return $surveys;
    }

    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false
    {
        $survey = $this->db->table('tiki_surveys')->fetchRow([], ['surveyId' => $objectId]);
        if (! $survey) {
            return false;
        }

        $result = [
            'title' => $typeFactory->plaintext($survey['name']),
            'survey_description' => $typeFactory->plaintext($survey['description']),
            'survey_restriction' => $typeFactory->plaintext($survey['restriction']),
            'survey_taken' => $typeFactory->numeric($survey['taken']),
            'survey_last_taken' => $typeFactory->timestamp($survey['lastTaken']),
            'survey_created' => $typeFactory->timestamp($survey['created']),
            'survey_status' => $typeFactory->plaintext($survey['status']),

            'view_permission' => $typeFactory->identifier('tiki_p_take_survey'),
        ];
        return $result;
    }

    public function getProvidedFields(): array
    {
        return [
            'title',
            'survey_description',
            'survey_restriction',
            'survey_taken',
            'survey_last_taken',
            'survey_created',
            'survey_status',
            'view_permission',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
            'title' => true,
            'survey_description' => true,
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'title' => 'plaintext',
            'survey_description' => 'plaintext',
            'survey_restriction' => 'plaintext',
            'survey_taken' => 'numeric',
            'survey_last_taken' => 'timestamp',
            'survey_created' => 'timestamp',
            'survey_status' => 'plaintext',
            'view_permission' => 'identifier',
        ];
    }
}
