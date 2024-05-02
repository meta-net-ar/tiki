<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tracker_Field_TikiManager extends \Tracker\Field\AbstractField
{
    public static function getManagedTypesInfo(): array
    {
        $utilities = new \Services_Manager_Utilities();
        $options = $utilities::getAvailableActions();

        return [
            'TM' => [
                'name'        => tr('Tiki Manager'),
                'description' => tr('Integrate Tiki Manager commands in tracker items.'),
                'prefs'       => ['trackerfield_tikimanager'],
                'tags'        => ['experimental'],
                'help'        => 'Manager',
                'default'     => 'n',
                'params'      => [
                    'showactions' => [
                        'name' => tra('Show actions'),
                        'description' => tra('Comma-separated list of actions shown in the interface. If none are listed, all actions will be available by default.'),
                        'default' => '',
                        'filter' => 'text',
                        'separator' => ',',
                        "options" => $options
                    ],
                    'hideactions' => [
                        'name' => tra('Hide actions'),
                        'description' => tra('Comma-separated list of actions hidden from the interface. If none are listed, all actions will be available by default.'),
                        'default' => '',
                        'filter' => 'text',
                        'separator' => ',',
                        "options" => $options
                    ],
                    'source' => [
                        'name' => tra('Virtualmin server'),
                        'description' => tra('If you have a remotely managed virtualmin server defined in DSN/Content Authentication section, you can specify it here to allow automatic subdomain creation and specify just the template url parameter below. '),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceType' => [
                        'name' => tra('New Instance Type'),
                        'description' => tra('Type of instance when creating a new one.'),
                        'default' => '',
                        'filter' => 'alpha',
                        'options'      => [
                            'ftp' => tr('FTP'),
                            'local' => tr('Local'),
                            'ssh' => tr('SSH'),
                        ],
                    ],
                    'newInstanceHost' => [
                        'name' => tra('New Instance Host'),
                        'description' => tra('SSH or FTP host'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstancePort' => [
                        'name' => tra('New Instance Port'),
                        'description' => tra('SSH or FTP host, e.g. 22 or 21.'),
                        'default' => '',
                        'filter' => 'int',
                    ],
                    'newInstanceUser' => [
                        'name' => tra('New Instance User'),
                        'description' => tra('SSH or FTP user'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstancePass' => [
                        'name' => tra('New Instance Pass'),
                        'description' => tra('FTP password'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceTemplateUrl' => [
                        'name' => tra('New Instance Template Url'),
                        'description' => tra('This will be used as a template for created instances web url. {slug} string will be replaced with the instance unique identifier. Possible formats: https://{slug}.domain.com or https://domain.com/{slug}/'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceWebroot' => [
                        'name' => tra('New Instance Web Root'),
                        'description' => tra('Local filesystem path to the instance webroot. {slug} string will be replaced with the instance unique identifier. E.g. /var/www/virtual/{slug}.domain.com/html/'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceTempdir' => [
                        'name' => tra('New Instance Work Dir'),
                        'description' => tra('Local filesystem path to the instance temp directory. {slug} string will be replaced with the instance unique identifier. E.g. /tmp/tiki_mgr_{slug}'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceBackupUser' => [
                        'name' => tra('New Instance Backup User'),
                        'description' => tra('Owner of the backup files for this instance.'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceBackupGroup' => [
                        'name' => tra('New Instance Backup Group'),
                        'description' => tra('Group owner of the backup files for this instance.'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceBackupPerms' => [
                        'name' => tra('New Instance Backup Perms'),
                        'description' => tra('Backup files permission mask. E.g. 755'),
                        'default' => '',
                        'filter' => 'int',
                    ],
                    'newInstanceDbHost' => [
                        'name' => tra('New Instance DB Host'),
                        'description' => tra('Database host'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceDbUser' => [
                        'name' => tra('New Instance DB User'),
                        'description' => tra('Database user with permissions to create other users and databases'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceDbPass' => [
                        'name' => tra('New Instance DB Pass'),
                        'description' => tra('Password for database user'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                    'newInstanceDbPrefix' => [
                        'name' => tra('New Instance DB Prefix'),
                        'description' => tra('Newly created databases and users will be prefixed by this string.'),
                        'default' => '',
                        'filter' => 'text',
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = [])
    {
        global $prefs;

        $ret = [];

        if ($prefs['feature_tiki_manager'] !== 'y') {
            $ret['error'] = tra('Tiki Manager feature not enabled.');
            return $ret;
        }

        if (! class_exists('TikiManager\Config\Environment')) {
            $linkTo_installed_packages = "<a href='tiki-admin.php?page=packages#contenttabs_admin_packages-1'><strong>" . tr(' Admin->Packages.') . "</strong></a>";
            $ret['error'] = tr('Tiki Manager not found. Please check if it is installed from') . $linkTo_installed_packages;
            return $ret;
        }

        try {
            $utilities = new Services_Manager_Utilities();
            $utilities->loadEnv();
        } catch (Exception $e) {
            $ret['error'] = $e->getMessage();
            return $ret;
        }

        $manager_output = $utilities->getManagerOutput();

        $instanceIds = explode(',', $this->getValue());
        $showactions = array_filter($this->getOption('showactions', []));
        $hideactions = array_filter($this->getOption('hideactions', []));

        $instances = TikiManager\Application\Instance::getInstances(false);
        $instances = array_filter($instances, function ($i) use ($instanceIds) {
            return empty($instanceIds) || in_array($i->getId(), $instanceIds);
        });

        // taken from Tiki manager available commands, TODO: hook these up with the interface
        $available_actions = Services_Manager_Utilities::getAvailableActions();
        if ($showactions) {
            $available_actions = array_filter($available_actions, function ($action) use ($showactions) {
                return in_array($action, $showactions);
            });
        }
        if ($hideactions) {
            $available_actions = array_filter($available_actions, function ($action) use ($hideactions) {
                return ! in_array($action, $hideactions);
            });
        }

        $versions = Services_Manager_Utilities::getAvailableTikiVersions();

        $ret = [
            'id' => $this->getItemId(),
            'error' => '',
            'instances' => $instances,
            'available_actions' => $available_actions,
            'manager_output' => $manager_output->fetch(),
            'versions' => $versions,
            'source' => $this->getOption('source'),
            'value' => $this->getValue() ? $this->getValue() : 'none', // this is required to show the field, otherwise it gets hidden if tracker is set to doNotShowEmptyField
        ];

        return $ret;
    }

    public function renderInput($context = [])
    {
        TikiLib::lib('header')->add_cssfile('themes/base_files/feature_css/tiki-manager.css');
        return $this->renderTemplate('trackerinput/tikimanager.tpl', $context);
    }

    public function renderOutput($context = [])
    {
        TikiLib::lib('header')->add_cssfile('themes/base_files/feature_css/tiki-manager.css');
        return $this->renderTemplate('trackerinput/tikimanager.tpl', $context);
    }

    public function addValue($instanceId)
    {
        $instances = explode(',', $this->getValue());
        if (! in_array($instanceId, $instances)) {
            $instances[] = $instanceId;
        }
        $result = implode(',', array_filter($instances, function ($i) {
            return is_numeric($i);
        }));
        return $result ? $result : 'none';
    }

    public function removeValue($instanceId)
    {
        $instances = explode(',', $this->getValue());
        if (in_array($instanceId, $instances)) {
            $instances[] = $instanceId;
        }
        $instances = array_filter($instances, function ($i) use ($instanceId) {
            return $i != $instanceId;
        });
        $result = implode(',', array_filter($instances, function ($i) {
            return is_int($i);
        }));
        return $result ? $result : 'none';
    }
}
