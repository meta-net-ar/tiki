<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Composer;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\ConsoleOutput;
use Tiki\Lib\IconGenerator;
use Composer\Script\Event;

class GenerateIcons
{
    public static function run(Event $event)
    {
        $output = new ConsoleOutput();
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require $vendorDir . '/autoload.php';
        try {
            $generator = new IconGenerator($output);
                $generator->execute();
                $output->writeln("<info>Done!</info>");
                return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("<error>" . $e->getMessage() . "</error>");
            return Command::FAILURE;
        }
    }
}
