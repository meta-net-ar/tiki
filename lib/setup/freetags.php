<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

if (isset($section) and isset($sections[$section])) {
    $freetaglib = TikiLib::lib('freetag');
    $freetaglib->handleCurrentObjectTagRequest();

    $tags = [];
    if ($object = current_object()) {
        $objectTags = $freetaglib->get_tags_on_object($object['object'], $object['type']);
        if ($objectTags) {
            $tags = $objectTags['data'];
        }
    }
    $smarty->assign('tags', $tags);

    if ($tiki_p_freetags_tag == 'y' && $prefs['freetags_multilingual'] == 'y') {
        $ft_lang = null;
        $ft_multi = false;
        if (! empty($tags['data'])) {
            foreach ($tags['data'] as $row) {
                $l = $row['lang'];

                if (! $l) {
                    continue;
                }

                if (! $ft_lang) {
                    $ft_lang = $l;
                } elseif ($ft_lang != $l) {
                    $ft_multi = true;
                    break;
                }
            }
        }

        if ($ft_multi && $object = current_object()) {
            $smarty->assign(
                'freetags_mixed_lang',
                'tiki-freetag_translate.php?objType=' . urlencode($object['type']) . '&objId=' . urlencode($object['object'])
            );
        }
    }
}
