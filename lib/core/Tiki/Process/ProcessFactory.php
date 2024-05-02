<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

declare(strict_types=1);

namespace Tiki\Process;

use RuntimeException;

/**
 * Wrapper class to create processes
 *
 * Allowing to inject this class, and use it in the code will simplify
 * testing vs create the Process Objects directly
 */
class ProcessFactory
{
    /**
     * @param string|array   $commandline The command line to run
     * @param string|null    $cwd         The working directory or null to use the working dir of the current PHP process
     * @param array|null     $env         The environment variables or null to use the same environment as the current PHP process
     * @param mixed|null     $input       The input as stream resource, scalar or \Traversable, or null for no input
     * @param int|float|null $timeout     The timeout in seconds or null to disable
     * @param array|null     $options     An array of options for proc_open
     *
     * @returns Process
     *
     * @throws RuntimeException When proc_open is not installed
     */
    public function create($commandline, string|null $cwd = null, array|null $env = null, $input = null, $timeout = 60, array|null $options = null): Process
    {
        return new Process($commandline, $cwd, $env, $input, $timeout, $options);
    }
}
