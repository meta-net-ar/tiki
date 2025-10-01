<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Symfony\Component\Console\Input\ArgvInput;

class Scheduler_Task_ConsoleCommandTask extends Scheduler_Task_CommandTask
{
    public function execute($params = null)
    {
        if (empty($params['console_command'])) {
            $this->errorMessage = tra('Missing parameters to run the command.');
            return false;
        }

        $this->logger->debug(sprintf(tra('Executing console command: %s'), $params['console_command']));

        $consoleParams = 'console.php ' . $params['console_command'];
        $args = $this->parseConsoleParams($consoleParams);

        $commandName = $args[1];

        try {
            $consoleBuilder = new Tiki\Command\ConsoleApplicationBuilder();
            $console = $consoleBuilder->create();

            $command = $console->find($commandName);

            $input = new ArgvInput($args);
            $input->setInteractive(false);

            // switch context for the user as the command does not run through console.php which handles this switch
            // note that automatic switch back to the original user is happening on context object desctruction
            if ($asUser = $input->getParameterOption('--as-user')) {
                if (TikiLib::lib('user')->user_exists($asUser)) {
                    $permissionContext = new Perms_Context($asUser);
                }
            }

            $statusCode = $command->run($input, $this->output);

            $content = $this->output->fetch();
            $this->errorMessage = $content;

            return $statusCode === 0;
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();

            return false;
        }
    }

    private function parseConsoleParams($params)
    {

        preg_match_all('/(?<=^|\s)([\'"]?)(.+?)(?<!\\\\)\1(?=$|\s)/', $params, $args);

        return $args[2];
    }

    public function getParams()
    {
        return [
            'console_command' => [
                'name' => tra('Console command'),
                'type' => 'textarea',
                'required' => true,
            ],
        ];
    }
}
