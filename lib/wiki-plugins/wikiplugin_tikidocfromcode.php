<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
require_once 'lib/wiki/pluginslib.php';

class WikiPluginTikiDocFromCode extends PluginsLib
{
    public $expanded_params = ['info'];
    public function getDefaultArguments()
    {
        return [
                    'info' => 'description|parameters|paraminfo',
                    'type' => '',
                    'plugin' => '',
                    'module' => '',
                    'preference' => '',
                    'trackerfield' => '',
                    'prefsexport' => '',
                    'singletitle' => 'none',
                    'titletag' => 'h3',
                    'start' => '',
                    'limit' => '',
                    'paramtype' => '',
                    'showparamtype' => 'n',
                    'showtopinfo' => 'y'
                ];
    }
    public function getName()
    {
        return 'PluginTikiDocFromCode';
    }
    public function getVersion()
    {
        return preg_replace("/[Revision: $]/", '', "\$Revision: 1.11 $");
    }
    public function getDescription()
    {
        return wikiplugin_tikidocfromcode_info();
    }
    public function run($data, $params)
    {
        global $helpurl;
        $wikilib = TikiLib::lib('wiki');
        $prefslib = TikiLib::lib('prefs');
        $trklib = TikiLib::lib('trk');
        $sOutput = '';

        if (empty($helpurl)) {
            $helpurl = 'http://doc.tiki.org/';
        }

        $params = $this->getParams($params);
        $aPlugins = [];
        extract($params, EXTR_SKIP);
        if (
            // Check for multiple defined initial variables (excluding $info)
            (! empty($module) === true ? 1 : 0) +
            (! empty($plugin) === true ? 1 : 0) +
            (! empty($preference) === true ? 1 : 0) +
            (! empty($trackerfield) === true ? 1 : 0) +
            (! empty($prefsexport) === true ? 1 : 0) >= 2
        ) {
            return $this->error(tra('Either the module, plugin, preferences, tracker field or prefsexport parameter must be set, but not two or more.'));
        } elseif (! empty($module)) {
            $aPrincipalField = ['field' => 'plugin', 'name' => 'Module'];
            $helppath = $helpurl . $aPrincipalField['name'] . ' ';
            $filepath = 'mod-func-';
            $modlib = TikiLib::lib('mod');
            $aPlugins = $modlib->list_module_files();
            $plugin = $module;
            $sourceurl = 'https://gitlab.com/tikiwiki/tiki/-/blob/master/modules/';
        } elseif (! empty($preference)) {
            $aPlugins = $prefslib->getPreference($preference);
        } elseif (! empty($trackerfield)) {
            $options = $trklib->field_types();
            $aPlugins = $trklib->getFieldTypeByLabel($options, $trackerfield);
        } elseif (! empty($prefsexport)) {
            $selectedFields = explode('|', $prefsexport);
            $sOutput = $this->generateFieldsExport($selectedFields);
        } elseif (
            // One of the variables below, not two or more, plus $info set
            $this->exactlyOneSet([[$module, $plugin, $preference, $trackerfield]]) && isset($info)
        ) {
            $aPrincipalField = ['field' => 'plugin', 'name' => 'Plugin'];
            $helppath = $helpurl . $aPrincipalField['name'];
            $filepath = WIKIPLUGINS_SRC_PATH . '/wikiplugin_';
            $aPlugins = $wikilib->list_plugins();
            $sourceurl = 'https://gitlab.com/tikiwiki/tiki/-/blob/master/';
            $type = "plugin";
        }

        $all = $aPlugins;
        //if the user set $module, that setting has now been moved to $plugin so that one code set is used
        //$aPlugins and $all now has the complete list of plugin or module file names - the code below modifies $aPlugins
        //if necessary based on user settings
        if (! empty($plugin)) {
            if (strpos($plugin, '|') !== false) {
                $aPlugins = [];
                $userlist = explode('|', $plugin);
                foreach ($userlist as $useritem) {
                    $file = $filepath . $useritem . '.php';
                    $confirm = in_array($file, $all);
                    if ($confirm === false) {
                        return '{BOX(class="text-bg-light")}' . tr('Tiki Doc From Code error: %0%1 not found', $useritem, $type) . '{BOX}';
                    } else {
                        $aPlugins[] = $file;
                    }
                }
            } elseif (strpos($plugin, '-') !== false) {
                $userrange = explode('-', $plugin);
                $begin = array_search($filepath . $userrange[0] . '.php', $aPlugins);
                $end = array_search($filepath . $userrange[1] . '.php', $aPlugins);
                $beginerror = '';
                $enderror = '';
                $type2 = $type;
                if ($begin === false || $end === false) {
                    if ($begin === false) {
                        $beginerror = $userrange[0];
                    }
                    if ($end === false) {
                        $enderror = $userrange[1];
                        if (! empty($beginerror)) {
                            $and = ' and ';
                        } else {
                            $and = '';
                            $type = '';
                        }
                    }
                    return '{BOX(class="text-bg-light")}' . tr('Tiki Doc From Code error: %0%1%2%3%4 not found', $beginerror, $type, $and, $enderror, $type2) . '{BOX}';
                } elseif ($end > $begin) {
                    $aPlugins = array_slice($aPlugins, $begin, $end - $begin + 1);
                } else {
                    $aPlugins = array_slice($aPlugins, $end, $begin - $end + 1);
                }
            } elseif (! empty($limit)) {
                $begin = array_search($filepath . $plugin . '.php', $aPlugins);
                if ($begin === false) {
                    return '{BOX(class="text-bg-light")}' . tr('Tiki Doc From Code error: %0%1 not found', $begin, $type) . '{BOX)}';
                } else {
                    $aPlugins = array_slice($aPlugins, $begin, $limit);
                }
            } elseif ($plugin != 'all') {
                $file = $filepath . $plugin . '.php';
                $confirm = in_array($file, $aPlugins);
                if ($confirm === false) {
                    return '{BOX(class="text-bg-light")}' . tr('Tiki Doc From Code error:  %0%1 not found', $plugin, $type) . '{BOX}';
                } else {
                    $aPlugins = [];
                    $aPlugins[] = $file;
                }
            }
        } elseif (! empty($preference) || ! empty($trackerfield)) {
            $object = ! empty($preference) ? $preference : $trackerfield;
            // Generate a list of elements based on separators
            if (strpos($object, '|') !== false || strpos($object, '-') !== false) {
                $separator = '|'; // Default separator
                if (strpos($object, '|') === false) {
                    $separator = '-';
                } elseif (strpos($object, '-') !== false && strpos($object, '-') < strpos($object, '|')) {
                    $separator = '-';
                }
                $objectsArr = explode($separator, $object);
            } else {
                // If the object doesn't contain | or -
                $objectsArr[] = $object;
            }
        } else {
            if (! empty($start) || ! empty($limit)) {
                if (! empty($start) && ! empty($limit)) {
                    $aPlugins = array_slice($aPlugins, $start - 1, $limit);
                } elseif (! empty($start)) {
                    $aPlugins = array_slice($aPlugins, $start - 1);
                } else {
                    $aPlugins = array_slice($aPlugins, 0, $limit);
                }
            }
        }

        global $sPlugin, $numparams;
        $title = '';
        $pluginDetails = '';

        // Manage the output of plugins and modules
        if (($type === "plugin" || $type === "module") || (! empty($plugin) || ! empty($module))) {
            if ($type === "module" || ! empty($module)) {
                $sourcecode = $sourceurl . $aPlugins[0];
                $infoPlugin = get_module_parameters($aPlugins[0]);
                $namepath = $sPlugin;
            } elseif ($type === "plugin" || ! empty($plugin)) {
                $sourcecode = $sourceurl . $aPlugins[0];
                $infoPlugin = get_plugin_informations($aPlugins[0]);
                $namepath = ucfirst($sPlugin);
            }

            //single title table
            if ($singletitle == 'table' || count($aPlugins) > 1) {
                $aData = [];
                foreach ($aPlugins as $sPluginFile) {
                    global $sPlugin, $numparams;
                    if ($type === "module" || ! empty($module)) {
                        $infoPlugin = get_module_parameters($sPluginFile);
                        $namepath = $sPlugin;
                    } else {
                        $infoPlugin = get_plugin_informations($sPluginFile);
                        $namepath = ucfirst($sPlugin);
                    }
                    if (in_array('description', $info)) {
                        if (isset($infoPlugin['description'])) {
                            if ($numparams > 1) {
                                $aData[$sPlugin]['description']['onekey'] = $infoPlugin['description'];
                            } else {
                                $aData[$sPlugin]['description'] = $infoPlugin['description'];
                            }
                        } else {
                            $aData[$sPlugin]['description'] = ' --- ';
                        }
                    }
                    if (in_array('sourcecode', $info)) {
                        if ($numparams > 1) {
                            $aData[$sPlugin]['sourcecode']['onekey'] = '[' . $sourceurl . $sPluginFile . '|' . tra('Go to the source code') . ']';
                        } else {
                            $aData[$sPlugin]['sourcecode'] = '[' . $sourceurl . $sPluginFile . '|' . tra('Go to the source code') . ']';
                        }
                    }
                    if (in_array('parameters', $info)) {
                        if ($numparams > 0) {
                            if ($aPrincipalField['field'] == 'plugin' && ! in_array('options', $info) && $numparams > 1) {
                                $aData[$sPlugin][$aPrincipalField['field']]['rowspan'] = $numparams;
                                if (in_array('description', $info)) {
                                    $aData[$sPlugin]['description']['rowspan'] = $numparams;
                                }
                            }
                            foreach ($infoPlugin['params'] as $paramname => $param) {
                                if (isset($infoPlugin['params'][$paramname]['description'])) {
                                    $paramblock = '~np~' . $infoPlugin['params'][$paramname]['description'] . '~/np~';
                                }
                                if (isset($param['options']) && is_array($param['options'])) {
                                    $paramblock .= '<br /><em>' . tra('Options:') . '</em> ';
                                    $i = 0;
                                    foreach ($param['options'] as $oplist => $opitem) {
                                        if (isset($opitem['value'])) {
                                            $paramblock .= $opitem['value'];
                                        } else {
                                            $paramblock .= $opitem['text'];
                                        }
                                        $paramblock .= ' | ';
                                        $i++;
                                    }
                                    $paramblock = substr($paramblock, 0, -3);
                                }
                                if (isset($infoPlugin['params'][$paramname]['required']) && $infoPlugin['params'][$paramname]['required'] == true) {
                                    $aData[$sPlugin]['parameters']['<b><code>' . $paramname . '</code></b>'] = $paramblock;
                                } else {
                                    $aData[$sPlugin]['parameters']['<code>' . $paramname . '</code>'] = $paramblock;
                                }
                            }
                        } else {
                            $aData[$sPlugin]['parameters']['<em>no parameters</em>'] = '<em>' . tra('n/a') . '</em>';
                        }
                    }
                    $aData[$sPlugin]['plugin']['plugin'] = '[' . $helppath . $namepath . '|' . ucfirst($sPlugin) . ']';
                } // Plugins Loop
                return PluginsLibUtil::createTable($aData, $info, $aPrincipalField);
            } else {
                //Replicates a documentation table for parameters for a single plugin or module
                //Not using plugin lib table to avoid making custom modifications

                $headbegin = "\n\t\t" . '<th class="heading">';
                $cellbegin = "\n\t\t" . '<td>';
                $header = "\n\t" . '<tr class="heading sticky-top bg-light">' . $headbegin . 'Parameters</th>';
                $rows = '';
                if (isset($numparams) && $numparams > 0) {
                    $header .= $headbegin . tra('Accepted Values') . '</th>';
                        $header .= $headbegin . tra('Description') . '</th>';
                    $rowCounter = 1;
                    //sort required params first
                    $reqarray = array_column($infoPlugin['params'], 'required');
                    $keysarray = array_keys($infoPlugin['params']);
                    $reqarray = array_combine($keysarray, $reqarray);
                    if (count($reqarray) == count($infoPlugin['params'])) {
                        array_multisort($reqarray, SORT_DESC, $infoPlugin['params']);
                    }
                    //add body instructions to the parameter array
                    if (! empty($infoPlugin['body'])) {
                        $body = ['(body of plugin)' => ['description' => $infoPlugin['body']]];
                        $infoPlugin['params'] = array_merge($body, $infoPlugin['params']);
                    }
                    $count = 1;
                    foreach ($infoPlugin['params'] as $paramname => $paraminfo) {
                        unset($sep, $septext);
                        //check is paramtype filter is set
                        if (
                            empty($params['paramtype'])
                            || ((empty($paraminfo['doctype']) && ! empty($params['paramtype']) && $params['paramtype'] === 'none')
                            || (! empty($paraminfo['doctype']) && $params['paramtype'] == $paraminfo['doctype']))
                        ) {
                            $filteredparams[] = $paraminfo;
                            $rows .= "\n\t" . '<tr style="word-break: break-word;">' . $cellbegin;
                            //Parameters column
                            if (isset($paraminfo['required']) && $paraminfo['required'] == true) {
                                $rows .= '<strong><code>' . $paramname . '</code></strong>';
                            } elseif ($paramname == '(body of plugin)') {
                                $rows .= tra('(body of plugin)');
                            } else {
                                $rows .= '<code>' . $paramname . '</code>' ;
                            }
                            if (
                                isset($params['showparamtype']) && $params['showparamtype'] === 'y'
                                && ! empty($paraminfo['doctype'])
                            ) {
                                $rows .= '<br /><small>(' . $paraminfo['doctype'] . ')</small>';
                            }
                            $rows .= '</td>';
                            //Accepted Values column
                            $rows .= $cellbegin;
                            if (isset($paraminfo['separator'])) {
                                $sep = $paraminfo['separator'];
                                $septext = tr('%0separator:%1 ', '<em>', '</em>');
                                if (is_string($paraminfo['separator'])) {
                                    $septext .= '<code>' . $paraminfo['separator'] . '</code>';
                                } elseif (is_array($paraminfo['separator'])) {
                                    $septext .= implode(' ', array_map(function ($separator) {
                                        return '<code>' . $separator . '</code>';
                                    }, $paraminfo['separator']));
                                }
                            } else {
                                $sep = '| ';
                            }
                            if (isset($paraminfo['accepted'])) {
                                if (isset($septext)) {
                                    $rows .= '<br />' . $septext;
                                }
                                $rows .= '</td>';
                            } elseif (isset($paraminfo['options'])) {
                                $optcounter = 1;
                                $numoptions = count($paraminfo['options']);
                                foreach ($paraminfo['options'] as $oplist => $opitem) {
                                    if (isset($opitem['value'])) {
                                        $rows .= strlen($opitem['value']) == 0 ? tra('(blank)') : $opitem['value'];
                                    } else {
                                        $rows .= tra('(blank)'); // Set a default value if 'value' key is not defined
                                    }
                                    if ($optcounter < $numoptions) {
                                        if ($numoptions > 10) {
                                            $rows .= $sep;
                                        } else {
                                            $rows .= '<br />';
                                        }
                                    }
                                    $optcounter++;
                                }
                                if (isset($septext)) {
                                    $rows .= '<br />' . $septext;
                                }
                                $rows .= '</td>';
                            } elseif (isset($paraminfo['filter'])) {
                                if ($paraminfo['filter'] == 'striptags') {
                                    $rows .= tra('any string except for HTML and PHP tags');
                                } else {
                                    $rows .= $paraminfo['filter'];
                                }
                                if (isset($septext)) {
                                    $rows .= '<br />' . $septext;
                                }
                                $rows .= '</td>';
                            } else {
                                if (isset($septext)) {
                                    $rows .= '<br />' . $septext;
                                }
                                $rows .= '</td>';
                            }
                            //Description column
                            if (isset($paraminfo['description'])) {
                                $rows .= $cellbegin . $paraminfo['description'] . '</td>';
                            }
                            //Default column
                            if ($rowCounter == 1) {
                                $header .= $headbegin . tra('Default') . '</th>';
                            }
                            if (! isset($paraminfo['default'])) {
                                $paraminfo['default'] = '';
                            }
                            $default = is_array($paraminfo['default']) ? implode(',', $paraminfo['default']) : $paraminfo['default'];
                            $rows .= $cellbegin . $default . '</td>';
                            //Since column
                            if ($rowCounter == 1) {
                                $header .= $headbegin . tra('Since') . '</th>';
                            }
                            $since = ! empty($paraminfo['since']) ? $paraminfo['since'] : '';
                            $rows .= $cellbegin . $since . '</td>';
                            $rows .= "\n\t" . '</tr>';
                            $rowCounter++;
                        }
                    }
                    if (! empty($infoPlugin['additional']) && (empty($params['paramtype']) || $params['paramtype'] === 'none')) {
                        $rows .= '<tr><td colspan="5">' . $infoPlugin['additional'] . '</td></tr>';
                    }
                } else {
                    if (! empty($infoPlugin['body'])) {
                        $rows .= "\n\t" . '<tr>' . $cellbegin . '<em>' . tra('(body of plugin)') . ' - </em>'
                            . $infoPlugin['body'] . '</td>';
                    }
                    $rows .= "\n\t" . '<tr>' . $cellbegin . '<em>' . tra('no parameters') . '</em></td>';
                }
                $header .= "\n\t" . '</tr>';
                $pluginprefs = ! empty($infoPlugin['prefs']) && $params['showtopinfo'] !== 'n' ? '<em>'
                    . tra('Preferences required:') . '</em> ' . implode(', ', $infoPlugin['prefs']) . '<br/>' : '';
                $title .= isset($infoPlugin['introduced']) && $params['showtopinfo'] !== 'n' ? '<em>' .
                    tr('Introduced in %0', 'Tiki ' . $infoPlugin['introduced']) . '.</em>' : '';
                $link = '[' . $sourcecode . '|' . tra('Go to the source code') . ']';
                $required = ! empty($filteredparams) ? array_column($filteredparams, 'required') : [];
                $bold = in_array(true, $required) > 0 ? '<em> ' . tr(
                    'Required parameters are in%0 %1bold%2',
                    '</em>',
                    '<strong><code>',
                    '</code></strong>.'
                ) : '';
                $description = $infoPlugin['description'];
                $sOutput = $description . '<br/>' . $title . $bold . '<br>' . $link . '<br>' . $pluginprefs . '<br> <div class="table-responsive overflow-visible">' .
                    '<table class="table table-striped table-hover">' . $header . $rows . '</table></div>' . "\n";
            }
        } elseif (($type === "preference" || ! empty($preference)) || ($type === "trackerfield" || ! empty($trackerfield))) {
            $pluginDetails = '';
            $tContent = '';
            $sOutput = '';
            // Manage the output of preference & trackerfield
            if ($singletitle == "table") {
                foreach ($objectsArr as $object) {
                    if ($type === "preference" || ! empty($preference)) {
                        $objectData = $prefslib->getPreference($object);
                    } else {
                        $objectData = $trklib->getFieldTypeByLabel($options, $object);
                    }
                    if (is_array($objectData) && ! empty($objectData)) {
                        foreach ($objectData as $key => $value) {
                            if (is_array($value) && ! empty($value)) {
                                $value = implode(', ', $value) . '.';
                            } elseif (is_array($value) && empty($value)) {
                                $value = '';
                            }

                            $pluginDetails .= '<tr><td class="wikicell">' . $key . ':</td> <td> ' . $value . ' </td></tr>';
                        }

                        $tContent .= '
                        <tr>
                        <th colspan="2"><b>' . ucfirst($type) . '</b>: <em>' . $object . '</em></th>
                        </tr>
                        <tr>
                        <th><b>' . tra('Option') . ' </b></th>
                        <th><b>' . tra('Description') . '</b></th>
                        </tr> ' . $pluginDetails;
                    } else {
                        $tContent .= '
                        <tr>
                        <td class="text-center" colspan="2">
                        <b>Tiki Doc From Code</b>: ' . $object . ' ' . $type . ' not found
                        </td>
                        </tr>
                        ';
                    }
                }
                $sOutput = '<div class="table-responsive overflow-visible">' .
                    '<table class="table table-striped table-hover"><tbody>' . $tContent . '</tbody></table></div>' . "\n";
            } else {
                $infoPlugin = $all;
                if (is_array($infoPlugin) && ! empty($infoPlugin)) {
                    foreach ($infoPlugin as $key => $value) {
                        if (is_array($value) && ! empty($value)) {
                            $value = implode(', ', $value) . '.';
                        } elseif (is_array($value) && empty($value)) {
                            $value = '';
                        }

                        $pluginDetails .= '<tr><td class="wikicell">' . $key . ':</td> <td> ' . $value . ' </td></tr>';
                    }
                } else {
                    $pluginDetails .= '
                    <tr>
                    <td class="text-center" colspan="2">
                    <b>Tiki Doc From Code</b>: ' . $preference ?? $trackerfield . ' ' . $type . ' not found
                    </td>
                    </tr>
                    ';
                }
                $sOutput .= '<div class="table-responsive overflow-visible">' .
                    '<table class="table table-striped table-hover"><tbody>
                <tr>
                    <th colspan="2"><b>' . ucfirst($type) . ' </b></th>
                </tr>
                <tr>
                <td><b>' . tra('Option') . ' </b></td>
                <td><b>' . tra('Description') . '<b></td>
                </tr>' . $pluginDetails . '</tbody></table></div>' . "\n";
            }
        }
        return $sOutput;
    }
    public function processDescription($sDescription)
    {
        $sDescription = str_replace(',', ', ', $sDescription);
        $sDescription = str_replace('|', '| ', $sDescription);
        $sDescription = strip_tags(wordwrap($sDescription, 35));
        return $sDescription;
    }
    public function exactlyOneSet($vars)
    {
        $count = 0;
        foreach ($vars as $var) {
            if (isset($var)) {
                $count++;
                if ($count > 1) {
                    return false;
                }
            }
        }
        return $count === 1;
    }
    protected function generateFieldsExport($selectedFields): string
    {
        $defaultValues = get_default_prefs();
        $fields = $this->prepareFields();
        $stopWords = ['', 'in', 'and', 'a', 'to', 'be', 'of', 'on', 'the', 'for', 'as', 'it', 'or', 'with', 'by', 'is', 'an'];

        $data = [];

        $data = $this->collectRawData($fields);
        $this->removeFakeDescriptions($data);
        $this->setDefaultValues($data, $defaultValues);
        $this->collectLocations($data);
        $index = [
            'name' => $this->indexData($data, 'name'),
            'description' => $this->indexData($data, 'description'),
        ];
        $this->updateSearchFlag($data, $index, $stopWords);

        // Selected fields to display and set default values
        empty($selectedFields) ? ['name', 'description', 'locations'] : $selectedFields;

        // Start building the table
        $table = '<div class="table-responsive"><table class="table table-striped table-bordered">';
        $table .= '<thead><tr>';

        // Display selected fields in the table header
        foreach ($selectedFields as $field) {
            $table .= "<th style='word-wrap: break-word'>$field</th>";
        }
        $table .= '</tr></thead>';
        $table .= '<tbody>';

        // Loop through the data to populate the table rows
        foreach ($data as $values) {
            $table .= '<tr>';
            foreach ($selectedFields as $field) {
                $table .= "<td>{$values[$field]}</td>";
            }
            $table .= '</tr>';
        }

        $table .= '</tbody></table></div>';
        return $table;
    }
    private function prepareFields(): array
    {
        // Return fields
        return [
            'preference' => '',
            'hard_to_search' => false,
            'duplicate_name' => 0,
            'duplicate_description' => 0,
            'word_count' => 0,
            'filter' => '',
            'name' => '',
            'help' => '',
            'default' => '',
            'description' => '',
            'locations' => '',
            'dependencies' => '',
            'type' => '',
            'options' => '',
            'admin' => '',
            'module' => '',
            'view' => '',
            'permission' => '',
            'plugin' => '',
            'extensions' => '',
            'tags' => '',
            'parameters' => '',
            'detail' => '',
            'warning' => '',
            'hint' => '',
            'shorthint' => '',
            'perspective' => '',
            'separator' => '',
        ];
    }
    /**
     * @param $fields
     * @return array
     */
    public function collectRawData($fields)
    {
        $data = [];

        foreach (glob('lib/prefs/*.php') as $file) {
            $name = substr(basename($file), 0, -4);
            $function = "prefs_{$name}_list";

            if ($name == 'index') {
                continue;
            }

            include $file;
            $list = $function();

            foreach ($list as $name => $raw) {
                $entry = $fields;

                $entry['preference'] = $name;
                $entry['name'] = isset($raw['name']) ? $raw['name'] : '';
                $entry['description'] = isset($raw['description']) ? $raw['description'] : '';
                $entry['filter'] = isset($raw['filter']) ? $raw['filter'] : '';
                $entry['help'] = isset($raw['help']) ? $raw['help'] : '';
                $entry['dependencies'] = ! empty($raw['dependencies']) ? implode(',', (array) $raw['dependencies']) : '';
                $entry['type'] = isset($raw['type']) ? $raw['type'] : '';
                $entry['options'] = isset($raw['options']) ? implode(',', $raw['options']) : '';
                $entry['admin'] = isset($raw['admin']) ? $raw['admin'] : '';
                $entry['module'] = isset($raw['module']) ? $raw['module'] : '';
                $entry['view'] = isset($raw['view']) ? $raw['view'] : '';
                $entry['permission'] = isset($raw['permission']) ? implode(',', $raw['permission']) : '';
                $entry['plugin'] = isset($raw['plugin']) ? $raw['plugin'] : '';
                $entry['extensions'] = isset($raw['extensions']) ? implode(',', $raw['extensions']) : '';
                $entry['tags'] = isset($raw['tags']) ? implode(',', $raw['tags']) : '';
                $entry['parameters'] = isset($raw['parameters']) ? implode(',', $raw['parameters']) : '';
                $entry['detail'] = isset($raw['detail']) ? $raw['detail'] : '';
                $entry['warning'] = isset($raw['warning']) ? $raw['warning'] : '';
                $entry['hint'] = isset($raw['hint']) ? $raw['hint'] : '';
                $entry['shorthint'] = isset($raw['shorthint']) ? $raw['shorthint'] : '';
                $entry['perspective'] = isset($raw['perspective']) ? $raw['perspective'] ? 'true' : 'false' : '';
                $entry['separator'] = isset($raw['separator']) ? $raw['separator'] : '';
                $data[] = $entry;
            }
        }

        return $data;
    }
    /**
     * @param $data
     * @return array
     */
    public function removeFakeDescriptions(&$data)
    {
        foreach ($data as & $row) {
            if ($row['name'] == $row['description']) {
                $row['description'] = '';
            }
        }
    }
    /**
     * @param $data
     * @param $prefs
     */
    public function setDefaultValues(&$data, $prefs)
    {
        foreach ($data as & $row) {
            $row['default'] = isset($prefs[$row['preference']]) ? $prefs[$row['preference']] : '';

            if (is_array($row['default'])) {
                $row['default'] = implode($row['separator'], $row['default']);
            }
        }
    }
    /**
     * @param $data
     */
    public function collectLocations(&$data)
    {
        $prefslib = TikiLib::lib('prefs');

        foreach ($data as & $row) {
            $pages = $prefslib->getPreferenceLocations($row['preference']);
            foreach ($pages as & $page) {
                $page = $page[0] . '/' . $page[1];
            }
            $row['locations'] = implode(', ', $pages);
        }
    }
    /**
    * @param $data
    * @param $field
    * @return array
    */
    public function indexData($data, $field)
    {
        $index = [];

        foreach ($data as $row) {
            $value = strtolower($row[$field]);

            if (! isset($index[$value])) {
                $index[$value] = 0;
            }

            $index[$value]++;
        }

        return $index;
    }
     /**
     * @param $data
     * @param $index
     * @param $stopWords
     */
    public function updateSearchFlag(&$data, $index, $stopWords)
    {
        foreach ($data as & $row) {
            $name = strtolower($row['name']);
            $description = strtolower($row['description']);

            $words = array_diff(explode(' ', $name . ' ' . $description), $stopWords);

            $row['duplicate_name'] = $index['name'][$name];
            if (! empty($description)) {
                $row['duplicate_description'] = $index['description'][$description];
            }
            $row['word_count'] = count($words);

            if (count($words) < 5) {
                $row['hard_to_search'] = 'X';
            } elseif ($index['name'][$name] > 2) {
                $row['hard_to_search'] = 'X';
            } elseif ($index['description'][$description] > 2) {
                $row['hard_to_search'] = 'X';
            }
        }
    }
}

function wikiplugin_tikidocfromcode_info()
{
    return [
        'name' => tra('Plugin Tiki Docs'),
        'documentation' => 'PluginTikiDocFromCode',
        'description' => tra('List wiki plugin, module, preference or tracker field documentation for the site'),
        'prefs' => [ 'wikiplugin_tikidocfromcode' ],
        'introduced' => 27,
        'iconname' => 'plugin',
        'params' => [
            'info' => [
                'required' => false,
                'name' => tra('Information'),
                'description' => tr('Determines what information is shown. Values separated with %0|%1.
                    Ignored when %0singletitle%1 is set to %0top%1 or %0none%1.', '<code>', '</code>'),
                   'filter' => 'text',
                'accepted' => tra('One or more of: description | parameters | paraminfo'),
                'default' => 'description | parameters | paraminfo ',
                'since' => '27',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Description'), 'value' => 'description'],
                    ['text' => tra('Description and Source Code'), 'value' => 'description|sourcecode'],
                    ['text' => tra('Description and Parameters'), 'value' => 'description|parameters'],
                    ['text' => tra('Description & Parameter Info'), 'value' => 'description|paraminfo'],
                    ['text' => tra('Parameters & Parameter Info'), 'value' => 'parameters|paraminfo'],
                    ['text' => tra('All'), 'value' => 'description|parameters|paraminfo']
                ]
            ],
            'plugin' => [
                'required' => false,
                'name' => tra('Plugin'),
                'description' => tr('Name of a plugin (e.g., backlinks), or list separated by %0|%1, or range separated
                     by %0-%1. Single plugin can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'module' => [
                'required' => false,
                'name' => tra('Module'),
                'description' => tr('Name of a module (e.g., calendar_new), or list separated by %0|%1, or range separated
                    by %0-%1. Single module can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'preference' => [
                'required' => false,
                'name' => tra('Preference'),
                'description' => tr('Name of a preference (e.g., bigbluebutton_dynamic_configuration), or list separated by %0|%1, or range separated
                    by %0-%1. Single preference can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'trackerfield' => [
                'required' => false,
                'name' => tra('Tracker field'),
                'description' => tr('Name of a tracker field, or list separated by %0|%1, or range separated
                    by %0-%1. Single preference can be used with %0limit%1 parameter.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'prefsexport' => [
                'required' => false,
                'name' => tra('Preferences export'),
                'description' => tr('Default preferences export with columns name, description, location or list more separated by %0|%1.', '<code>', '</code>'),
                'filter' => 'text',
                'default' => 'name | description | locations',
                'since' => '27',
            ],
            'type' => [
                'required' => false,
                'name' => tra('Type'),
                'description' => tr('Type of the object to get tiki doc from (e.g: plugin, module,  preference, ...)'),
                'filter' => 'text',
                'default' => '',
                'since' => '27',
            ],
            'singletitle' => [
                'required' => false,
                'name' => tra('Single Title'),
                'description' => tr('Set placement of plugin name and description when displaying information for only one plugin'),
                'filter' => 'alpha',
                'default' => 'none',
                'since' => '27',
                'options' => [
                    ['text' => tra(''), 'value' => ''],
                    ['text' => tra('Top'), 'value' => 'top'],
                    ['text' => tra('Table'), 'value' => 'table'],
                ],
            ],
            'titletag' => [
                'required' => false,
                'name' => tra('Title Heading'),
                'description' => tr('Sets the heading size for the title, e.g., %0h2%1.', '<code>', '</code>'),
                'filter' => 'alnum',
                'default' => 'h3',
                'since' => '27',
                'advanced' => true,
            ],
            'start' => [
                'required' => false,
                'name' => tra('Start'),
                'description' => tra('Start with this plugin record number (must be an integer 1 or greater).'),
                'filter' => 'digits',
                'default' => '',
                'since' => '27',
            ],
            'limit' => [
                'required' => false,
                'name' => tra('Limit'),
                'description' => tra('Number of plugins to show. Can be used either with start or plugin as the starting
                    point. Must be an integer 1 or greater.'),
                'filter' => 'digits',
                'default' => '',
                'since' => '27',
            ],
            'paramtype' => [
                'required' => false,
                'name' => tra('Parameter Type'),
                'description' => tr('Only list parameters with this %0doctype%1 setting. Set to %0none%1 to show only
                    parameters without a type setting and the body instructions.', '<code>', '</code>'),
                'since' => '27',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
            ],
            'showparamtype' => [
                'required' => false,
                'name' => tra('Show Parameter Type'),
                'description' => tr('Show the parameter %0doctype%1 value.', '<code>', '</code>'),
                'since' => '27',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
            'showtopinfo' => [
                'required' => false,
                'name' => tra('Show Top Info'),
                'description' => tr('Show information above the table regarding preferences required and the first
                    version when the plugin became available. Shown by default.'),
                'since' => '27',
                'filter' => 'alpha',
                'default' => '',
                'advanced' => true,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
            ],
        ],
    ];
}

function get_plugin_informations($sPluginFile)
{
    preg_match("/wikiplugin_(.*)\.php/i", $sPluginFile, $match);
    global $sPlugin, $numparams;
    $sPlugin = $match[1];
    include_once($sPluginFile);
    global $tikilib;
    $parserlib = TikiLib::lib('parser');

    $infoPlugin = $parserlib->plugin_info($sPlugin);
    $numparams = isset($infoPlugin['params']) ? count($infoPlugin['params']) : 0;
    return $infoPlugin;
}

function get_module_parameters($sPluginFile)
{
    preg_match("/mod-func-(.*)\.php/i", $sPluginFile, $match);
    global $sPlugin, $numparams;
    $sPlugin = $match[1];
    include_once('modules/' . $sPluginFile);
    $info_func = "module_{$sPlugin}_info";
    $infoPlugin = $info_func();
    $infoPluginParams = $infoPlugin['params'] ?? [];
    foreach ($infoPluginParams as &$param) {
        $param['required'] = ! empty($param['required']);
    }
    $numparams = isset($infoPluginParams) ? count($infoPluginParams) : 0;
    $infoPlugin['params'] = $infoPluginParams;
    return $infoPlugin;
}

function wikiplugin_tikidocfromcode($data, $params)
{
    $plugin = new WikiPluginTikiDocFromCode();
    return $plugin->run($data, $params);
}
