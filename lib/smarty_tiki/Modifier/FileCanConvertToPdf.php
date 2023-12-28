<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Modifier;

/**
 * Smarty plugin
 * -------------------------------------------------------------
 * Type:     modifier
 * Name:     file_can_convert_to_pdf
 * Purpose:  Checks if mimetype is supported to convert to PDF
 * -------------------------------------------------------------
 */
class FileCanConvertToPdf
{
    public function handle($string)
    {
        return \Tiki\File\PDFHelper::canConvertToPDF($string);
    }
}
