<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

/**
 *
 */
class UserMenuLib extends TikiLib
{
    /**
     * @param $user
     */
    public function add_bk($user)
    {
        $query = 'select tubu.`name`,`url`' .
                        ' from `tiki_user_bookmarks_urls` tubu, `tiki_user_bookmarks_folders` tubf' .
                        ' where tubu.`folderId`=tubf.`folderId` and tubf.`parentId`=? and tubu.`user`=?';

        $result = $this->query($query, [0, $user]);
        $start = $this->get_max_position($user) + 1;

        while ($res = $result->fetchRow()) {
            // Check for duplicate URL
            if (! $this->getOne('select count(*) from `tiki_user_menus` where `url`=?', [$res['url']])) {
                $this->replace_usermenu($user, 0, $res['name'], $res['url'], $start, 'w');

                $start++;
            }
        }

        $query = 'select tubu.`name`,`url` from `tiki_user_bookmarks_urls` tubu where tubu.`folderId`=? and tubu.user=?';
        $result = $this->query($query, [0, $user]);
        $start = $this->get_max_position($user) + 1;

        while ($res = $result->fetchRow()) {
            // Check for duplicate URL
            if (! $this->getOne('select count(*) from `tiki_user_menus` where `url`=?', [$res['url']])) {
                $this->replace_usermenu($user, 0, $res['name'], $res['url'], $start, 'w');

                $start++;
            }
        }
    }

    /**
     * @param $user
     * @param $offset
     * @param $maxRecords
     * @param $sort_mode
     * @param $find
     * @return array
     */
    public function list_usermenus($user, $offset, $maxRecords, $sort_mode, $find)
    {

        if ($find) {
            $findesc = '%' . $find . '%';

            $mid = ' and (`name` like ? or url like ?)';
            $bindvars = [$user, $findesc, $findesc];
        } else {
            $mid = ' ';
            $bindvars = [$user];
        }

        $query = "select * from `tiki_user_menus` where `user`=? $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_user_menus` where `user`=? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }

        $retval = [];
        $retval['data'] = $ret;
        $retval['count'] = $count;
        return $retval;
    }

    /**
     * @param $user
     * @param $menuId
     * @return mixed
     */
    public function get_usermenu($user, $menuId)
    {
        $query = 'select * from `tiki_user_menus` where `user`=? and `menuId`=?';

        $result = $this->query($query, [$user, $menuId]);
        $res = $result->fetchRow();
        return $res;
    }

    /**
     * @param $user
     * @return mixed
     */
    public function get_max_position($user)
    {
        return $this->getOne('select max(position) from `tiki_user_menus` where `user`=?', [$user]);
    }

    /**
     * @param $user
     * @param $menuId
     * @param $name
     * @param $url
     * @param $position
     * @param $mode
     * @return mixed
     */
    public function replace_usermenu($user, $menuId, $name, $url, $position, $mode)
    {

        if ($menuId) {
            $query = 'update `tiki_user_menus` set `name`=?, `position`=?, `url`=?, `mode`=? where `user`=? and `menuId`=?';

            $this->query($query, [$name, $position, $url, $mode, $user, $menuId]);
            return $menuId;
        } else {
            $query = 'insert into `tiki_user_menus`(`user`,`name`,`url`,`position`,`mode`) values(?,?,?,?,?)';

            $this->query($query, [$user, $name, $url, $position, $mode]);
            return $this->lastInsertId();
        }
    }

    /**
     * @param $user
     * @param $menuId
     */
    public function remove_usermenu($user, $menuId)
    {
        $query = 'delete from `tiki_user_menus` where `user`=? and `menuId`=?';

        $this->query($query, [$user,$menuId]);
    }
}
$usermenulib = new UserMenuLib();
