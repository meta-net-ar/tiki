<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Wiki_StructureController
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_wiki');
        Services_Exception_Disabled::check('feature_wiki_structure');
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'wiki page';
    }

    public function action_save_structure($input)
    {
        $html = '';
        $data = json_decode($input->data->none());
        if (! $data) {
            return ['html' => '', 'error' => tra('Unable to save structure: invalid data.')];
        }

        $params = json_decode($input->params->none());
        if (! $params || empty($params->page_ref_id)) {
            return ['html' => '', 'error' => tra('Unable to save structure: missing structure parameters.')];
        }

        $structlib = TikiLib::lib('struct');
        $result = $structlib->reorder_structure($data);
        if ($result === false) {
            return ['html' => '', 'error' => tra('Unable to save structure: permission denied or invalid structure.')];
        }

        $_GET = [];     // self_link and query objects used by get_toc adds all this request data to the action links
        $_POST = [];

        $html = $structlib->get_toc(
            $params->page_ref_id,
            $params->order,
            $params->showdesc,
            $params->numbering,
            $params->numberPrefix,
            $params->type,
            $params->page,
            $params->maxdepth,
            $params->mindepth,
            $params->sortalpha ?? $params->mindepthsortalpha ?? 0,
            $params->structurePageName
        );

        if (strpos($html, 'data-params=') === false) {
            return ['html' => '', 'error' => tra('Unable to save structure: permission denied or invalid structure.')];
        }

        //Empty structure caches to refresh structure data in menu module. Seems better to empty cache for any possible subnodes, might make it a bit slow
        $cachelib = TikiLib::lib('cache');
        $cachelib->invalidateAll('menu');
        $cachelib->invalidateAll('structure');
        $structurePages = [];
        $structurePages = $structlib->s_get_structure_pages($params->page_ref_id);
        foreach ($structurePages as &$value) {
            $cachetype = 'structure_' . $value["page_ref_id"] . '_';
            $cachelib->invalidateAll($cachetype);
        }
        unset($value);

        return ['html' => $html];
    }
}
