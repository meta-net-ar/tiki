<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

class VirtualPath
{
    public function handle($fileOrPageId, $type = 'file')
    {
        global $prefs, $base_url;

        $filegallib = \TikiLib::lib('filegal');

        if ($type == 'wiki page') {
            return $base_url . 'tiki-webdav.php/Wiki Pages/' . $fileOrPageId;
        } else {
            return $base_url . 'tiki-webdav.php' . ($filegallib->get_full_virtual_path($fileOrPageId, $type));
        }
    }
}
