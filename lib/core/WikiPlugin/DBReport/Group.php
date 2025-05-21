<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

require_once('lib/wiki-plugins/wikiplugin_dbreport.php');

class Group
{
    public $link;
    public $style;
    public $fields;
    public $field_count;
    public $contents;

    public function __toString()
    {
        $result = '';
        foreach ($this->contents as $entry) {
            $result .= $entry;
        }
        return $result;
    }

    public function code($indent = '')
    {
        $result = $indent . 'GROUP';
        if (isset($this->style)) {
            $result .= ' ' . $this->style->code();
        }
        if (isset($this->fields)) {
            foreach ($this->fields as $field) {
                $result .= ' ' . $field->code();
            }
        }
        if (isset($this->link)) {
            $result .= ' ' . $this->link->code();
        }
        if (isset($this->contents)) {
            foreach ($this->contents as $content) {
                $result .= ' ' . $content->code();
            }
        }
        $result .= "\n";
        return $result;
    }

    public function check_break(&$row)
    {
        $ret = false;
        // compare the field values against the break values
        for ($i = 0; $i < $this->field_count; $i++) {
            $field =& $this->fields[$i];
            $value =& $row[$field->index];
            if ($value !== $field->break) {
                $ret = true;
                $field->break =& $value;
            }
        }
        return $ret;
    }

    public function start_html($row)
    {
        global $wikiplugin_dbreport_record;
        $wikiplugin_dbreport_record = $row;
        $html = '';
        // generate a new <div> with the report content at the top
        if (isset($this->style)) {
            $html .= '<div' . $this->style->attributes() . '>' . "\n";
        } else {
            $html .= '<div>' . "\n";
        }
        if (isset($this->contents)) {
            foreach ($this->contents as $content) {
                $html .= $content->html();
            }
        }
        return $html;
    }

    public function end_html(&$row)
    {
        $html = '';
        // close the <div>
        $html .= '</div>';
        return $html;
    }
}
