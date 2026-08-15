<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\WikiParser\Markdown\Node;

use League\CommonMark\Node\Inline\AbstractInline;

/**
 * AST node representing a citation
 */
class Citation extends AbstractInline
{
    private array $citations;

    public function __construct(array $citations)
    {
        parent::__construct();
        $this->citations = $citations;
    }

    public function getCitations(): array
    {
        return $this->citations;
    }
}
