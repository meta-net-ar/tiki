<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    die('This script may only be included.');
}

use Tiki\Sections;

/**
 * Register callback for section changes
 *
 * When the application section changes, this callback processes freetag operations.
 * The global declarations ensure the callback accesses the current/updated values
 * of the required variables.
 *
 * Used in this callback:
 * - $sections: check if section exists
 * - $freetaglib: tag/untag objects, retrieve tags
 * - $tiki_p_freetags_tag, $tiki_p_admin, $tiki_p_unassign_freetags: permission checks
 * - $prefs: check antibot and multilingual settings
 * - $smarty: assign tags and error messages to template
 */
Sections::onSectionChange(function ($section) {
    global $sections, $freetaglib, $tiki_p_freetags_tag, $prefs, $smarty;
    if (isset($sections[$section])) {
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
});
