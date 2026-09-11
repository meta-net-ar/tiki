<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Wiki;

use TikiLib;
use WikiParser_PluginArgumentParser;
use WikiParser_PluginMatcher;

class ConvertToTiki9
{
    public $parserlib;
    public $argumentParser;

    public function __construct()
    {
        $this->parserlib = TikiLib::lib('parser');
        $this->argumentParser = new WikiParser_PluginArgumentParser();
    }

    //<!--below methods are used for converting objects
    //<!--Start for converting pages
    public function convertPages()
    {
        $infos = $this->parserlib->fetchAll(
            'SELECT data, page_id' .
                ' FROM tiki_pages' .
                ' LEFT JOIN tiki_db_status ON tiki_db_status.objectId = tiki_pages.page_id' .
                ' WHERE tiki_db_status.tableName = "tiki_pages" IS NULL'
        );

        foreach ($infos as $info) {
            if (! empty($info['data'])) {
                $converted = $this->convertData($info['data']);

                $this->updatePlugins($converted['fingerPrintsOld'], $converted['fingerPrintsNew']);

                $this->savePage($info['page_id'], $converted['data']);
            }
        }
    }

    public function savePage($id, $data)
    {
        $status = $this->checkObjectStatus($id, 'tiki_pages');

        if (empty($status)) {
            $this->parserlib->query("UPDATE tiki_pages SET data = ? WHERE page_id = ?", [$data, $id]);

            $this->saveObjectStatus($id, 'tiki_pages', 'conv9.0');
        }
    }
    //end for converting pages-->


    //<!--start for converting histories
    public function convertPageHistoryFromPageAndVersion($page, $version)
    {
        $infos = $this->parserlib->fetchAll(
            'SELECT data, historyId' .
                ' FROM tiki_history' .
                ' LEFT JOIN tiki_db_status' .
                ' ON tiki_db_status.objectId = tiki_history.historyId' .
                ' WHERE tiki_db_status.tableName = "tiki_history" IS NULL' .
                ' AND pageName = ? AND version = ?',
            [$page, $version]
        );

        foreach ($infos as $info) {
            if (! empty($info['data'])) {
                $converted = $this->convertData($info['data']);

                //update plugins first, if it failes, no problems with the page
                $this->updatePlugins($converted['fingerPrintsOld'], $converted['fingerPrintsNew']);

                $this->savePageHistory($info['historyId'], $converted['data']);
            }
        }
    }

    public function convertPageHistories()
    {
        $infos = $this->parserlib->fetchAll(
            'SELECT data, historyId' .
                ' FROM tiki_history' .
                ' LEFT JOIN tiki_db_status ON tiki_db_status.objectId = tiki_history.historyId' .
                ' WHERE tiki_db_status.tableName = "tiki_history" IS NULL'
        );

        foreach ($infos as $info) {
            if (! empty($info['data'])) {
                $converted = $this->convertData($info['data']);

                $this->updatePlugins($converted['fingerPrintsOld'], $converted['fingerPrintsNew']);

                $this->savePageHistory($info['historyId'], $converted['data']);
            }
        }
    }

    public function savePageHistory($id, $data)
    {
        $status = $this->checkObjectStatus($id, 'tiki_history');

        if (empty($status)) {
            $this->parserlib->query(
                'UPDATE tiki_history' .
                    ' SET data = ?' .
                    ' WHERE historyId = ?',
                [$data, $id]
            );

            $this->saveObjectStatus($id, 'tiki_history', 'conv9.0');
        }
    }
    //end for converting histories-->



    //<!--start for converting modules
    public function convertModules()
    {
        $infos = $this->parserlib->fetchAll(
            'SELECT data, name' .
                ' FROM tiki_user_modules' .
                ' LEFT JOIN tiki_db_status ON tiki_db_status.objectId = tiki_user_modules.name' .
                ' WHERE tiki_db_status.tableName = "tiki_user_modules" IS NULL'
        );

        foreach ($infos as $info) {
            if (! empty($info['data'])) {
                $converted = $this->convertData($info['data']);

                $this->updatePlugins($converted['fingerPrintsOld'], $converted['fingerPrintsNew']);

                $this->saveModule($info['name'], $converted['data']);
            }
        }
    }

    public function saveModule($name, $data)
    {
        $status = $this->checkObjectStatus($name, 'tiki_user_modules');

        if (empty($status)) {
            $this->parserlib->query('UPDATE tiki_user_modules SET data = ? WHERE name = ?', [$data, $name]);

            $this->saveObjectStatus($name, 'tiki_user_modules', 'conv9.0');
        }
    }
    //end for converting modules-->
    //end conversion of objects-->



    //<!--below methods are used in tracking status of pages
    public function saveObjectStatus($objectId, $tableName, $status = 'new9.0+')
    {
        $currentStatus = $this->parserlib->getOne("SELECT status FROM tiki_db_status WHERE objectId = ? AND tableName = ?", [$objectId, $tableName]);

        if (empty($currentStatus)) {
            //Insert a status record if one doesn't exist
            $this->parserlib->query(
                'INSERT INTO tiki_db_status ( objectId,    tableName,    status )' .
                    ' VALUES (?, ?, ?)',
                [$objectId,     $tableName, $status]
            );
        } else {
            //update a status record, it already exists
            $this->parserlib->query(
                'UPDATE tiki_db_status' .
                    ' SET status = ?' .
                    ' WHERE objectId = ? AND tableName = ?',
                [$status, $objectId, $tableName]
            );
        }
    }

    public function checkObjectStatus($objectId, $tableName)
    {
        return $this->parserlib->getOne(
            'SELECT status' .
                ' FROM tiki_db_status' .
                ' WHERE objectId = ? AND tableName = ?',
            [$objectId, $tableName]
        );
    }
    //end status methods-->


    //<!--below methods are used for conversion of plugins and data
    public function updatePlugins($fingerPrintsOld, $fingerPrintsNew)
    {
        //here we find the old fingerprint and replace it with the new one
        for ($i = 0, $count_fingerPrintsOld = count($fingerPrintsOld); $i < $count_fingerPrintsOld; $i++) {
            if (! empty($fingerPrintsOld[$i]) && $fingerPrintsOld[$i] != $fingerPrintsNew[$i]) {
                //Remove any that may conflict with the new fingerprint, not sure how to fix this yet
                $this->parserlib->query("DELETE FROM tiki_plugin_security WHERE fingerprint = ?", [$fingerPrintsNew[$i]]);

                // Now update fingerprint (if it exists)
                $this->parserlib->query("UPDATE tiki_plugin_security SET fingerprint = ? WHERE fingerprint = ?", [$fingerPrintsNew[$i], $fingerPrintsOld[$i]]);
            }
        }
    }

    public function convertData($data)
    {
        //we store the original matches because we are about to change and update them, we need to get their fingerprint
        $oldMatches = WikiParser_PluginMatcher::match($data);

        // HTML-decode pages
        $data = htmlspecialchars_decode($data);

        // find the plugins
        $matches = WikiParser_PluginMatcher::match($data);

        $replaced = [];

        $fingerPrintsOld = [];
        foreach ($oldMatches as $match) {
            $name = $match->getName();
            $meta = $this->parserlib->plugin_info($name);
            // only check fingerprints of plugins requiring validation
            if (! empty($meta['validate'])) {
                $args = $this->argumentParser->parse($match->getArguments());

                //RobertPlummer - pre 9, latest findings from v8 is that the < and > chars are THE ONLY ones converted to &lt; and &gt; everything else seems to be decoded
                $body = $match->getBody();

                // jonnyb - pre 9.0, Tiki 6 (?) fingerprints are calculated with the undecoded body
                $fingerPrint = $this->parserlib->plugin_fingerprint($name, $meta, $body, $args);

                // so check the db for previously recorded plugins
                if (! $this->parserlib->getOne('SELECT COUNT(*) FROM tiki_plugin_security WHERE fingerprint = ?', [$fingerPrint])) {
                    // jb but v 7 & 8 fingerprints may be calculated differently, so check both fully decoded and partially
                    $body = htmlspecialchars_decode($body);
                    $fingerPrint = $this->parserlib->plugin_fingerprint($name, $meta, $body, $args);

                    if (! $this->parserlib->getOne('SELECT COUNT(*) FROM tiki_plugin_security WHERE fingerprint = ?', [$fingerPrint])) {
                        $body = str_replace(['<', '>'], ['&lt;', '&gt;'], $body);
                        $fingerPrint = $this->parserlib->plugin_fingerprint($name, $meta, $body, $args);

                        if (! $this->parserlib->getOne('SELECT COUNT(*) FROM tiki_plugin_security WHERE fingerprint = ?', [$fingerPrint])) {
                            // old fingerprint not found - what to do? Might be worth trying &quot; chars too...
                            $fingerPrint = '';
                        }
                    }
                }
                $fingerPrintsOld[] = $fingerPrint;
            }
        }

        $fingerPrintsNew = [];
        // each plugin
        foreach ($matches as $match) {
            $name = $match->getName();
            $meta = $this->parserlib->plugin_info($name);
            $argsRaw = $match->getArguments();

            //Here we detect if a plugin was double encoded and this is the second decode
            //try to detect double encoding
            if (preg_match("/&amp;&/i", $argsRaw) || preg_match("/&quot;/i", $argsRaw) || preg_match("/&gt;/i", $argsRaw)) {
                $argsRaw = htmlspecialchars_decode($argsRaw);               // decode entities in the plugin args (usually &quot;)
            }

            $args = $this->argumentParser->parse($argsRaw);
            $plugin = (string) $match;
            $key = '§' . md5(TikiLib::genPass()) . '§';                 // by replace whole plugin with a guid

            $data = str_replace($plugin, $key, $data);

            $body = $match->getBody();                                  // leave the bodies alone
            $key2 = '§' . md5(TikiLib::genPass()) . '§';                    // by replacing it with a guid
            $plugin = str_replace($body, $key2, $plugin);

            //Here we detect if a plugin was double encoded and this is the second decode
            //try to detect double encoding
            if (preg_match("/&amp;&/i", $plugin) || preg_match("/&quot;/i", $plugin) || preg_match("/&gt;/i", $plugin)) {
                $plugin = htmlspecialchars_decode($plugin);             // decode entities in the plugin args (usually &quot;)
            }

            $plugin = str_replace($key2, $body, $plugin);               // finally put the body back

            $replaced['key'][] = $key;
            $replaced['data'][] = $plugin;                              // store the decoded-args plugin for replacement later

            // only check fingerprints of plugins requiring validation
            if (! empty($meta['validate'])) {
                $fingerPrintsNew[] = $this->parserlib->plugin_fingerprint($name, $meta, $body, $args);
            }
        }

        $this->parserlib->plugins_replace($data, $replaced);                    // put the plugins back into the page

        return [
            "data" => $data,
            "fingerPrintsOld" => $fingerPrintsOld,
            "fingerPrintsNew" => $fingerPrintsNew
        ];
    }
    //end conversion methods-->
}
