<?php

namespace Tiki\Lib\core\WikiPlugin\Options;

trait HasEnumLabelsAndOptionsTrait
{
    public function label(string $labelSet): string
    {
        return self::getLabel($this, $labelSet);
    }
    /**
     * Generate an array of options with value and label for select inputs.
     *
     * @param string|null $emptyOptionLabel Optional label for an empty-value option
     *                                       If provided, an option with an empty value will be
     *                                       added to the beginning of the options array.
     * @param string $labelSet               An enum type can have multiple labels
     *                                       the labelSet is the key that tells the system
     *                                       which specific label to use.
     *
     * @return array
     */
    public static function options(?string $emptyOptionLabel = null, string $labelSet = self::LABELSET_DEFAULT): array
    {
        $options = array_map(fn ($case) => [
            'value' => $case->value,
            'text' => $case->label($labelSet),
        ], self::cases());

        if ($emptyOptionLabel !== null) {
            return array_merge(
                [
                    [
                        'value' => '',
                        'text' => $emptyOptionLabel,
                    ],
                ],
                $options,
            );
        }

        return $options;
    }
}
