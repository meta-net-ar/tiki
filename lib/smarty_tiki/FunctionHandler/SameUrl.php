<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

// Do NOT change this plugin under any circunstances!
class SameUrl extends Base
{
    public function handle($params, Template $template)
    {
        global $sameurl_elements;
        $data = $_SERVER['SCRIPT_NAME'];
        $first = true;
        $sets = [];

        foreach ($params as $name => $val) {
            if (isset($_REQUEST[$name])) {
                $_REQUEST[$name] = $val;
            } else {
                if (in_array($name, $sameurl_elements) && ! is_array($name) && ! is_array($val)) {
                    if (! in_array($name, $sets)) {
                        if ($first) {
                            $first = false;
                            $sep = '?';
                        } else {
                            $sep = '&amp;';
                        }
                        $data .= $sep . urlencode($name) . '=' . urlencode($val);
                        $sets[] = $name;
                    }
                }
            }
        }

        foreach ($_REQUEST as $name => $val) {
            if (isset($$name)) {
                $val = $$name;
            }
            if (in_array($name, $sameurl_elements) && ! is_array($name) && ! is_array($val)) {
                if (! in_array($name, $sets)) {
                    if ($first) {
                        $first = false;
                        $sep = '?';
                    } else {
                        $sep = '&amp;';
                    }

                    $data .= $sep . urlencode($name) . '=' . urlencode($val);
                    $sets[] = $name;
                }
            }
        }
        print($data);
    }
}
