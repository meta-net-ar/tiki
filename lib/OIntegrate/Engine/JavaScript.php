<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OIntegrate\Engine;

use Tiki\Lib\OIntegrate\EngineInterface;

/**
 *
 */
class JavaScript implements EngineInterface
{
    /**
     * @param $data
     * @param $templateFile
     * @return string
     */
    public function process($data, $templateFile)
    {
        $json = json_encode($data);

        return <<<EOC
<script type="text/javascript">
var response = $json;
</script>
EOC
        . file_get_contents($templateFile);
    }
}
