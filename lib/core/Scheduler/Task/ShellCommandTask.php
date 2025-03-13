<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Process\Process;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class Scheduler_Task_ShellCommandTask extends Scheduler_Task_CommandTask
{
    public function execute($params = null)
    {
        global $prefs;
        if ($prefs['scheduler_shell_command'] !== 'y') {
            $this->errorMessage = tra('Shell command execution is disabled.');
            return false;
        }

        if (empty($params['shell_command'])) {
            $this->errorMessage = tra('Missing shell command to execute.');
            return false;
        }

        $command = $params['shell_command'];

        $this->logger->debug(sprintf(tra('Executing shell command: %s'), $command));
        $process = Process::fromShellCommandline($command);
        if (! empty($params['timeout'])) {
            $process->setTimeout($params['timeout']);
            $process->setIdleTimeout($params['timeout']);
        }
        try {
            $process->run(
                function ($type, $message) {
                    if ($type != 'err') {
                        $this->output->write($message);
                    }
                }
            );
        } catch (ProcessTimedOutException $e) {
            $this->errorMessage = $e->getMessage();
            return false;
        }

        if ($success = $process->isSuccessful()) {
            $this->errorMessage = $process->getOutput();
        } else {
            $this->errorMessage = $process->getErrorOutput();
        }

        return $success;
    }

    public function getParams()
    {
        return [
            'shell_command' => [
                'name' => tra('Shell command'),
                'type' => 'textarea',
                'required' => true,
            ],
            'timeout' => [
                'name' => tra('Run timeout') . '<br>(' . tra('in seconds') . ')',
                'type' => 'text',
                'required' => false,
            ],
        ];
    }
}
