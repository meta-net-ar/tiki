<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Command\ListExecuteCommand;
use Tiki\Lib\Wiki\PluginsLibUtil;

function wikiplugin_listexecute_info()
{
    return [
        'name' => tra('List Execute'),
        'documentation' => 'PluginListExecute',
        'description' => tra('Set custom actions that can be executed on a filtered list of objects'),
        'prefs' => ['wikiplugin_listexecute', 'feature_search'],
        'body' => tra('List configuration information'),
        'validate' => 'all',
        'filter' => 'wikicontent',
        'profile_reference' => 'search_plugin_content',
        'iconname' => 'list',
        'introduced' => 11,
        'tags' => [ 'advanced' ],
        'params' => [
            'button_label' => [
                'required' => false,
                'name' => tra('Button label'),
                'description' => tr(
                    'Label for the form submit button. Default is %0.',
                    '<code>' . tr('Apply') . '</code>'
                ),
                'since' => '27.3',
                'default' => 'Apply'
            ]
        ],
    ];
}

function wikiplugin_listexecute($data, $params, $offset, $parser)
{
    global $prefs, $tiki_p_modify_object_categories, $tiki_p_admin_categories;

    static $iListExecute = 0;
    $iListExecute++;
    $fingerprint = "listexecute-$iListExecute";

    $unifiedsearchlib = TikiLib::lib('unifiedsearch');

    TikiLib::lib('header')->add_jsfile('lib/jquery_tiki/wikiplugin-listexecute.js', true);

    $actions = [];

    $factory = new Search_Action_Factory();
    $factory->register(
        [
            'change_status' => 'Search_Action_ChangeStatusAction',
            'data_channel' => 'Search_Action_DataChannel',
            'delete' => 'Search_Action_Delete',
            'email' => 'Search_Action_EmailAction',
            'filegal_change_filename' => 'Search_Action_FileGalleryChangeFilename',
            'filegal_image_overlay' => 'Search_Action_FileGalleryImageOverlay',
            'snapshot' => 'Search_Action_Snapshot',
            'tracker_item_clone' => 'Search_Action_TrackerItemClone',
            'tracker_item_insert' => 'Search_Action_TrackerItemInsert',
            'tracker_item_modify' => 'Search_Action_TrackerItemModify',
            'user_group_modify' => 'Search_Action_UserGroupModify',
            'wiki_approval' => 'Search_Action_WikiApprovalAction',
            'categorize_object' => 'Search_Action_CategorizeObjectAction',
            'export_pdf' => 'SearchActionExportPDF',
        ]
    );

    $query = new Search_Query();
    $unifiedsearchlib->initQuery($query);

    $matches = WikiParser_PluginMatcher::match($data);

    $builder = new Search_Query_WikiBuilder($query);
    $builder->apply($matches, true);
    $tsret = $builder->applyTablesorter($matches, true);
    if (! empty($tsret['max']) || ! empty($_GET['numrows'])) {
        $max = ! empty($_GET['numrows']) ? $_GET['numrows'] : $tsret['max'];
        $builder->wpquery_pagination_max($query, $max);
        $builder->applyPagination();
    }
    $paginationArguments = $builder->getPaginationArguments();

    if (! empty($_REQUEST[$paginationArguments['sort_arg']])) {
        $query->setOrder($_REQUEST[$paginationArguments['sort_arg']]);
    }

    $selectedObjects = $_POST["objects$iListExecute"] ?? [];

    if (! empty($selectedObjects)) {
        $searchQuery = clone $query;
        if (in_array('ALL', $selectedObjects)) {
            // unified search needs a hard limit and we want to apply the action to as many items as possible
            $query->setRange(0, 9999);
        } else {
            // select only the items to apply the action to
            foreach ($selectedObjects as $identifier) {
                if (! empty($identifier)) {
                    list($type, $id) = explode(':', $identifier);
                    if ($type && $type != 'aggregate' && $id) {
                        $query->addObject($type, $id);
                    }
                }
            }
        }
    }

    $customOutput = false;
    foreach ($matches as $match) {
        $name = $match->getName();

        if ($name == 'action') {
            $action = $factory->fromMatch($match);

            if ($action && $action->isAllowed(Perms::get()->getGroups())) {
                $actions[$action->getName()] = $action;
            } else {
                foreach ($action->getSteps() as $step) {
                    if ($step instanceof Search_Action_UnknownStep) {
                        Feedback::error(tr("Invalid action: %0.", $step->getName()));
                    }
                }
            }
        }

        if ($name == 'output') {
            $customOutput = true;
        }
    }

    $index = $unifiedsearchlib->getIndex();

    PluginsLibUtil::handleDownload($query, $index, $matches);

    $result = $query->search($index);
    $result->setId('wplistexecute-' . $iListExecute);

    $resultBuilder = new Search_ResultSet_WikiBuilder($result);
    $resultBuilder->apply($matches, $params);

    $dataSource = $unifiedsearchlib->getDataSource();
    $builder = new Search_Formatter_Builder();
    $builder->setPaginationArguments($paginationArguments);
    $builder->setAdditionalOutputData(['fingerprint' => $fingerprint]);
    $builder->setActions($actions);
    $builder->setId('wplistexecute-' . $iListExecute);
    $builder->setCount($result->count());
    $builder->setTsOn($tsret['tsOn']);
    $builder->apply($matches, $params);

    $result->setTsSettings($builder->getTsSettings());
    $result->setTsOn($tsret['tsOn']);

    $formatter = $builder->getFormatter();
    $formatter->setCounter($iListExecute);

    if (! $customOutput) { // default list output when OUTPUT block is not in use
        $plugin = new Search_Formatter_Plugin_SmartyTemplate('templates/wiki-plugins/wikiplugin_listexecute.tpl');
        $plugin->setFields(['report_status' => null]);

        $pluginData = [
            'actions' => $actions,
            'iListExecute' => $iListExecute,
            'fingerprint' => $fingerprint,
            'label' => $params['button_label'] ?? '',
        ];

        if ($prefs['feature_categories'] == 'y') {
            $categlib = TikiLib::lib('categ');
            $categories = $categlib->getCategories();

            foreach ($categories as &$category) {
                $category['canchange'] = 'y';
            }
            // Get the categories tree for display
            $cat_tree = $categlib->generate_cat_tree($categories);
            $pluginData['categories'] = $categories;
            $pluginData['cat_tree'] = $cat_tree;
            $pluginData['tiki_p_modify_object_categories'] = $tiki_p_modify_object_categories;
            $pluginData['tiki_p_admin_categories'] = $tiki_p_admin_categories;
        }

        $page = $_GET['page'] ?? null;

        if (! is_null($page)) {
            $perms = Perms::get();

            if ($perms->tiki_p_admin_schedulers) {
                $schedulersAmount = 0;
                $schedulerLib = TikiLib::lib('scheduler');
                $schedulersTable = TikiDb::get()->table('tiki_scheduler');

                $schedulers = $schedulerLib->get_scheduler(null, null, [
                    'params'    => $schedulersTable->contains(ListExecuteCommand::getDefaultName()),
                    'task'      => $schedulersTable->exactly('ConsoleCommandTask')
                ]);

                foreach ($schedulers as $scheduler) {
                    $schedulerParameters = json_decode($scheduler['params']);
                    $arguments = explode(' ', $schedulerParameters->console_command);

                    // Page is always the first argument (after the command name)
                    $schedulerPage = $arguments[1] ?? null;

                    if ($schedulerPage == $page) {
                        $pluginData['scheduler_id'] = $scheduler['id'];
                        $schedulersAmount++;
                    }
                }

                $pluginData['schedulers_amount'] = $schedulersAmount;
            }
        }

        $plugin->setData($pluginData);
        $builder->setFormatterPlugin($plugin);
        $formatter = $builder->getFormatter();
        $formatter->setCounter($iListExecute);
    }

    if (isset($_POST['list_action']) && ! empty($selectedObjects)) {
        $action = $_POST['list_action'];

        if ($result->count() > 9999) {
            Feedback::error(tr("There are too many search result items to apply %0 action to.", $_POST['list_action']));
        } elseif (isset($actions[$action])) {
            TikiLib::setExternalContext(true);

            $reportSource = new Search_Action_ReportingTransform();

            $tx = TikiDb::get()->begin();

            $action = $actions[$action];
            $numGood = 0;
            $formatter->setContext('actions');
            $list = $formatter->getPopulatedList($result);
            $formatter->setContext(null);

            foreach ($list as $entry) {
                $entry['object_type'] = str_replace(['~/np~', '~np~'], '', $entry['object_type']); // Remove ~/np~~np~ from object type
                $identifier = "{$entry['object_type']}:{$entry['object_id']}";
                if (in_array($identifier, $selectedObjects) || in_array('ALL', $selectedObjects)) {
                    if (isset($_POST['list_input'])) {
                        $entry['value'] = $_POST['list_input'];
                    }
                    // If it is a categorization action
                    if (isset($_POST['cat_categories'])) {
                        $entry['category'] = $_POST['cat_categories'];
                    }

                    try {
                        $success = $action->execute($entry);
                        if (! $success) {
                            Feedback::error(tr("Unknown error executing action %0 on item %1.", $_POST['list_action'], $entry['title']));
                        }
                    } catch (Search_Action_Exception $e) {
                        Feedback::error(
                            tr("Error executing action %0 on item %1:", $_POST['list_action'], $entry['title'])
                            . ' ' . $e->getMessage()
                        );
                        $success = false;
                    }

                    if ($success) {
                        $numGood++;
                    }

                    $reportSource->setStatus($entry['object_type'], $entry['object_id'], $success);
                }
            }

            $tx->commit();

            TikiLib::setExternalContext(false);

            if ($numGood) {
                Feedback::success(tr("Action %0 executed successfully on %1 item(s).", $_POST['list_action'], $numGood));
            }

            // need to reload search results in case action has modified the original contents
            // or queried only specific objects
            $result = $searchQuery->search($index);
            $result->setId('wplistexecute-' . $iListExecute);
            $resultBuilder = new Search_ResultSet_WikiBuilder($result);
            $resultBuilder->apply($matches, $params);
            $builder->setCount($result->count());
            // remove any tablesorter header js that will be added twice otherwise
            foreach (TikiLib::lib('header')->jq_onready as &$scripts) {
                foreach ($scripts as $key => $js) {
                    if (str_contains($js, '$(\'table#wplistexecute-' . $iListExecute . '\').tablesorter(')) {
                        unset($scripts[$key]);
                    }
                }
            }
            $builder->apply($matches, $params);
            $result->setTsSettings($builder->getTsSettings());
            $result->setTsOn($tsret['tsOn']);
            $formatter = $builder->getFormatter();
            $formatter->setCounter($iListExecute);

            $result->applyTransform($reportSource);
        }
    }

    $formatted = $formatter->format($result);

    if (! str_contains($formatted, "listexecute-$iListExecute")) {
        $formatted = '~np~
<form method="post" class="list-executable" id="listexecute-' . $iListExecute . '" data-id="wplistexecute-' . $iListExecute . '">
    <input type="hidden" name="plugin" value="' . $fingerprint . '">~/np~' . $formatted . '~np~
</form>~/np~';
    }

    $mark_plugins = $parser->option['mark_plugins'];
    if (empty($mark_plugins && ! empty(TikiLib::lib('parser')->core_options['mark_plugins']))) {
        $mark_plugins = TikiLib::lib('parser')->core_options['mark_plugins'];
    }

    if (! empty($mark_plugins) && in_array($fingerprint, $mark_plugins)) {
        $formatted = "~$fingerprint~" . $formatted . "~/$fingerprint~";
    }

    return $formatted;
}
