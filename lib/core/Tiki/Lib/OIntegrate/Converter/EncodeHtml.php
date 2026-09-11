<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OIntegrate\Converter;

use Tiki\Lib\OIntegrate\ConverterInterface;

class EncodeHtml implements ConverterInterface // {{{
{
    /**
     * @param $content
     * @return string
     */
    public function convert($content)
    {
        return htmlentities($content, ENT_QUOTES, 'UTF-8');
    }
}
