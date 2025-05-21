<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

require_once('lib/wiki-plugins/wikiplugin_dbreport.php');

class Line
{
    public $link;
    public $styles;
    public $cells;

    public function code($indent = '', $rtype = 'ROW', $cellmode = 'ROW')
    {
        $result = $indent . $rtype;
        if (isset($this->styles)) {
            foreach ($this->styles as $style) {
                $result .= ' ' . $style->code();
            }
        }
        if (isset($this->link)) {
            $result .= ' ' . $this->link->code();
        }
        $result .= "\n";
        foreach ($this->cells as $cell) {
            $result .= $indent . '  ' . $cell->code($cellmode) . "\n";
        }
        return $result;
    }

    public function row_html($data, $style, $heading = false)
    {
        // set the global report row
        global $wikiplugin_dbreport_record;
        $wikiplugin_dbreport_record = $data;
        // generate HTML
        $html = '<tr';
        if (isset($style)) {
            $html .= $style->attributes();
        }
        if (isset($this->link)) {
            $html .= ' ' . $this->link->html_onclick();
        }
        $html .= '>';
        foreach ($this->cells as $cell) {
            $html .= $cell->html($heading);
        }
        $html .= '</tr>' . "\n";
        return $html;
    }
}
