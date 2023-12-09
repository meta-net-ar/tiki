<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class MemUsage extends Base
{
    public function handle($params, Template $template)
    {
        if (function_exists('memory_get_peak_usage')) {
            // PHP 5.2+
            $memusage = memory_get_peak_usage();
        } elseif (function_exists('memory_get_usage')) {
            //PHP 4 >= 4.3.2, PHP 5
            $memusage = memory_get_usage();
        } else {
            $memusage = 0;
        }

        if ($memusage > 0) {
            $memunit = "B";
            if ($memusage > 1024) {
                $memusage = $memusage / 1024;
                $memunit = "kB";
            }
            if ($memusage > 1024) {
                $memusage = $memusage / 1024;
                $memunit = "MB";
            }
            if ($memusage > 1024) {
                $memusage = $memusage / 1024;
                $memunit = "GB";
            }
            print(number_format($memusage, 2) . $memunit);
        } else {
            print (tra("Unknown"));
        }
    }
}
