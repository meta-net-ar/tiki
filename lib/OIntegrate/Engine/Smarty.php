<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OIntegrate\Engine;

use Tiki\Smarty\SmartyTiki;
use Tiki\Lib\OIntegrate\EngineInterface;

/**
 *
 */
class Smarty implements EngineInterface
{
    private $changeDelimiters;

    /**
     * @param bool $changeDelimiters
     */
    public function __construct($changeDelimiters = false)
    {
        $this->changeDelimiters = $changeDelimiters;
    }

    /**
     * @param $data
     * @param $templateFile
     * @return mixed
     */
    public function process($data, $templateFile)
    {
        /** @var SmartyTiki $smarty */
        $smarty = new SmartyTiki();
        $smarty->setTemplateDir(dirname($templateFile));

        if ($this->changeDelimiters) {
            $smarty->setLeftDelimiter('{{');
            $smarty->setRightDelimiter('}}');
        }

        $smarty->assign('response', $data);
        return $smarty->fetch($templateFile);
    }
}
