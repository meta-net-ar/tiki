<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

interface Tracker_Field_EnumerableInterface extends Tracker_Field_Interface
{
    /**
     * For trackerfields having finite enumerable values (Dropdown, Users, etc.)
     * return the values the user can set a tracker item to.  Typically used to 
     * show dropdowns, configure query filters, etc.
     * 
     * @return ['value' => 'labelForValue']
     */
    public function getPossibleItemValues();
}
