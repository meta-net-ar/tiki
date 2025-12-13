<?php

require_once __DIR__ . '/../../lib/core/Cache/NoCache.php';

function upgrade_20130513_convert_tracker_field_parameters_tiki($installer)
{
    // Using an old version of the definition could be critical here, so making sure
    // a fresh one is used
    $cachelib = TikiLib::lib('cache');
    $oldCache = $cachelib->replaceImplementation(new NoCache());

    $fields = $installer->fetchAll('SELECT fieldId, type, options FROM tiki_tracker_fields');
    $table = $installer->table('tiki_tracker_fields');

    foreach ($fields as $field) {
        $info = Tracker_Field_Factory::getFieldInfo($field['type']);
        $options = Tracker_Options::fromString($field['options'], $info);

        $table->update(
            [
                'options' => $options->serialize(),
            ],
            [
                'fieldId' => $field['fieldId']
            ]
        );
    }

    $cachelib->replaceImplementation($oldCache);
}
