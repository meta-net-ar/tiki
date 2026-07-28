<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OIntegrate;

use DeclFilter;
use Feedback;
use Symfony\Component\Yaml\Yaml;
use Tiki\Lib\OIntegrate\Converter\Direct;
use Tiki\Lib\OIntegrate\Converter\EncodeHtml;
use Tiki\Lib\OIntegrate\Converter\HtmlToTiki;
use Tiki\Lib\OIntegrate\Converter\Indexer;
use Tiki\Lib\OIntegrate\Converter\TikiToHtml;
use Tiki\Lib\OIntegrate\Engine\Index;
use Tiki\Lib\OIntegrate\Engine\JavaScript;
use Tiki\Lib\OIntegrate\Engine\Smarty;
use TikiLib;

/**
 *
 */
class OIntegrate
{
    /**
     * @var mixed[]
     */
    public $acceptTemplate;
    private $schemaVersion = [];
    private $acceptTemplates = [];

    /**
     * @param $name
     * @param $engineOutput
     * @return Tiki\Lib\OIntegrate\Engine\JavaScript|Tiki\Lib\OIntegrate\Engine\Smarty|Tiki\Lib\OIntegrate\Engine\Index
     */
    public static function getEngine($name, $engineOutput)
    {
        switch ($name) {
            case 'javascript':
                return new JavaScript();
            case 'smarty':
                return new Smarty($engineOutput == 'tikiwiki');
            case 'index':
                return new Index();
        }
    }

    /**
     * @param $from
     * @param $to
     * @return Tiki\Lib\OIntegrate\Converter\Direct|Tiki\Lib\OIntegrate\Converter\EncodeHtml|Tiki\Lib\OIntegrate\Converter\HtmlToTiki|Tiki\Lib\OIntegrate\Converter\TikiToHtml|Tiki\Lib\OIntegrate\Converter\Indexer
     */
    public static function getConverter($from, $to)
    {
        switch ($from) {
            case 'html':
                if ($to == 'tikiwiki') {
                    return new HtmlToTiki();
                } elseif ($to == 'html') {
                    return new Direct();
                }
                break;
            case 'tikiwiki':
                if ($to == 'html') {
                    return new TikiToHtml();
                } elseif ($to == 'tikiwiki') {
                    return new EncodeHtml();
                }
                break;
            case 'index':
            case 'mindex':
                if ($to == 'index') {
                    return new Indexer();
                } elseif ($to == 'html') {
                    return new Indexer('html');
                } elseif ($to == 'tikiwiki') {
                    return new Indexer('tikiwiki');
                }
                break;
        }
    }

    /**
     * @param string $url
     * @param string $postBody url or json encoded post parameters
     * @param bool $clearCache
     * @return Response
     */
    public function performRequest($url, $postBody = null, $clearCache = false) // {{{
    {
        $cachelib = TikiLib::lib('cache');
        $tikilib = TikiLib::lib('tiki');

        $cacheKey = $url . $postBody;

        if ($cache = $cachelib->getSerialized($cacheKey)) {
            if (time() < $cache['expires'] && ! $clearCache) {
                return $cache['data'];
            }

            $cachelib->invalidate($cacheKey);
        }

        $client = $tikilib->get_http_client($url);
        $method = null;

        if (empty($postBody)) {
            $method = 'GET';
            $http_headers = [
                    'Accept' => 'application/json,text/x-yaml',
                    'OIntegrate-Version' => '1.0',
                ];
        } else {
            $method = 'POST';
            if (json_decode($postBody)) {   // autodetect if the content type should be json
                $requestContentType = 'application/json';
            } else {
                $requestContentType = 'application/x-www-form-urlencoded';
            }
            $http_headers = [
                    'Accept' => 'application/json,text/x-yaml',
                    'OIntegrate-Version' => '1.0',
                    'Content-Type' => $requestContentType,
            ];
            $client->setRawBody($postBody);
        }

        if (count($this->schemaVersion)) {
            $http_headers['OIntegrate-SchemaVersion'] = implode(', ', $this->schemaVersion);
        }
        if (count($this->acceptTemplates)) {
            $http_headers['OIntegrate-AcceptTemplate'] = implode(', ', $this->acceptTemplates);
        }

        // merge with existing headers
        $headers = $client->getRequest()->getHeaders();
        $http_headers = array_merge($headers->toArray(), $http_headers);

        $client->setHeaders($http_headers);

        $client->setMethod($method);
        $httpResponse = $client->send();
        $content = $httpResponse->getBody();

        $requestContentType = $httpResponse->getHeaders()->get('Content-Type');
        $cacheControl = $httpResponse->getHeaders()->get('Cache-Control');

        $response = new Response();
        $response->contentType = $requestContentType;
        $response->cacheControl = $cacheControl;
        if ($requestContentType) {
            $mediaType = $requestContentType->getMediaType();
        } else {
            $mediaType = '';
        }
        $response->data = $this->unserialize($mediaType, $content);

        if ($response->data === null) {
            Feedback::error(tr('Webservice response could not be parsed as JSON or YAML (URL: %0).', $url));
        }

        $filter = new DeclFilter();
        $filter->addCatchAllFilter('xss');

        $response->data = $filter->filter($response->data);
        $response->version = $httpResponse->getHeaders()->get('OIntegrate-Version');
        $response->schemaVersion = $httpResponse->getHeaders()->get('OIntegrate-SchemaVersion');
        if (! $response->schemaVersion && isset($response->data->_version)) {
            $response->schemaVersion = $response->data->_version;
        }
        $response->schemaDocumentation = $httpResponse->getHeaders()->get('OIntegrate-SchemaDocumentation');

        global $prefs;
        if (empty($cacheControl)) {
            $maxage = 0;
            $nocache = false;
        } else {
            // Respect cache duration and no-cache asked for
            $maxage = $cacheControl->getDirective('max-age');
            $nocache = $cacheControl->getDirective('no-cache');
        }
        if ($maxage) {
            $expiry = time() + $maxage;

            $cachelib->cacheItem(
                $cacheKey,
                serialize(['expires' => $expiry, 'data' => $response])
            );
        // Unless service specifies not to cache result, apply a default cache
        } elseif (empty($nocache) && $prefs['webservice_consume_defaultcache'] > 0) {
            $expiry = time() + $prefs['webservice_consume_defaultcache'];

            $cachelib->cacheItem($cacheKey, serialize(['expires' => $expiry, 'data' => $response]));
        }

        return $response;
    }

    /**
     * @param string $type
     * @param string $data
     * @return array|mixed|null
     */
    public function unserialize($type, $data)
    {

        if (empty($data)) {
            return null;
        }

        switch ($type) {
            case 'application/json':
            case 'text/javascript':
                if ($out = json_decode($data, true)) {
                    return $out;
                }

                // Handle invalid JSON too...
                $fixed = preg_replace('/(\w+):/', '"$1":', $data);
                $out = json_decode($fixed, true);
                return $out;
            case 'text/x-yaml':
                try {
                    return Yaml::parse($data);
                } catch (\Symfony\Component\Yaml\Exception\ParseException) {
                    return null;
                }
            default:
                // Attempt anything...
                if ($out = $this->unserialize('application/json', $data)) {
                    return $out;
                }
                if ($out = $this->unserialize('text/x-yaml', $data)) {
                    return $out;
                }
        }
    }

    /**
     * @param $version
     */
    public function addSchemaVersion($version)
    {
        $this->schemaVersion[] = $version;
    }

    /**
     * @param $engine
     * @param $output
     */
    public function addAcceptTemplate($engine, $output)
    {
        $this->acceptTemplate[] = "$engine/$output";
    }
}
