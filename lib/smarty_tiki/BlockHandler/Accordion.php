<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 * Smarty plugin Accordion
 *
 * \brief smarty_block_tabs : add tabs to a template
 *
 * params: name (optional but unique per page if set)
 * params: toggle=y on n default
 *
 * usage:
 * \code
 *  {accordion}
 *      {accordion_group title="{tr}Title 1{/tr}"}tab content{/accordion_group}
 *      {accordion_group title="{tr}Title 2{/tr}"}tab content{/accordion_group}
 *  {/accordion}
 * \endcode
 */
class Accordion extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        global $accordion_current_group;

        if ($repeat) {
            $accordion_current_group = null;
            return;
        } else {
            return <<<CONTENT
<div class="accordian" id="$accordion_current_group">
$content
</div>
CONTENT;
        }
    }
}
