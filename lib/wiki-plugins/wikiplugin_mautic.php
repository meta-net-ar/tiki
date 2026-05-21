<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Mautic\Auth\ApiAuth;
use Mautic\MauticApi;

function wikiplugin_mautic_info()
{
    return [
        'name' => tra('Mautic Integration'),
        'documentation' => 'PluginMautic',
        'description' => tra('Add the tracking code for Mautic'),
        'prefs' => [ 'wikiplugin_mautic' ],
        'iconname' => 'chart',
        'format' => 'html',
        'introduced' => 25,
        'params' => [
            'type' => [
                'required' => true,
                'name' => tra('Type data'),
                'description' => tr('Defines type of data to be tracked'),
                'since' => '25.0',
                'filter' => 'text',
                'default' => 'form',
                'options' => [
                    ["text" => tra("Mautic Form"), "value" => "form"],
                    ["text" => tra("Mautic Contact"), "value" => "contacts"],
                ],
            ],
            'form_id' => [
                'required' => false,
                'name' => tra('Form ID'),
                'description' => tr('The focus form ID you want to load in Tiki'),
                'since' => '25.0',
                'filter' => 'int',
                'default' => '',
                'refers_to' => 'form'
            ],
            'available_actions' => [
                'required' => false,
                'name' => tra('Vote options'),
                'description' => tra('The available options are used to display the possible actions in the contact list, the actions are separated by a comma'),
                'since' => '25.0',
                'filter' => 'text',
                'default' => default_available_actions(),
                'separator' => ',',
                'refers_to' => 'contacts'
            ],
        ],
    ];
}

function wikiplugin_mautic($data, $params)
{
    global $prefs;
    $ret = '';

    if ($prefs['site_mautic_enable'] !== 'y') {
        return tra('You cannot use this plugin until the feature mautic is activated');
    }

    if ($prefs['site_mautic_url'] === '') {
        return tra('You must configure Mautic URL before using this plugin');
    }

    $params = WikiPlugin_Helper::applySeparators($params, wikiplugin_mautic_info());

    if (empty($params['type'])) {
        return tra('Type parameter is required');
    }

    $url = $prefs['site_mautic_url'] . "/mtc.js";

    if ($params['type'] == 'form') {
        $form_id = $params['form_id'];
        if (empty($form_id)) {
            return tra('Id form parameter is required');
        }
        $form = '<script type="text/javascript" src="' . $prefs['site_mautic_url'] . '/form/generate.js?id=' . $form_id . '"></script>';
        $ret = <<<HTML
            $form
HTML;
    }

    if ($params['type'] == 'inclusion') {
        $ret = <<<HTML
            <script>
                (function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
                    w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
                    m=d.getElementsByTagName(t)[0];a.async=1;a.src=u;m.parentNode.insertBefore(a,m)
                })(window,document,'script','$url','mt');
                mt('send', 'pageview');
            </script>
HTML;
    }

    if ($params['type'] == 'contacts') {
        $smarty = TikiLib::lib('smarty');
        $userName = $prefs['site_mautic_username'];
        $password = $prefs['site_mautic_password'];
        $apiUrl = $prefs['site_mautic_url'] . "/api/"; //Mautic API

        if ($userName === '' || $password === '') {
            return tra('Please configure the username and password of your mautic account to get the contacts in the API');
        }

        $settings = [
            'userName'   => $userName,
            'password'   => $password,
        ];

        // Initiate the auth object specifying to use BasicAuth
        $initAuth = new ApiAuth();
        $auth = $initAuth->newAuth($settings, 'BasicAuth');
        $api = new MauticApi();
        $contactApi = $api->newApi('contacts', $auth, $apiUrl);

        $contacts = $contactApi->getList('', 0, 130);
        $allContacts = $contacts[$contactApi->listName()];
        $contacts = [];

        foreach ($allContacts as $key => $value) {
            if ($value['fields']['all']['email'] !== null) {
                $contact = new stdClass();
                $contact->id = $key;
                $contact->email = $value['fields']['all']['email'];
                $contact->fullname = $value['fields']['all']["firstname"] . " " . $value['fields']['all']["firstname"];
                $contact->company = $value['fields']['all']['company'];
                $contact->points = $value['fields']['all']['points'];
                $contacts [] = $contact;
            }
        }

        $available_actions = $params['available_actions'];
        $smarty->assign('type', $params['type']);
        $smarty->assign('contacts', $contacts);
        $smarty->assign('available_actions', $available_actions);
        return $smarty->fetch('wiki-plugins/wikiplugin_mautic.tpl');
    }

    return $ret;
}

function default_available_actions()
{
    return 'info,create,sync';
}
