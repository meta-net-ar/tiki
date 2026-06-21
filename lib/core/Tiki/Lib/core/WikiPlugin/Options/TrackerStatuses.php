<?php

namespace Tiki\Lib\core\WikiPlugin\Options;

enum TrackerStatuses: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    // Keep these cases in sync with TrackerStatusesSets single-case enum cases.
    // They are explicitly repeated in TrackerStatusesSets because
    // PHP 8.1 requires enum case values to be compile-time constants.
    case Open    = 'o';
    case Pending = 'p';
    case Closed  = 'c';
    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Open => tr('Open'),
            self::Pending => tr('Pending'),
            self::Closed => tr('Closed'),
        };
    }
}
