<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OIntegrate\Engine;

use Feedback;
use Tiki\Lib\OIntegrate\EngineInterface;

/**
 * Engine to pass on raw data and mapping info from the template
 */
class Index implements EngineInterface
{
    /**
     * @param array $data
     * @param string $templateFile
     * @return array
     */
    public function process($data, $templateFile)
    {
        $mappingString = file_get_contents($templateFile);
        $mapping = json_decode($mappingString, true);

        if ($mappingString && ! $mapping) {
            Feedback::error(tr('Could decode JSON webservice template for indexing, check the syntax'));
        }

        return [
            'data' => $data,
            'mapping' => $mapping,
        ];
    }
}
