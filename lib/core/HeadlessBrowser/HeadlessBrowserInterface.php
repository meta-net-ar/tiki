<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\HeadlessBrowser;

interface HeadlessBrowserInterface
{
    public function getType();

    public function getUrlAsHtml($url, $cssSelector = null);

    public function getUrlAsImage($url, $imageOutputPath = null, $cssSelector = null, $timeout = null);

    public function getDiagramAsImage($diagramXml);
}
