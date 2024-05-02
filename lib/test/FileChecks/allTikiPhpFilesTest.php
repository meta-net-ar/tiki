<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\test\FileChecks;

use PHPUnit\Framework\TestCase;
use Tiki\Lib\test\TestHelpers\GlobRecursiveHelper;

class allTikiPhpFilesTest extends TestCase
{
    private $phpFiles;

    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->phpFiles = (new GlobRecursiveHelper('*.php'))->process();
    }

    public function testOutputBeforePhpTags(): void
    {
        foreach ($this->phpFiles as $fileName) {
            if (strpos($fileName, TIKI_CUSTOMIZATIONS_SRC_PATH) === 0) {
                // not a tiki file
                continue;
            }
            $handle = fopen($fileName, 'r');
            $fileContent = '';
            $count = 0;
            do {
                $buffer = fgets($handle);
                if (! $count && strpos($buffer, '#!') !== 0) {
                    $fileContent .= $buffer;
                    if (stripos($buffer, '<?php') !== false) { // match several different comment styles
                        $this->assertDoesNotMatchRegularExpression('/([\S\s]+)<\?php/iU', $fileContent, $fileName . ' does not start with <?php');
                        break;
                    }
                }
                $count++;
            } while ($count < 3 && $buffer); // search through up to 3 lines of code (no results increasing that)
            fclose($handle);
        }
    }
}
