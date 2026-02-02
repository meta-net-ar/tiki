<?php

namespace Tiki\Lib\core\WikiPlugin\Options;

enum Boolean: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case True = 'true';
    case False = 'false';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::False => tr('No'),
            self::True => tr('Yes'),
        };
    }
}
