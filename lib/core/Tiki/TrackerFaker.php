<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki;

use Faker\Factory as FakerFactory;
use Faker\Generator;
use Tracker_Definition;
use TikiLib;

/**
 * Orchestrates fake data generation for tracker fields.
 *
 * Owns a configured Faker generator and the field-type-to-formatter map.
 * Commands use this to resolve which faker formatter applies to a field,
 * generate values, and build field data arrays for trackerlib->replace_item.
 */
class TrackerFaker
{
    protected Generator $faker;

    protected array $fieldTypeMap = [
        'e' => 'tikiCategories', // Category
        'c' => 'tikiCheckbox', // Checkbox
        'y' => 'country', // Country Selector
        'b' => ['numberBetween', [0, 10000]], // Currency Field
        'f' => 'unixTime', // Date and Time
        'j' => 'unixTime', // Date and Time
        'd' => ['tikiDropdown', 'fieldId'], // Drop Down
        'D' => ['tikiDropdown', 'fieldId'], // Drop Down with Other
        'R' => ['tikiRadio', 'fieldId'], // Radio Buttons
        'M' => ['tikiMultiselect', 'fieldId'], // Multiselect
        'w' => '', // Dynamic Items List
        'm' => 'email', // Email
        'FG' => ['tikiFiles', 'fieldId'], // Files
        'h' => '', // Header
        'icon' => ['tikiFiles', ['fieldId', true]], // Icon
        'r' => ['tikiItemLink', 'fieldId'], // Item Link
        //'l' => '', // Items List
        'LANG' => 'languageCode', // Language
        'G' => 'tikiLocation', // Location
        'math' => '', // Mathematical Calculation
        'n' => ['numberBetween', [0, 10000]], // Numeric Field
        'k' => 'tikiPageSelector', // Page Selector
        'S' => ['tikiStaticText', 'fieldId'], // Static Text
        'a' => 'text', // Text Area
        't' => ['text', [30]], // Text Field
        'q' => ['tikiUniqueIdentifier', 'fieldId'], // AutoIncrement
        'L' => 'url', // Url
        'u' => ['tikiUserSelector', 'fieldId'], // User Selector
        'g' => 'tikiGroupSelector', // Group Selector
        'wiki' => 'text', // Wiki Page
        //'x' => '', // Action - Not supported
        'articles' => 'tikiArticles', // Articles
        //'C' => '', // Computed Field - not supported
        //'A' => '', // Attachment - deprecated
        'F' => ['words', [3, true]], // Tags
        //'GF' => '', // Geographic Feature
        //'i' => '', // Image - deprecated
        //'N' => '', // In Group
        'I' => 'localIpv4', // IP Selector
        //'kaltura' => '', // Kaltura video
        'p' => '', // Ldap lookup
        'STARS' => ['tikiRating', 'fieldId'], // Rating
        //'*' => '', // Stars (deprecated)
        //'s' => '', // Stars (system - deprecated)
        'REL' => ['tikiRelations', ['fieldId', true, 5]], // Relations
        //'STO' => '', // show.tiki.org - Not supported
        'usergroups' => '', // Display list of user groups
        //'p' => '', // User Preference - Not Supported
        //'U' => '', // User Subscription
        'W' => '', // Webservice
    ];

    public function __construct(bool $reuseFiles = true)
    {
        $this->faker = FakerFactory::create();
        $provider = new Faker($this->faker);
        $provider->setTikiFilesReuseFiles($reuseFiles);
        $this->faker->addProvider($provider);
    }

    /**
     * Determine the faker formatter for a tracker field.
     *
     * Resolution order: explicit overrides (by fieldId or permName),
     * description annotation (~tc~faker:...~/tc~), field type default, fallback.
     *
     * @param array  $field      Tracker field info (must have fieldId, permName, type, description)
     * @param array  $overrides  Map of fieldId|permName => formatter
     * @param string $fallback   Formatter when nothing else matches
     * @return string|array
     */
    public function resolveFakerForField(array $field, array $overrides = [], string $fallback = ''): string|array
    {
        if (isset($overrides[$field['fieldId']])) {
            return $overrides[$field['fieldId']];
        }
        if (isset($overrides[$field['permName']])) {
            return $overrides[$field['permName']];
        }
        if (preg_match('/~tc~faker:(.*)~\/tc~/', $field['description'] ?? '', $matches)) {
            $parts = explode(',', trim($matches[1]));
            $formatter = array_shift($parts);
            return ! empty($parts) ? [$formatter, $parts] : $formatter;
        }
        if (isset($this->fieldTypeMap[$field['type']])) {
            return $this->fieldTypeMap[$field['type']];
        }
        return $fallback;
    }

    /**
     * Generate a single fake value for a tracker field.
     *
     * @param array              $fieldFaker         ['fieldId' => int, 'faker' => string|array]
     * @param Tracker_Definition $trackerDefinition
     * @return mixed
     */
    public function generateFieldValue(array $fieldFaker, Tracker_Definition $trackerDefinition)
    {
        $value = '';

        if (is_array($fieldFaker['faker'])) {
            $fakerAction = $fieldFaker['faker'][0];
            $fakerArguments = $fieldFaker['faker'][1];
            if (! is_array($fakerArguments)) {
                if ($fakerArguments == 'fieldId') {
                    $fakerArguments = $trackerDefinition->getField($fieldFaker['fieldId']);
                }
                $fakerArguments = [$fakerArguments];
            } elseif ($fakerArguments[0] === 'fieldId') {
                $fakerArguments[0] = $trackerDefinition->getField($fieldFaker['fieldId']);
            } elseif (substr($fakerAction, 0, 4) === 'tiki') {
                array_unshift($fakerArguments, $trackerDefinition->getField($fieldFaker['fieldId']));
            }
            $value = call_user_func_array([$this->faker, $fakerAction], $fakerArguments);
        } elseif (! empty($fieldFaker['faker'])) {
            $fakerAction = $fieldFaker['faker'];
            $value = $this->faker->$fakerAction();
        }

        if (isset($value) && is_object($value) && get_class($value) === 'DateTime') {
            $value = $value->format('U');
        }

        return $value;
    }

    /**
     * Generate a fake value and merge it with field info for use with replace_item.
     *
     * @param array              $fieldFaker         ['fieldId' => int, 'faker' => string|array]
     * @param Tracker_Definition $trackerDefinition
     * @return array|null  Field data entry, or null if no value generated
     */
    public function buildFieldData(array $fieldFaker, Tracker_Definition $trackerDefinition): ?array
    {
        $value = $this->generateFieldValue($fieldFaker, $trackerDefinition);

        if (isset($value)) {
            $trackerLib = TikiLib::lib('trk');
            return array_merge(
                $trackerLib->get_field_info($fieldFaker['fieldId']),
                ['value' => $value]
            );
        }
        return null;
    }
}
