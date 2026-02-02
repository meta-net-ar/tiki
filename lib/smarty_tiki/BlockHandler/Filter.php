<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

class Filter extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        global $prefs;

        if ($repeat) {
            return;
        }

        $tikilib = \TikiLib::lib('tiki');
        $unifiedsearchlib = \TikiLib::lib('unifiedsearch');
        $smarty = \TikiLib::lib('smarty');

        if (! isset($params['action'])) {
            $params['action'] = '';
        }

        $types = $unifiedsearchlib->getSupportedTypes();

        $filter = ! empty($_REQUEST['filter']) ? $_REQUEST['filter'] : [];
        if (isset($params['filter'])) {
            $filter = array_merge($filter, $params['filter']);
        }

        $filter = new \JitFilter($filter);

        // General
        $smarty->assign('filter_action', $params['action']);

        $smarty->assign('filter_content', $filter->content->text());
        $smarty->assign('filter_type', $filter->type->wordspace() ? $filter->type->wordspace() : $prefs['search_default_where']);
        $smarty->assign('filter_types', $types);

        $sort_mode = $_REQUEST['sort_mode'] ?? 'score_ndesc';
        $sort_modes = [
            'score_ndesc' => tra('Relevance'),
            'object_type_asc' => tra('Type'),
            'title_asc' => tra('Title'),
            'modification_date_ndesc' => tra('Modified date'),
            'visits_ndesc' => tra('Visits'),
        ];
        $smarty->assign('sort_mode', $sort_mode);
        $smarty->assign('sort_modes', $sort_modes);

        // Categories
        if ($prefs['feature_categories'] == 'y' && $prefs['search_show_category_filter'] == 'y') {
            $smarty->assign('filter_deep', $filter->offsetExists('deep'));
            $smarty->assign('filter_categories', explode(' ', $filter->categories->wordspace() ?? ''));

            $categlib = \TikiLib::lib('categ');
            $ctall = $categlib->getCategories();

            if ($prefs['unified_excluded_categories'] === 'y') {        // remove those excluded categs
                $ctall = array_diff_key($ctall, array_flip($prefs['unified_excluded_categories']));
            }

            $smarty->assign('categories', $ctall);
        }

        if ($prefs['feature_freetags'] == 'y' && $prefs['search_show_tag_filter'] == 'y') {
            $freetaglib = \TikiLib::lib('freetag');

            $tags_string = $filter->tags->wordspace();
            $filter_tags = $tags_string === null ? [] : explode(' ', $tags_string);

            $smarty->assign('filter_tags', $filter_tags);
            $smarty->assign('tags', \TikiDb::get()->fetchAll('SELECT tagId, tag FROM tiki_freetags'));
        }

        // Language
        if ($prefs['feature_multilingual'] == 'y') {
            $langLib = \TikiLib::lib('language');
            $languages = $langLib->list_languages();
            $smarty->assign('filter_languages', $languages);
            $smarty->assign('filter_language_unspecified', $filter->offsetExists('language_unspecified'));
            $smarty->assign('filter_language', $filter->language->text());
        }

        return $smarty->fetch('filter.tpl');
    }
}
