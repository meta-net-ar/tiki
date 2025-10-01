<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

require_once('lib/wiki-plugins/wikiplugin_dbreport.php');

class Field
{
    public $name;
    public $variable;
    public $break;
    public $index;

    public function __construct($text)
    {
        global $wikiplugin_dbreport_fields, $wikiplugin_dbreport_fields_allowed;
        $this->name = stripcslashes($text);
        if ($text[0] == '$') {
            $this->variable = substr($this->name, 1);
            // print("new variable $this->variable ");
        } else {
            // add to the list of parsed fields
            // if ($wikiplugin_dbreport_fields_allowed)
            $wikiplugin_dbreport_fields[] =& $this;
        }
    }

    public function text()
    {
        global $wikiplugin_dbreport_record;
        if (isset($this->index)) {
            // indexed field
            return (string) ($wikiplugin_dbreport_record[$this->index]);
        } elseif (isset($this->variable)) {
            // PHP variable
            if (isset($GLOBALS[$this->variable])) {
                return (string) $GLOBALS[$this->variable];
            } elseif (isset($_SESSION[$this->variable])) {
                return (string) $_SESSION[$this->variable];
            } elseif (isset($_REQUEST[$this->variable])) {
                return (string) $_REQUEST[$this->variable];
            }
        } else {
            return "[$this->name]";
        }
    }

    public function code()
    {
        if (isset($this->variable)) {
            return '[$' . addcslashes($this->variable, "\0..\37[]$\\") . ']';
        } else {
            return '[' . addcslashes($this->name, "\0..\37[]$\\") . ']';
        }
    }

    public function html()
    {
        return htmlentities($this->text(), ENT_COMPAT);
    }

    public function uri()
    {
        return urlencode($this->text());
    }
}
