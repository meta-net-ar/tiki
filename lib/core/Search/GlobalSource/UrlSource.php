<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_GlobalSource_UrlSource implements Search_GlobalSource_Interface
{
    public function getData($objectType, $objectId, Search_Type_Factory_Interface $typeFactory, array $data = [])
    {
        if (isset($data['url'])) {
            return false;
        }

        $url = smarty_modifier_sefurl($objectId, $objectType);
        return [
            'url' => $typeFactory->identifier($url),
        ];
    }

    public function getProvidedFields(): array
    {
        return [
            'url',
        ];
    }

    public function getProvidedFieldTypes(): array
    {
        return [
            'url' => 'identifier',
        ];
    }

    public function getGlobalFields(): array
    {
        return [
        ];
    }
}
