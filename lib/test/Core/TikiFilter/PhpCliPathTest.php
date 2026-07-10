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

class PhpCliPathTest extends TikiTestCase
{
    public function testFilterRejectsPathsThatAreNotAPhpCliBinary(): void
    {
        $filter = \TikiFilter::get('phpclipath');

        $this->assertSame('', $filter->filter(''));
        $this->assertSame('', $filter->filter('/nonexistent/php'));
        $this->assertSame('', $filter->filter('/bin/sh'));
        $this->assertSame('', $filter->filter(__FILE__));
    }

    public function testFilterAcceptsARealPhpCliBinary(): void
    {
        if (! preg_match('/^php([0-9]+(\.[0-9]+)?)?(-cli)?(\.exe)?$/i', basename(PHP_BINARY))) {
            $this->markTestSkipped('PHP_BINARY does not follow the PHP CLI naming convention: ' . PHP_BINARY);
        }

        $filter = \TikiFilter::get('phpclipath');

        $this->assertSame(PHP_BINARY, $filter->filter(PHP_BINARY));
    }
}
