<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class LdapLib extends TikiLib
{
    /**
     * Retrieve a specific field from a LDAP filter.
     *
     * @param str $dsn
     * @param str $filter
     * @param str $field
     * @param bool $all : return all records if true
     * @return str or array if $all = true
     */
    public function get_field($dsn, $filter, $field, $all = false)
    {
        // Try to connect
        $ldaplink = new PDO($dsn);
        $return = null;

        if (! $ldaplink) {
            // Wrong DSN
            return $return;
        }

        $ldaplink->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $stmt = $ldaplink->prepare($filter);
        $stmt->execute();
        $rs = $stmt->fetchAll();

        if ($rs) {
            foreach ($rs as $arr) {
                if (isset($arr[$field])) {
                    // Retrieve field
                    $return[] = $arr[$field];
                    if ($all === false) {
                        break;
                    }
                }
            }
        }

        // Disconnect
        $ldaplink = null;

        return ($all ? $return : array_shift($return)) ;
    }
}
