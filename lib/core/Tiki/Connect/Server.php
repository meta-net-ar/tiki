<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Connect_Server extends Tiki_Connect_Abstract
{
    private $indexFile;

    public function __construct()
    {
        parent::__construct();
        $this->indexFile = rtrim(tiki_temp_dir(), '/') . '/connect_server-index';
    }

    public function getMatchingConnections($criteria, $maxRecords = 25, $sort = '')
    {
        $index = $this->getIndex();

        $query = new Search_Query($criteria);
        $query->setCount($maxRecords);

        if ($sort) {
            $query->setOrder($sort);
        }

        $results = $query->search($index);

        $ret = [];
        foreach ($results as $hit) {
            $res = [];
            $res['created'] = $hit['created'];
            $res['title'] = @$hit['title'];
            $res['url'] = @$hit['url'];
            $res['keywords'] = @$hit['keywords'];
            $res['language'] = @$hit['language'];
            $res['geo_lat'] = @$hit['geo_lat'];
            $res['geo_lon'] = @$hit['geo_lon'];
            $res['geo_zoom'] = @$hit['geo_zoom'];

            $res['class'] = 'tablename';
            $res['metadata'] = '';

            if ($res['geo_lat'] && $res['geo_lon']) {
                $res['class'] .= ' geolocated connection';
                $res['metadata'] = " data-geo-lat=\"{$res['geo_lat']}\" data-geo-lon=\"{$res['geo_lon']}\"";

                if (isset($res['geo_zoom'])) {
                    $res['metadata'] .= " data-geo-zoom=\"{$res['geo_zoom']}\"";
                }
                $res['metadata'] .= ' data-icon-name="tiki"';
            }

            $ret[] = $res;
        }

        return $ret;
    }

    public function rebuildIndex()
    {
        $this->getIndex(true);
    }

    private function getIndex($rebuld = false)
    {
        $index = TikiLib::lib('unifiedsearch')->getIndex('connect');

        if ($rebuild || ! $index->exists()) {
            $typeFactory = $index->getTypeFactory();

            foreach ($this->getReceivedDataLatest() as $connection) {
                $data = json_decode($connection['data'], true);

                if ($data) {
                    $doc = $this->indexConnection($typeFactory, $connection['created'], $data);
                    $index->addDocument($doc);
                }
            }

            $index->optimize();
        }

        return $index;
    }

    private function indexConnection($typeFactory, $created, $data)
    {
        $doc = [
            'created' => $typeFactory->timestamp($created),
            'version' => $typeFactory->plaintext($data['version']),
        ];

        if (! empty($data['site'])) {
            if (! empty($data['site']['connect_site_title'])) {
                $doc['title'] = $typeFactory->plaintext($data['site']['connect_site_title']);
            }
            if (! empty($data['site']['connect_site_url'])) {
                $doc['url'] = $typeFactory->plaintext($data['site']['connect_site_url']);
            }
            if (! empty($data['site']['connect_site_email'])) {
                $doc['email'] = $typeFactory->plaintext($data['site']['connect_site_email']);
            }
            if (! empty($data['site']['connect_site_keywords'])) {
                $doc['keywords'] = $typeFactory->plaintext($data['site']['connect_site_keywords']);
            }
            if (! empty($data['site']['connect_site_location'])) {
                $loc = TikiLib::lib('geo')->parse_coordinates($data['site']['connect_site_location']);
                if (count($loc) > 1) {
                    $doc['geo_lat'] = $typeFactory->numeric($loc['lat']);
                    $doc['geo_lon'] = $typeFactory->numeric($loc['lon']);
                    if (count($loc) > 2) {
                        $doc['geo_zoom'] = $typeFactory->numeric($loc['zoom']);
                    }
                }
            }
        } else {
            $doc['title'] = $typeFactory->plaintext(tra('Anonymous'));
        }
        if (! empty($data['tables'])) {
            $doc['tables'] = $typeFactory->plaintext(serialize($data['tables']));
        }
        if (! empty($data['prefs'])) {
            $doc['prefs'] = $typeFactory->plaintext(serialize($data['prefs']));
            if (! empty($data['prefs']['language'])) {
                $langLib = TikiLib::lib('language');
                $languages = $langLib->get_language_map();
                $doc['language'] = $typeFactory->plaintext($languages[$data['prefs']['language']]);
            }
        }
        if (! empty($data['server'])) {
            $doc['server'] = $typeFactory->plaintext(serialize($data['server']));
        }
        if (! empty($data['votes'])) {
            $doc['votes'] = $typeFactory->plaintext(serialize($data['votes']));
        }

        $contents = $doc['created']->getValue() . ' ' . $doc['title']->getValue();
        if (isset($doc['language'])) {
            $contents .= ' ' . $doc['language']->getValue();
        }
        if (isset($doc['keywords'])) {
            $contents .= ' ' . $doc['keywords']->getValue();
        }
        $doc['contents'] = $typeFactory->plaintext($contents);

        return $doc;
    }

    public function recordConnection($status, $guid, $data = '', $server = false)
    {
        $created = parent::recordConnection($status, $guid, $data, $server);
        if ($server) {
            $index = TikiLib::lib('unifiedsearch')->getIndex('connect');
            $typeFactory = $index->getTypeFactory();
            $doc = $this->indexConnection($typeFactory, $created, $data);
            $index->addDocument($doc);
        }
    }


    /**
     * Gets a summary of connections
     *
     * @return array
     */

    public function getReceivedDataStats()
    {
        global $prefs;

        $ret = [];

        $ret['received'] = $this->connectTable->fetchCount(
            [
                'type' => 'received',
                'server' => 1,
            ]
        );

        // select distinct guid from tiki_connect where server=1;
        $res = TikiLib::lib('tiki')->getOne('SELECT COUNT(DISTINCT `guid`) FROM `tiki_connect` WHERE `server` = 1 AND `type` = \'received\';');

        $ret['guids'] = $res;

        return $ret;
    }

    public function getReceivedDataLatest()
    {

        // select distinct guid from tiki_connect where server=1;
        $res = TikiLib::lib('tiki')->fetchAll('SELECT `data` FROM (SELECT * FROM `tiki_connect` WHERE `server` = 1 AND `type` = \'received\' ORDER BY `created` DESC) as `tc` GROUP BY `guid`, `data`, `created` ORDER BY `created` DESC;');

        return $res;
    }

    /**
     * test if a guid is pending
     * Connect Server
     *
     * @param string $guid
     * @return string
     */

    public function isPendingGuid($guid)
    {
        $res = $this->connectTable->fetchOne(
            'data',
            [
                'type' => 'pending',
                'server' => 1,
                'guid' => $guid,
            ]
        );
        return $res;
    }

    /**
     * text if a guid is confirmed here
     * Connect Server
     *
     * @param string $guid
     * @return bool
     */

    public function isConfirmedGuid($guid)
    {
        $res = $this->connectTable->fetchCount(
            [
                'type' => 'confirmed',
                'server' => 1,
                'guid' => $guid,
            ]
        );
        return $res > 0;
    }
}
