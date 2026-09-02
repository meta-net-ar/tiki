<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Extension;

use Smarty\FunctionHandler\Base;
use Smarty\Template;

/**
 * Wraps a whitelisted native PHP function (see pref smarty_security_allowed_builtin_php_functions)
 * so it can be called as a Smarty function/tag, e.g. {strtoupper value=$val}.
 *
 * Tag calls pass named params, unlike native PHP functions which take positional
 * arguments, so params are mapped to the function's declared parameter names via reflection.
 */
class NativeFunctionHandler extends Base
{
    /** @var string */
    private $functionName;

    public function __construct(string $functionName)
    {
        $this->functionName = $functionName;
    }

    public function handle($params, Template $template)
    {
        $reflection = new \ReflectionFunction($this->functionName);
        $args = [];
        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $params)) {
                $args[] = $params[$name];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[] = $parameter->getDefaultValue();
            } else {
                $args[] = null;
            }
        }

        return call_user_func_array($this->functionName, $args);
    }
}
