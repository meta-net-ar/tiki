<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

class BookmarkLib extends TikiLib
{
    public function get_folder_path($folderId, $user)
    {
        $path = '';

        $info = $this->get_folder($folderId, $user);
        $path = '<a class="link" href=tiki-user_bookmarks.php?parentId="' . $info["folderId"] . '">' . htmlspecialchars($info["name"]) . '</a>';

        while ($info["parentId"] != 0) {
            $info = $this->get_folder($info["parentId"], $user);

            $path
                = $path = '<a class="link" href=tiki-user_bookmarks.php?parentId="' . $info["folderId"] . '">' . htmlspecialchars($info["name"]) . '</a>' . '>' . $path;
        }

        return $path;
    }

    public function get_folder($folderId, $user)
    {
        $query = "select * from `tiki_user_bookmarks_folders` where `folderId`=? and `user`=?";

        $result = $this->query($query, [$folderId,$user]);

        if (! $result->numRows()) {
            return false;
        }

        $res = $result->fetchRow();
        return $res;
    }

    public function get_url($urlId)
    {
        $query = "select * from `tiki_user_bookmarks_urls` where `urlId`=?";

        $result = $this->query($query, [$urlId]);

        if (! $result->numRows()) {
            return false;
        }

        $res = $result->fetchRow();
        return $res;
    }

    public function remove_url($urlId, $user)
    {
        $query = "delete from `tiki_user_bookmarks_urls` where `urlId`=? and `user`=?";

        $result = $this->query($query, [$urlId,$user]);
        return true;
    }

    public function remove_folder($folderId, $user)
    {
        // Delete the category
        $query = "delete from `tiki_user_bookmarks_folders` where `folderId`=? and `user`=?";

        $result = $this->query($query, [$folderId,$user]);
        // Remove objects for this category
        $query = "delete from `tiki_user_bookmarks_urls` where `folderId`=? and `user`=?";
        $result = $this->query($query, [$folderId,$user]);
        // SUbfolders
        $query = "select `folderId` from `tiki_user_bookmarks_folders` where `parentId`=? and `user`=?";
        $result = $this->query($query, [$folderId,$user]);

        while ($res = $result->fetchRow()) {
            // Recursively remove the subcategory
            $this->remove_folder($res["folderId"], $user);
        }

        return true;
    }

    public function update_folder($folderId, $name, $user)
    {

        $query = "update `tiki_user_bookmarks_folders` set `name`=? where `folderId`=? and `user`=?";
        $result = $this->query($query, [$name,$folderId,$user]);
    }

    public function add_folder($parentId, $name, $user)
    {
        // Don't allow empty/blank folder names.
        if (empty($name)) {
            return false;
        }

        // Find the next folderId
        $query = "select max(`folderId`) from `tiki_user_bookmarks_folders` WHERE `user`=?";
        $maxId = $this->getOne($query, [$user]);
        if ((int)$maxId == 0) {
            $maxId = 0;
        }

        $query = "insert into `tiki_user_bookmarks_folders`(`folderId`, `name`,`parentId`,`user`) values(?,?,?,?)";
        $result = $this->query($query, [$maxId + 1,$name,$parentId,$user]);
        return ($result && $result->numRows() > 0);
    }

    public function replace_url($urlId, $folderId, $name, $url, $user)
    {
        if ($urlId) {
            $query = "update `tiki_user_bookmarks_urls` set `user`=?,`lastUpdated`=?,`folderId`=?,`name`=?,`url`=? where `urlId`=?";
            $bindvars = [$user, $this->now, $folderId, $name, $url, $urlId];
            $result = $this->query($query, $bindvars);
            return  $urlId;
        } else {
            $query = " insert into `tiki_user_bookmarks_urls`(`name`,`url`,`data`,`lastUpdated`,`folderId`,`user`) values(?,?,?,?,?,?)";
            $bindvars = [$name, $url, '', $this->now, $folderId, $user];
            $result = $this->query($query, $bindvars);
            if ($result && $result->numRows()) {
                return $this->lastInsertId();
            }
            return false;
        }
    }

    public function refresh_url($urlId)
    {
        $info = $this->get_url($urlId);

        if (str_contains($info["url"], 'tiki-') || str_contains($info["url"], 'messu-')) {
            return false;
        }

        $data = @$this->httprequest($info["url"]);

        if (! $data) {
            return false;
        }

        $query = "update `tiki_user_bookmarks_urls` set `lastUpdated`=?, `data`=? where `urlId`=?";
        $result = $this->query($query, [$this->now,$data,$urlId]);
        return true;
    }

    public function list_folder($folderId, $offset, $maxRecords, $sort_mode, $find, $user)
    {

        if ($find) {
            $findesc = '%' . $find . '%';

            $mid = " and `name` like ? or `url` like ?";
            $bindvars = [$folderId,$user,$findesc,$findesc];
        } else {
            $mid = "";
            $bindvars = [$folderId,$user];
        }

        $query = "select * from `tiki_user_bookmarks_urls` where `folderId`=? and `user`=? $mid order by " . $this->convertSortMode($sort_mode);
        $query_count = "select count(*) from `tiki_user_bookmarks_urls` where `folderId`=? and `user`=? $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $res["datalen"] = strlen($res["data"]);

            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    public function get_child_folders($folderId, $user)
    {
        $ret = [];

        $query = "select * from `tiki_user_bookmarks_folders` where `parentId`=? and `user`=?";
        $result = $this->query($query, [$folderId,$user]);

        while ($res = $result->fetchRow()) {
            $count = $this->getOne("select count(*) from `tiki_user_bookmarks_urls` where `folderId`=? and `user`=?", [$res["folderId"],$user]);

            $res["urls"] = $count;
            $ret[] = $res;
        }

        return $ret;
    }
}
$bookmarklib = new BookmarkLib();
