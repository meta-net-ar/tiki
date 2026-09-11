<?php

namespace Tiki\Lib\core\WikiPlugin\Options;

enum BooleanInteger: int implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Yes = 1;
    case No = 0;
    public const LABELSET_SWITCH = 'switch';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($labelSet) {
            self::LABELSET_SWITCH  => match ($value) {
                self::No => tr('Off'),
                self::Yes => tr('On'),
            },
            self::LABELSET_DEFAULT => match ($value) {
                self::No => tr('No'),
                self::Yes => tr('Yes'),
            },
        };
    }
}
