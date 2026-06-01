<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * DB Upgrade: Tracker Configuration History
 * Task item-161718
 *
 * Creates tiki_tracker_config_history and seeds tiki_actionlog_conf
 * so that existing add_action() calls for 'tracker' objectType are not silently dropped.
 */

$table = $installer->table('tiki_tracker_config_history');
$table->column('historyId',   'i_auto', [], 'pk');
$table->column('objectType',  'enum',   ['values' => ['tracker', 'trackerfield']], 'nn');
$table->column('objectId',    'i',      [], 'nn');
$table->column('trackerId',   'i',      [], 'nn');
$table->column('action',      'char',   ['len' => 40], 'nn');
$table->column('changedBy',   'char',   ['len' => 200], 'nn d:');
$table->column('changedAt',   'i',      [], 'nn');
$table->column('ip',          'char',   ['len' => 39], '');
$table->column('before',      'mediumtext', [], '');
$table->column('after',       'mediumtext', [], '');
$table->column('diff',        'mediumtext', [], '');
$table->index('object_idx',   'ix', ['objectType', 'objectId', 'changedAt']);
$table->index('tracker_idx',  'ix', ['trackerId', 'changedAt']);
$table->index('user_idx',     'ix', ['changedBy', 'changedAt']);
$table->insert();

// Seed actionlog_conf so existing add_action() calls for 'tracker' are honoured
$installer->query(
    "INSERT IGNORE INTO `tiki_actionlog_conf` (`action`, `objectType`, `status`)
     VALUES
     ('Created', 'tracker', 'y'),
     ('Updated', 'tracker', 'y'),
     ('Deleted', 'tracker', 'y')"
);
