<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (strpos($_SERVER['SCRIPT_NAME'], basename(__FILE__)) !== false) {
    header('location: index.php');
    exit;
}

/**
 * Class Table_Settings_TikiPermGroups
 *
 * Tablesorter settings for the table listing eponymous groups at tiki-objectpermissions.php
 *
 * @package Tiki
 * @subpackage Table
 * @uses Table_Settings_Standard
 */
class Table_Settings_TikiPermGroups extends Table_Settings_Standard
{
    protected $ts = [
        'ajax' => [
            'url' => [
                'file' => 'tiki-objectpermissions.php'
            ],
        ],
        'columns' => [
            '#checkbox' => [
                'sort' => [
                    'type' => false
                ],
                'filter' => [
                    'type' => false
                ],
                'resizable' => false,
                'priority' => 'critical'
            ],
            '#group' => [
                'sort' => [
                    'type' => true,
                    'dir' => 'asc',
                    'ajax' => 'groupName'
                ],
                'filter' => [
                    'type' => 'text',
                    'ajax' => 'find'
                ],
                'priority' => 'critical'
            ],
            '#desc' => [
                'sort' => [
                    'type' => false,
                ],
                'filter' => [
                    'type' => false
                ],
                'priority' => 6
            ],
        ]
    ];
}
