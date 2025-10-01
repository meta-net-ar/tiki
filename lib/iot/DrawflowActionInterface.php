<?php

declare(strict_types=1);

namespace Tiki\Lib\iot;

interface DrawflowActionInterface
{
    /**
     * Get the display name of the node
     */
    public function getName(): string;
    /**
     * Get the node description
     */
    public function getDescription(): string;

    /**
     * Get the node type (category)
     */
    public function getType(): DrawflowNodeType;

    /**
     * Generate HTML template for the node
     * @param array<string, mixed> $config Node configuration data
     */
    public function getTemplate(array $config): string;

    /**
     * Execute the node's logic
     * @param array<string, mixed> $input Flow input data
     * @param mixed|null $user_input User-provided values
     * @return array<string, mixed>|bool Execution result
     */
    public function execute(mixed $input, mixed $user_input): bool|array;
}
