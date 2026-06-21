<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OIntegrate;

use TikiLib;

/**
 *
 */
class Response
{
    public $version = null;
    public $schemaVersion = null;
    public $schemaDocumentation = null;
    public $contentType = null;
    public $cacheControl = null;
    public $data;
    public $errors = [];

    /**
     * @param $data
     * @param $schemaVersion
     * @param int $cacheLength
     * @return Response
     */
    public static function create($data, $schemaVersion, $cacheLength = 300)
    {
        $response = new self();
        $response->version = '1.0';
        $response->data = $data;
        $response->schemaVersion = $schemaVersion;

        if ($cacheLength > 0) {
            $response->cacheControl = "max-age=$cacheLength";
        } else {
            $response->cacheControl = "no-cache";
        }

        return $response;
    }

    /**
     * @param $engine
     * @param $output
     * @param $templateLocation
     */
    public function addTemplate($engine, $output, $templateLocation)
    {
        if (! array_key_exists('_template', $this->data)) {
            $this->data['_template'] = [];
        }
        if (! array_key_exists($engine, $this->data['_template'])) {
            $this->data['_template'][$engine] = [];
        }
        if (! array_key_exists($output, $this->data['_template'][$engine])) {
            $this->data['_template'][$engine][$output] = [];
        }

        if (! str_starts_with($templateLocation, 'http')) {
            $host = $_SERVER['HTTP_HOST'];
            $proto = 'http';
            $path = dirname($_SERVER['SCRIPT_NAME']);
            $templateLocation = ltrim($templateLocation, '/');

            $templateLocation = "$proto://$host$path/$templateLocation";
        }

        $this->data['_template'][$engine][$output][] = $templateLocation;
    }

    public function send()
    {
        header('OIntegrate-Version: 1.0');
        header('OIntegrate-SchemaVersion: ' . $this->schemaVersion);
        if ($this->schemaDocumentation) {
            header('OIntegrate-SchemaDocumentation: ' . $this->schemaDocumentation);
        }
        header('Cache-Control: ' . $this->cacheControl);

        $data = $this->data;
        $data['_version'] = $this->schemaVersion;

        $access = TikiLib::lib('access');
        $access->output_serialized($data);
        exit;
    }

    /**
     * @param $engine
     * @param $engineOutput
     * @param $outputContext
     * @param $templateFile
     * @return mixed|string
     */
    public function render($engine, $engineOutput, $outputContext, $templateFile)
    {
        $engine = OIntegrate::getEngine($engine, $engineOutput);
        if (! $engine) {
            $this->errors = [ 1000, tr('Engine "%0" not found.', $engineOutput) ];
            return false;
        }

        if (! $output = OIntegrate::getConverter($engineOutput, $outputContext)) {
            $this->errors = [ 1001, tr('Output converter "%0" not found.', $outputContext) ];
            return false;
        }

        $raw = $engine->process($this->data, $templateFile);
        return $output->convert($raw);
    }

    /**
     * @param null $supportedPairs
     * @return array
     */
    public function getTemplates($supportedPairs = null)
    {
        if (! is_array($this->data) || ! isset($this->data['_template']) || ! is_array($this->data['_template'])) {
            return [];
        }

        $templates = [];

        foreach ($this->data['_template'] as $engine => $outputs) {
            foreach ($outputs as $output => $files) {
                if (is_array($supportedPairs) && ! in_array("$engine/$output", $supportedPairs)) {
                    continue;
                }

                $files = (array) $files;

                foreach ($files as $file) {
                    $content = TikiLib::lib('tiki')->httprequest($file);

                    $templates[] = [
                        'engine' => $engine,
                        'output' => $output,
                        'content' => $content,
                    ];
                }
            }
        }

        return $templates;
    }
}
