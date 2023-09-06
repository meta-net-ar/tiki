<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

class Services_ColorModes_Controller
{
    public function action_save_icons_for_default_modes($input)
    {
        $data = $input->payload->array();
        $modes = json_decode($data);
        $success = '';
        $failed = '';
        foreach ($modes as $name => $icon) {
            try {
                TikiLib::lib('db')->query("UPDATE tiki_custom_color_modes SET icon = ? WHERE name = '$name'", [$icon], -1, -1, 'exception');
                $success .= $name . ', ';
            } catch (Exception $e) {
                $failed .= $name . ', ';
            }
        }
        Feedback::success('Successfuly updated icons for ' . $success . ' modes');
        return true;
    }

    public function action_save_new_mode($input)
    {
        $mode_infos_json = $input->payload->text();
        $mode_infos = json_decode($mode_infos_json);
        $mode_name = $mode_infos->mode_name;
        $mode_icon = $mode_infos->mode_icon;
        $css = '[data-bs-theme="' . $mode_name . '"] .navbar,
                [data-bs-theme="' . $mode_name . '"] .tiki-topbar-nav-light,
                [data-bs-theme="' . $mode_name . '"] .tiki-topbar-nav-dark,
                [data-bs-theme="' . $mode_name . '"] .tiki.tiki-admin .top_modules.navbar-dark-parent,
                [data-bs-theme="' . $mode_name . '"] .tiki.tiki-admin .top_modules.navbar-light-parent,
                [data-bs-theme="' . $mode_name . '"] .tiki .tiki-admin-top-nav-dark, 
                [data-bs-theme="' . $mode_name . '"] .tiki .tiki-admin-aside-nav-dark, 
                [data-bs-theme="' . $mode_name . '"] .tiki .tiki-admin-top-nav-light, 
                [data-bs-theme="' . $mode_name . '"] .tiki .tiki-admin-aside-nav-light, 
                [data-bs-theme="' . $mode_name . '"] .tiki-top-nav-dark, 
                [data-bs-theme="' . $mode_name . '"] .tiki-top-nav-light, 
                [data-bs-theme="' . $mode_name . '"]{
            ';
        foreach ($mode_infos->colors_vars as $var => $value) {
            $css .= $var . ': ' . $value . ';
            ';
        }
        $css .= "}";
        try {
            TikiLib::lib('db')->query("INSERT INTO `tiki_custom_color_modes` (`name`, `icon`,`custom`,`css_variables`) VALUES (?,?,?,?)", [$mode_name,$mode_icon,'y',$css], -1, -1, 'exception');
            Feedback::success('Successfully created the new color mode: ' . $mode_name);
            return true;
        } catch (Exception $e) {
            Feedback::error('Couldn\'t create a new color mode : ' . $e->getMessage());
            return false;
        }
    }

    public function action_delete_mode($input)
    {
        $payload = $input->payload->text();
        try {
            TikiLib::lib('db')->query('DELETE FROM `tiki_custom_color_modes` WHERE  `id`=?;', [$payload[1]], -1, -1, 'exception');
            Feedback::success('Successfully deleted the ' . $payload[0] . 'color mode!');
            return true;
        } catch (Exception $e) {
            Feedback::error('Failed to delete color mode: ' . $e->getMessage());
            return false;
        }
    }

    public function action_edit_mode($input)
    {
           $mode_infos_json = $input->payload->text();
           $id = $input->id->text();
           $mode_infos = json_decode($mode_infos_json);
           $mode_name = $mode_infos->mode_name;
           $mode_icon = $mode_infos->mode_icon;
           $css = ' [data-bs-theme="' . $mode_name . '"] .navbar,
                    [data-bs-theme="' . $mode_name . '"] .tiki-topbar-nav-light,
                    [data-bs-theme="' . $mode_name . '"] .tiki-topbar-nav-dark,
                    [data-bs-theme="' . $mode_name . '"] .tiki.tiki-admin .top_modules.navbar-dark-parent,
                    [data-bs-theme="' . $mode_name . '"] .tiki.tiki-admin .top_modules.navbar-light-parent,
                    [data-bs-theme="' . $mode_name . '"] .tiki .tiki-admin-top-nav-dark, 
                    [data-bs-theme="' . $mode_name . '"] .tiki .tiki-admin-aside-nav-dark, 
                    [data-bs-theme="' . $mode_name . '"] .tiki .tiki-admin-top-nav-light, 
                    [data-bs-theme="' . $mode_name . '"] .tiki .tiki-admin-aside-nav-light, 
                    [data-bs-theme="' . $mode_name . '"] .tiki-top-nav-dark, 
                    [data-bs-theme="' . $mode_name . '"] .tiki-top-nav-light, 
                    [data-bs-theme="' . $mode_name . '"]{
                ';
        foreach ($mode_infos->colors_vars as $var => $value) {
            $css .= $var . ': ' . $value . ';
               ';
        }
           $css .= "}";
        try {
            TikiLib::lib('db')->query("UPDATE `tiki_custom_color_modes` SET `name`=?, `icon`=?,`css_variables`=? WHERE id=?", [$mode_name,$mode_icon,$css,$id], -1, -1, 'exception');
            Feedback::success('Successfully edited the existing color mode: ' . $mode_name);
            return true;
        } catch (Exception $e) {
            Feedback::error('Couldn\'t edit the existing color mode : ' . $e->getMessage());
            return false;
        }
    }
}
