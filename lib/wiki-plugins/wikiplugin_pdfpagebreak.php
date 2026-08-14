<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_pdfpagebreak_info()
{
     return [
                'name' => tra('PluginPDF Page Break'),
                'documentation' => 'PluginPDFPageBreak',
                'description' => tra('Helpful to format PDF files created, plugin adds page break in PDF file generated.'),
                'tags' => [ 'basic' ],
                'iconname' => 'pdf',
                'prefs' => [ 'wikiplugin_pdfpagebreak' ],
                'introduced' => 17,

                           ];
}

function wikiplugin_pdfpagebreak()
{
    if (! defined('TIKI_DISPLAY_CONTAINS_PDF') || ! TIKI_DISPLAY_CONTAINS_PDF) {
        return;
    }
    return '<pagebreak></pagebreak>';
}
