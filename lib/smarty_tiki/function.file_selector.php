<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Variable arguments to be sent as filters for the object list. Filters match the unified search
 * field filters.
 *
 * Reserved parameters:
 *  - name for the field name
 *  - galleryId
 *  - value for the current value (fileId, comma-separated for multiple)
 *  - type for the mime type filter (image/*)
 *  - limit for the maximum amount of files (defaults to 1)
 *
 * The component will build a drop list for the object selector if the results fit in a reasonable amount
 * of space or will use autocomplete on the object title otherwise.
 */
function smarty_function_file_selector($params, \Smarty\Template $template)
{
    $smartyFunctionFileSelectorHandler = new \SmartyTiki\FunctionHandler\FileSelector();
    return $smartyFunctionFileSelectorHandler->handle($params, $template);
}
