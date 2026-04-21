<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Class that contains helpers functions for wiki plugins
 */
class WikiPlugin_Helper
{
    /**
     * Check if analytics code should be used.
     * If $user is anonymous the analytics is always displayed.
     *
     * @param array $prefs
     * @return boolean
     */
    public static function showAnalyticsCode($prefs)
    {
        global $user;

        if ($user && ! empty($prefs['group_option'])) {
            if ($prefs['group_option'] == 'included' && empty($prefs['groups'])) {
                return true;
            }

            if ($prefs['group_option'] == 'excluded' && empty($prefs['groups'])) {
                return false;
            }

            $userlib = TikiLib::lib('user');
            $userGroups = $userlib->get_user_groups($user);
            $availableGroups = explode(',', $prefs['groups']);
            $validGroups = array_intersect($userGroups, $availableGroups);

            if (
                ($prefs['group_option'] == 'included' && empty($validGroups)) ||
                ($prefs['group_option'] == 'excluded' && ! empty($validGroups))
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * When wikiplugin is outside the normal context of wikiplugins.
     * This method help to get return of wikiplugin as string
     *
     * @param string|WikiParser_PluginOutput $result
     * @return string
     */
    public static function resultString($result): string
    {
        if ($result instanceof WikiParser_PluginOutput) {
            $result = $result->toWiki();
        }

        return $result;
    }

    /**
     * Apply default parameters from plugin info
     *
     * @param array $params Parameter values
     * @param array $info Plugin info
     * @return array Updated parameters with defaults
     */
    public static function applyParamsDefaults($params, $info)
    {
        if (isset($info['params'])) {
            foreach ($info['params'] as $key => $param) {
                if (! isset($params[$key])) {
                    if (isset($param['default'])) {
                        $params[$key] = $param['default'];
                    } else {
                        $params[$key] = null;
                    }
                }
            }
        }

        return $params;
    }

    /**
     * Validate required parameters for a plugin.
     *
     * Checks for empty values (after trimming) but does not modify
     * the original parameter array.
     *
     * @param string $pluginName Name of the plugin
     * @param array  $params Parameters to validate
     * @return array List of missing required parameters
     */
    public static function validateRequiredParams(string $pluginName, array $params): array
    {
        $infoFunction = "wikiplugin_{$pluginName}_info";

        if (! function_exists($infoFunction)) {
            return [];
        }

        $info = $infoFunction();
        $missing = [];

        foreach ($info['params'] ?? [] as $key => $definition) {
            if (! empty($definition['required'])) {
                $value = trim($params[$key] ?? '');

                if ($value === '') {
                    $missing[] = $key;
                }
            }
        }

        return $missing;
    }

    /**
     * Apply separator processing to plugin parameters
     *
     * Converts string parameters with defined separators into arrays. (eg. "1:2:3" converts to [1, 2, 3])
     *
     * @param array $params Plugin parameters
     * @param array $info Plugin info array (must contain 'params')
     * @return array Updated parameters with separators applied
     */
    public static function applySeparators($params, $info)
    {
        $tikilib = TikiLib::lib('tiki');

        if (! isset($info['params'])) {
            return $params;
        }

        foreach ($info['params'] as $key => $paramInfo) {
            if (! isset($paramInfo['separator'])) {
                continue;
            }

            // Skip if parameter not provided or is null
            if (! isset($params[$key]) || $params[$key] === null) {
                continue;
            }

            // If already an array, skip processing
            if (is_array($params[$key])) {
                continue;
            }

            // Split the string value using the separator
            $params[$key] = $tikilib->multi_explode($paramInfo['separator'], $params[$key]);
            $params[$key] = array_values(array_filter($params[$key]));
        }

        return $params;
    }
}
