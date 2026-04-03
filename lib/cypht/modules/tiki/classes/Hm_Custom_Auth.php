<?php
/**
 * Custom auth class. To use this, you must set the AUTH_TYPE environment variable to 'custom'.
 */
class Hm_Custom_Auth extends Hm_Auth {

    public function check_credentials($user, $pass)
    {
        $userlib = TikiLib::lib('user');
        list($isvalid, $user) = $userlib->validate_user($user, $pass);
        return $isvalid;
    }
}