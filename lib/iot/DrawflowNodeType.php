<?php

declare(strict_types=1);

namespace Tiki\Lib\iot;

enum DrawflowNodeType: string
{
    case TEMPLATE = 'template';
    case USER_INPUT = 'user_input';
    case USER_CHOICE = 'user_choice';
    case HARDWARE_IO = 'hardware_io';

    /**
     * Get human-readable label for the node type
     */
    public function label(): string
    {
        return match ($this) {
            self::TEMPLATE => 'Template Node',
            self::USER_INPUT => 'User Input',
            self::USER_CHOICE => 'User Choice',
            self::HARDWARE_IO => 'Hardware I/O',
        };
    }

    /**
     * Convert label to enum instance
     * @param string $label Human-readable node type label
     */
    public static function tryFromLabel(string $label): ?self
    {
        return match (strtolower($label)) {
            'template node' => self::TEMPLATE,
            'user input' => self::USER_INPUT,
            'user choice' => self::USER_CHOICE,
            'hardware i/o' => self::HARDWARE_IO,
            default => null,
        };
    }
}
