<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Auth;

use Exception;
use PDO;
use PDOException;

// Tikiwiki authentication backend for phpBB3
// By Jacob 'jacmoe2' Moen 10 Dec 2009
// Based on:
// Mediawiki authentication plugin for phpBB3 with mysql4
// By Steve Streeting 26 Dec 2008

// some definitions for helping with authentication
// Er, what about definition clashes ?
// @Todo: Make sure there is no definition clash

//TODO: support other database types

class PhpBBLib
{
    public $db;

    public const PHPBB_INVALID_CREDENTIALS = -21;
    public const PHPBB_INVALID_SYNTAX = -23;
    public const PHPBB_NO_SUCH_USER = -25;
    public const PHPBB_SUCCESS = -29;
    public const SERVER_ERROR = -1;

    public function check($user, $pass)
    {

    // no need to progress further if the user doesn't even exist
        if (! $this->userExists($user)) {
            return self::PHPBB_NO_SUCH_USER;
        }

        // if the user does exist, authenticate
        if ($this->authenticate($user, $pass)) {
            return self::PHPBB_SUCCESS;
        } else {
            return self::PHPBB_INVALID_CREDENTIALS;
        }
    }

    public function connectdb()
    {
        global $prefs;
        $dbhost = $prefs['auth_phpbb_dbhost'];
        $dbuser = $prefs['auth_phpbb_dbuser'];
        $dbpasswd = $prefs['auth_phpbb_dbpasswd'];
        $dbname = $prefs['auth_phpbb_dbname'];

        try {
            $dbconnection = new PDO("mysql:$dbhost;dbname=$dbname", $dbuser, $dbpasswd);
        } catch (PDOException $e) {
            throw new Exception(tr("Error connecting with PHPBB service: %0", $e->getMessage()));
        }
        if ($dbconnection) {
            return $dbconnection;
        }
        return false;
    }

    /**
    * Check whether there exists a user account with the given name.
    *
    * @param string $username
    * @return bool
    * @access public
    */
    public function userExists($username)
    {
        global $prefs;

        if (! $this->connectdb()) {
            return false;
        }

        $dbconnection = $this->connectdb();

        // MySQL queries are case insensitive anyway
        $query = "select username from " . $prefs['auth_phpbb_table_prefix'] . "users where lcase(username) = ?";

        $sth = $dbconnection->prepare($query);
        $sth->execute([strtolower($username)]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        if (! $result) {
            die('AuthPhpBB : User not found: ' . $result->errorInfo());
        }
        return ! empty($result['username']);
    }

    /**
    * Check if a username+password pair is a valid login.
    *
    * @param string $username
    * @param string $password
    * @return bool
    * @access public
    */
    public function authenticate($username, $password)
    {
        global $prefs;

        $dbconnection = $this->connectdb();

        $query = "select user_password from " . $prefs['auth_phpbb_table_prefix'] . "users where lcase(username) = ?";
        $sth = $dbconnection->prepare($query);
        $sth->execute([strtolower($username)]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);

        if (! $result) {
            die('AuthPhpBB : Authentication failed: ' . $result->errorInfo());
        }

        $PasswordHasher = new PasswordHash(8, true);
        return $PasswordHasher->CheckPassword($password, $result['user_password']);
    }

    /**
    * Returns a user's email from the phpbb3 user table.
    * @param string $username
    * @access public
    * @return string user's email or 0
    */
    public function grabEmail($username)
    {
        global $prefs;
        $dbconnection = $this->connectdb();

        // Just add email
        $query = "select user_email from " . $prefs['auth_phpbb_table_prefix'] . "users where lcase(username) = ?";
        $sth = $dbconnection->prepare($query);
        $sth->execute([strtolower($username)]);
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        if (! $result) {
            die('AuthPhpBB : Cannot grab email: ' . $result->errorInfo());
        }
        return $result['user_email'] ?? 0;
    }
}
