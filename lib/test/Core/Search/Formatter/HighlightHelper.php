<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Core\Search\Formatter;

use Laminas\Filter\FilterInterface;

class HighlightHelper implements FilterInterface
{
    public function filter($content)
    {
        return str_replace('Hello', '<strong>Hello</strong>', $content);
    }
}
