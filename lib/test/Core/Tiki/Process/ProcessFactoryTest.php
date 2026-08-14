<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Test\Tiki\Process;

use Tiki\Process\ProcessFactory;
use PHPUnit\Framework\TestCase;

class ProcessFactoryTest extends TestCase
{
    public function testCreate()
    {
        $processFactory = new ProcessFactory();
        $process = $processFactory->create(['echo', 'Hello_World']);

        $this->assertInstanceOf('\Tiki\Process\Process', $process);

        // account for possible escape of command line
        $this->assertMatchesRegularExpression('/.?echo.? .?Hello_World.?/', $process->getCommandLine());
    }
}
