<?php

// (c) Copyright by authors of the Tiki Wiki/CMS/Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tiki\Command\Application;
use Tiki\Command\UsersPasswordCommand;

class UsersPasswordCommandTest extends TestCase
{
    private const TEST_USER = 'john doe';
    private const TEST_PASSWORD = 'password1234';

    private $commandName = 'users:password';

    public static function setUpBeforeClass(): void
    {
        global $prefs;
        $prefs['feature_user_encryption'] = 'n';
    }

    private function assertPasswordChanged($commandTester)
    {
        $this->assertStringContainsString(UsersPasswordCommand::MSG_PASSWORD_CHANGED, $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    private function getCommandTester($userExists = true, $changeUserPassword = true)
    {
        $mock = $this->createMock(UsersLib::class);
        $mock->method('user_exists')->willReturn($userExists);
        $mock->method('change_user_password')->willReturn($changeUserPassword);

        $app = new Application();
        $app->add(new UsersPasswordCommand($mock));

        $command = $app->find($this->commandName);
        return new CommandTester($command);
    }

    public function setUp(): void
    {
        // reset the env variables
        putenv('USERNAME');
        putenv('PASSWORD');
    }

    public function testExecuteSuccess()
    {

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [$this::TEST_USER, $this::TEST_PASSWORD],
        ]);

        $this->assertPasswordChanged($commandTester);
    }

    public function testExecuteWithEnvPassword()
    {
        putenv('PASSWORD=' . $this::TEST_PASSWORD);
        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [$this::TEST_USER],
        ]);

        $this->assertPasswordChanged($commandTester);
    }

    public function testExecuteWithEnvUsername()
    {
        putenv('USERNAME=' . $this::TEST_USER);
        $commandTester = $this->getCommandTester();

        // The prompt will ask for username which we set to null so that the env variable is used
        $commandTester->setInputs([null]);
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [null, $this::TEST_PASSWORD],
        ]);

        $this->assertPasswordChanged($commandTester);
    }

    public function testPromptForPassword()
    {
        $commandTester = $this->getCommandTester();

        $commandTester->setInputs([$this::TEST_PASSWORD]);
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [$this::TEST_USER],
        ]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(UsersPasswordCommand::MSG_PROMPT_PASSWORD, $output);
        $this->assertPasswordChanged($commandTester);
    }

    public function testPromptForUsername()
    {
        $commandTester = $this->getCommandTester();

        $commandTester->setInputs(['user']);
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [null, $this::TEST_PASSWORD],
        ]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(sprintf(UsersPasswordCommand::MSG_PROMPT_USER, ''), $output);
        $this->assertPasswordChanged($commandTester);
    }

    public function testExecuteWithEnvUsernameAndPassword()
    {
        putenv('USERNAME=' . $this::TEST_USER);
        putenv('PASSWORD=' . $this::TEST_PASSWORD);
        $commandTester = $this->getCommandTester();

        // If the username is not provided as an argument,
        // the prompt will ask for username which we set to null so that the env variable is used
        $commandTester->setInputs([null]);
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [],
        ]);

        $this->assertPasswordChanged($commandTester);
    }

    public function testPromptForUsernameAndPassword()
    {
        $commandTester = $this->getCommandTester();

        $commandTester->setInputs([$this::TEST_USER, $this::TEST_PASSWORD]);
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [],
        ]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(sprintf(UsersPasswordCommand::MSG_PROMPT_USER, ''), $output);
        $this->assertStringContainsString(UsersPasswordCommand::MSG_PROMPT_PASSWORD, $output);
        $this->assertStringContainsString(UsersPasswordCommand::MSG_PASSWORD_CHANGED, $output);
    }

    public function testExecuteWithEmptyUsername()
    {
        $commandTester = $this->getCommandTester();

        // If the username is not provided as an argument,
        // the prompt will ask for username which we set to null to avoid exiting with an error
        $commandTester->setInputs([null]);
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => ['', $this::TEST_PASSWORD],
        ]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(UsersPasswordCommand::MSG_USER_REQUIRED, $output);
    }

    public function testExecuteWithEmptyPassword()
    {
        $commandTester = $this->getCommandTester();

        // If the password is not provided as an argument,
        // the prompt will ask for password which we set to null to avoid exiting with an error
        $commandTester->setInputs([null]);
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [$this::TEST_USER, ''],
        ]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(UsersPasswordCommand::MSG_PASSWORD_REQUIRED, $output);
    }

    public function testExecuteWithWrongUsername()
    {
        $commandTester = $this->getCommandTester(false);

        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [$this::TEST_USER, $this::TEST_PASSWORD],
        ]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(sprintf(UsersPasswordCommand::MSG_USER_DOES_NOT_EXIST, $this::TEST_USER), $output);
    }

    public function testExecuteWithWrongNumberOfArguments()
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [$this::TEST_USER, $this::TEST_PASSWORD, 'extra'],
        ]);
        $output = $commandTester->getDisplay();

        $this->assertStringContainsString(UsersPasswordCommand::MSG_TOO_MANY_ARGUMENTS, $output);
    }

    public function testExecuteWithUserEncryptionEnabledAndNoForceOption()
    {
        global $prefs;
        $prefs['feature_user_encryption'] = 'y';
        $cryptlib = TikiLib::lib('crypt');

        $commandTester = $this->getCommandTester();

        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [$this::TEST_USER, $this::TEST_PASSWORD],
        ]);
        $output = $commandTester->getDisplay();
        if ($cryptlib->getUserCryptDataStats('mcrypt') > 0 || $cryptlib->getUserCryptDataStats('openssl') > 0 || $cryptlib->getUserCryptDataStats('sodium') > 0) {
            $this->assertStringNotContainsString(UsersPasswordCommand::MSG_PASSWORD_CHANGED, $output);
            $this->assertStringContainsString(UsersPasswordCommand::MSG_ENCRYPTION_FT_NOTICE, $output);
            $this->assertSame(1, $commandTester->getStatusCode());
        } else {
            $this->assertStringNotContainsString(UsersPasswordCommand::MSG_ENCRYPTION_FT_NOTICE, $output);
            $this->assertStringContainsString(UsersPasswordCommand::MSG_PASSWORD_CHANGED, $output);
            $this->assertSame(0, $commandTester->getStatusCode());
        }
    }

    public function testExecuteWithUserEncryptionEnabledAndForceOption()
    {
        global $prefs;
        $prefs['feature_user_encryption'] = 'y';

        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'command' => $this->commandName,
            'params' => [$this::TEST_USER, $this::TEST_PASSWORD],
            '--force' => true,
        ]);

        $this->assertPasswordChanged($commandTester);
    }
}
