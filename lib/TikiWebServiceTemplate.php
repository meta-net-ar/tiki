<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * TikiWebServiceTemplate
 */

namespace Tiki\Lib;

use Feedback;
use Tiki\Lib\OIntegrate\Response;

class TikiWebServiceTemplate
{
    public $webservice;
    public $name;
    public $engine;
    public $output;
    public $content;
    public $lastModif;

    public function save()
    {
        global $tikilib;

        $tikilib->query(
            "DELETE FROM tiki_webservice_template WHERE service = ? AND template = ?",
            [ $this->webservice->getName(), $this->name ]
        );

        $tikilib->query(
            "INSERT INTO tiki_webservice_template (service, template, engine, output, content, last_modif) VALUES(?,?,?,?,?,?)",
            [
                $this->webservice->getName(),
                $this->name,
                $this->engine,
                $this->output,
                $this->content,
                time(),
            ]
        );

        if ($this->engine === 'index') {
            if ($this->output === 'mindex') {
                Feedback::warning(tra('You will need to rebuild the search index to see these changes'));
            }

            require_once 'lib/search/refresh-functions.php';
            refresh_index('webservice', $this->name);
        }
    }

    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $token = sprintf("%s_%s", $this->webservice->getName(), $this->name);
        $file = "temp/cache/" . md5($token) . '.tpl';

        if (! file_exists($file) || $this->lastModif > filemtime($file)) {
            file_put_contents($file, $this->content);
        }

        return realpath($file);
    }

    /**
     * @param Tiki\Lib\OIntegrate\Response $response
     * @param $outputContext
     * @return mixed|string
     */
    public function render(Response $response, $outputContext)
    {
        return $response->render($this->engine, $this->output, $outputContext, $this->getTemplateFile());
    }
}
