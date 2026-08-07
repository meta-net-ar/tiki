<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Videogals;

class VimeoLib
{
    private $client;

    public function __construct()
    {
        $this->client = null;
    }

    private function getClient()
    {
        if ($this->client) {
            return $this->client;
        }

        global $prefs;

        $clientId = $prefs['vimeo_consumer_key'] ?? '';
        $clientSecret = $prefs['vimeo_consumer_secret'] ?? '';
        $accessToken = $prefs['vimeo_access_token'] ?? '';

        if (empty($clientId) || empty($clientSecret) || empty($accessToken)) {
            throw new \Exception(tr("Vimeo configuration is missing (consumer key, secret, or access token)."));
        }

        if (! class_exists(\Vimeo\Vimeo::class)) {
            throw new \Exception(tr("Vimeo PHP SDK is not installed. Please run 'composer require vimeo/vimeo-api' in vendor_bundled."));
        }

        $this->client = new \Vimeo\Vimeo($clientId, $clientSecret, $accessToken);
        return $this->client;
    }

    public function testSetup()
    {
        global $prefs;
        return ! empty($prefs['vimeo_consumer_key'])
            && ! empty($prefs['vimeo_consumer_secret'])
            && ! empty($prefs['vimeo_access_token']);
    }

    public function testConnection()
    {
        try {
            if (! $this->testSetup()) {
                return tr("Vimeo configuration is missing. Please check your API credentials in the admin panel.");
            }

            $response = $this->getClient()->request('/tutorial', [], 'GET');
            if (isset($response['status']) && $response['status'] == 200) {
                return true;
            } else {
                $msg = $response['body']['error'] ?? tr("Unknown error during Vimeo API request.");
                return tr("Could not connect to Vimeo API: %0", $msg);
            }
        } catch (\Exception $e) {
            return tr("Failed to connect to Vimeo API: %0", $e->getMessage());
        }
    }

    public function uploadVideo(array $file, array $metadata)
    {
        if (empty($file['videofile'])) {
            throw new \Exception(tr('No video file provided for upload.'));
        }

        $filePath = $file['videofile'];
        $params = [];

        if (! empty($metadata['name'])) {
            $params['name'] = $metadata['name'];
        }

        if (! empty($metadata['description'])) {
            $params['description'] = $metadata['description'];
        }

        if (! empty($metadata['privacy'])) {
            $params['privacy'] = ['view' => $metadata['privacy']];
        }

        $response = $this->getClient()->upload($filePath, $params);

        // $response is the URI of the uploaded video, e.g. /videos/123456789
        if (is_string($response) && strpos($response, '/videos/') === 0) {
            $uuid = str_replace('/videos/', '', $response);
            return [
                'video' => [
                    'uuid'      => $uuid,
                    'shortUUID' => $uuid,
                ],
            ];
        }

        throw new \Exception(tr('Upload failed or unexpected response: %0', print_r($response, true)));
    }

    public function replaceVideo($videoId, array $file)
    {
        if (empty($file['videofile'])) {
            throw new \Exception(tr('No video file provided for replacement.'));
        }

        $filePath = $file['videofile'];
        $uri = strpos($videoId, '/videos/') === 0 ? $videoId : '/videos/' . $videoId;

        $response = $this->getClient()->replace($uri, $filePath);

        // $response is the URI of the replaced video
        if (is_string($response) && strpos($response, '/videos/') === 0) {
            return true;
        }

        throw new \Exception(tr('Video replacement failed or unexpected response: %0', print_r($response, true)));
    }

    public function uploadThumbnail($videoId, array $file, $activate = true)
    {
        if (empty($file['thumbfile'])) {
            throw new \Exception(tr('No thumbnail file provided.'));
        }

        $filePath = $file['thumbfile'];
        $uri = strpos($videoId, '/videos/') === 0 ? $videoId : '/videos/' . $videoId;
        $picturesUri = $uri . '/pictures';

        $response = $this->getClient()->uploadImage($picturesUri, $filePath, $activate);

        if (is_string($response) && strpos($response, $uri) === 0) {
            return true;
        }

        // Often Vimeo returns the picture URI array. If the upload doesn't throw, it likely succeeded.
        // Let's check if the response has uri
        if (is_array($response) && ! empty($response['uri'])) {
            return true;
        }

        throw new \Exception(tr('Thumbnail upload failed or unexpected response: %0', print_r($response, true)));
    }

    public function listVideos($sort_mode = 'date_desc', $page = 1, $page_size = 10, $find = '')
    {
        $map = [
            'date_desc' => 'date',
            'date_asc'  => 'date',
            'name_desc' => 'alphabetical',
            'name_asc'  => 'alphabetical',
        ];

        $apiSort = $map[$sort_mode] ?? 'date';
        $direction = str_ends_with($sort_mode, '_asc') ? 'asc' : 'desc';

        $params = [
            'page'      => $page,
            'per_page'  => $page_size,
            'sort'      => $apiSort,
            'direction' => $direction,
        ];

        if (! empty($find)) {
            $params['query'] = $find;
        }

        $response = $this->getClient()->request('/me/videos', $params, 'GET');

        if ($response['status'] === 200) {
            $videos = [];
            foreach ($response['body']['data'] as $vid) {
                // Parse UUID
                $uuid = str_replace('/videos/', '', $vid['uri']);
                // Grab the best available thumbnail, typically size index 2 is approx 295x166
                $thumbUrl = null;
                if (! empty($vid['pictures']['sizes']) && is_array($vid['pictures']['sizes'])) {
                    $sizes = $vid['pictures']['sizes'];
                    $thumbUrl = $sizes[min(2, count($sizes) - 1)]['link'] ?? $sizes[0]['link'] ?? null;
                }

                $videos[] = (object)[
                    'id'          => $uuid,
                    'uuid'        => $uuid,
                    'shortUUID'   => $uuid,
                    'name'        => $vid['name'],
                    'publishedAt' => $vid['created_time'],
                    'views'       => $vid['stats']['plays'] ?? 0,
                    'privacy'     => ['label' => $vid['privacy']['view']],
                    'privacyLabel' => $vid['privacy']['view'],
                    'duration'    => $vid['duration'],
                    'thumbUrl'    => $thumbUrl,
                ];
            }
            return (object)[
                'total' => $response['body']['total'],
                'data'  => $videos,
            ];
        }

        throw new \Exception('Failed to list videos: ' . print_r($response['body'], true));
    }

    public function getVideo($id)
    {
        $id = trim($id);
        if (empty($id)) {
            throw new \Exception("Video ID cannot be empty");
        }

        // Handle full URIs or bare IDs
        $uri = strpos($id, '/videos/') === 0 ? $id : '/videos/' . $id;

        $response = $this->getClient()->request($uri, [], 'GET');

        if ($response['status'] === 200) {
            $vid = $response['body'];

            $uuid = str_replace('/videos/', '', $vid['uri']);

            // Grab the best available thumbnail
            $thumbUrl = null;
            if (! empty($vid['pictures']['sizes']) && is_array($vid['pictures']['sizes'])) {
                $sizes = $vid['pictures']['sizes'];
                $thumbUrl = $sizes[min(2, count($sizes) - 1)]['link'] ?? $sizes[0]['link'] ?? null;
            }

            // Transform object to resemble peertube data structure
            return [
                'id'          => $uuid,
                'uuid'        => $uuid,
                'shortUUID'   => $uuid,
                'name'        => $vid['name'],
                'description' => $vid['description'],
                'publishedAt' => $vid['created_time'],
                'views'       => $vid['stats']['plays'] ?? 0,
                'privacy'     => ['label' => $vid['privacy']['view']],
                'privacyLabel' => $vid['privacy']['view'],
                'duration'    => $vid['duration'],
                'tags'        => array_column($vid['tags'], 'name'),
                'status'      => $vid['status'],
                'embedPath'   => '',
                'link'        => $vid['link'],
                'thumbUrl'    => $thumbUrl,
            ];
        }

        throw new \Exception('Failed to get video: ' . print_r($response['body'], true));
    }

    public function deleteVideo($uuid)
    {
        $uri = strpos($uuid, '/videos/') === 0 ? $uuid : '/videos/' . $uuid;
        $response = $this->getClient()->request($uri, [], 'DELETE');
        if ($response['status'] === 204) {
            return ['success' => true];
        }
        throw new \Exception('Failed to delete video: ' . print_r($response['body'], true));
    }

    public function updateVideo($id, array $data)
    {
        $uri = strpos($id, '/videos/') === 0 ? $id : '/videos/' . $id;
        $response = $this->getClient()->request($uri, $data, 'PATCH');
        if ($response['status'] === 200) {
            return true;
        }
        throw new \Exception('Failed to update video: ' . print_r($response['body'], true));
    }

    public function getVideoList(array $videoIds)
    {
        $videos = [];
        foreach ($videoIds as $id) {
            try {
                $videos[] = $this->getVideo($id);
            } catch (\Exception $e) {
                // log and skip
                error_log("Failed to fetch Vimeo video $id: " . $e->getMessage());
            }
        }
        return $videos;
    }
}
