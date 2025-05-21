<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiPlugin\DBReport;

class Token
{
    public $type; // key=keyword, fld=field, str=string, var=variable, sty=style, eof=end of file
    public $content;
    public $start;
    public $after;
    public $code;

    public function type_name()
    {
        switch ($this->type) {
            case 'key':
                return 'Keyword';
            case 'txt':
                return 'Text';
            case 'sty':
                return 'Style';
            case 'fld':
                return 'Field';
            case 'var':
                return 'Variable';
            case 'str':
                return 'String';
            case 'bra':
                return 'Brackets';
            case 'eof':
                return 'End';
            default:
                return $token->type;
        }
    }

    public function __construct($content = null)
    {
        $this->content = $content;
    }
}
