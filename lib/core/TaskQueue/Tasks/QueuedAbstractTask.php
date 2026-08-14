<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TaskQueue\Tasks;

use Exception;
use ReflectionClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\TaskQueue\Exception\QueueManagerException;

/**
 * Abstract class representing a queued task.
 */
abstract class QueuedAbstractTask implements QueuedTaskInterface
{
    protected array $params;
    protected BufferedOutput $output;
    protected ?int $id = null;
    protected ?string $type = null;
    protected ?string $status = null;
    protected ?string $result = null;
    protected ?string $createdAt = null;
    protected ?string $startedAt = null;
    protected ?string $endedAt = null;

    /**
     * Constructor for QueuedTask.
     *
     * @param array $params Parameters for the task.
     * @param int|null $id Unique task ID.
     * @param string|null $type Task type.
     * @param string|null $status Task status (e.g., Pending, Completed).
     * @param string|null $result Task result or output.
     * @param string|null $createdAt Creation timestamp of the task.
     * @param string|null $startedAt Start timestamp of the task.
     * @param string|null $endedAt End timestamp of the task.
     */
    public function __construct(
        array $params,
        ?int $id = null,
        ?string $type = null,
        ?string $status = null,
        ?string $result = null,
        ?string $createdAt = null,
        ?string $startedAt = null,
        ?string $endedAt = null
    ) {
        $this->setParams($params);
        $this->setId($id);
        $this->setType($type);
        $this->setStatus($status);
        $this->setResult($result);
        $this->setCreatedAt($createdAt);
        $this->setStartedAt($startedAt);
        $this->setEndedAt($endedAt);
        $this->setBufferedOutput();
    }

    /**
     * Executes the task.
     * @return mixed The result of the task execution.
     */
    abstract public function execute(): mixed;

    /**
     * Get the output of the task.
     * @return BufferedOutput The output of the task.
     */
    public function getBufferedOutput(): BufferedOutput
    {
        return $this->output;
    }

    /**
     * Set the output to be buffered.
     * @return void
     */
    public function setBufferedOutput(): void
    {
        $this->output = new BufferedOutput(OutputInterface::VERBOSITY_VERBOSE);
    }

    /**
     * Extracts and sets the type from the class name.
     * Enforces that the class implements QueuedTaskInterface.
     * @return void
     */
    public function setType(?string $type = null): void
    {
        if ($type) {
            $this->type = $type;
            return;
        }

        // Check if this class implements the QueuedTaskInterface
        if (! $this instanceof QueuedTaskInterface) {
            throw new QueueManagerException(
                tr("QueuedTask type %0 is not valid.", static::class)
            );
        }

        $className = (new ReflectionClass(static::class))->getShortName();
        $this->type = $className;
    }

    /**
     * Get the type of the task.
     * @return string The type of the task.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get the parameters for the task.
     * @return array The parameters for the task.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set the parameters for the task.
     * @param array $params The parameters for the task.
     * @return void
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     * Get the unique task ID.
     *
     * @return int The unique task ID.
     */
    public function getId(): int
    {
        if (empty($this->id)) {
            throw new QueueManagerException(tr('Task ID is not set.'));
        }
        return $this->id;
    }

    /**
     * Set the unique task ID.
     *
     * @param int $id The unique task ID.
     * @return void
     */
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    /**
     * Get the task status.
     *
     * @return string|null The task status.
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

     /**
     * Set the task status.
     *
     * @param string|null $status The task status.
     * @return void
     */
    public function setStatus(?string $status): void
    {
        $this->status = $status;
    }

    /**
     * Get the task result.
     *
     * @return string|null The task result.
     */
    public function getResult(): ?string
    {
        return $this->result;
    }

    /**
     * Set the task result.
     *
     * @param string|null $result The task result.
     * @return void
     */
    public function setResult(?string $result): void
    {
        $this->result = $result;
    }

    /**
     * Get the creation timestamp.
     *
     * @return string|null The creation timestamp.
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Set the creation timestamp.
     *
     * @param string|null $createdAt The creation timestamp.
     * @return void
     */
    public function setCreatedAt(?string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    /**
     * Get the start timestamp.
     *
     * @return string|null The start timestamp.
     */
    public function getStartedAt(): ?string
    {
        return $this->startedAt;
    }

    /**
     * Set the start timestamp.
     *
     * @param string|null $startedAt The start timestamp.
     * @return void
     */
    public function setStartedAt(?string $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    /**
     * Get the end timestamp.
     *
     * @return string|null The end timestamp.
     */
    public function getEndedAt(): ?string
    {
        return $this->endedAt;
    }

    /**
     * Set the end timestamp.
     *
     * @param string|null $endedAt The end timestamp.
     * @return void
     */
    public function setEndedAt(?string $endedAt): void
    {
        $this->endedAt = $endedAt;
    }

    /**
     * Convert the object into an associative array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'status' => $this->getStatus(),
            'result' => $this->getResult(),
            'created_at' => $this->getCreatedAt(),
            'started_at' => $this->getStartedAt(),
            'ended_at' => $this->getEndedAt(),
        ];
    }

    /**
     * Run a command.
     *
     * @param mixed $cmd The command to run.
     * @param ArrayInput|null $input The input for the command.
     * @return void
     */
    public function runCommandForQueuedTasks($cmd, $input = null)
    {
        try {
            $cwd = getcwd();
            if (! $input) {
                $input = new ArrayInput([
                    'command' => $cmd->getName(),
                ]);
            }
            $input->setInteractive(false);
            $app = new Application();
            $app->add($cmd);
            $app->setAutoExit(false);
            $bufferedOutput = $this->getBufferedOutput();
            $app->run($input, $bufferedOutput);
            // some TM commands might change current working dir
            chdir($cwd);
            $output = $bufferedOutput->fetch();
            $bufferedOutput->write($output);
        } catch (Exception $e) {
            throw new QueueManagerException($e->getMessage());
        }
    }
}
