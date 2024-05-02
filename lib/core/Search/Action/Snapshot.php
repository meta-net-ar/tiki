<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Action_Snapshot implements Search_Action_Action
{
    public function getValues()
    {
        return [
            'object_type'     => true,
            'object_id'       => true,
            'url'             => true,
            'page'            => true,
            'css_selector'    => false,
            'tiki_token'      => false,
            'template_page'   => false,
            'only_if_changed' => false,
        ];
    }

    public function validate(JitFilter $data)
    {
        global $prefs;

        $object_type = $data->object_type->text();
        $object_id = $data->object_id->int();
        $tiki_token = $data->tiki_token->bool();

        if ($object_type != 'trackeritem') {
            throw new Search_Action_Exception(tr('This action only support %0 type.', $object_type));
        }

        if ($tiki_token && $prefs['auth_token_access'] == 'n') {
            throw new Search_Action_Exception(tr('To use tiki_token can not be used. The option the preference \'auth_token_access\' is disabled.', $object_id));
        }

        return true;
    }

    public function execute(JitFilter $data)
    {
        $objectId = $data->object_id->int();
        $cssSelector = $data->css_selector->text() ?: 'body';
        $page = $data->page->text();
        $templatePage = $data->template_page->text();
        $url = $data->url->text();
        $onlyIfChanged = $data->only_if_changed->bool();
        $tikiToken = $data->tiki_token->bool();

        $itemObject = Tracker_Item::fromId($objectId);
        if (! $itemObject) {
            throw new Search_Action_Exception(tr('Tracker item %0 not found.', $objectId));
        }
        $itemData = $itemObject->getData();
        $page = $this->extractValue($page, $itemData);
        $url = $this->extractValue($url, $itemData);
        $cssSelector = $this->extractValue($cssSelector, $itemData);

        $url = $this->resolveURL($url, $tikiToken);
        $snapshot = $this->snapshot($url, $cssSelector);
        $this->saveSnapshot($snapshot, $page, $onlyIfChanged, $templatePage);

        return true;
    }


    public function requiresInput(JitFilter $data)
    {
        return false;
    }

    /**
     * @param $value
     * @param $itemData
     *
     * @return array|string|string[]|null
     */
    private function extractValue($value, $itemData)
    {
        $re = '/\%entry\.(.+)\%/';
        if (preg_match($re, $value, $matches) == 1) {
            $field = $matches[1];
            if ('id' == $field) {
                $v = $itemData['itemId'];
            } else {
                $v = $itemData['fields'][$field];
            }
            return preg_replace($re, $v ?? '', $value);
        } else {
            return $value;
        }
    }

    /**
     * @param string $url
     * @param string $cssSelector
     *
     * @return string
     * @throws Search_Action_Exception
     */
    private function snapshot(string $url, string $cssSelector)
    {
        $script = <<<JS
            var casper = require('casper').create();
            casper.start('$url', function() {
                var html = casper.evaluate(function() {
                    return document.querySelector("$cssSelector").innerHTML;
                });
                this.echo(html);
            });
             
            casper.run();
        JS;

        $runner = new WikiPlugin_Casperjs_Runner();
        $result = $runner->run($script);
        $result = implode("\n", array_filter($result->getScriptOutput(), function ($line) {
            return ! empty($line);
        }));
        if ($result == 'null') {
            throw new Search_Action_Exception(tr('Invalid css selector "%0".', $cssSelector));
        }
        return $result;
    }

    /**
     * @param string $snapshot
     * @param string $page
     * @param string $templatePage
     * @param bool   $onlyIfChanged
     *
     * @throws Exception
     */
    private function saveSnapshot(string $snapshot, string $page, bool $onlyIfChanged, ?string $templatePage = null)
    {
        global $user;

        $attributeKey = 'tiki.snapshot.md5';
        $tikilib = TikiLib::lib('tiki');
        $wikilib = TikiLib::lib('wiki');
        $attributelib = TikiLib::lib('attribute');

        $md5 = md5($snapshot);

        $attribute = $attributelib->get_attribute('wiki page', $page, $attributeKey);
        if ($attribute == $md5 && $onlyIfChanged) { //Compare the old md5 with the new snapshot.
            return;
        }

        $snapshot = '{HTML()}' . $snapshot . '{HTML}'; //Use HTML wikiplugin to show html properly.
        $pageInfo = $tikilib->get_page_info($page);

        if (! $pageInfo) {
            if ($templatePage && $tikilib->page_exists($templatePage)) {
                $wikilib->wiki_duplicate_page($templatePage, $page, true, true, $snapshot);
            } else {
                $tikilib->create_page(
                    $page,
                    0,
                    $snapshot,
                    $tikilib->now,
                    'Created with SnapshotAction',
                    $user
                );
            }
        } else {
            $tikilib->update_page(
                $page,
                $snapshot,
                'Updated with SnapshotAction',
                $user,
                '0.0.0.0'
            );
        }
        $attributelib->set_attribute('wiki page', $page, $attributeKey, $md5);
    }

    /**
     * @param string $url
     * @param bool   $tikiToken
     *
     * @return string
     * @throws Search_Action_Exception
     */
    private function resolveURL(string $url, bool $tikiToken = false): string
    {
        global $base_url, $prefs, $user;

        $userlib = TikiLib::lib('user');
        $urlRegex = '/^(https?):\/\/[^\s\/$.?\#].[^\s]*$/';
        $baseUrl = $base_url ?? $prefs['fallbackBaseUrl'];

        if (preg_match($urlRegex, $url) == 0) {
            if (empty($baseUrl)) {
                throw new Search_Action_Exception(
                    tr('Unable to get base url. To fix this issue set the fallbackBaseUrl preference with a valid URL.')
                );
            }
            $url = rtrim($baseUrl, '/') . '/' . $url;
        }

        if (! preg_match($urlRegex, $url)) {
            throw new Search_Action_Exception(tr('URL %0 is not valid.', $url));
        }

        if ($tikiToken && isset($user) && str_contains($url, $baseUrl)) {
            require_once 'lib/auth/tokens.php';
            $tokenlib = AuthTokens::build($prefs);

            $groups = $userlib->get_user_groups($user);
            //Add the token to the URL with the same permission that the user. This token will be valid for just one hit.
            $url = $tokenlib->includeToken($url, $groups, '', 60, 1);
        }

        return $url;
    }
}
