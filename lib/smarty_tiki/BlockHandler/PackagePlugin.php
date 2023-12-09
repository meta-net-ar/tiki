<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\BlockHandler;

use Smarty\BlockHandler\Base;
use Smarty\Template;

class PackagePlugin extends Base
{
    public function handle($params, $content, Template $template, &$repeat)
    {
        extract($params, EXTR_SKIP);

        if (empty($params['package']) || empty($params['plugin'])) {
            return tra("Please specify the name of the package and the wiki plugin.");
        }

        if (! $extensionPackage = \Tiki\Package\ExtensionManager::get($params['package'])) {
            return tr('Package %0 is not enabled', $params['package']);
        }

        $path = $extensionPackage->getPath() . '/lib/wiki-plugins/' . $params['plugin'] . '.php';

        if (! file_exists($path)) {
            return tra("Error: Unable to locate wiki plugin file for the package.");
        }

        require_once($path);

        $namespace = $extensionPackage->getBaseNamespace();
        if (! empty($namespace)) {
            $namespace .= '\\PackagePlugins\\';
        }
        $functionname = $namespace . $params['plugin'];

        if (! function_exists($functionname)) {
            return tra("Error: Unable to locate function name for the package plugin.");
        }

        if ($params['assign']) {
            $smarty = \TikiLib::lib('smarty');
            $smarty->assign($params['assign'], $functionname($content, $params, $template));
        } else {
            return $functionname($content, $params, $template);
        }
    }
}
