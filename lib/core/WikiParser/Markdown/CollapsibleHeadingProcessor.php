<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\WikiParser\Markdown;

use Exception;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Node\NodeIterator;
use Tiki\WikiParser\Markdown\Node\CollapsibleHeading;
use Tiki\WikiParser\Markdown\Node\CollapsibleContainer;
use Tiki\WikiParser\Markdown\Node\CollapsibleLink;

/**
 * Searches the Document for CollapsibleHeading elements, adds a [+]/[-] link and
 *  surrounds the next block element with special collapsible div
 */
class CollapsibleHeadingProcessor
{
    /**
     * @throws Exception
     */
    public function __invoke(DocumentParsedEvent $e): void
    {
        global $page;
        static $i = 0;

        foreach ($e->getDocument()->iterator(NodeIterator::FLAG_BLOCKS_ONLY) as $node) {
            if ($node instanceof CollapsibleHeading) {
                $i++;
                $id = 'id' . preg_replace('/[^a-zA-z0-9]/', '', urlencode($page)) . 'heading' . $i;
                $this->wrapNext($node, $id);
                $this->addHeadingLink($node, $id);
            }
        }
    }

    private function addHeadingLink(CollapsibleHeading $heading, string $id): void
    {
        $link = new CollapsibleLink($id, $heading->getOpen());
        $heading->insertAfter($link);
    }

    private function wrapNext(CollapsibleHeading $heading, string $id): void
    {
        $container = new CollapsibleContainer($id, $heading->getOpen());
        $next = $heading->next();
        if ($next) {
            $next->replaceWith($container);
            $container->appendChild($next);
        } else {
            $heading->insertAfter($container);
        }
    }
}
