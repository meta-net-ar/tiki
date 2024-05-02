<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

/**
 *
 * \brief smarty_block_tabs : add tabs to a template
 *
 * params: name (optional but unique per page if set)
 * params: toggle=y on n default
 *
 * usage:
 * \code
 *  {tabset name='tabs' skipsingle=1}
 *      {tab name='tab1'}tab content{/tab}
 *      {tab name='tab2'}tab content{/tab}
 *      {tab name='tab3'}tab content{/tab}
 *  {/tabset}
 * \endcode
 */
class Tabset extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        global $prefs, $smarty_tabset_name, $smarty_tabset, $smarty_tabset_i_tab, $cookietab, $tabset_index;
        $smarty = \TikiLib::lib('smarty');
        if ($smarty->getTemplateVars('print_page') == 'y' || $prefs['layout_tabs_optional'] === 'n') {
            $params['toggle'] = 'n';
        }
        if ($repeat) {
            // opening
            if (! is_array($smarty_tabset)) {
                $smarty_tabset = [];
            }
            $tabset_index = count($smarty_tabset) + 1;
            $smarty_tabset_name = getTabsetName($params, $tabset_index);
            $smarty_tabset[$tabset_index] = ['name' => $smarty_tabset_name, 'tabs' => []];
            if (! isset($smarty_tabset_i_tab)) {
                $smarty_tabset_i_tab = 1;
            }

            if (! isset($cookietab) || $tabset_index > 1) {
                $cookietab = getCookie($smarty_tabset_name, 'tabs', 1);
            }
            // work out cookie value if there
            if (isset($_REQUEST['cookietab']) && $tabset_index) {   // overrides cookie if added to request as in tiki-admin.php?page=look&cookietab=6
                $cookietab = empty($_REQUEST['cookietab']) ? 1 : $_REQUEST['cookietab'];
                setCookieSection($smarty_tabset_name, $cookietab, 'tabs');  // too late to set it here as output has started
            }

            // If the tabset specifies the tab, override any kind of memory but only if not doing "no tabs" mode
            if (isset($params['cookietab']) && $cookietab !== 'n') {
                $cookietab = $params['cookietab'];
            }

            $smarty_tabset_i_tab = 1;

            // add styles
            applyStyles($params, $smarty_tabset_name);

            return '';
        } else {
            $content = trim($content);
            if (empty($content)) {
                return '';
            }

            if (! empty($params['skipsingle']) && count($smarty_tabset[$tabset_index]['tabs']) == 1) {
                return $content;
            }

            $ret = '';
            $notabs = '';
            //closing
            if ($prefs['feature_tabs'] == 'y') {
                if (empty($params['toggle']) || $params['toggle'] != 'n') {
                    if ($cookietab == 'n') {
                        $button_params['_text'] = tra('Tab View');
                    } else {
                        $button_params['_text'] = tra('No Tabs');
                    }
                    $button_params['_size'] = 'mini';
                    $button_params['_auto_args'] = '*';
                    $button_params['_onclick'] = "setCookie('$smarty_tabset_name','" . ($cookietab == 'n' ? 1 : 'n') . "', 'tabs') ;";
                    $button_params['_class'] = 'btn-sm'; // btn-secondary removed because btn-primary is also being applied somehow.
                    $notabs = smarty_function_button($button_params, $smarty->getEmptyInternalTemplate());
                    $notabs = "<div class='float-end'>$notabs</div>";
                    $content_class = '';
                } else {
                    $content_class = ' full_width'; // no no-tabs button
                }
            } else {
                return $content;
            }
            if ($cookietab == 'n') {
                return $ret . $notabs . $content;
            }

            $smarty_tabset_name = getTabsetName($params, $tabset_index);
            if (isset($params['params']['direction']) && $params['params']['direction'] == 'vertical') {
                $count = 1;
                $ret .= '<div class="d-flex align-items-start">
                        <div class="nav flex-column nav-pills me-3" id="nav-' . $smarty_tabset_name . '" role="tablist" aria-orientation="vertical">' . $notabs . '<span style="height:5px;">&nbsp;</span>';
                foreach ($smarty_tabset[$tabset_index]['tabs'] as $value) {
                    $ret .= '<a class="nav-link" id="v-pills-' . $value['id'] . '-tab" data-bs-toggle="tab" href="#v-pills-' . $value['id'] . '" role="tab" aria-controls="v-pills-' . $value['id'] . '" >' . $value['label'] . '</a>';
                    ++$count;
                }
                $ret .= "</div>";
                $tabset_index--;
                $ret .= '<div class="tab-content" id="v-pills-' . $smarty_tabset_name . '">' . $content . '</div>
                    </div>';
            } else {
                $ret .= '<div class="clearfix tabs" data-name="' . $smarty_tabset_name . '">' . $notabs;

                $count = 1;

                $ret .= '<ul class="nav nav-tabs" id="nav-' . $smarty_tabset_name . '">';
                foreach ($smarty_tabset[$tabset_index]['tabs'] as $value) {
                    $ret .= '<li class="nav-item"><a class="nav-link ' . $value['active'] . '" href="#' . $value['id'] . '" data-bs-toggle="tab">' . $value['label'] . '</a></li>';
                    ++$count;
                }
                $ret .= '</ul>';

                $ret .= "</div>";
                $tabset_index--;

                $ret .= '<div class="tab-content" id="v-pills-' . $smarty_tabset_name . '">' . $content . '</div>';
            }
            return $ret;
        }
    }
}

/**
 * @param $params
 * @param $tabset_index
 * @return array
 */
function getTabsetName($params, $tabset_index)
{
    $tikilib = \TikiLib::lib('tiki');
    if (! empty($params['name'])) {
        $smarty_tabset_name = $params['name'];    // names have to be unique
    } else {
        $short_name = str_replace(['tiki-', '.php'], '', basename($_SERVER['SCRIPT_NAME']));
        $smarty_tabset_name = '-' . $short_name . $tabset_index;
    }
    $smarty_tabset_name = $tikilib->urlFragmentString($smarty_tabset_name);
    return $smarty_tabset_name;
}

function applyStyles($params, $smarty_tabset_name)
{
    $headerlib = \TikiLib::lib('header');
    $panels = '#v-pills-' . $smarty_tabset_name;
    $tabs = '#nav-' . $smarty_tabset_name . ' .nav-link';
    $style = '';
    $style .= ! empty($params['params']['tabborderstyle']) ? $tabs . '{border-style:' . $params['params']['tabborderstyle'] . ';} ' : '';
    $style .= ! empty($params['params']['tabborderwidth']) ? $tabs . '{border-width:' . $params['params']['tabborderwidth'] . 'px;} ' : '';
    $style .= ! empty($params['params']['tabbordercolor']) ? $tabs . '{border-color:' . $params['params']['tabbordercolor'] . ';} ' : '';
    $style .= ! empty($params['params']['tabfontstyle']) ? $tabs . '{font-style:' . $params['params']['tabfontstyle'] . ';} ' : '';
    $style .= ! empty($params['params']['tabfontweight']) ? $tabs . '{font-weight:' . $params['params']['tabfontweight'] . ';} ' : '';
    $style .= ! empty($params['params']['tabfontsize']) ? $tabs . '{font-size:' . $params['params']['tabfontsize'] . 'px;} ' : '';
    $style .= ! empty($params['params']['tabtextcolor']) ? $tabs . '{color:' . $params['params']['tabtextcolor'] . ';} ' : '';
    $style .= ! empty($params['params']['tabactivetextcolor']) ? $tabs . '.active {color:' . $params['params']['tabactivetextcolor'] . ';} ' : '';
    $style .= ! empty($params['params']['tabbgcolor']) ? $tabs . '{background-color:' . $params['params']['tabbgcolor'] . ';} ' : '';
    $style .= ! empty($params['params']['tabactivebgcolor']) ? $tabs . '.active {background:' . $params['params']['tabactivebgcolor'] . ';} ' : '';
    $style .= ! empty($params['params']['panelbgcolor']) ? $panels . '{background-color:' . $params['params']['panelbgcolor'] . ';} ' : '';
    $style .= ! empty($params['params']['paneltextcolor']) ? $panels . '{color:' . $params['params']['paneltextcolor'] . ';} ' : '';
    $style .= ! empty($params['params']['paneltextstyle']) ? $panels . '{font-style:' . $params['params']['paneltextstyle'] . ';} ' : '';
    $style .= ! empty($params['params']['panelfontweight']) ? $panels . '{font-weight:' . $params['params']['panelfontweight'] . ';} ' : '';
    $style .= ! empty($params['params']['panelfontsize']) ? $panels . '{font-size:' . $params['params']['panelfontsize'] . 'px;} ' : '';
    $style .= ! empty($params['params']['panelborderstyle']) ? $panels . '{border-style:' . $params['params']['panelborderstyle'] . ';} ' : '';
    $style .= ! empty($params['params']['panelborderwidth']) ? $panels . '{border-width:' . $params['params']['panelborderwidth'] . 'px;} ' : '';
    $style .= ! empty($params['params']['panelbordercolor']) ? $panels . '{border-color:' . $params['params']['panelbordercolor'] . ';} ' : '';

    // Headers are already sent by now sometimes (e.g. in tiki-editpage.tpl)
    // Not sure how it could be moved eleswhere... jonnyb 230613
    if ($style /* && ! $headerlib->->outputHeadersHasBegun no access to this here sadly */) {
        $headerlib->add_css($style);
    }
}
