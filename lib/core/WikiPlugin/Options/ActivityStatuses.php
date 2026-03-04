<?php

namespace Tiki\Lib\core\WikiPlugin\Options;

enum ActivityStatuses: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    case Added = 'a';
    case AddedViewed = 'a:v';
    case Viewed = 'v';
    case ViewedAdded = 'v:a';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Added => tr('Added'),
            self::AddedViewed => tr('Added and Viewed'),
            self::Viewed => tr('Viewed'),
            self::ViewedAdded => tr('Viewed and Added'),
        };
    }
}
