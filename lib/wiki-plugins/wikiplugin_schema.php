<?php
// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_schema_info() {
    return array(
        'name' => tra('Schema JSON-LD'),
        'documentation' => tra('Outputs Schema.org JSON-LD based on parameters'),
        'description' => tra('Generates a script type="application/ld+json" block'),
        'prefs' => array( 'wikiplugin_schema' ),
        'format' => 'html',
        'tag' => ['seo','basic'],
        'params' => array(
            'type' => array(
                'required' => true,
                'name' => tra('Schema Type'),
                'filter' => 'text',
                'description' => tra('Schema.org type like Product, Article, Organization'),
            )
        ),
    );
}

function insert_schema_path(&$array, $path, $value)
{
    $segments = explode('.', $path);
    $current = &$array;

    foreach ($segments as $i => $segment) {

        if (preg_match('/^(.+)\[(.*?)\]$/', $segment, $m)) {

            $key = $m[1];
            $index = $m[2];

            if (!isset($current[$key])) {
                $current[$key] = [];
            }

            if ($index === '') {

                if ($i === count($segments) - 1) {
                    $current[$key][] = $value;
                    return;
                }

                $current[$key][] = [];
                $current = &$current[$key][array_key_last($current[$key])];

            } else {

                if (!isset($current[$key][$index])) {
                    $current[$key][$index] = [];
                }

                if ($i === count($segments) - 1) {
                    $current[$key][$index] = $value;
                    return;
                }

                $current = &$current[$key][$index];
            }

        } else {

            if ($i === count($segments) - 1) {
                $current[$segment] = $value;
                return;
            }

            if (!isset($current[$segment])) {
                $current[$segment] = [];
            }

            $current = &$current[$segment];
        }
    }
}

function wikiplugin_schema($data, $params, $context)
{
    $headerlib = TikiLib::lib('header');

    if (empty($params['type'])) {
        return '';
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => $params['type']
    ];

    $lines = preg_split('/\r?\n/', $data);

    foreach ($lines as $line) {

        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // split first word (key) and the rest (value)
        if (!preg_match('/^([^\s]+)\s+(.+)$/', $line, $m)) {
            continue;
        }

        $path = trim($m[1]);
        $value = trim($m[2]);

        // remove quotes
        if (preg_match('/^"(.*)"$/', $value, $v)) {
            $value = $v[1];
        }

        // ignore empty values
        if ($value === '' || $value === null) {
            continue;
        }

        // boolean detection
        if ($value === 'true') {
            $value = true;
        } elseif ($value === 'false') {
            $value = false;
        }

        // numeric detection
        elseif (is_numeric($value)) {
            $value = $value + 0;
        }

        insert_schema_path($schema, $path, $value);
    }

    $jsonld = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $headerlib->add_rawhtml(
        "<script type=\"application/ld+json\">$jsonld</script>"
    );

    return '';
}