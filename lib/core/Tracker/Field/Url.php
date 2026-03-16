<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * Handler class for url fields:
 *
 * - url key ~L~
 */
class Tracker_Field_Url extends \Tracker\Field\AbstractItemField implements \Tracker\Field\SynchronizableInterface, \Tracker\Field\ExportableInterface
{
    public static function getManagedTypesInfo(): array
    {
        return [
            'L' => [
                'name' => tr('URL'),
                'description' => tr('Create a link to a specified URL.'),
                'help' => 'URL-Tracker-Field',
                'prefs' => ['trackerfield_url'],
                'tags' => ['basic'],
                'default' => 'y',
                'supported_changes' => ['d', 'D', 'R', 'M', 'm', 't', 'a', 'L'],
                'params' => [
                    'linkToURL' => [
                        'name' => tr('Display'),
                        'description' => tr('How the URL should be rendered'),
                        'filter' => 'int',
                        'options' => [
                            0 => tr('URL as link'),
                            1 => tr('Plain text'),
                            2 => tr('Site title as link'),
                            3 => tr('URL as link plus site title'),
                            4 => tr('Text as link (see Other)'),
                        ],
                        'legacy_index' => 0,
                        'default' => 0,
                    ],
                    'other' => [
                        'name' => tr('Other'),
                        'description' => tr('Label of the link text. Requires "Display" to be set to "Text as link"'),
                        'filter' => 'text',
                        'default' => '',
                    ],
                    'labelasplaceholder' => [
                        'name' => tr('Use label as placeholder'),
                        'description' => tr('Display the field name as a placeholder in the input field instead of separate label.'),
                        'deprecated' => false,
                        'filter' => 'int',
                        'default' => 0,
                        'options' => [
                            0 => tr('No'),
                            1 => tr('Yes'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getFieldData(array $requestData = []): array
    {
        $ins_id = $this->getInsertId();

        return [
            'value' => (isset($requestData[$ins_id]))
                ? $requestData[$ins_id]
                : $this->getValue(),
        ];
    }

    public function renderOutput($context = [])
    {
        $smarty = TikiLib::lib('smarty');
        $rawValue = (string) $this->getConfiguration('value');
        $parsedWikiLink = self::extractParsedWikiLinkData(trim($rawValue));

        $url = self::normalizeStoredValueToUrl($rawValue);

        if ($url === '' || ($context['list_mode'] ?? '') === 'csv' || $this->getOption('linkToURL') == 1) {
            return $url;
        } elseif ($this->getOption('linkToURL') == 2) { // Site title as link
            return smarty_function_object_link(
                [
                    'type' => 'external',
                    'id' => $url,
                ],
                $smarty->getEmptyInternalTemplate()
            );
        } elseif ($this->getOption('linkToURL') == 0) { // URL as link
            return smarty_function_object_link(
                [
                    'type' => 'external',
                    'id' => $url,
                    'title' => $parsedWikiLink['label'] ?? $url,
                ],
                $smarty->getEmptyInternalTemplate()
            );
        } elseif ($this->getOption('linkToURL') == 3) { // URL + site title
            return smarty_function_object_link(
                [
                    'type' => 'external_extended',
                    'id' => $url,
                ],
                $smarty->getEmptyInternalTemplate()
            );
        } elseif ($this->getOption('linkToURL') == 4) { // URL as link
            return smarty_function_object_link(
                [
                    'type' => 'external',
                    'id' => $url,
                    'title' => tr($this->getOption('other')),
                ],
                $smarty->getEmptyInternalTemplate()
            );
        } else {
            return $url;
        }
    }

    protected static function isWikiSyntaxLink(string $value): bool
    {
        return (str_starts_with($value, '((') && str_ends_with($value, '))'))
            || (str_starts_with($value, '[') && str_ends_with($value, ']'));
    }

    public function isValid($ins_fields_data)
    {
        $fieldId = $this->getFieldId();
        $value = $ins_fields_data[$fieldId]['value'] ?? $this->getValue();
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return true;
        }

        if (self::isWikiSyntaxLink($trimmed)) {
            $resolvedHref = self::extractParsedWikiLinkData($trimmed)['href'] ?? null;
            if ($resolvedHref === null) {
                return tr('Invalid wiki syntax. The link target could not be resolved.');
            }
            if (! self::isSyntacticallyValidUrl($resolvedHref)) {
                return tr('Invalid wiki syntax. The resolved link target "%0" is not a valid URL.', $resolvedHref);
            }
            // Non-blocking warning for internal non existing yet wiki page target
            $target = self::extractWikiLinkTarget($trimmed);
            if ($target !== null && ! self::looksLikeExternalUrl($target) && ! TikiLib::lib('tiki')->page_exists($target)) {
                Feedback::warning(tr('Warning: Target wiki page "%0" does not exist yet.', $target));
            }
            return true;
        }

        if (! self::isSyntacticallyValidUrl($trimmed)) {
            return tr('Invalid URL syntax.');
        }

        return true;
    }

    public function renderInput($context = [])
    {
        $templateData = [
            'wikiSyntaxInfo' => tr('You can also use complete wiki-link syntax: ((PageName)) or [url|text].'),
        ];

        return $this->renderTemplate("trackerinput/url.tpl", $context, $templateData);
    }

    public function importRemote($value)
    {
        return $value;
    }

    public function exportRemote($value)
    {
        return $value;
    }

    public function importRemoteField(array $info, array $syncInfo)
    {
        return $info;
    }

    public function getTabularSchema()
    {
        $schema = new Tracker\Tabular\Schema($this->getTrackerDefinition());

        $permName = $this->getConfiguration('permName');
        $name = $this->getConfiguration('name');

        $schema->addNew($permName, 'default')
            ->setLabel($name)
            ->setRenderTransform(function ($value) {
                return $value;
            })
            ->setParseIntoTransform(function (&$info, $value) use ($permName) {
                $info['fields'][$permName] = $value;
            });

        return $schema;
    }

    // Keep wiki-syntax handling intentionally limited to full-value wrappers like ((PageName)) or [url|text].
    // For full wiki parsing consistency (escaping, multilingual behavior, shared parsing path),
    // consider refactoring URL to inherit Tracker_Field_Text.
    protected static function normalizeStoredValueToUrl(string $value): string
    {
        $trimmed = trim($value);

        if (! self::isWikiSyntaxLink($trimmed)) {
            return $value;
        }

        $resolvedHref = self::extractParsedWikiLinkData($trimmed)['href'] ?? null;
        // Normalization keeps only the resolved target URL.
        // The optional wiki-link label is extracted separately for rendering.
        return $resolvedHref ?? $value;
    }

    protected static function extractParsedWikiLinkData(string $value): ?array
    {
        $parsed = TikiLib::lib('parser')->parse_data_simple($value);

        if (! preg_match('/<a\b[^>]*\bhref=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is', $parsed, $matches)) {
            return null;
        }

        $label = trim(html_entity_decode(strip_tags($matches[3]), ENT_QUOTES, 'UTF-8'));

        return [
            'href' => html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8'),
            'label' => $label !== '' ? $label : null,
        ];
    }

    protected static function isSyntacticallyValidUrl(string $url): bool
    {
        if ($url === '') {
            return true;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }

        if (str_starts_with($url, '/')) {
            return ! preg_match('/\s/', $url);
        }

        $parsed = parse_url($url);
        if ($parsed === false) {
            return false;
        }

        if (isset($parsed['scheme'])) {
            return (bool) preg_match('/^[a-z][a-z0-9+.-]*$/i', $parsed['scheme']) && ! preg_match('/\s/', $url);
        }

        return ! preg_match('/\s/', $url);
    }

    protected static function extractWikiLinkTarget(string $value): ?string
    {
        if (str_starts_with($value, '((') && str_ends_with($value, '))')) {
            $inside = trim(substr($value, 2, -2));
            if ($inside === '') {
                return null;
            }

            $parts = preg_split('/[|#]/', $inside, 2);
            return trim($parts[0] ?? '');
        }

        if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
            $inside = trim(substr($value, 1, -1));
            if ($inside === '') {
                return null;
            }

            $parts = explode('|', $inside, 2);
            return trim($parts[0]);
        }

        return null;
    }

    protected static function looksLikeExternalUrl(string $value): bool
    {
        return (bool) preg_match('/^(https?:\/\/|ftp:\/\/|mailto:|news:)/i', $value);
    }
}
