<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;

/**
 * Variable arguments to be sent as filters for the object list. Filters match the unified search
 * field filters.
 *
 * Reserved parameters:
 *  - _id for the field ID
 *  - _class for the field classes
 *  - _name for the field name
 *  - _value for the current value (type:objectId)
 *  - _filter is the same as all other arguements, expecting an array
 *
 * The component will build a drop list for the object selector if the results fit in a reasonable amount
 * of space or will use autocomplete on the object title otherwise.
 */
class ObjectSelectorMulti extends Base
{
    public function handle($params, Template $template)
    {
        global $prefs;
        $smarty = TikiLib::lib('smarty');
        static $uniqid = 0;
        $templateFile = 'object_selector_multi.tpl';

        $arguments = [
            'name' => null,
            'class' => null,
            'id' => null,
            'value' => null,
            'filter' => [],
            'title' => null,
            'searchfield' => $prefs['tiki_object_selector_searchfield'],
            'simplename' => null,
            'simpleid' => null,
            'simpleclass' => null,
            'simplevalue' => null,
            'separator' => null,
            'threshold' => null,
            'parent' => null,
            'parentkey' => null,
            'format' => null,
            'placeholder' => tr('Title'),
            'sort' => null,
            'wildcard' => 'n',
            'extra_type' => null,
            'selector_type_reference' => null,
            'relations' => [],
            'relationshipTrackerId' => null,
        ];

        // Handle reserved parameters
        foreach (array_keys($arguments) as $var) {
            if (isset($params["_$var"])) {
                $arguments[$var] = $params["_$var"];
            }
            unset($params["_$var"]);
        }

        if ($prefs['feature_search'] !== 'y') {
            if ($arguments['simplename'] && isset($arguments['simplevalue'])) {
                if ($params['type'] === 'trackerfield' && $arguments['separator'] === ',') {
                    $help = tra('Comma-separated list of field IDs');
                } else {
                    $help = tr('%0 list separated with "%1"', ucfirst($params['type']), $arguments['separator']);
                }
                return "<input type='text' name='{$arguments['simplename']}' value='{$arguments['simplevalue']}' size='50'>" .
                    "<div class='form-text'>" . $help . "</div>";
            } else {
                return tra('Object selector requires Unified Index to be enabled.');
            }
        }

        if (empty($arguments['id'])) {
            $arguments['id'] = 'object_selector_multi_' . ++$uniqid;
        }
        if (empty($arguments['simpleid'])) {
            $arguments['simpleid'] = 'object_selector_multi_' . ++$uniqid;
        }

        if ($arguments['filter']) {
            $arguments['filter'] = array_merge($arguments['filter'], $params);
        } else {
            $arguments['filter'] = $params;
        }

        $selector = \TikiLib::lib('objectselector');

        if ($arguments['relations']) {
            $arguments['current_selection'] = $arguments['relations'];
        } elseif ($arguments['simplevalue'] && ! empty($arguments['filter']['type']) && $arguments['separator']) {
            $arguments['current_selection'] = $selector->readMultipleSimple($arguments['filter']['type'], $arguments['simplevalue'], $arguments['separator'], $arguments['format']);
        } else {
            $arguments['current_selection'] = $selector->readMultiple($arguments['value'], $arguments['format']);
        }

        if (isset($arguments['extra_type'])) {
            $templateFile = 'object_selector_multi_extra.tpl';
        }

        if ($arguments['simplename']) {
            $arguments['class'] .= ' d-none';
        } else {
            $arguments['simpleclass'] .= ' d-none';
        }

        if ($arguments['relationshipTrackerId']) {
            $arguments['threshold'] = 0;
        }

        $arguments['current_selection_simple'] = array_map(function ($item) {
            return $item['id'];
        }, $arguments['current_selection']);

        $arguments['filter'] = json_encode($arguments['filter']);

        $smarty->assign(
            'object_selector_multi',
            $arguments
        );

        return $smarty->fetch($templateFile);
    }
}
