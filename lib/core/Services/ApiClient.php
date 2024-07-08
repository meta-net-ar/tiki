<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_ApiClient
{
    protected $url;
    protected $isTiki;
    protected $apiBridge;
    protected $user;
    protected $format;

    public function __construct($url, $isTiki = true)
    {
        $this->url = $url;
        $this->isTiki = $isTiki;
        $this->apiBridge = new Services_ApiBridge();
        $this->user = null;
        $this->format = 'json';
    }

    public function setContextUser($user)
    {
        $this->user = $user;
    }

    public function setFormat($format)
    {
        $this->format = $format;
    }

    public function __call($method, $args)
    {
        $endpoint = $args[0] ?? '';
        $arguments = $args[1] ?? [];
        $content_type = $args[2] ?? null;
        $uploads = $args[3] ?? [];

        $client = $this->getClient($method, $endpoint, $arguments);

        $headers = $client->getRequest()->getHeaders();
        $headers->addHeaders(['Accept' => $this->getAcceptHeader()]);
        if ($content_type) {
            $headers->addHeaders(['Content-Type' => $content_type]);
        }

        foreach ($uploads as $name => $upload) {
            $client->setFileUpload($upload['filename'], $name, $upload['content'], $upload['filetype']);
        }

        $response = $client->send();
        if (! $response->isSuccess()) {
            $body = json_decode($response->getBody())?->message ?? $response->getBody();
            if (is_array($body)) {
                $key = key($body);
                $error = $body->$key ?? $body;
            } else {
                $error = $body;
            }

            if (! is_string($error)) {
                $error = json_encode($error);
            }
            throw new Services_Exception(tr('Remote service inaccessible (%0), error: %1', $response->getStatusCode(), $error), 400);
        }

        return $this->parseResponse($response->getBody());
    }

    public function getResultLoader($endpoint, $arguments = [], $offsetKey = 'offset', $maxRecordsKey = 'maxRecords', $resultKey = 'result', $perPage = 20)
    {
        $client = $this->getClient('get', $endpoint, $arguments);
        return new Services_ResultLoader(
            [new Services_ResultLoader_WebService($client, $offsetKey, $maxRecordsKey, $resultKey), '__invoke'],
            $perPage
        );
    }

    public function route($name, $args = [])
    {
        return $this->apiBridge->generateRoute($name, $args);
    }

    protected function getClient($method, $endpoint, $arguments)
    {
        $tikilib = TikiLib::lib('tiki');
        if ($this->isTiki) {
            $url = $this->url . '/tiki-api.php?route=' . $endpoint;
        } else {
            $url = $this->url;
            if ($endpoint) {
                $url .= '/' . $endpoint;
            }
        }
        $client = $tikilib->get_http_client($url, null, $this->user);
        switch ($method) {
            case 'get':
                $client->setMethod(Laminas\Http\Request::METHOD_GET);
                $client->setParameterGet(array_merge(
                    $client->getRequest()->getQuery()->getArrayCopy(),
                    $arguments
                ));
                break;
            case 'post':
                $client->setMethod(Laminas\Http\Request::METHOD_POST);
                break;
            case 'put':
                $client->setMethod(Laminas\Http\Request::METHOD_PUT);
                break;
            case 'patch':
                $client->setMethod(Laminas\Http\Request::METHOD_PATCH);
                break;
            case 'delete':
                $client->setMethod(Laminas\Http\Request::METHOD_DELETE);
                break;
            default:
                throw new Services_Exception(tr('Remove service invalid method used: %0, endpoint: %1', $method, $endpoint));
        }
        if (in_array($method, ['post', 'put', 'patch', 'delete'])) {
            if (is_array($arguments)) {
                $client->setParameterPost(array_merge(
                    $client->getRequest()->getPost()->getArrayCopy(),
                    $arguments
                ));
            } else {
                $client->setRawBody($arguments);
            }
        }
        return $client;
    }

    protected function getAcceptHeader()
    {
        switch ($this->format) {
            case 'csv':
                return 'text/csv';
                break;
            case 'ndjson':
                return 'application/x-ndjson';
                break;
            default:
                return 'application/json';
        }
    }

    protected function parseResponse($data)
    {
        $result = null;

        if (empty($data)) {
            return [];
        }

        switch ($this->format) {
            case 'csv':
                $result = array_map('str_getcsv', preg_split("/\r?\n/", $data));
                break;
            case 'ndjson':
                $result = array_map(function ($row) {
                    return json_decode($row, true);
                }, preg_split("/\r?\n/", $data));
                break;
            default:
                $result = json_decode($data, true);
        }

        if (json_last_error() != JSON_ERROR_NONE) {
            throw new Services_Exception(tr('Remote service responded with invalid JSON: %0', $data));
        }

        return $result;
    }
}
