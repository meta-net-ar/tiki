<?php

namespace Tiki\Lib\core\WikiPlugin\Options;

interface PluginOptionsInterface
{
    public const LABELSET_DEFAULT = 'default';
    /**
     * Get the label for the enum case.
     *
     * @return string  the translated display text for the Enum case.
     */
    public function label(string $labelSet): string;

    /**
     * Generate an array of options with value, label, and description for select inputs.
     *
     * @param string|null $emptyOptionLabel Optional label for an empty-value option
     * @param string      $labelSet
     *
     * @return array
     */
    public static function options(?string $emptyOptionLabel = null, string $labelSet = self::LABELSET_DEFAULT): array;

    /**
     * List all the labels sets.
     *
     * @return string the set of labels you can choose from.
     */
    public static function getLabel(self $value, string $labelSet): string;
}
