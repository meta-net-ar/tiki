<?php

use Tiki\Installer\Installer;

/**
 * Move the Manticore morphology preference into the JSON settings preference.
 *
 * @param Installer $installer
 */
function upgrade_20260926_manticore_morphology_pref_to_json_tiki(Installer $installer)
{
    $legacyMorphology = $installer->getOne(
        'SELECT `value` FROM `tiki_preferences` WHERE `name` = ?',
        ['unified_manticore_morphology']
    );
    if ($legacyMorphology) {
        TikiLib::lib('tiki')->set_preference(
            'unified_manticore_settings',
            json_encode(['morphology' => $legacyMorphology])
        );
    }

    $installer->query(
        'DELETE FROM `tiki_preferences` WHERE `name` = ?',
        ['unified_manticore_morphology']
    );
}
