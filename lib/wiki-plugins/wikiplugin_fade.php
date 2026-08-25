<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_fade_info()
{
    return [
        'name' => tra('Fade'),
        'documentation' => 'PluginFade',
        'description' => tra('Create a link that shows/hides initially hidden content'),
        'prefs' => ['wikiplugin_fade'],
        'body' => tra('Content of the hideable zone (in Wiki syntax)'),
        'filter' => 'wikicontent',
        'format' => 'html',
        'iconname' => 'wizard',
        'introduced' => 3,
        'tags' => [ 'basic' ],
        'params' => [
            'summary' => [
                'required' => false,
                'name' => tra('Summary'),
                'filter' => 'wikicontent',
                'description' => tra('Optional visible summary that stays shown above the collapsible details (Wiki syntax). It is displayed under the label as a subtitle; if no label is set, it becomes the heading of the block.'),
                'default' => '',
                'since' => '30.2',
            ],
            'expanded' => [
                'required' => false,
                'name' => tra('Expanded'),
                'filter' => 'alpha',
                'description' => tra('Start with details shown (y) or hidden (n). If not set, the site preference for the Fade plugin applies.'),
                'default' => '',
                'since' => '30.2',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'fade_group' => [
                'required' => false,
                'name' => tra('Fade Group'),
                'filter' => 'text',
                'description' => tr(
                    'Optional group name (letters, digits, underscore and hyphen) to be used with PluginButton params
                    such as %0_fade_group%1 and %0_fade_action%1.',
                    '<code>',
                    '</code>'
                ),
                'default' => '',
                'since' => '30.2',
            ],
            'label' => [
                'required' => true,
                'name' => tra('Label'),
                'filter' => 'text',
                'description' => tra('Label for link that shows and hides the content when clicked'),
                'default' => tra('Unspecified label'),
                'since' => '3.0',
            ],
            'icon' => [
                'required' => false,
                'name' => tra('Icon'),
                'filter' => 'alpha',
                'description' => tra('Arrow icon showing that content can be hidden or shown.'),
                'default' => '',
                'since' => '7.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'show_speed' => [
                'required' => false,
                'name' => tra('Show Speed'),
                'filter' => 'alnum',
                'description' => tr('Speed of animation in milliseconds when showing content (%0200%1 is fast and
                    %0600%1 is slow. %01000%1 equals 1 second).', '<code>', '</code>'),
                'default' => 400,
                'since' => '7.0',
                'accepted' => tr(
                    'Integer greater than 0 and less than or equal to 1000, or %0 or %1',
                    '<code>fast</code>',
                    '<code>slow</code>'
                ),
                'advanced' => true,
            ],
            'hide_speed' => [
                'required' => false,
                'name' => tra('Hide Speed'),
                'filter' => 'alnum',
                'description' => tr('Speed of animation in milliseconds when hiding content (%0200%1 is fast and
                    %0600%1 is slow. %01000%1 equals 1 second).', '<code>', '</code>'),
                'default' => 400,
                'since' => '7.0',
                'accepted' => tr(
                    'Integer greater than 0 and less than or equal to 1000, or %0 or %1',
                    '<code>fast</code>',
                    '<code>slow</code>'
                ),
                'advanced' => true,
            ],
             'bootstrap' => [
                'required' => false,
                'name' => tra('Use Bootstrap'),
                'description' => tra('Use Bootstrap collapsible box'),
                'since' => '16.0',
                'filter' => 'alpha',
                'default' => 'n',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n']
                ]
             ],
            'class' => [
                'required' => false,
                'name' => tra('CSS Class'),
                'description' => tra('Apply custom CSS class.'),
                'since' => '16.0',
                'filter' => 'text',
                'default' => '',
            ],
        ]
    ];
}

function wikiplugin_fade($body, $params)
{
    static $id = 0;

    $params['show_speed'] = validate_speed($params['show_speed']);
    $params['hide_speed'] = validate_speed($params['hide_speed']);

    $unique = 'wpfade-' . ++$id;
    $unique_link = $unique . '-link';

    $headerlib = TikiLib::lib('header');
    $headerlib->add_js_module('import "@tiki/plugins/fade";');

    $body = trim($body);
    $body = TikiLib::lib('parser')->parse_data($body);

    $groupNorm = wikiplugin_fade_normalize_group($params['fade_group'] ?? '');
    $isExpanded = wikiplugin_fade_resolve_expanded($params);

    $summaryRaw = trim((string) ($params['summary'] ?? ''));
    $hasSummary = $summaryRaw !== '';
    $summaryHtml = '';
    if ($hasSummary) {
        $summaryHtml = TikiLib::lib('parser')->parse_data($summaryRaw);
    }
    $dataAttrs = wikiplugin_fade_format_data_attrs($unique, $groupNorm, $params);
    $useBootstrap = ($params['bootstrap'] ?? 'n') === 'y';

    $defaultLabel = tra('Unspecified label');
    $userLabelRaw = (string) ($params['label'] ?? '');
    $userSetCustomLabel = trim($userLabelRaw) !== '' && $userLabelRaw !== $defaultLabel;
    $toggleEsc = htmlspecialchars((string) $params['label'], ENT_QUOTES, 'UTF-8');
    $fadeSummaryAriaLabel = htmlspecialchars(tra('Show or hide details'), ENT_QUOTES, 'UTF-8');
    $fadeSummaryAriaAttr = $hasSummary ? ' aria-label="' . $fadeSummaryAriaLabel . '"' : '';

    if ($params['icon'] == 'y') {
        $span_class = 'wpfade-span-icon';
        $div_class = 'wpfade-div-icon';
    } else {
        $span_class = 'wpfade-span-plain';
        $div_class = 'wpfade-div-plain';
    }

    $ae = $isExpanded ? 'true' : 'false';

    if ($useBootstrap) {
        $unique_outer = $unique . '-outer';
        $unique_inner = $unique . '-inner';
        $collapseClass = 'collapse' . ($isExpanded ? ' show' : '');

        // icon unset keeps the historical right-hand chevron, icon=y switches to the left arrow
        // used by the legacy mode, icon=n removes it altogether.
        $iconParam = (string) ($params['icon'] ?? '');
        $showLeftArrow = $iconParam === 'y';
        $showChevron = $iconParam === '';

        $wrapperClass = 'wikiplugin-fade card ' . trim($params['class'] ?? '');
        if ($showLeftArrow) {
            $wrapperClass .= ' wikiplugin-fade--icon';
        }
        if ($hasSummary) {
            $wrapperClass .= ' wikiplugin-fade--summary';
            if ($userSetCustomLabel) {
                $wrapperClass .= ' wikiplugin-fade--summary-labeled';
            }
        }
        if ($isExpanded) {
            $wrapperClass .= ' wikiplugin-fade--initial-open';
        }
        $wrapperClass = trim($wrapperClass);

        if ($hasSummary && $userSetCustomLabel) {
            $headerInner = '<div class="wikiplugin-fade__summary-stack flex-grow-1 min-w-0">'
                . '<div class="wikiplugin-fade__label-text">' . $toggleEsc . '</div>'
                . '<div class="wikiplugin-fade__summary-text small text-muted">' . $summaryHtml . '</div>'
                . '</div>';
        } elseif ($hasSummary) {
            $headerInner = '<div class="wikiplugin-fade__summary-text flex-grow-1 me-2">' . $summaryHtml . '</div>';
        } else {
            $headerInner = '<span class="flex-grow-1 me-2">' . $toggleEsc . '</span>';
        }

        if ($showChevron) {
            $headerInner .= '<span class="wikiplugin-fade__chevron icon fas fa-chevron-down fa-fw" aria-hidden="true"></span>';
        }

        return '<div id="' . htmlspecialchars($unique_outer, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') . '"'
            . $dataAttrs
            . '><div class="card-header wikiplugin-fade__header p-0">'
                . '<a data-bs-toggle="collapse" class="d-flex align-items-start w-100 text-decoration-none text-body px-3 py-2" role="button" href="#' . htmlspecialchars($unique_inner, ENT_QUOTES, 'UTF-8') . '" aria-expanded="' . $ae . '" aria-controls="' . htmlspecialchars($unique_inner, ENT_QUOTES, 'UTF-8') . '"' . $fadeSummaryAriaAttr . '>'
                . $headerInner
                . '</a>'
            . '</div>'
                . '<div id="' . htmlspecialchars($unique_inner, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($collapseClass, ENT_QUOTES, 'UTF-8') . '">'
                  . '<div class="card-body">' . $body . '</div>'
                . '</div>'
              . '</div>';
    }

    $outerClass = 'wikiplugin-fade';
    if ($hasSummary) {
        $outerClass .= ' wikiplugin-fade--summary';
        if ($userSetCustomLabel) {
            $outerClass .= ' wikiplugin-fade--summary-labeled';
        }
    }
    if ($isExpanded) {
        $outerClass .= ' wikiplugin-fade--initial-open';
    }
    if (trim($params['class'] ?? '') !== '') {
        $outerClass .= ' ' . trim($params['class']);
    }

    $linkClasses = 'wpfade-legacy-toggle';
    if ($params['icon'] == 'y') {
        $linkClasses .= $isExpanded ? ' wpfade-shown' : ' wpfade-hidden';
    }

    if ($hasSummary && $userSetCustomLabel) {
        $legacyLinkInner = '<span class="wikiplugin-fade__summary-stack">'
            . '<span class="wikiplugin-fade__label-text d-block">' . $toggleEsc . '</span>'
            . '<span class="wikiplugin-fade__summary-text small text-muted d-block">' . $summaryHtml . '</span>'
            . '</span>';
    } elseif ($hasSummary) {
        $legacyLinkInner = '<span class="wikiplugin-fade__summary-text d-block">' . $summaryHtml . '</span>';
    } else {
        $legacyLinkInner = $toggleEsc;
    }

    return '<div class="' . htmlspecialchars($outerClass, ENT_QUOTES, 'UTF-8') . '"' . $dataAttrs . '>'
        . '<span class="' . htmlspecialchars($span_class, ENT_QUOTES, 'UTF-8') . ' wikiplugin-fade__legacy-header">' . "\r\t\t"
        . '<a id="' . htmlspecialchars($unique_link, ENT_QUOTES, 'UTF-8') . '" href="#" class="' . htmlspecialchars($linkClasses, ENT_QUOTES, 'UTF-8') . '" aria-expanded="' . $ae . '"' . $fadeSummaryAriaAttr . '>' . "\r\t\t\t"
        . $legacyLinkInner . "\r\t\t" . '</a>' . "\r\t" . '</span>' . "\r\t"
        . '<div id="' . htmlspecialchars($unique, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($div_class, ENT_QUOTES, 'UTF-8') . '">' . "\r\t\t\t"
        . $body . "\r\t" . '</div>' . "\r" . '</div>' . "\r";
}

function wikiplugin_fade_normalize_group($group)
{
    $group = trim((string) $group);
    if ($group === '') {
        return '';
    }
    $group = preg_replace('/[^a-zA-Z0-9_-]+/', '', $group);

    return substr($group, 0, 120);
}

function wikiplugin_fade_resolve_expanded(array $params)
{
    global $prefs;
    $ex = trim((string) ($params['expanded'] ?? ''));
    if ($ex === 'y') {
        return true;
    }
    if ($ex === 'n') {
        return false;
    }

    return ! empty($prefs['wikiplugin_fade_default_expanded']) && $prefs['wikiplugin_fade_default_expanded'] === 'y';
}

function wikiplugin_fade_format_data_attrs($fadeId, $group, array $params)
{
    $out = ' data-fade-id="' . htmlspecialchars($fadeId, ENT_QUOTES, 'UTF-8') . '"';
    if ($group !== '') {
        $out .= ' data-fade-group="' . htmlspecialchars($group, ENT_QUOTES, 'UTF-8') . '"';
    }
    $out .= ' data-fade-show-speed="' . htmlspecialchars((string) $params['show_speed'], ENT_QUOTES, 'UTF-8') . '"';
    $out .= ' data-fade-hide-speed="' . htmlspecialchars((string) $params['hide_speed'], ENT_QUOTES, 'UTF-8') . '"';

    return $out;
}

function validate_speed($speed_param)
{
    if (
        ! (($speed_param > 0 && $speed_param <= 1000)
        || $speed_param == 'fast' || $speed_param == 'slow')
    ) {
            $speed_param = 400;
    }
    return $speed_param;
}
