<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Hm_Custom_Session extends Hm_Session
{
    /**
     * @var bool
     */
    public $active;
    public $site_config;
    /**
     * check for an active session or an attempt to start one
     * @param object $request request object
     * @return bool
     */
    public function check($request)
    {
        $this->active = session_status() == PHP_SESSION_ACTIVE;
        return $this->is_active();
    }

    /**
     * Tiki sessions are always active
     * @return bool
     */
    public function is_active()
    {
        return true;
    }

    /**
     * Start the session. This could be an existing session or a new login
     * @param object $request request details
     * @return void
     */
    public function start($request, $existing_session = false)
    {
        // Tiki handles this
        return;
    }

    /**
     * Call the configured authentication method to check user credentials
     * @param string $user username
     * @param string $pass password
     * @return bool true if the authentication was successful
     */
    public function auth($user, $pass)
    {
        $userlib = TikiLib::lib('user');
        list($isvalid, $user) = $userlib->validate_user($user, $pass);
        return $isvalid;
    }

    /**
     * Return a session value, or a user settings value stored in the session
     * @param string $name session value name to return
     * @param mixed $default value to return if $name is not found
     * @return mixed the value if found, otherwise $defaultHm_Auth
     */
    public function get($name, $default = false, $user = false)
    {
        if ($user) {
            return array_key_exists($this->session_prefix(), $_SESSION) && array_key_exists('user_data', $_SESSION[$this->session_prefix()]) && array_key_exists($name, $_SESSION[$this->session_prefix()]['user_data']) ? $_SESSION[$this->session_prefix()]['user_data'][$name] : $default;
        } else {
            return array_key_exists($this->session_prefix(), $_SESSION) && array_key_exists($name, $_SESSION[$this->session_prefix()]) ? $_SESSION[$this->session_prefix()][$name] : $default;
        }
    }

    /**
     * Save a value in the session
     * @param string $name the name to save
     * @param string $value the value to save
     * @return void
     */
    public function set($name, $value, $user = false)
    {
        $just_started = false;
        if (session_status() == PHP_SESSION_NONE) {
            // need to start the session again as ajax requests close it to allow concurrency
            session_start();
            $just_started = true;
        }
        if ($user) {
            $_SESSION[$this->session_prefix()]['user_data'][$name] = $value;
        } else {
            $_SESSION[$this->session_prefix()][$name] = $value;
        }
        if ($just_started) {
            // keep it closed if it was closed
            session_write_close();
        }
    }

    /**
     * Delete a value from the session
     * @param string $name name of value to delete
     * @return void
     */
    public function del($name)
    {
        if (array_key_exists($this->session_prefix(), $_SESSION) && array_key_exists($name, $_SESSION[$this->session_prefix()])) {
            unset($_SESSION[$this->session_prefix()][$name]);
        }
    }

    /**
     * End a session after a page request is complete. This only closes the session and
     * does not destroy it
     * @return void
     */
    public function end()
    {
        $this->active = false;
        return true;
    }

    /**
     * Destroy a session for good
     * @param object $request request details
     * @return void
     */
    public function destroy($request)
    {
        if (function_exists('delete_uploaded_files')) {
            delete_uploaded_files($this);
        }
        unset($_SESSION[$this->session_prefix()]);
        $this->active = false;
    }

    /**
     * Dump current session contents
     * @return array
     */
    public function dump()
    {
        if (array_key_exists($this->session_prefix(), $_SESSION)) {
            return $_SESSION[$this->session_prefix()];
        } else {
            return [];
        }
    }

    public function close_early()
    {
        // noop;
    }

    public function record_unsaved($value)
    {
        $list = $this->get('changed_settings', []);
        $list[] = $value;
        $this->set('changed_settings', $list);
    }

    protected function session_prefix()
    {
        return $this->site_config->get('session_prefix') ?? 'cypht';
    }
}
