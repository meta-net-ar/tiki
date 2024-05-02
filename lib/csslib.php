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

class cssLib extends TikiLib
{
    public function browse_css($path)
    {
        if (! is_file($path)) {
            return ['error' => "No such file : $path"];
        }

        $meat = implode('', file($path));

        $find[0] = '/\}/';
        $repl[0] = "\n}\n";

        $find[1] = '/\{/';
        $repl[1] = "\n{\n";

        $find[2] = '/\/\*/';
        $repl[2] = "\n/*\n";

        $find[3] = '/\*\//';
        $repl[3] = "\n*/\n";

        $find[4] = '/;/';
        $repl[4] = ";\n";

        $find[5] = '/(W|w)hite/';
        $repl[5] = '#FFFFFF';

        $find[6] = '/(B|b)lack/';
        $repl[6] = '#000000';

        $res = preg_replace($find, $repl, $meat);
        return [
            'error' => '',
            'content' => explode("\n", $res)
        ];
    }

    public function parse_css($data)
    {
        $back = [];

        $index = 0;
        $type = '';

        foreach ($data as $line) {
            $line = trim($line);

            if ($line) {
                if (($type != 'comment') and ($line == '/*')) {
                    $type = 'comment';

                    $index++;
                    $back["$index"]['comment'] = '';
                    $back["$index"]['items'] = [];
                    $back["$index"]['attributes'] = [];
                } elseif (($type == 'comment') and ($line == '*/')) {
                    $type = '';
                } elseif ($type == 'comment') {
                    $back["$index"]['comment'] .= "$line\n";
                } elseif (($type == 'items') and ($line == '{')) {
                    $type = 'attributes';
                } elseif ($type == 'items') {
                    $li = explode(',', $line);

                    foreach ($li as $l) {
                        $l = trim($l);

                        if ($l) {
                            $back["$index"]['items'][] = $l;
                        }
                    }
                } elseif (($type == 'attributes') and ($line == '}')) {
                    $type = '';

                    $index++;
                    $back["$index"]['comment'] = '';
                    $back["$index"]['items'] = [];
                    $back["$index"]['attributes'] = [];
                } elseif ($type == 'attributes') {
                    $parts = explode(':', str_replace(';', '', $line));

                    if (isset($parts[0]) && isset($parts[1])) {
                        $obj = trim($parts[0]);

                        $back["$index"]['attributes']["$obj"] = trim($parts[1]);
                    }
                } else {
                    $li = explode(',', $line);

                    foreach ($li as $l) {
                        $l = trim($l);

                        if ($l) {
                            $back["$index"]['items'][] = $l;
                        }
                    }

                    $type = 'items';
                }

                $back['content'] = $line;
            }
        }

        return $back;
    }
}
