<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;
use SmartyTiki\TikiSmartyExtensionInterface;

/**
 * \brief smarty_block_tabs : add tabs to a template
 *
 *
 * @param array $params - params are passed through the array params and available under their key. i.e $params['name']
 * The following params are supported via $params as keys:
 * string name - name of the tab
 * string print - 'y' this tab will be printed (by setting the class active flag)
 * integer key  ????
 * @param string $content - content of the tab
 * @param object $smarty - ref to smarty instance
 * @param ref $repeat - ????
 *

 *
 * usage:
 * \code
 *  {tab name="myname" print=1}
 *  tab content
 *  {/tab}
 * \endcode
 */
class Tab extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'tab';
    }

    public function handle($params, $content, Template $template, &$repeat)
    {
        global $prefs, $smarty_tabset, $cookietab, $cookietabTabset, $smarty_tabset_i_tab, $smarty_tabset_name;
        $smarty = \TikiLib::lib('smarty');
        if ($repeat) {
            return '';
        } else {
            $print_page = $smarty->getTemplateVars('print_page');

            $name = $smarty_tabset[$smarty_tabset_name]['name'];
            $id = '';
            if ($print_page != 'y') {
                $smarty_tabset_i_tab = count($smarty_tabset[$smarty_tabset_name]['tabs']) + 1;

                if (empty($params['name'])) {
                    $params['name'] = "tab" . $smarty_tabset_i_tab;
                }

                if (empty($params['key'])) {
                    $params['key'] = $smarty_tabset_i_tab;
                }

                $id = $id = "content$name-{$params['key']}";
                $active = ($smarty_tabset_i_tab == ($cookietabTabset ?? $cookietab)) ? 'active' : '';
                $def = [
                    'label' => $params['name'],
                    'id' => $id,
                    'active' => $active,
                ];
                $smarty_tabset[$smarty_tabset_name]['tabs'][] = $def;
            } else {
                // if we print a page then then all tabs would be "not active" so hidden and we would print nothing.
                // we cannot click something so no js handler involed. thats we use the defaultActive
                // so get the cookietab as the enabled tab.
                $active = (isset($params['print']) && $params['print'] == 'y') ? 'active' : '';
            }

            if (isset($params['params']['direction']) && $params['params']['direction'] == 'vertical') {
                $ret = '<div class="tab-pane ' . $active . '" id="v-pills-' . $id . '" role="tabpanel" aria-labelledby="v-pills-' . $id . '-tab">' .
                    $content
                    . '</div>';
            } else {
                $ret = "<div id='{$id}' class='tab-pane pt-3 $active'>$content</div>";
            }

            return $ret;
        }
    }
}
