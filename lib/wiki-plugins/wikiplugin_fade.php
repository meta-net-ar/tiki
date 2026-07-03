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
                'description' => tra('Optional visible summary above the collapsible details (Wiki syntax). When set, a generic toggle label is used unless you set label explicitly.'),
                'default' => '',
                'since' => '30.0',
            ],
            'expanded' => [
                'required' => false,
                'name' => tra('Expanded'),
                'filter' => 'alpha',
                'description' => tra('Start with details shown (y) or hidden (n). If not set, the site preference for the Fade plugin applies.'),
                'default' => '',
                'since' => '30.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 'y'],
                    ['text' => tra('No'), 'value' => 'n'],
                ],
            ],
            'group' => [
                'required' => false,
                'name' => tra('Group'),
                'filter' => 'text',
                'description' => tra('Optional group name (letters, digits, underscore and hyphen) for future tools such as expand all.'),
                'default' => '',
                'since' => '30.0',
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

function wikiplugin_fade($body, $params, $offset = 0, $context = null)
{
    global $prefs;

    static $id = 0;
    static $fadeAssetsAdded = false;

    $params['show_speed'] = validate_speed($params['show_speed']);
    $params['hide_speed'] = validate_speed($params['hide_speed']);

    $unique = 'wpfade-' . ++$id;
    $unique_link = $unique . '-link';

    $headerlib = TikiLib::lib('header');
    if (! $fadeAssetsAdded) {
        $fadeAssetsAdded = true;
        $headerlib->add_jsfile('lib/jquery_tiki/wikiplugin-fade.js', true);
    }

    $body = trim($body);
    $body = TikiLib::lib('parser')->parse_data($body);

    $groupNorm = wikiplugin_fade_normalize_group($params['group'] ?? '');
    $isExpanded = wikiplugin_fade_resolve_expanded($params);

    $summaryRaw = trim((string) ($params['summary'] ?? ''));
    $hasSummary = $summaryRaw !== '';
    $summaryHtml = '';
    if ($hasSummary) {
        $summaryHtml = TikiLib::lib('parser')->parse_data($summaryRaw);
    }
    $dataAttrs = wikiplugin_fade_format_data_attrs($unique, $groupNorm, $params, $hasSummary);
    $useBootstrap = $hasSummary || (($params['bootstrap'] ?? 'n') === 'y');

    $defaultLabel = tra('Unspecified label');
    $toggleText = (string) $params['label'];
    if ($hasSummary && ($toggleText === $defaultLabel || trim($toggleText) === '')) {
        $toggleText = tra('Details');
    }

    $userLabelRaw = (string) ($params['label'] ?? '');
    $userSetCustomLabel = trim($userLabelRaw) !== '' && $userLabelRaw !== $defaultLabel;
    $toggleEsc = htmlspecialchars($toggleText, ENT_QUOTES, 'UTF-8');
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

        if ($hasSummary) {
            $wrapperClass = 'wikiplugin-fade wikiplugin-fade--inline ' . trim($params['class'] ?? '');
            $wrapperClass .= ' wikiplugin-fade--summary';
            if ($userSetCustomLabel) {
                $wrapperClass .= ' wikiplugin-fade--summary-labeled';
            }
            if ($isExpanded) {
                $wrapperClass .= ' wikiplugin-fade--initial-open';
            }
            $wrapperClass = trim($wrapperClass);

            if ($userSetCustomLabel) {
                $headerInner = '<div class="wikiplugin-fade__summary-stack flex-grow-1 min-w-0">'
                    . '<div class="wikiplugin-fade__summary-text">' . $summaryHtml . '</div>'
                    . '<span class="wikiplugin-fade__extra-label small text-muted">'
                    . htmlspecialchars($userLabelRaw, ENT_QUOTES, 'UTF-8')
                    . '</span>'
                    . '</div>';
            } else {
                $headerInner = '<div class="wikiplugin-fade__summary-text flex-grow-1 me-2">' . $summaryHtml . '</div>';
            }

            return '<div id="' . htmlspecialchars($unique_outer, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') . '"'
                . $dataAttrs
                . '><div class="wikiplugin-fade__summary card-header p-0">'
                    . '<a data-bs-toggle="collapse" class="d-flex align-items-start w-100 text-decoration-none py-1" role="button" href="#' . htmlspecialchars($unique_inner, ENT_QUOTES, 'UTF-8') . '" aria-expanded="' . $ae . '" aria-controls="' . htmlspecialchars($unique_inner, ENT_QUOTES, 'UTF-8') . '"' . $fadeSummaryAriaAttr . '>'
                    . $headerInner
                    . '</a>'
                . '</div>'
                    . '<div id="' . htmlspecialchars($unique_inner, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($collapseClass, ENT_QUOTES, 'UTF-8') . '">'
                      . '<div class="wikiplugin-fade__content">' . $body . '</div>'
                    . '</div>'
                  . '</div>';
        }

        $headerInner = '<span class="flex-grow-1 me-2">' . $toggleEsc . '</span>';
        $wrapperClass = 'wikiplugin-fade card ' . trim($params['class'] ?? '');
        if ($isExpanded) {
            $wrapperClass .= ' wikiplugin-fade--initial-open';
        }
        $wrapperClass = trim($wrapperClass);

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

    if ($hasSummary) {
        $legacyLinkInner = '<span class="wikiplugin-fade__summary-text d-block">' . $summaryHtml . '</span>';
        if ($userSetCustomLabel) {
            $legacyLinkInner .= '<span class="wikiplugin-fade__extra-label small text-muted d-block">' . htmlspecialchars($userLabelRaw, ENT_QUOTES, 'UTF-8') . '</span>';
        }
    } else {
        $legacyLinkInner = $toggleEsc;
    }

    // Inline display beats global .wpfade-div-plain { display: none } when theme SCSS override is missing from the bundle.
    $legacyBodyStyle = $isExpanded ? ' style="display: block !important;"' : '';

    return '<div class="' . htmlspecialchars($outerClass, ENT_QUOTES, 'UTF-8') . '"' . $dataAttrs . '>'
        . '<span class="' . htmlspecialchars($span_class, ENT_QUOTES, 'UTF-8') . ' wikiplugin-fade__legacy-header">' . "\r\t\t"
        . '<a id="' . htmlspecialchars($unique_link, ENT_QUOTES, 'UTF-8') . '" href="#" class="' . htmlspecialchars($linkClasses, ENT_QUOTES, 'UTF-8') . '" aria-expanded="' . $ae . '"' . $fadeSummaryAriaAttr . '>' . "\r\t\t\t"
        . $legacyLinkInner . "\r\t\t" . '</a>' . "\r\t" . '</span>' . "\r\t"
        . '<div id="' . htmlspecialchars($unique, ENT_QUOTES, 'UTF-8') . '" class="' . htmlspecialchars($div_class, ENT_QUOTES, 'UTF-8') . '"' . $legacyBodyStyle . '>' . "\r\t\t\t"
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

function wikiplugin_fade_format_data_attrs($fadeId, $group, array $params, $hasSummary)
{
    global $prefs;
    $mobile = ! empty($prefs['wikiplugin_fade_mobile_expand']) && $prefs['wikiplugin_fade_mobile_expand'] === 'y';

    $out = ' data-fade-id="' . htmlspecialchars($fadeId, ENT_QUOTES, 'UTF-8') . '"';
    $out .= ' data-fade-mobile-pref="' . ($mobile ? 'y' : 'n') . '"';
    $out .= ' data-has-summary="' . ($hasSummary ? '1' : '0') . '"';
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
