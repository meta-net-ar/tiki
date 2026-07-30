<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_schema_info()
{
    return [
        'name' => tra('Schema JSON-LD'),
        'documentation' => tra('Output a JSON-LD Schema markup block based on provided JSON and type'),
        'description' => tra('This plugin simply allows to add a JSON-LD schema markup in the page content, based on a provided JSON body and type.'),
        'prefs' => [ 'wikiplugin_schema' ],
        'params' => [
            'type' => [
                'required' => true,
                'name' => tra('Schema Type'),
                'filter' => 'text',
                'description' => tra('Schema.org type like Product, Article, Organization'),
            ],
            'json' => [
                'required' => true,
                'name' => tra('JSON Body'),
                'filter' => 'text',
                'description' => tra('JSON fragment for the schema body (without @context/@type)'),
            ],
        ],
    ];
}

function wikiplugin_schema($data, $params)
{

    $headerlib = TikiLib::lib('header');
    extract($params, EXTR_SKIP);

    if (empty($params['type']) || empty($params['json'])) {
        return '';
    }

    $body = json_decode($params['json'], true);
    if (! is_array($body)) {
        return '';
    }

    $schema = array_merge(
        ['@context' => 'https://schema.org', '@type' => $params['type']],
        $body
    );

    $jsonld = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $headerlib->add_rawhtml(
        "<script type=\"application/ld+json\">$jsonld</script>"
    );

    return '';
}
