<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * @group unit
 */
namespace Tiki\Lib\Test\Core\TikiFilter;

use TikiTestCase;

class LangTest extends TikiTestCase
{
    public function testFilterAcceptsOnlyExistingLanguageDirectoryNames(): void
    {
        $filter = \TikiFilter::get('lang');

        $this->assertSame('en', $filter->filter('en'));
        $this->assertSame('fy-NL', $filter->filter('fy-NL'));
        $this->assertSame('', $filter->filter('../lang/en'));
        $this->assertSame('', $filter->filter('en/../../en'));
        $this->assertSame('', $filter->filter('en.php'));
        $this->assertSame('', $filter->filter(''));
    }
}
