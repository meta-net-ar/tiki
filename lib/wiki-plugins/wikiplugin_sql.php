<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\File\FileHelper;

function wikiplugin_sql_info()
{
    return [
        'name' => tra('SQL'),
        'documentation' => 'PluginSQL',
        'description' => tra('Query a MySQL database and display the results'),
        'prefs' => [ 'wikiplugin_sql' ],
        'body' => tr('The SQL query goes in the body. Example: ') . '<code>SELECT column1, column2 FROM table</code>',
        'validate' => 'all',
        'iconname' => 'database',
        'introduced' => 1,
        'extraparams' => true,
        'params' => [
            'db' => [
                'required' => true,
                'name' => tra('DSN Name'),
                'description' => tr('DSN name of the database being queried. The DSN name needs to first be defined at
                    %0', '<code>tiki-admin_dsn.php</code>'),
                'since' => '1',
            ],
            'raw' => [
                'required' => false,
                'name' => tra('Raw return'),
                'description' => tra('Return with table formatting (default) or raw data with no table formatting'),
                'since' => '11.0',
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Normal'), 'value' => '0'],
                    ['text' => tra('Raw'), 'value' => '1']
                ]
            ],
            'delim' => [
                'required' => false,
                'name' => tra('Delim'),
                'description' => tr('The delimiter to be used between data elements (sets %0)', '<code>raw=1</code>'),
                'since' => '11.0',
            ],
            'wikiparse' => [
                'required' => false,
                'name' => tra('Wiki Parse'),
                'description' => tr('Turn wiki parsing of select results on and off (default is on)'),
                'since' => '11.0',
                'default' => '1',
                'filter' => 'digits',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Off'), 'value' => '0'],
                    ['text' => tra('On'), 'value' => '1']
                ]
            ],
            'audit' => [
                'required' => false,
                'name' => tra('Audit'),
                'description' => tr('Create a log entry containing information about the SQL call.'),
                'since' => '21.2',
                'default' => '0',
            ],
            'audit_csv' => [
                'required' => false,
                'name' => tra('Audit CSV path'),
                'description' => tr('If set, a CSV file will be created or appended with information about the SQL call performed.'),
                'since' => '21.2',
                'filter' => 'text',
                'default' => '',
            ],
        ]
    ];
}

function wikiplugin_sql($data, $params)
{

    global $tikilib, $prefs, $user;
    extract($params, EXTR_SKIP);

    $perms = Perms::get([ 'type' => 'dsn', 'object' => $db ]);
    if (! $perms->dsn_query) {
        return tra('You do not have the permission that is needed to use this feature');
    }

    $bindvars = [];
    $data = html_entity_decode($data);
    if ($nb = preg_match_all("/\?/", $data, $out)) {
        foreach ($params as $key => $value) {
            if (preg_match('/^[0-9]*$/', $key)) {
                if (preg_match('/(.*)\[\$([^\]]*)\](.*)/', $value, $variable)) {
                    $originalVarValue = $varValue = '{{' . $variable[2] . '}}';
                    $varName = $variable[2];
                    TikiLib::lib('parser')->parse_wiki_argvariable($varValue);
                    if ($originalVarValue !== $varValue) {
                        $bindvars[$key] = $variable[1] . $varValue . $variable[3];
                    } else {
                        $bindvars[$key] = $variable[1] . $GLOBALS[$varName] . $variable[3];
                    }
                } elseif (str_starts_with($value, "$")) {
                    $varName = substr($value, 1);
                    $originalVarValue = $varValue = '{{' . $varName . '}}';
                    TikiLib::lib('parser')->parse_wiki_argvariable($varName);
                    if ($originalVarValue !== $varValue) {
                        $bindvars[$key] = $varValue;
                    } else {
                        $bindvars[$key] = $GLOBALS[$varName];
                    }
                } else {
                    $bindvars[$key] = $value;
                }
            }
        }
        if (count($bindvars) != $nb) {
            return tra('Missing db param');
        }
    }

    $ret = '';
    $dbmsg = '';

    if ($db = $tikilib->get_db_by_name($db)) {
        $result = $db->query($data, $bindvars);
    } else {
        return '~np~' . tra('Could not obtain valid DSN connection.') . '~/np~';
    }
    $setup_table = ! ((! is_null($raw) or ! is_null($delim)));
    $class = 'even';
    while ($result && $res = $result->fetchRow()) {
        if ($setup_table) {
            $ret .= "<table class='normal'><thead><tr>";

            $setup_table = false;

            foreach (array_keys($res) as $col) {
                $ret .= "<th>$col</th>";
            }

            $ret .= "</tr></thead>";
        }

        if (is_null($raw) && is_null($delim)) {
            $ret .= "<tr>";
        }

        if ($class == 'even') {
            $class = 'odd';
        } else {
            $class = 'even';
        }

        $first_field = true;
        foreach ($res as $val) {
            if (! is_null($delim) && ! $first_field) {
                $ret .= $delim;
            }

            if (! is_null($raw) || ! is_null($delim)) {
                $ret .= "$val";
            } else {
                $ret .= "<td class=\"$class\">$val</td>";
            }

            $first_field = false;
        }

        if (is_null($raw) && is_null($delim)) {
            $ret .= "<tr>";
        } elseif (isset($delim)) {
            $ret .= "<br>";
        }
    }

    if ($ret && is_null($raw)) {
        $ret .= "</table>";
    }
    if ($dbmsg) {
        $ret .= $dbmsg;
    }

    if (! empty($params['audit'])) {
        TikiLib::lib('logs')->add_log('wikiplugin_sql', "Page - " . $_GET['page'] . "\nParameters - " . print_r($bindvars, true)); // @phpstan-ignore disallowedFunctions.printR (returns bind vars as string for Tiki log entry, never prints)
    }

    if (! empty($params['audit_csv'])) {
        $headers = ['date', 'user', 'page', 'vars'];
        $contentRow[] = [
            $tikilib->date_format($prefs['short_date_format'] . ' ' . $prefs['long_time_format'], $tikilib->now),
            $user,
            $_GET['page'] ?? '',
            $bindvars
        ];

        if (! FileHelper::appendCSV($params['audit_csv'], $headers, $contentRow)) {
            Feedback::error(tr('Unable to create or open the file "%0" to log the SQL operation,', $params['audit_csv']));
        }
    }

    if ($wikiparse) {
        return $ret;
    } else {
        return '~np~' . $ret . '~/np~';
    }
}
