<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Kaltura\Client\Client;
use Kaltura\Client\Configuration;
use Kaltura\Client\Type\FilterPager;
use Kaltura\Client\Type\MediaEntry;
use Kaltura\Client\Type\MediaEntryFilter;
use Kaltura\Client\Type\MixEntry;
use Kaltura\Client\Type\MixEntryFilter;
use Kaltura\Client\Type\UiConfFilter;

class KalturaLib
{
    private const CONFIGURATION_LIST = 'kaltura_configuration_list';
    private const SESSION_ADMIN = 2;
    private const SESSION_USER = 0;

    private $kconfig;
    private $client;
    private $sessionType;
    private $initialized = false;

    public function __construct($session_type)
    {
        $this->sessionType = $session_type;
    }

    public function getSessionKey()
    {
        if ($session = $this->storedKey()) {
            return $session;
        }

        if ($this->getClient()) {
            return $this->storedKey();
        }

        return '';
    }

    private function storedKey($key = null)
    {
        global $user;
        $tikilib = TikiLib::lib('tiki');
        $session = "kaltura_session_{$this->sessionType}_$user";

        if (is_null($key)) {
            if (isset($_SESSION[$session]) && $_SESSION[$session]['expiry'] > $tikilib->now) {
                return $_SESSION[$session]['key'];
            }
        } else {
            $_SESSION[$session] = [
                'key' => $key,
                'expiry' => $tikilib->now + 1800, // Keep for half an hour
            ];
        }

        return $key;
    }

    private function getConfig()
    {
        if (! $this->kconfig) {
            global $prefs;
            $this->kconfig = new Configuration();
            $this->kconfig->setServiceUrl($prefs['kaltura_kServiceUrl']);
        }

        return $this->kconfig;
    }

    private function getClient()
    {
        if (! $this->initialized && ! $this->client) {
            $this->initialized = true;
            try {
                $client = new Client($this->getConfig());
                if ($session = $this->storedKey()) {
                    $client->setKs($session);
                    $this->client = $client;
                } elseif ($session = $this->initializeClient($client)) {
                    $client->setKs($session);
                    $this->client = $client;
                    $this->storedKey($session);
                }
            } catch (Exception $e) {
                Feedback::error(tr("There was an issue with the integration with Kaltura: %0", $e->getMessage()));
            }
        }

        return $this->client;
    }

    public function getMediaUrl($entryId, $playerId)
    {
        global $prefs;
        $config = $this->getConfig();
        return $config->getServiceUrl() . "kwidget/wid/_{$prefs['kaltura_partnerId']}/uiconf_id/$playerId/entry_id/$entryId";
    }

    public function getPlaylist($entryId)
    {
        if ($client = $this->getClient()) {
            if ($playlist = $client->playlist) {
                return $playlist->get($entryId);
            }
        }
        return null;
    }

    public function testSetup()
    {
        global $prefs;
        if (empty($prefs['kaltura_partnerId']) || ! is_numeric($prefs['kaltura_partnerId']) || empty($prefs['kaltura_secret']) || empty($prefs['kaltura_adminSecret'])) {
            return false;
        } else {
            return true;
        }
    }

    private function initializeClient($client)
    {
        global $prefs, $user;

        if (! $this->testSetup()) {
            return false;
        }

        if ($user) {
            $kuser = $user;
        } else {
            $kuser = 'Anonymous';
        }

        if ($this->sessionType == self::SESSION_ADMIN) {
            $session = $client->session->start($prefs['kaltura_adminSecret'], $kuser, self::SESSION_ADMIN, $prefs['kaltura_partnerId'], 86400, '*');
        } else {
            $session = $client->session->start($prefs['kaltura_secret'], $kuser, self::SESSION_USER, $prefs['kaltura_partnerId'], 86400, 'edit:*');
        }

        return $session;
    }

    private function internalGetPlayersUiConfs()
    {
        if ($client = $this->getClient()) {
            $filter = new UiConfFilter();
            $filter->objTypeEqual = 1; // 1 denotes Players
            $filter->orderBy = '-createdAt';
            $uiConfs = $client->uiConf->listAction($filter);

            if (is_null($client->error)) {
                return $uiConfs;
            }
        }

        $uiConfs = new stdClass();
        $uiConfs->objects = [];

        return $uiConfs;
    }

    public function getPlayersUiConfs()
    {
        $cachelib = TikiLib::lib('cache');

        if (! $configurations = $cachelib->getSerialized(self::CONFIGURATION_LIST)) {
            try {
                $obj = $this->internalGetPlayersUiConfs()->objects;
            } catch (Exception $e) {
                Feedback::error(tr("There was an issue with the integration with Kaltura: %0", $e->getMessage()));
                return [];
            }
            $configurations = [];
            foreach ($obj as $o) {
                $configurations[] = get_object_vars($o);
            }

            $cachelib->cacheItem(self::CONFIGURATION_LIST, serialize($configurations));
        }

        return $configurations;
    }

    public function getPlayersUiConf($playerId)
    {
        // Ontaining full list, because it is cached
        $confs = $this->getPlayersUiConfs();

        foreach ($confs as $config) {
            if ($config['id'] == $playerId) {
                return $config;
            }
        }
    }

    public function cloneMix($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->mixing->cloneAction($entryId);
        }
    }

    public function deleteMedia($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->media->delete($entryId);
        }
    }

    public function deleteMix($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->mixing->delete($entryId);
        }
    }

    public function flattenVideo($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->mixing->requestFlattening($entryId, 'flv'); // FIXME this method is no longer supported
        }
    }

    public function getMix($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->mixing->get($entryId);
        }
    }

    public function updateMix($entryId, array $data)
    {
        if ($client = $this->getClient()) {
            $kentry = new MixEntry();
            $kentry->name = $data['name'];
            $kentry->description = $data['description'];
            $kentry->tags = $data['tags'];
            $kentry->editorType = $data['editorType'];
            $kentry->adminTags = $data['adminTags'];

            return $client->mixing->update($entryId, $kentry);
        }
    }

    public function getMedia($entryId)
    {
        if ($client = $this->getClient()) {
            return $client->media->get($entryId);
        }
    }

    public function updateMedia($entryId, array $data)
    {
        if ($client = $this->getClient()) {
            $kentry = new MediaEntry();
            $kentry->name = $data['name'];
            $kentry->description = $data['description'];
            $kentry->tags = $data['tags'];
            $kentry->adminTags = $data['adminTags'];

            return $client->media->update($entryId, $kentry);
        }
    }

    public function listMix($sort_mode, $page, $page_size, $find)
    {
        if ($client = $this->getClient()) {
            $kpager = new FilterPager();
            $kpager->pageIndex = $page;
            $kpager->pageSize = $page_size;

            $kfilter = new MixEntryFilter();
            $kfilter->orderBy = $sort_mode;
            $kfilter->nameMultiLikeOr = $find;

            return $client->mixing->listAction($kfilter, $kpager);
        }
    }

    public function listMedia($sort_mode, $page, $page_size, $find)
    {
        if ($client = $this->getClient()) {
            $kpager = new FilterPager();
            $kpager->pageIndex = $page;
            $kpager->pageSize = $page_size;

            $kfilter = new MediaEntryFilter();
            $kfilter->orderBy = $sort_mode;
            $kfilter->nameMultiLikeOr = $find;
            $kfilter->statusIn = '-1,-2,0,1,2';

            return $client->media->listAction($kfilter, $kpager);
        }
    }

    public function getMovieList(array $movies)
    {
        if (count($movies) && $client = $this->getClient()) {
            $kpager = new FilterPager();
            $kpager->pageIndex = 0;
            $kpager->pageSize = count($movies);

            $kfilter = new MediaEntryFilter();
            $kfilter->idIn = implode(',', $movies);

            $mediaList = [];
            foreach ($client->media->listAction($kfilter, $kpager)->objects as $media) {
                $mediaList[] = [
                    'id' => $media->id,
                    'name' => $media->name,
                ];
            }

            return $mediaList;
        }

        return [];
    }

    /**
     * Upload video to Kaltura
     *
     * @return MediaEntry|null
     */
    public function uploadVideo($filePath, $name, $description = '', $tags = '')
    {
        if ($client = $this->getClient()) {
            try {
                // 1. Create upload token
                $uploadToken = new \Kaltura\Client\Type\UploadToken();
                $uploadToken = $client->uploadToken->add($uploadToken);

                // 2. Upload the file to the token
                $uploadedToken = $client->uploadToken->upload($uploadToken->id, $filePath);

                // 3. Create media entry
                $mediaEntry = new MediaEntry();
                $mediaEntry->name = $name;
                $mediaEntry->description = $description;
                $mediaEntry->tags = $tags;
                $mediaEntry->mediaType = \Kaltura\Client\Enum\MediaType::VIDEO;

                $entry = $client->media->add($mediaEntry);

                // 4. Attach the uploaded content to the entry
                $resource = new \Kaltura\Client\Type\UploadedFileTokenResource();
                $resource->token = $uploadedToken->id;

                $entry = $client->media->addContent($entry->id, $resource);

                return $entry;
            } catch (\Exception $e) {
                Feedback::error(tr('Kaltura upload error: %0', $e->getMessage()));
                return null;
            }
        }

        return null;
    }
}
