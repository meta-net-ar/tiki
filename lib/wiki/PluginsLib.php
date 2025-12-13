<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Plugin Lib
 *
 * A port of PhpWiki WikiPlugin class
 * Principal use is port PhpWiki plugins, but can be used to make new ones.
 * Use:
 * - Extends PluginsLib with your class
 * - add the lines
 * <code>
 * include "pluginslib.php";
 *
 * function wikiplugin_backlinks($data, $params) {
 *    $plugin = new BackLinks();
 *    return $plugin->run($data, $params);
 * }
 * function wikiplugin_backlinks_help() {
 *    $plugin = new BackLinks();
 *    return $plugin->getDescription();
 * }    * </code>
 * @package Tiki
 * @subpackage Plugins
 * @author Claudio Bustos
 * @version $Revision: 1.12 $
 */

namespace Tiki\Lib\Wiki;

use TikiLib;

//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

class PluginsLib extends TikiLib
{
    public $errors;
    public $data;
    public $params;
    /**
     * Array of params to be expanded as arrays. Explode the string with {@link $separator}
     * @var array
     */
    public $expanded_params = [];
    /**
     * Separator used to explote params listed on {@link $expanded_params}
     * @var string
     */
    public $separator = "|";
    /**
     * List of fields retrieved from {@link TikiLib::list_pages()}
     * Keys are the name of the fields and values the names for tra();
     * @var array
     */
    public $aInfoPresetNames = [
            'hits' => 'Hits', 'lastModif' => 'Last mod', 'user' => 'Last author', 'len' => 'Size', 'comment' => 'Com',
            'creator' => 'Creator', 'version' => 'Last ver', 'flag' => 'Status', 'versions' => 'Vers', 'links' => 'Links',
            'backlinks' => 'Backlinks'];
    /**
     * Process the params, in this order:
     * - default values, asigned on {@link PluginsLib::getDefaultArguments()}
     * - request values, sended by GET or POST method, if $request is put to true
     * - explicit values, asigned on the Wiki
     * @param array sended to wikiplugin_name($data, $params)
     * @param bool if set to true, accept values from $_REQUEST
     * @param bool if set to true, assign default values from {@link PluginsLib::getDefaultArguments()}
     * @return array list of params
     */
    public function getParams($params, $request = false, $defaults = false)
    {
        if ($defaults === false) {
            $defaults = $this->getDefaultArguments();
        }
        $args = [];

        foreach ($defaults as $arg => $default_val) {
            if (isset($params[$arg])) {
                $args[$arg] = $params[$arg];
            } elseif (isset($_REQUEST[$arg])) {
                $args[$arg] = $_REQUEST[$arg];
            } elseif (isset($_REQUEST['page'])) {
                // maybe this kind of transformation can be grouped on a external function
                if ($default_val === '[pagename]') {
                    $default_val = $_REQUEST['page'];
                }
                $args[$arg] = $default_val;
            }
            if (in_array($arg, $this->expanded_params) && ! (isset($args[$arg]) && is_array($args[$arg]))) {
                if (isset($args[$arg]) && $args[$arg]) {
                    $args[$arg] = explode($this->separator, $args[$arg]);
                    foreach ($args[$arg] as $id => $value) {
                        $args[$arg][$id] = trim($value);
                    }
                } else {
                    $args[$arg] = [];
                }
            }
        }
        return $args;
    }

    /**
     * Returns the name of the Plugin
     * By default, erase the first 'WikiPlugin'
     * Made for overload it.
     * @return string
     */
    public function getName()
    {
        return preg_replace('/^WikiPlugin/', '', get_class($this));
    }

    /**
     * Returns a description of the Plugin
     * Made for overload it.
     * @return string
     */
    public function getDescription()
    {
        return $this->getName();
    }

    /**
     * Returns the version of the version
     * Made for overload it.
     * @return string
     */
    public function getVersion()
    {
        return tra('No version indicated');
        //return preg_replace("/[Revision: $]/", '',
        //                    "\$Revision: 1.12 $");
    }

    /**
     * Returns the default arguments for the plugin
     * Use keys as the arguments and values as ... the default values
     * @return array
     */
    public function getDefaultArguments()
    {
        return ['description' => $this->getDescription()];
    }

    /**
     * Run the plugin
     * For sake of God, overload it!
     * @param string
     * @param array
     */
    public function run($data, $params)
    {
        /**
         * UGLY ERROR!.
         */
        return $this->error('PluginsLib::run: pure virtual function. Don\'t be so lazy!');
    }

    public function error($message)
    {
        return '~np~<span class="warn">' . tra('Plugin ') . $this->getName() . ' ' . tra('failed')
            . ' : ' . tra($message) . '</span>~/np~';
    }

    public function getErrorDetail()
    {
        return $this->errors;
    }

    public function setErrorDetail($message)
    {
        $this->errors = $message;
        return false;
    }
}
