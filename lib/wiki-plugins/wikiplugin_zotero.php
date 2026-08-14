<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_zotero_info()
{
    return [
        'name' => tra('Zotero Citation'),
        'description' => tra('Retrieves and includes a Zotero reference in the page.'),
        'prefs' => ['zotero_enabled', 'wikiplugin_zotero', 'wikiplugin_footnote'],
        'iconname' => 'bookmark',
        'introduced' => 7,
        'params' => [
            'key' => [
                'name' => tra('Reference Key'),
                'description' => tra('Unique reference for the group associated to the site. Can be retrieved from the
                    Zotero Bibliography module.'),
                'required' => false,
                'since' => '7.0',
                'filter' => 'alnum',
            ],
            'tag' => [
                'name' => tra('Reference Tag'),
                'description' => tra('Uses the first result using the specified tag. Useful when the tag mechanism is
                    coerced into creating unique human memorizable keys.'),
                'since' => '7.0',
                'required' => false,
                'filter' => 'alnum',
            ],
            'note' => [
                'name' => tra('Note'),
                'description' => tra('Append a note to the reference for additional information, like page numbers or
                    other sub-references.'),
                'since' => '7.0',
                'required' => false,
                'filter' => 'text',
            ],
        ],
    ];
}

function wikiplugin_zotero($data, $params)
{
    $zotero = TikiLib::lib('zotero');
    $cachelib = TikiLib::lib('cache');

    $tag = null;
    $key = null;
    $note = null;

    if (! is_null($params['key'])) {
        $key = $params['key'];
        $cacheKey = "key_$key";
    } elseif (! is_null($params['tag'])) {
        $tag = $params['tag'];
        $cacheKey = "tag_$tag";
    } else {
        return WikiParser_PluginOutput::argumentError(['key', 'tag']);
    }

    if (! is_null($params['note'])) {
        $note = $params['note'];
    }

    if ($cached = $cachelib->getCached($cacheKey, 'zotero')) {
        $info = unserialize($cached);
    } else {
        if ($key) {
            $info = $zotero->get_entry($key);
        } else {
            $info = $zotero->get_first_entry($tag);
        }

        $cachelib->cacheItem($cacheKey, serialize($info), 'zotero');
    }

    // Get the title from Zotero data
    // Some Zotero entries don't have a direct 'title' field (e.g., notes or attachments)
    // In these cases, extract the title from the content field to display something meaningful
    $title = isset($info['title']) ? $info['title'] : '';

    if (empty($title)) {
        // Extract title from HTML content
        // Replace block-level HTML tags with newlines to preserve structure
        $content = $info['content'];
        $content = preg_replace('/<\/(p|div|h[1-6]|li|tr|br)>/i', "\n", $content);

        // Strip remaining HTML tags and decode entities
        $textContent = strip_tags($content);
        $textContent = html_entity_decode($textContent, ENT_QUOTES, 'UTF-8');
        $textContent = trim($textContent);

        // Use first non-empty line as title
        $lines = array_filter(array_map('trim', explode("\n", $textContent)));
        $title = ! empty($lines) ? reset($lines) : '';
    }

    // Combine title with note if provided
    $footnoteContent = $title;
    if (! empty($note)) {
        $footnoteContent .= ' - ' . $note;
    }

    // Return the footnote with the title
    return "{FOOTNOTE()}{$footnoteContent}{FOOTNOTE}";
}
