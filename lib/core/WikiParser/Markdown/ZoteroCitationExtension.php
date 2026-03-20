<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\WikiParser\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Extension\ExtensionInterface;
use Tiki\WikiParser\Markdown\Node\Citation;
use Tiki\WikiParser\Markdown\Parser\CitationParser;
use Tiki\WikiParser\Markdown\Renderer\CitationRenderer;

/**
 * Extension to support {@citekey} citation syntax for Pandoc rendering
 */
class ZoteroCitationExtension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addInlineParser(new CitationParser())
            ->addRenderer(Citation::class, new CitationRenderer());
    }
}
