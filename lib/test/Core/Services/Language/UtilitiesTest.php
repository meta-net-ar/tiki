<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\Services\Language;

use Services_Exception;
use Services_Language_Utilities;
use TikiTestCase;

class UtilitiesTest extends TikiTestCase
{
    private Services_Language_Utilities $utilities;
    private string $customFile;
    private bool $hadCustomFile = false;
    private string $previousCustomContents = '';

    protected function setUp(): void
    {
        $this->utilities = new Services_Language_Utilities();
        $this->customFile = 'lang/en/custom.php';
        $this->hadCustomFile = file_exists($this->customFile);
        if ($this->hadCustomFile) {
            $this->previousCustomContents = file_get_contents($this->customFile);
        }
    }

    protected function tearDown(): void
    {
        if ($this->hadCustomFile) {
            file_put_contents($this->customFile, $this->previousCustomContents);
        } elseif (file_exists($this->customFile)) {
            unlink($this->customFile);
        }
    }

    public function testRejectsPathTraversalLanguage(): void
    {
        $this->expectException(Services_Exception::class);
        $this->utilities->assertValidLanguage('../temp');
    }

    public function testRejectsPathTraversalOnWrite(): void
    {
        $this->expectException(Services_Exception::class);
        $this->utilities->writeCustomPhpTranslations('../temp', [
            'k' => '\");echo \'PWNED\';?>',
        ]);
    }

    public function testEscapesBackslashQuoteInjection(): void
    {
        $payload = '\");echo \'PWNED-CUSTOM-PHP\';?>';
        $this->utilities->writeCustomPhpTranslations('en', [
            'hello' => $payload,
        ]);

        $contents = file_get_contents($this->customFile);
        // Escaped form inside the double-quoted PHP value is \\\");echo (three backslashes then quote)
        $this->assertStringContainsString('\\\\\\");echo', $contents);

        // Including the file must restore the literal payload, not execute injected PHP
        $lang = [];
        $lang_custom = null;
        ob_start();
        include $this->customFile;
        $stdout = ob_get_clean();

        $this->assertSame('', $stdout);
        $this->assertIsArray($lang_custom);
        $this->assertSame($payload, $lang_custom['hello']);
        $this->assertSame($payload, $lang['hello']);
    }
}
