<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TaskQueue\Tasks;

/**
 * Interface for all queued tasks
 */
interface QueuedTaskInterface
{
    /**
     * Execute the task
     * @return mixed The result of the task execution
     */
    public function execute(): mixed;

    /**
     * Get the task type identifier
     * @return string The task type
     */
    public function getType(): string;

    /**
     * Get the task parameters
     * @return array The task parameters
     */
    public function getParams(): array;

    /**
     * Set the task parameters
     * @param array $params The task parameters
     */
    public function setParams(array $params): void;

    /**
     * Get the task ID
     * @return int|null The task ID
     */
    public function getId(): ?int;

    /**
     * Set the task ID
     * @param int|null $id The task ID
     */
    public function setId(?int $id): void;

    /**
     * Get the task status
     * @return string|null The task status
     */
    public function getStatus(): ?string;

    /**
     * Set the task status
     * @param string|null $status The task status
     */
    public function setStatus(?string $status): void;
}
