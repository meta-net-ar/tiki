<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * filegal_manager_url handler: Return the URL of the filegal manager, that goes to the list of filegalleries
 */
class FileGalManagerUrl extends Base
{
    public function handle($params, Template $template)
    {
        global $tikilib, $prefs;

        $return = 'tiki-upload_file.php?galleryId=' . $prefs['home_file_gallery'] . '&view=browse';

        if (! empty($params['area_id'])) {
            $return .= '&filegals_manager=' . $params['area_id'];
        }

        return $return;
    }
}
