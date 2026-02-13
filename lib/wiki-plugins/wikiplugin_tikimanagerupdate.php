<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_tikimanagerupdate_info()
{
    return [
        'name' => tra('Tiki Manager Update'),
        'documentation' => 'PluginTikiManagerUpdate',
        'description' => tra('Embed a button in wiki page to perform on demand update of an instance.'),
        'tags' => ['advanced'],
        'prefs' => ['feature_tiki_manager', 'wikiplugin_tikimanagerupdate'],
        'body' => tra('Button text(The text that will be displayed on the update instance button).'),
        'introduced' => 25,
        'validate' => 'all',
        'params' => [
            'instances' => [
                'required' => true,
                'name' => tra('Instances'),
                'description' => tra('List of instance IDs to be updated, separated by comma (,).'),
                'since' => '25.0',
                'filter' => 'string',
                'advanced' => false,
            ],
            'email' => [
                'required' => true,
                'name' => tra('Email'),
                'description' => tra('Email address to notify in case of failure. Use , (comma) to separate multiple email addresses.'),
                'since' => '25.0',
                'filter' => 'string',
                'advanced' => false,
            ],
            'skipReindex' => [
                'required' => false,
                'name' => tra('Skip reindex'),
                'description' => tra('Skip rebuilding index step.'),
                'since' => '25.0',
                'filter' => 'int',
                'options' => [
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'default' => 0,
                'advanced' => true,
            ],
            'skipCacheWarmup' => [
                'required' => false,
                'name' => tra('Skip cache warmup'),
                'description' => tra('Skip generating cache step.'),
                'since' => '25.0',
                'filter' => 'int',
                'options' => [
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'default' => 0,
                'advanced' => true,
            ],
            'unifiedIndexRebuild' => [
                'required' => false,
                'name' => tra('Unified index rebuild'),
                'description' => tra('Live reindex, set instance maintenance off and after perform index rebuild.'),
                'since' => '25.0',
                'filter' => 'int',
                'options' => [
                    ['text' => tra('Yes, and site is open during rebuild '), 'value' => '1'],
                    ['text' => tra('No, and site is closed during rebuild'), 'value' => '0'],
                ],
                'default' => 0,
                'advanced' => true,
            ],
            'lag' => [
                'required' => false,
                'name' => tra('Lag'),
                'description' => tra('Time delay commits by X number of days. Useful for avoiding newly introduced bugs in automated updates.'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => 0,
                'advanced' => false
            ],
            'stash' => [
                'required' => false,
                'name' => tra('Stash'),
                'description' => tra('Saves your local modifications, and try to apply after update/upgrade'),
                'since' => '25.0',
                'filter' => 'int',
                'options' => [
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'default' => 1,
                'advanced' => true,
            ],
            'ignoreRequirements' => [
                'required' => false,
                'name' => tra('Ignore requirements'),
                'description' => tra('Ignore version requirements. Allows to select non-supported branches, useful for testing.'),
                'since' => '25.0',
                'filter' => 'int',
                'options' => [
                    ['text' => tra('No'), 'value' => '0'],
                    ['text' => tra('Yes'), 'value' => '1'],
                ],
                'default' => 0,
                'advanced' => true,
            ]
        ]
    ];
}

function wikiplugin_tikimanagerupdate($data, $params)
{
    global $user;
    global $prefs;
    extract($params, EXTR_SKIP);

    //Prevent the plugin to be executed by anonymous users
    if (! $user or $user === 'anonymous') {
        return;
    }

    try {
        $utilities = new Services_Manager_Utilities();
        $utilities->tikiManagerCheck();
        $utilities->loadEnv();
    } catch (Exception $e) {
        return WikiParser_PluginOutput::error(tra('Error'), $e->getMessage());
    }

    $availbleInstances = TikiManager\Application\Instance::getInstances(true);   // @phpstan-ignore class.notFound (depends on TikiManager, which isn't installed by default)
    $availbleInstancesIds = array_map(function ($element) {
        return $element->id;
    }, $availbleInstances);

    foreach (explode(',', $instances) as $instanceId) {
        if (! in_array($instanceId, $availbleInstancesIds)) {
            return WikiParser_PluginOutput::error(tra('Error'), tra('Unknown instance ' . $instanceId));
        }
    }

    $featureRealtime = $prefs['feature_realtime'];
    $lag = ! empty($lag) ? $lag : 0;
    $consoleCommand = 'manager:instance:update -i ' . $instances . ' --email=' . $email . (($skipReindex) ? ' --skip-reindex' : '') . (($skipCacheWarmup) ? ' --skip-cache-warmup' : '') . (($unifiedIndexRebuild) ? ' --live-reindex' : '') . ' --lag=' . $lag . (($stash) ? ' --stash' : '') . (($ignoreRequirements) ? ' --ignore-requirements' : '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($featureRealtime === 'y') {
            $utilities->addInteractiveJS($consoleCommand);
        } else {
            Scheduler_Manager::queueJob('Update instance ' . $instances, 'ConsoleCommandTask', ['console_command' => $consoleCommand]);
            Feedback::success(tr("Instance %0 scheduled to update in the background. You can check command output via <a href='tiki-admin_schedulers.php#contenttabs_admin_schedulers-3'>Scheduler logs</a>.", $instances));
        }
    }

    $updateInstanceButtonValue = ! empty($data) ? $data : 'Update Instance(s) ' . $params['instances'];
    $wikiLib = TikiLib::lib('wiki');
    $url = $wikiLib->sefurl($_GET['page']);

    $smarty = TikiLib::lib('smarty');
    $smarty->assign('url', $url);
    $smarty->assign('updateInstanceButtonValue', $updateInstanceButtonValue);

    return $smarty->fetch('wiki-plugins/wikiplugin_tikimanagerupdate.tpl');
}
