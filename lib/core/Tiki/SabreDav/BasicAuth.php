<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\SabreDav;

use Sabre\DAV\Auth\Backend\AbstractBasic;
use TikiLib;
use Perms;
use Perms_Context;

class BasicAuth extends AbstractBasic
{
    protected function validateUserPass($username, $password)
    {
        global $user;

        if (! empty($username) && strtolower($username) === 'anonymous') {
            $user = 'Anonymous';
            $isvalid = true;
        } else {
            list($isvalid, $user) = TikiLib::lib('user')->validate_user($username, $password);

            if ($isvalid && ! TikiLib::lib('user')->canLogin($username)) {
                $isvalid = false;
            }
        }

        if ($isvalid) {
          // enforce permissions of the incoming user and their groups
            $permContext = new Perms_Context($user, false);
            $permContext->activatePermanently();
        }

        return $isvalid;
    }
}
