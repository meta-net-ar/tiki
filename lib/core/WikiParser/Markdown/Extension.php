<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiParser\Markdown;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\TaskList\TaskListItemMarker;
use League\CommonMark\Extension\TaskList\TaskListItemMarkerParser;
use Tiki\WikiParser\Markdown\Renderer\TaskListItemMarkerRenderer;

class Extension implements ExtensionInterface
{
    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment
            ->addBlockStartParser(new Parser\HeadingAutonumberingCollapsibleParser(), 70)
            ->addRenderer(Node\CollapsibleHeading::class, new Renderer\CollapsibleHeadingRenderer(), 0)
            ->addRenderer(Node\CollapsibleLink::class, new Renderer\CollapsibleLinkRenderer(), 0)
            ->addRenderer(Node\CollapsibleContainer::class, new Renderer\CollapsibleContainerRenderer(), 0)
            ->addRenderer(FencedCode::class, new Renderer\FencedCodeRenderer(), 10)
        ;
        $environment->addEventListener(DocumentParsedEvent::class, new CollapsibleHeadingProcessor(), -100);

        $environment->addInlineParser(new TaskListItemMarkerParser(), 35);
        $environment->addRenderer(TaskListItemMarker::class, new TaskListItemMarkerRenderer());
    }
}
