<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace SmartyTiki\Utils;

use Kint\Parser\Parser;
use Kint\Parser\PluginBeginInterface;
use Kint\Value\AbstractValue;
use Kint\Value\Context\BaseContext;
use Kint\Value\Context\ContextInterface;

//    https://kint-php.github.io/kint/writing-plugins/
class SmartyKint implements PluginBeginInterface
{
    protected $parser;

    public function setParser(Parser $p): void
    {
        $this->parser = $p;
    }

    public function getTypes(): array
    {
        return ['integer', 'string', 'array', 'object'];
    }

    public function getTriggers(): int
    {
        return Parser::TRIGGER_BEGIN;
    }
    public function parseBegin(&$var, ContextInterface $c): ?AbstractValue
    {
        // TODO: Implement parseBegin() method.
        $accessPath = $c->getAccessPath();
        if ($accessPath !== null) {
            $replace = preg_replace('/.*\[\'([^\']+)\']->value/', '$$1', $accessPath);
            if ($accessPath !== $replace) {
                $base = new BaseContext($replace);
                $base->depth = $c->getDepth();
                $base->access_path = $c->getAccessPath();
                $base->reference = $c->isRef();
                return $this->parser?->parse($var, $base);
            }
        }

        return null;
    }
}
