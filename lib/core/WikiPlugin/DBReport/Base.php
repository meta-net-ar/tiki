<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

/**
 * Base class for all DbReport objects.
 *
 * @author Tiki Wiki CMS Groupware
 */
class Base
{
    public $sql;
    public $params;
    public $groups;
    public $table;
    public $columns;
    public $fail;

    public function code($indent = '')
    {
        // write the report in cannonical form.
        $result = $indent . 'SQL {' . $this->sql . '}' . "\n";
        if (isset($this->params)) {
            foreach ($this->params as $param) {
                $result .= $param->code($indent . '  ');
            }
        }
        if (isset($this->groups)) {
            foreach ($this->groups as $group) {
                $result .= $group->code($indent);
            }
        }
        if (isset($this->table)) {
            $result .= $this->table->code($indent);
        }
        if (isset($this->fail)) {
            $result .= $this->fail->code($indent);
        }
        return $result;
    }
}
