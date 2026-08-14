<?php

namespace Tiki\Lib\core\WikiPlugin\Options;

enum TrackerStatusesSets: string implements PluginOptionsInterface
{
    use HasEnumLabelsAndOptionsTrait;

    // PHP 8.1 requires enum case values to be compile-time constants.
    // Using dynamic or non-literal values will fail the phplint check,
    // which is why each value is explicitly repeated here.
    // Single-case values (Open, Pending, Closed) directly reflect TrackerStatuses->cases().
    case Open    = 'o';
    case Pending = 'p';
    case Closed  = 'c';
    // Combinations
    case OpenPending       = 'op';
    case OpenClosed        = 'oc';
    case PendingClosed     = 'pc';
    case OpenPendingClosed = 'opc';

    public static function getLabel(PluginOptionsInterface $value, string $labelSet): string
    {
        return match ($value) {
            self::Open              => TrackerStatuses::Open->label($labelSet),
            self::Pending           => TrackerStatuses::Pending->label($labelSet),
            self::Closed            => TrackerStatuses::Closed->label($labelSet),
            self::OpenPending       => tr('Open & Pending'),
            self::OpenClosed        => tr('Open & Closed'),
            self::PendingClosed     => tr('Pending & Closed'),
            self::OpenPendingClosed => tr('Open, Pending & Closed'),
        };
    }
}
