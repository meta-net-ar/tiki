<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace SmartyTiki\Extension;

use Smarty\BlockHandler\BlockHandlerInterface;
use Smarty\FunctionHandler\FunctionHandlerInterface;
use Smarty\Compile\CompilerInterface;
use Tiki\Composer\SmartyExtensionMapper;
use TikiLib;

class SmartyTikiExtension extends \Smarty\Extension\Base
{
    private $blockHandlers = [];
    private $functionHandlers = [];
    private $outputFilters = [];
    private $preFilters = [];
    private $tags = [];
    private array $extensionMap = [];

    public function __construct()
    {
        $mapFile = SmartyExtensionMapper::SMARTY_MAP_FILE;
        if (file_exists($mapFile)) {
            $map = require $mapFile;
            if (is_array($map)) {
                $this->extensionMap = $map;
            }
        }
    }

    public function getTagCompiler(string $tag): ?CompilerInterface
    {
        if (isset($this->tags[$tag])) {
            return $this->tags[$tag];
        }

        // Mapping lookup
        if (isset($this->extensionMap['tag_compilers'][$tag])) {
            $class = $this->extensionMap['tag_compilers'][$tag];
            $this->tags[$tag] = new $class();
            return $this->tags[$tag];
        }

        return null;
    }

    public function getModifierCompiler(string $modifier): ?\Smarty\Compile\Modifier\ModifierCompilerInterface
    {
        // Mapping lookup
        if (isset($this->extensionMap['modifier_compilers'][$modifier])) {
            $class = $this->extensionMap['modifier_compilers'][$modifier];
            return new $class();
        }

        // let DefaultExtension handle the rest
        return null;
    }

    public function getModifierCallback(string $modifierName)
    {
        // No TikiLib outside a full Tiki bootstrap, see SecurityPolicy::isTrustedModifier()
        if (class_exists('TikiLib') && ($tikilib = TikiLib::lib('tiki'))) {
            $allowed_builtin_php_functions = array_filter($tikilib->get_preference('smarty_security_allowed_builtin_php_functions', [], true));
            if (in_array($modifierName, $allowed_builtin_php_functions) && is_callable($modifierName)) {
                return function (...$args) use ($modifierName) {
                    return call_user_func_array($modifierName, $args);
                };
            }
        }

        // Mapping lookup for modifier classes
        if (isset($this->extensionMap['modifiers'][$modifierName])) {
            $class = $this->extensionMap['modifiers'][$modifierName];
            return [new $class(), 'handle'];
        }
        return null;
    }

    public function getBlockHandler(string $blockTagName): ?BlockHandlerInterface
    {
        if (isset($this->blockHandlers[$blockTagName])) {
            return $this->blockHandlers[$blockTagName];
        }

        // Mapping lookup
        if (isset($this->extensionMap['block_handlers'][$blockTagName])) {
            $class = $this->extensionMap['block_handlers'][$blockTagName];
            $this->blockHandlers[$blockTagName] = new $class();
            return $this->blockHandlers[$blockTagName];
        }

        return $this->blockHandlers[$blockTagName] ?? null;
    }

    public function getFunctionHandler(string $functionName): ?FunctionHandlerInterface
    {
        if (isset($this->functionHandlers[$functionName])) {
            return $this->functionHandlers[$functionName];
        }

        // Mapping lookup
        if (isset($this->extensionMap['function_handlers'][$functionName])) {
            $class = $this->extensionMap['function_handlers'][$functionName];
            $this->functionHandlers[$functionName] = new $class();
            return $this->functionHandlers[$functionName];
        }

        return $this->functionHandlers[$functionName] ?? null;
    }

    public function getOutputFilters(): array
    {
        if (isset($_REQUEST['highlight']) || (isset($prefs['feature_referer_highlight']) && $prefs['feature_referer_highlight'] == 'y')) {
            $this->outputFilters[] = new \SmartyTiki\Filter\Output\Highlight();
        }

        if (! empty($prefs['feature_sefurl_filter']) && $prefs['feature_sefurl_filter'] === 'y') {
            $this->outputFilters[] = new \SmartyTiki\Filter\Output\Sefurl();
        }

        return $this->outputFilters;
    }

    public function getPreFilters(): array
    {
        global $prefs;

        $this->preFilters = [
            new \SmartyTiki\Filter\Pre\Tr(),
            new \SmartyTiki\Filter\Pre\Jq()
        ];

        if (! empty($prefs['log_tpl']) && $prefs['log_tpl'] === 'y') {
            $this->preFilters[] = new \SmartyTiki\Filter\Pre\LogTpl();
        }

        return $this->preFilters;
    }
}
