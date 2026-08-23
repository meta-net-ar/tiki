<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Question\Question;
use Tiki\Installer\Installer;

/**
 * @param $installer
 */
function upgrade_20260823_configure_admin_user_tiki(Installer $installer)
{
    if (TikiLib::lib('user')->getDefaultAdminLogin() !== 'admin') {
        return;
    }

    // exit if not running in CLI mode
    if (! isset($installer->commandLineInput) || ! isset($installer->commandLineOutput)) {
        return;
    }

    // exit if not running in interactive mode (basically when running from 'doc/devtools/check_schema_upgrade.php')
    if (! $installer->commandLineInput->isInteractive()) {
        return;
    }

    $installer->commandLineOutput->writeln('<info>Since Tiki 31 the default admin username is no longer supported. A custom username must be set.</info>');

    $question = new Question('Please enter a new username for the admin user: ');
    $question->setValidator(function ($answer) {
        if (empty($answer)) {
            throw new \RuntimeException('The username cannot be empty.');
        }

        if ($answer === 'admin') {
            throw new \RuntimeException('The username "admin" is no longer supported. Please choose a different username.');
        }
        return $answer;
    });

    $questionHelper = new QuestionHelper();
    $newAdminUsername = $questionHelper->ask($installer->commandLineInput, $installer->commandLineOutput, $question);

    TikiLib::lib('user')->change_login('admin', $newAdminUsername, true);
}
