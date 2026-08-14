<?php

namespace Tiki\Lib\core\WikiPlugin\Options;

enum SortDirections: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Ascending = 'asc';
    case Descending = 'desc';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Ascending => tr('Ascending'),
            self::Descending => tr('Descending'),
        };
    }
}
