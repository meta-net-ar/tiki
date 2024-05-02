<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_GlobalSource_IndexNameSource implements Search_GlobalSource_Interface
{
    public function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = [])
    {
        global $prefs;
        return [
            'index_name' => $typeFactory->identifier($prefs['unified_manticore_index_rebuilding']),
        ];
    }

    public function getProvidedFields(): array
    {
        return [
            'index_name',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'index_name' => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
        ];
    }
}
