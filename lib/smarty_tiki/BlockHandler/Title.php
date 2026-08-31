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
 * smarty_block_title : add a title to a template.
 *
 * params:
 *    help: name of the doc page on doc.tiki.org
 *    admpage: admin panel name
 *    url: link on the title
 *
 * usage: {title help='Example' admpage='example'}{tr}Example{/tr}{/title}
 */
class Title extends Base implements TikiSmartyExtensionInterface
{
    public static function getSmartyName(): string
    {
        return 'title';
    }

    public function handle($params, $content, Template $template, &$repeat)
    {
        global $prefs, $tiki_p_admin;
        $smarty = \TikiLib::lib('smarty');

        if ($repeat || empty($content)) {
            return;
        }

        if (! isset($params['help'])) {
            $params['help'] = '';
        }
        if (! isset($params['admpage'])) {
            $params['admpage'] = '';
        }
        if (! isset($params['actions'])) {
            $params['actions'] = '';
        }

        // Set the variable for the HTML title tag
        $smarty->assign('headtitle', $content);

        $class = '';
        $current = current_object();

        if (! isset($params['url'])) {
            if ($current) {
                $params['url'] = \SmartyTiki\Modifier\Sefurl::apply($current['object'], $current['type']);
            } else {
                $params['url'] = $_SERVER['REQUEST_URI'];
            }
        }

        $params['url'] = str_replace('"', '', $params['url']);

        $metadata = '';
        $coordinates = $current ? \TikiLib::lib('geo')->get_coordinates($current['type'], $current['object']) : null;
        if ($coordinates) {
            $class = ' geolocated primary';
            $metadata = " data-geo-lat=\"{$coordinates['lat']}\" data-geo-lon=\"{$coordinates['lon']}\"";

            if (isset($coordinates['zoom'])) {
                $metadata .= " data-geo-zoom=\"{$coordinates['zoom']}\"";
            }
        }

        $html = '<h1 class="pagetitle">';
        $html .= '<a class="' . $class . '"' . $metadata . ' href="' . $params['url'] . '">' . \SmartyTiki\Modifier\Escape::apply($content) . "</a>\n";

        if ($template->getTemplateVars('print_page') != 'y') {
            if ($prefs['feature_help'] == 'y' && $prefs['helpurl'] != '' && $params['help'] != '') {
                $html .= '<a href="';

                $html .= $prefs['helpurl'] . str_replace("%23", "#", rawurlencode($params['help'])) . '" class="tips btn btn-link" title="' . \SmartyTiki\Modifier\Escape::apply($content) . '|' . tra('Help page') . '" target="tikihelp">'
                    . \SmartyTiki\FunctionHandler\Icon::render(['name' => 'help'], $template)
                    . "</a>\n";
            }

            if ($tiki_p_admin == 'y' && $params['admpage'] != '') {
                $html .= '<a class="tips btn btn-link" href="tiki-admin.php?page=';

                $html .= $params['admpage'] . '#' . ($params['help']) . '" title="' . htmlspecialchars($content) . '|' . tra('Settings') . '">'
                    . \SmartyTiki\FunctionHandler\Icon::render(['name' => 'settings'], $template)
                    . "</a>\n";
            }
            if ($params['actions'] != '') {
                $html .= $params['actions'];
            }
        }

        $html .= '</h1>';

        return $html;
    }
}
