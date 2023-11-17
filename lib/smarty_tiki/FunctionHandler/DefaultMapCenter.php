<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

class DefaultMapCenter extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $geolib = \TikiLib::lib('geo');
        $coords = $geolib->parse_coordinates($prefs['gmap_defaultx'] . ',' . $prefs['gmap_defaulty'] . ',' . $prefs['gmap_defaultz']);
        $center = $geolib->build_location_string($coords);
        return smarty_modifier_escape($center);
    }
}
