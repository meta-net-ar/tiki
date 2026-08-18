<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Services;

use TikiTestCase;

require_once TIKI_PATH . 'lib/core/Services/Language/Utilities.php';

class LanguageUtilitiesTest extends TikiTestCase
{
    public function testGetLanguageDirectoryRejectsPathTraversalLanguage(): void
    {
        $this->expectException(\Services_Exception::class);

        (new \Services_Language_Utilities())->getLanguageDirectory('../lang/en');
    }

    public function testGetLanguageDirectoryAcceptsLocaleDirectoryName(): void
    {
        global $tikidomain;

        $oldTikidomain = $tikidomain ?? null;
        $tikidomain = '';

        try {
            $this->assertSame(
                'lang/fy-NL/',
                (new \Services_Language_Utilities())->getLanguageDirectory('fy-NL')
            );
        } finally {
            if ($oldTikidomain === null) {
                unset($tikidomain);
            } else {
                $tikidomain = $oldTikidomain;
            }
        }
    }
}
