<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Profile_InstallHandler_PluginAlias extends Tiki_Profile_InstallHandler
{
    public function getData()
    {
        if ($this->data) {
            return $this->data;
        }

        $defaults = [
            'body' => [
                'input' => 'ignore',
                'default' => '',
                'params' => []
            ],
            'params' => [
            ],
        ];

        $data = array_merge($defaults, $this->obj->getData());

        return $this->data = $data;
    }

    public function canInstall()
    {
        $data = $this->getData();

        if (! isset($data['name'], $data['implementation'], $data['description'])) {
            return false;
        }

        if (! is_array($data['description']) || ! is_array($data['body']) || ! is_array($data['params'])) {
            return false;
        }

        return true;
    }

    public function doInstall()
    {
        global $tikilib;
        $data = $this->getData();

        $this->replaceReferences($data);

        $name = $data['name'];
        unset($data['name']);

        $parserlib = TikiLib::lib('parser');
        $parserlib->plugin_alias_store($name, $data);

        return $name;
    }

    /**
     * Remove plugin alias
     *
     * @param string $pluginAlias
     * @return bool
     */
    public function remove($pluginAlias)
    {
        if (! empty($pluginAlias)) {
            $parserlib = TikiLib::lib('parser');
            if ($parserlib->plugin_alias_delete($pluginAlias)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get current plugin alias data
     *
     * @param array $pluginAlias
     * @return mixed
     */
    public function getCurrentData($pluginAlias)
    {
        $pluginAliasName = ! empty($pluginAlias['name']) ? $pluginAlias['name'] : '';
        if (! empty($pluginAliasName)) {
            $parserlib = TikiLib::lib('parser');
            $pluginNameData = $parserlib->plugin_alias_info($pluginAliasName);
            if (! empty($pluginNameData)) {
                return $pluginNameData;
            }
        }
        return false;
    }

    /**
     * Export an existing plugin alias as a profile object.
     *
     * @param Tiki_Profile_Writer $writer  The profile writer instance to add the object to.
     * @param string              $name    The plugin alias name (case-insensitive).
     * @return bool  True on success, false if the alias does not exist or has no base plugin.
     */
    public static function export(Tiki_Profile_Writer $writer, $name)
    {
        $name = mb_strtolower($name);

        $info = WikiPlugin_Negotiator_Wiki_Alias::info($name);

        // Without this guard, a missing implementation would still pass canInstall() and install a dead alias.
        if (! $info || empty($info['implementation'])) {
            return false;
        }

        $out = [
            'name'           => $name,
            'implementation' => $info['implementation'],
            'description'    => $info['description'] ?? [], // canInstall() requires this key to be present
        ];

        if (! empty($info['body'])) {
            $out['body'] = $info['body'];
        }

        // isset(), not empty(): params can legitimately be an empty array
        if (isset($info['params'])) {
            $out['params'] = $info['params'];
        }

        $writer->addObject('plugin_alias', $name, $out);

        return true;
    }

    /**
     * Export a plugin alias and return the resulting profile YAML.
     *
     * @param string $name  The plugin alias name (case-insensitive).
     * @return string|false  The profile YAML, or false if the alias does not exist.
     */
    public static function dumpExport($name)
    {
        $writer = new Tiki_Profile_Writer('temp', 'none');

        if (! self::export($writer, $name)) {
            return false;
        }

        return $writer->dump();
    }
}
