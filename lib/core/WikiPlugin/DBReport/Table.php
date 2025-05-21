<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Table
{
    public $style;
    public $headers;
    public $rows;
    public $footers;
    public $style_index;

    public function code($indent = '')
    {
        $result = $indent . 'TABLE';
        if (isset($this->style)) {
            $result .= ' ' . $this->style->code();
        }
        $result .= "\n";
        if (isset($this->headers)) {
            foreach ($this->headers as $line) {
                $result .= $line->code($indent . '  ', 'HEADER');
            }
        }
        if (isset($this->rows)) {
            foreach ($this->rows as $line) {
                $result .= $line->code($indent . '  ', 'ROW');
            }
        }
        if (isset($this->footers)) {
            foreach ($this->footers as $line) {
                $result .= $line->code($indent . '  ', 'FOOTER');
            }
        }
        return $result;
    }

    public function line_row_html($list, $data, $heading = false)
    {
        $html = '';
        foreach ($list as $line) {
            $style = null;
            if (isset($line->styles)) {
                $style_count = count($line->styles);
                $style = $line->styles[$this->style_index % $style_count];
            }
            $html .= $line->row_html($data, $style, $heading);
        }
        return $html;
    }

    public function header_row_html($data)
    {
        $html = '';
        // generate a new table
        if (isset($this->style)) {
            $html .= '<table' . $this->style->attributes() . '>' . "\n";
        } else {
            $html .= '<table>' . "\n";
        }
        // write headers
        $style_index = 0;
        if (isset($this->headers)) {
            $html .= $this->line_row_html($this->headers, $data, true);
        }
        return $html;
    }

    public function record_row_html($data)
    {
        $html = '';
        if (isset($this->rows)) {
            $html .= $this->line_row_html($this->rows, $data);
        }
        $this->style_index++;
        return $html;
    }

    public function footer_row_html($data)
    {
        $html = '';
        // write footers
        $this->style_index = 0;
        if (isset($this->footers)) {
            $html .= $this->line_row_html($this->footers, $data, true);
        }
        // close the table
        $html .= '</table>';
        return $html;
    }
}
