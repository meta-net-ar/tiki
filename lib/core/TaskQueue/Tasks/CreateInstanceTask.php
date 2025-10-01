<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TaskQueue\Tasks;

use Exception;
use Services_Manager_Trait;
use Symfony\Component\Console\Input\ArrayInput;
use Tiki\TaskQueue\Exception\QueueManagerException;
use TikiManager\Application\Instance;
use TikiManager\Command\ApplyProfileCommand;
use TikiManager\Command\ConsoleInstanceCommand;
use TikiManager\Command\CreateInstanceCommand;

class CreateInstanceTask extends QueuedAbstractTask
{
    use Services_Manager_Trait;

    /**
     * Loads the environment.
     */
    public function loadEnv()
    {
        $this->loadManagerEnv(false);
        $this->setManagerOutput();
    }

    /**
     * Executes the task.
     * @return mixed The result of the task execution.
     */
    public function execute(): mixed
    {
        try {
            $this->loadEnv();
            $cmd = new CreateInstanceCommand();
            $params = $this->getParams();
            $inputCommand = new ArrayInput($params['params']);
            $lastInstanceId = Instance::getLastInstance()->id;
            $this->runCommand($cmd, $inputCommand);
            $output1 = '';
            $output2 = '';
            if (empty($params['leavepassword']) && $params['instance_type'] !== 'blank') {
                $output1 = $this->manager_output->fetch();
                $instanceURL = $params['params']['--url'];
                $info = "[OK] Please test your site at " . $instanceURL;
                if (str_contains($output1, $info)) {
                    $instance = Instance::getLastInstance();
                    $command = "users:password admin " . $params['tikipassword'];
                    $cmd = new ConsoleInstanceCommand();
                    $inputCmd = new ArrayInput([
                        'command' => $cmd->getName(),
                        '-i' => $instance->getId(),
                        '-c' => $command,
                    ]);
                    $this->runCommand($cmd, $inputCmd);
                    $output2 = $this->manager_output->fetch();
                }
            }
            $this->manager_output->write($output1 . "\n" . $output2);
            $newInstanceId = Instance::getLastInstance()->id;
            if (! empty($params['profile']) && $lastInstanceId != $newInstanceId) {
                $this->applyProfile($newInstanceId, $params['profile'], $params['repository']);
            }
            $result = $this->manager_output->fetch();
            return $result;
        } catch (Exception $e) {
            throw new QueueManagerException($e->getMessage());
        }
    }

    /**
     * Apply a profile to an instance.
     *
     * @param int $instanceId The ID of the instance.
     * @param string $profile The profile to apply.
     * @param string $repository The repository to use.
     * @return void
     */
    private function applyProfile($instanceId, $profile, $repository)
    {
        $cmd = new ApplyProfileCommand();
        $inputCmd = new ArrayInput([
            'command' => $cmd->getName(),
            '-i' => $instanceId,
            '-p' => $profile,
            '-r' => $repository,
        ]);
        try {
            $this->runCommand($cmd, $inputCmd);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
