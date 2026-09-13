<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Tiki_Hm_Custom_Auth extends Hm_Auth
{
    public function check_credentials($user, $pass)
    {
        $userlib = TikiLib::lib('user');
        list($isvalid, $user) = $userlib->validate_user($user, $pass);
        return $isvalid;
    }
}
