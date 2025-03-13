<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Action_DataChannel implements Search_Action_Action
{
    protected $definition = [];

    public function getValues()
    {
        $values = [
            'object_type' => true,
            'object_id' => true,
            'channel' => true,
            'params' => false,
            'value' => false,
            'empty_cache' => false,
        ];
        if (isset($this->definition['params'])) {
            $params = explode(':', $this->definition['params']);
            $params = array_fill_keys($params, false);
            $values = array_merge($values, $params);
        }
        return $values;
    }

    public function definitionAware(array $definition)
    {
        $this->definition = $definition;
    }

    public function validate(JitFilter $data)
    {
        global $prefs;

        $object_type = $data->object_type->text();
        $object_id = $data->object_id->text();
        $channels = array_filter(array_map('trim', explode(',', $data->channel->text())));
        $params = $data->params->text();

        if (empty($channels)) {
            throw new Search_Action_Exception(tr('No channel(s) specified.'));
        }

        // used to validate permissions
        $this->getChannelConfig($channels);

        return true;
    }

    public function execute(JitFilter $data)
    {
        $object_type = $data->object_type->text();
        $object_id = $data->object_id->text();
        $empty_cache = $data->empty_cache->text() ?? 'all';
        $channels = array_filter(array_map('trim', explode(',', $data->channel->text())));
        $params = $data->params->text();

        $config = $this->getChannelConfig($channels);

        $input = [];
        foreach (explode(':', $params) as $param) {
            $input[$param] = $data->$param->raw() ? $data->$param->text() : $data->value->text();
        }

        $success = true;

        Tiki_Profile::useUnicityPrefix(uniqid());
        $profiles = $config->getProfiles($channels);
        foreach ($profiles as $profile) {
            $profile->removeSymbols();
            $arguments = [];
            Tiki_Profile::useUnicityPrefix(uniqid());
            $installer = new Tiki_Profile_Installer();
            $installer->setUserData($input);
            $installer->disablePrefixDependencies();
            $result = $installer->install($profile, $empty_cache);
            if (! $result) {
                foreach ($installer->getFeedback() as $feed) {
                    Feedback::warning($feed);
                }
            }
            $success = $result && $success;
        }

        return $success;
    }

    public function inputType(): string
    {
        return "text";
    }

    public function requiresInput(JitFilter $data)
    {
        $params = $data->params->text();
        foreach (explode(':', $params) as $param) {
            if ($data->field_variants->raw()) {
                $exists = false;
                foreach ($data->field_variants->raw() as $variant) {
                    $key = $param . $variant;
                    if (! empty($data->$key->text())) {
                        $exists = true;
                        break;
                    }
                }
                if (! $exists) {
                    return true;
                }
            } else {
                if (empty($data->$param->text())) {
                    return true;
                }
            }
        }
        return false;
    }

    private function getChannelConfig(array $channels)
    {
        global $prefs;
        $groups = Perms::get()->getGroups();
        $config = Tiki_Profile_ChannelList::fromConfiguration($prefs['profile_channels']);
        if (! $config->canExecuteChannels($channels, $groups, true)) {
            throw new Search_Action_Exception(tr('You don\'t have permission to execute the specified channel(s).'));
        }
        return $config;
    }
}
