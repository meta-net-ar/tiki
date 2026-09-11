<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\FunctionHandler;

use Smarty\FunctionHandler\Base;
use Smarty\Template;
use TikiLib;
use SmartyTiki\TikiSmartyExtensionInterface;
use SmartyTiki\Traits\FunctionHandlerStaticFacadeTrait;

/* {user_selector
 *     user = $user
 *     select = 'user_tobe_selected'
 *     group = 'all'
 *     groupIds = ''
 *     name = 'user'
 *     id = user_selector_XX
 *     size = ''
 *     contact = 'false'
 *     multiple = 'false'
 *     editable = $tiki_p_admin
 *        allowNone = 'n'
 *     realnames = 'y'
 *  }
 *
 * Display a drop down menu of all users or
 * an input box with autocomplete if there are more users
 * than $prefs['user_selector_threshold']
 */
class UserSelector extends Base implements TikiSmartyExtensionInterface
{
    use FunctionHandlerStaticFacadeTrait;

    public static function getSmartyName(): string
    {
        return 'user_selector';
    }

    public function handle($params, Template $template)
    {
        global $prefs, $user, $tiki_p_admin;
        $tikilib = TikiLib::lib('tiki');
        $userlib = TikiLib::lib('user');

        static $iUserSelector = 0;
        $iUserSelector++;

        $defaults = [
            'user' => $user,
            'group' => 'all',
            'groupIds' => '',
            'contact' => 'false',
            'allowcreate' => 'n',
            'name' => 'user',
            'id' => 'user_selector_' . $iUserSelector,
            'multiple' => 'false',
            'mustmatch' => 'true',
            'style' => '',
            'editable' => $tiki_p_admin,
            'user_selector_threshold' => $prefs['user_selector_threshold'],
            'allowNone' => 'y',
            'noneLabel' => 'None',
            'realnames' => $prefs['user_show_realnames'],
            'class' => 'form-control',
            'lazyload' => 'false',
            'noneSelectable' => 'y',
            'exclude' => '',
        ];

        $params = array_merge($defaults, $params);
        $lazyload = filter_var($params['lazyload'], FILTER_VALIDATE_BOOLEAN);
        if (isset($params['size'])) {
            $sz = ' size="' . $params['size'] . '"';
        } else {
            $sz = '';
        }
        if ($params['editable'] != 'y') {
            $ed = ' disabled="disabled"';
        } else {
            $ed = '';
        }
        if ($params['multiple'] === 'true') {
            $mt = ' multiple="multiple"';
        } else {
            $mt = '';
        }

        $groupNames = [];
        if (is_array($params['groupIds'])) {
            foreach ($params['groupIds'] as $k => $groupId) {
                if ($groupId <= 0) {
                    unset($params['groupIds'][$k]);
                }
            }
            if (! empty($params['groupIds'])) {
                $groupIds = $params['groupIds'];
            }
        } elseif (! empty($params['groupIds'])) {
            $groupIds = explode('|', $params['groupIds']);
        }
        if (! empty($groupIds)) {
            foreach ($groupIds as $groupId) {
                $group_info = $userlib->get_groupId_info($groupId);
                $groupNames[] = $group_info['groupName'];
            }
        }

        $users = [];
        $ret = '';
        if (! empty($groupNames)) {
            $userCount = $userlib->count_users_consolidated($groupNames);
        } else {
            $userCount = $userlib->count_users('');
        }

        if ($lazyload || ($prefs['feature_elementplus'] == 'y' && $prefs['elementplus_select'] == 'y' && ($userCount > $prefs['user_selector_threshold'] || $userCount > $params['user_selector_threshold']))) {
            $urlParams = [
                'listonly' => $params['realnames'] === 'y' ? 'userrealnames' : 'users',
            ];

            if (! empty($groupNames)) {
                $urlParams['groups'] = implode(',', $groupNames);
            }

            TikiLib::setExternalContext(true);
            $remoteUrl = TikiLib::lib('service')->getUrl($urlParams);

            $options = "";
            $selectedValues = $params['select'] ?? [];
            foreach ($selectedValues as $selected) {
                $options .= "<option value=\"$selected\" selected>$selected</option>";
            }
            // Let a field opt into free-text entry (Element Plus allow-create) without
            // changing the global elementplus_select_allow_create preference.
            $allowCreateAttr = $params['allowcreate'] === 'y' ? ' data-allow-create="true"' : '';
            return <<<HTML
                <select name="{$params['name']}" id="{$params['id']}" class="form-control" data-remote-source-url="{$remoteUrl}"$allowCreateAttr $mt>{$options}</select>
            HTML;
        }

        // get the user list
        if ($params['group'] !== 'all') {
            $groupNames[] = $params['group'];
        }

        // NOTE: if groupIds are present, the list of users is limited to those groups regardless of group == 'all'
        if (! empty($groupNames)) {
            $groupNames = array_unique($groupNames);
            $usrs = [];
            foreach ($groupNames as $groupName) {
                $group_users = $userlib->get_group_users($groupName);
                $usrs = array_merge($usrs, $group_users);
            }
            $usrs = array_unique($usrs);
            foreach ($usrs as $usr) {
                $users["$usr"] = $params['realnames'] === 'y' ? \SmartyTiki\Modifier\Username::apply($usr) : $usr;
            }
        }

        if ($params['group'] == 'all' && empty($params['groupIds'])) {
            $usrs = $tikilib->list_users(0, -1, 'login_asc');
            foreach ($usrs['data'] as $usr) {
                $users["{$usr['login']}"] = $params['realnames'] === 'y' ? \SmartyTiki\Modifier\Username::apply($usr['login']) : $usr['login'];
            }
        }

        if (! empty($params['exclude'])) {
            $excludeUsers = is_array($params['exclude']) ? $params['exclude'] : explode(',', $params['exclude']);
            $excludeUsers = array_map('trim', $excludeUsers);
            foreach ($excludeUsers as $excludeUser) {
                unset($users[$excludeUser]);
            }
        }

        if ($params['realnames'] === 'y') {
            $dupes = [];
            foreach (array_count_values($users) as $usr => $c) {
                if ($c > 1) {
                    $dupes[] = $usr;
                }
            }
            foreach ($users as $usr => &$uname) {
                if (in_array($uname, $dupes)) {
                    if ($prefs['login_is_email'] === 'y' && $prefs['login_is_email_obscure'] === 'y') {
                        $added = ' (' . substr($usr, strpos($usr, '@')) . ')';
                    } else {
                        $added = " ($usr)";
                    }
                    $uname .= $added;
                }
            }
        }

        asort($users, SORT_NATURAL | SORT_FLAG_CASE);

        if (! empty($params['inputtype']) && $params['inputtype'] === 't') {
            return \SmartyTiki\FunctionHandler\JsTransferList::render([
                'fieldName' => $params['name'],
                'data' => $users,
                'defaultSelected' => $params['select'],
                'sourceListTitle' => $params['sourceListTitle'],
                'targetListTitle' => $params['targetListTitle'],
                'filterable' => $params['filterable'],
                'filterPlaceholder' => $params['filterPlaceholder'],
                'ordering' => $params['ordering'],
                'cardinalityParam' => $params['validationParam'],
                'validationMessage' => $params['validationMessage']
            ], $template);
        }

        $placeholder = '';
        if ($params['allowNone'] === 'y' && $params['multiple'] === 'true' && $params['noneLabel'] !== '') {
            // When selecting several users, "None" is not a value: the hidden input below already
            // submits the empty value. Render it as a placeholder, which clears itself once a user
            // is picked, instead of an option that can be selected alongside real users.
            $placeholder = ' placeholder="' . htmlspecialchars(tra($params['noneLabel'])) . '"';
        }

        $ret .= '<select name="' . $params['name'] . '" id="' . $params['id'] . '"' . $sz . $ed . $mt . $placeholder . ' style="' . $params['style'] . '" class="form-control">';
        if ($params['allowNone'] === 'y' && $params['multiple'] !== 'true') {
            $noneOptionAttributes = (empty($params['user']) ? ' selected="selected"' : '');
            if ($params['noneSelectable'] !== 'y') {
                $noneOptionAttributes .= ' disabled="disabled" hidden';
            }

            $ret .= '<option value=""' . $noneOptionAttributes . ' >' . tra($params['noneLabel']) . '</option>';
        }
        foreach ($users as $usr => $usersname) {
            $selected = isset($params['select']) && ($params['select'] === $usr || (is_array($params['select']) && in_array($usr, $params['select'])));
            if ($params['editable'] == 'y' || $usr == $params['user'] || $selected) {
                if (isset($params['select'])) {
                    $ret .= '<option value="' . htmlspecialchars($usr) . '"' . ($selected ? ' selected="selected"' : '') . ' >' . $usersname . '</option>';
                } else {
                    $ret .= '<option value="' . htmlspecialchars($usr) . '"' . ($usr == $params['user'] ? ' selected="selected"' : '') . ' >' . $usersname . '</option>';
                }
            }
        }
        $ret .= '</select>';

        if ($params['multiple'] === 'true' && $params['allowNone'] === 'y') {
            $ret .= '<input type="hidden" name="' . $params['name'] . '" value="">';
        }

        return $ret;
    }
}
