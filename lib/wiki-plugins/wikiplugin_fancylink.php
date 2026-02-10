<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_fancylink_info()
{
    return [
        'name' => tr('Fancy Link'),
        'documentation' => 'PluginFancyLink',
        'description' => tr('Display a rich preview of an external URL'),
        'prefs' => ['wikiplugin_fancylink'],
        'iconname' => 'link',
        'introduced' => 29,
        'tags' => ['basic'],
        'params' => [
            'url' => [
                'required' => true,
                'name' => tr('URL'),
                'description' => tr('URL to preview'),
                'filter' => 'url',
                'default' => '',
            ],
            'showimage' => [
                'required' => false,
                'name' => tr('Show Image'),
                'description' => tr('Display the URL image'),
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tr('Yes'), 'value' => 'y'],
                    ['text' => tr('No'), 'value' => 'n'],
                ],
            ],
            'showdesc' => [
                'required' => false,
                'name' => tr('Show Description'),
                'description' => tr('Display the URL description'),
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tr('Yes'), 'value' => 'y'],
                    ['text' => tr('No'), 'value' => 'n'],
                ],
            ],
            'preview' => [
                'required' => false,
                'name' => tr('Preview'),
                'description' => tr('Display rich preview instead of a simple link'),
                'filter' => 'alpha',
                'default' => 'y',
                'options' => [
                    ['text' => tr('Yes'), 'value' => 'y'],
                    ['text' => tr('No'), 'value' => 'n'],
                ],
            ],
        ],
    ];
}

/**
 * Fancy Link plugin implementation
 *
 * @param string $data Plugin content
 * @param array $params Plugin parameters
 * @return string HTML output
 */
function wikiplugin_fancylink($data, $params)
{
    global $prefs;

    // Check if plugin is enabled
    if ($prefs['wikiplugin_fancylink'] != 'y') {
        return $data;
    }

    // Load FancyLink defaults (theme-friendly sizing)
    if (method_exists('TikiLib', 'lib')) {
        TikiLib::lib('header')->add_cssfile(THEMES_BASE_FILES_FEATURE_CSS_PATH . '/wikiplugin-fancylink.css');
    }

    // Check URL
    if (empty($params['url'])) {
        return tr('URL parameter is required for the Fancy Link plugin.');
    }

    $url = $params['url'];
    $showImg = isset($params['showimage']) ? $params['showimage'] == 'y' : true;
    $showDesc = isset($params['showdesc']) ? $params['showdesc'] == 'y' : true;
    $shouldPreview = isset($params['preview']) ? $params['preview'] == 'y' : true;

    // Cache time - 1 day
    $cachetime = 86400;

    // If preview disabled, render a simple link
    if (! $shouldPreview) {
        $linkText = trim((string) $data) !== '' ? $data : $url;
        return '<a href="' . htmlspecialchars($url) . '">' . htmlspecialchars($linkText) . '</a>';
    }

    // Get metadata
    $metadata = extractUrlMetadata($url, $cachetime);

    if (! $metadata) {
        // Simple error message without conditionals
        return tr('Could not get info from URL: %0', $url);
    }

    // If body content exists, render as link with popover
    $bodyContent = trim((string) $data);
    if (! empty($bodyContent)) {
        return generateLinkWithPopover($url, $bodyContent, $metadata, $showImg, $showDesc);
    }

    // No body content, show preview card
    return generatePreview($metadata, $showImg, $showDesc);
}

/**
 * Generate link with popover preview
 *
 * @param string $url URL
 * @param string $linkText Link text
 * @param array $metadata URL metadata
 * @param bool $showImg Show image
 * @param bool $showDesc Show description
 * @return string HTML
 */
function generateLinkWithPopover($url, $linkText, $metadata, $showImg, $showDesc)
{
    $popoverContent = generatePopoverContent($metadata, $showImg, $showDesc);
    $uniqueId = 'fancylink-' . uniqid();

    $html = '<a href="' . htmlspecialchars($url) . '" ';
    $html .= 'id="' . $uniqueId . '" ';
    $html .= 'class="fancylink-popover-trigger" ';
    $html .= 'tabindex="0">';
    $html .= htmlspecialchars($linkText);
    $html .= '</a>';

    if (method_exists('TikiLib', 'lib')) {
        $headerlib = TikiLib::lib('header');
        $js = '$(function() {';
        $js .= 'var $el = $("#' . $uniqueId . '");';
        $js .= 'if (!$el.data("bs.popover")) {';
        $js .= 'var content = ' . json_encode($popoverContent) . ';';
        $js .= '$el.popover({';
        $js .= 'html: true,';
        $js .= 'content: content,';
        $js .= 'trigger: "hover focus",';
        $js .= 'placement: function(context, source) {';
        $js .= 'if (typeof $.tikiPopoverWhereToPlace === "function") {';
        $js .= 'return $.tikiPopoverWhereToPlace(context, source);';
        $js .= '}';
        $js .= 'return "auto";';
        $js .= '},';
        $js .= 'boundary: "window",';
        $js .= 'container: "body",';
        $js .= 'customClass: "fancylink-popover-wrapper"';
        $js .= '});';
        $js .= '}';
        $js .= '});';
        $headerlib->add_jq_onready($js);
    }

    return $html;
}

/**
 * Generate popover content HTML
 *
 * @param array $metadata URL info
 * @param bool $showImg Show image
 * @param bool $showDesc Show description
 * @return string HTML
 */
function generatePopoverContent($metadata, $showImg, $showDesc)
{
    $url = $metadata['url'];
    $title = $metadata['title'];
    $desc = $metadata['description'];
    $img = $metadata['image'];
    $site = $metadata['site_name'];

    if (strlen($desc) > 150) {
        $desc = substr($desc, 0, 147) . '...';
    }

    $html = '<div class="fancylink-popover">';
    $html .= '<a href="' . htmlspecialchars($url) . '" class="fancylink-popover-link">';

    if ($showImg && $img) {
        $html .= '<div class="fancylink-popover-img mb-2">';
        $html .= '<img src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($title) . '" class="img-fluid rounded fancylink-popover-image">';
        $html .= '</div>';
    }

    $html .= '<div class="fancylink-popover-content">';
    $html .= '<h6 class="mb-1 fw-bold">' . htmlspecialchars($title) . '</h6>';

    if ($showDesc && $desc) {
        $html .= '<p class="text-muted small mb-1">' . htmlspecialchars($desc) . '</p>';
    }

    if ($site) {
        $html .= '<p class="text-muted small mb-0"><small>' . htmlspecialchars($site) . '</small></p>';
    }

    $html .= '</div>';
    $html .= '</a>';
    $html .= '</div>';

    return $html;
}

/**
 * Make compact preview HTML
 *
 * @param array $metadata URL info
 * @param bool $showImg Show image
 * @param bool $showDesc Show description
 * @return string HTML
 */
function generatePreview($metadata, $showImg, $showDesc)
{
    $url = $metadata['url'];
    $title = $metadata['title'];
    $desc = $metadata['description'];
    $img = $metadata['image'];
    $site = $metadata['site_name'];

    // Shorten desc
    if (strlen($desc) > 150) {
        $desc = substr($desc, 0, 147) . '...';
    }

    // Make HTML
    $html = '<div class="card fancylink-card overflow-hidden position-relative">';
    $html .= '<div class="row g-0 align-items-center">';

    // Image part
    if ($showImg && $img) {
        $html .= '<div class="col-auto">';
        $html .= '<div class="fancylink-thumb">';
        $html .= '<img class="fancylink-img" src="' . htmlspecialchars($img) . '" alt="' . htmlspecialchars($title) . '">';
        $html .= '</div>';
        $html .= '</div>';
    }

    // Content part
    $html .= '<div class="col fancylink-content ms-3">';
    $html .= '<div class="card-body py-2 px-3">';
    $html .= '<h6 class="card-title mb-1 text-truncate">' . htmlspecialchars($title) . '</h6>';

    if ($showDesc && $desc) {
        $html .= '<p class="card-text text-muted small mb-1 fancylink-desc">' . htmlspecialchars($desc) . '</p>';
    }

    if ($site) {
        $html .= '<p class="card-text"><small class="text-muted">' . htmlspecialchars($site) . '</small></p>';
    }


    $html .= '<a href="' . htmlspecialchars($url) . '" class="stretched-link"></a>';

    $html .= '</div>';
    $html .= '</div>';

    $html .= '</div>';
    $html .= '</div>';

    return $html;
}
/**
 * Normalize internal Tiki URLs to absolute URLs
 *
 * @param string $url URL to normalize
 * @return string Normalized absolute URL or original URL if not internal
 */
function normalizeInternalUrl($url)
{
    global $base_url;
    if (empty($base_url)) {
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
        $scriptPath = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $base_url = $scheme . '://' . $host . $scriptPath;
        $base_url = rtrim($base_url, '/');
    }

    $url = trim($url);
    $originalUrl = $url;

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }

    if (
        ! preg_match('#^https?://#', $url) &&
        strpos($url, '/') === false &&
        strpos($url, '?') === false &&
        strpos($url, '&') === false &&
        strpos($url, '=') === false &&
        strpos($url, '.php') === false
    ) {
        $url = 'tiki-index.php?page=' . urlencode($url);
    }

    if (preg_match('#^tiki-index\.php(\?|$)#', $url)) {
        $url = $base_url . '/' . $url;
        return $url;
    }

    // Check if it's a relative URL starting with tiki-*.php
    if (preg_match('#^tiki-[^/]+\.php#', $url)) {
        // Make it absolute
        $url = $base_url . '/' . $url;
        return $url;
    }

    if (strpos($url, '/') === 0) {
        $parsedBase = parse_url($base_url);
        $scheme = $parsedBase['scheme'] ?? 'http';
        $host = $parsedBase['host'] ?? '';
        $port = isset($parsedBase['port']) ? ':' . $parsedBase['port'] : '';
        return $scheme . '://' . $host . $port . $url;
    }

    return $originalUrl;
}

/**
 * Extract metadata from a URL
 *
 * @param string $url URL to extract metadata from
 * @param int $cacheTime Cache time in seconds
 * @return array|false Metadata array or false on failure
 */
function extractUrlMetadata($url, $cacheTime = 86400)
{
    // Prevent empty or null URLs
    if (empty($url) || ! is_string($url)) {
        return false;
    }

    // Normalize internal URLs first (before validation)
    $url = normalizeInternalUrl($url);

    // Clean URL
    $url = trim($url);
    $url = preg_replace('/^[^a-z0-9]+/i', '', $url);
    $url = filter_var($url, FILTER_SANITIZE_URL);

    // Parse the URL to get path and query
    $parsedUrl = parse_url($url);
    $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
    $query = [];
    if (isset($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $query);
    }

    $pageType = '';
    $pageId = '';
    $pageTitle = '';
    $defaultImage = 'img/tiki/tikilogo.png'; // Default Tiki logo for all internal content

    if (strpos($path, 'tiki-view_sheets.php') !== false && isset($query['sheetId'])) {
        $pageType = 'Spreadsheet';
        $pageId = $query['sheetId'];
        $pageTitle = 'Spreadsheet #' . $pageId;

        // Try to get actual sheet title if possible
        global $tikilib;
        if (method_exists('TikiLib', 'lib') && ($sheetlib = TikiLib::lib('sheet'))) {
            if (method_exists($sheetlib, 'get_sheet_info')) {
                $sheetInfo = $sheetlib->get_sheet_info($pageId);
                if (isset($sheetInfo['title'])) {
                    $pageTitle = $sheetInfo['title'];
                }
            }
        }
    } elseif (strpos($path, 'tiki-index.php') !== false && isset($query['page'])) {
        $pageType = 'Wiki Page';
        $pageId = $query['page'];
        $pageTitle = $pageId;

        // Try to get the first image from the wiki page if possible
        global $tikilib;
        if (method_exists('TikiLib', 'lib') && ($wikilib = TikiLib::lib('wiki'))) {
            if (method_exists($wikilib, 'get_page_info')) {
                $pageInfo = $wikilib->get_page_info($pageId);
                if (isset($pageInfo['description'])) {
                    $description = $pageInfo['description'];
                }
            }
        }
    } elseif (strpos($path, 'tiki-view_tracker_item.php') !== false && isset($query['itemId'])) {
        $pageType = 'Tracker Item';
        $pageId = $query['itemId'];
        $pageTitle = 'Tracker Item #' . $pageId;

        // Only try to get the actual tracker item title for the current Tiki instance
        // For external Tiki URLs, we'll rely on the HTML metadata
        $parsedUrl = parse_url($url);
        $currentHost = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $urlHost = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';

        // Extract trackerId from URL query parameters if available
        $trackerId = null;
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
            if (isset($queryParams['trackerId'])) {
                $trackerId = $queryParams['trackerId'];
            }
        }

        // Only use Tiki libraries for URLs on the same host
        if ($urlHost === $currentHost) {
            global $tikilib;
            if (method_exists('TikiLib', 'lib') && ($trackerlib = TikiLib::lib('trk'))) {
                // If we don't have the trackerId from the URL, try to get it from the item
                if (empty($trackerId) && method_exists($trackerlib, 'get_tracker_item')) {
                    $item_info = $trackerlib->get_tracker_item($pageId);
                    if (! empty($item_info) && ! empty($item_info['trackerId'])) {
                        $trackerId = $item_info['trackerId'];
                    }
                }

                // Use the same function that the tracker item view page uses to get the main value
                if (method_exists($trackerlib, 'get_isMain_value') && ! empty($trackerId)) {
                    $mainValue = $trackerlib->get_isMain_value($trackerId, $pageId);
                    if (! empty($mainValue)) {
                        $pageTitle = $mainValue;

                        // Try to get the tracker name for the description
                        if (method_exists($trackerlib, 'get_tracker')) {
                            $tracker_info = $trackerlib->get_tracker($trackerId);
                            if (! empty($tracker_info) && ! empty($tracker_info['name'])) {
                                $description = $tracker_info['name'] . ' - ' . $mainValue;
                            }
                        }
                    }
                } else {
                    // Fallback to the old method if get_isMain_value is not available
                    if (method_exists($trackerlib, 'get_tracker_item')) {
                        $item_info = $trackerlib->get_tracker_item($pageId);
                        if (! empty($item_info)) {
                            // Get the tracker definition to find the main field
                            $tracker_info = $trackerlib->get_tracker($item_info['trackerId']);
                            if (! empty($tracker_info)) {
                                // Get fields for this tracker
                                $fields = $trackerlib->list_tracker_fields($item_info['trackerId']);
                                if (! empty($fields['data'])) {
                                    // Find the main field (usually the first field or one marked as title)
                                    $main_field = null;
                                    foreach ($fields['data'] as $field) {
                                        if (isset($field['isMain']) && $field['isMain'] == 'y') {
                                            $main_field = $field;
                                            break;
                                        }
                                    }

                                    // If no main field found, use the first field
                                    if (empty($main_field) && ! empty($fields['data'][0])) {
                                        $main_field = $fields['data'][0];
                                    }

                                    // Get the value of the main field
                                    if (! empty($main_field) && isset($item_info[$main_field['fieldId']])) {
                                        $main_value = $item_info[$main_field['fieldId']];
                                        if (! empty($main_value)) {
                                            $pageTitle = $main_value;
                                            $description = $tracker_info['name'] . ' - ' . $main_value;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // If we identified the page type, create basic metadata
    if (! empty($pageType)) {
        // Create a cache key
        $cacheLib = TikiLib::lib('cache');
        $cacheKey = 'url_metadata_' . md5($url);

        // Create basic metadata
        global $prefs;
        $siteName = ! empty($prefs['browsertitle']) ? $prefs['browsertitle'] : 'Tiki';
        $metadata = [
            'url' => $url,
            'title' => $pageTitle,
            'description' => isset($description) ? $description : $siteName . ' ' . $pageType . ' #' . $pageId,
            'image' => $defaultImage,
            'site_name' => $siteName,
            'type' => 'website',
            'is_internal' => true
        ];

        // Cache the result
        $cacheLib->cacheItem($cacheKey, serialize($metadata), $cacheTime);

        return $metadata;
    }

    // For external URLs, validate before proceeding
    if (! filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }

    // Check cache first
    $cacheLib = TikiLib::lib('cache');
    // Use the full URL in the cache key to avoid collisions between different domains
    $cacheKey = 'url_metadata_' . md5($url);
    $cachedData = $cacheLib->getCached($cacheKey);

    if ($cachedData) {
        return unserialize($cachedData);
    }

    // Fetch URL content
    $content = fetchUrlContent($url);
    if (! $content) {
        return false;
    }

    // Extract metadata
    $metadata = parseUrlMetadata($content, $url);

    // Cache the result
    $cacheLib->cacheItem($cacheKey, serialize($metadata), $cacheTime);

    return $metadata;
}

/**
 * Get URL content
 *
 * @param string $url URL to get
 * @return string|false Content or false if failed
 */
function fetchUrlContent($url)
{
    if (empty($url) || ! is_string($url)) {
        return false;
    }

    $uaVersion = '';
    if (isset($GLOBALS['TWV']) && is_object($GLOBALS['TWV']) && ! empty($GLOBALS['TWV']->version)) {
        $uaVersion = $GLOBALS['TWV']->version;
    } elseif (class_exists('TWVersion')) {
        $uaVersion = (new TWVersion())->getVersion();
    }
    $userAgent = 'Tiki/' . $uaVersion;

    // Try curl first
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
        $content = curl_exec($ch);
        if ($content) {
            return $content;
        }
    }

    // Fallback to file_get_contents
    $opts = [
        'http' => [
            'method' => 'GET',
            'header' => 'User-Agent: ' . $userAgent,
            'timeout' => 10
        ]
    ];
    $context = stream_context_create($opts);
    $content = @file_get_contents($url, false, $context);
    return $content;
}

/**
 * Get metadata from HTML
 *
 * @param string $content HTML
 * @param string $url URL
 * @return array Metadata
 */
function parseUrlMetadata($content, $url)
{
    // Default values
    $metadata = [
        'url' => $url,
        'title' => '',
        'description' => '',
        'image' => '',
        'site_name' => parse_url($url, PHP_URL_HOST),
        'type' => 'website',
    ];

    // Parse HTML
    $dom = new DOMDocument();

    // Ignore errors
    libxml_use_internal_errors(true);

    // Add UTF-8 encoding
    $content = '<?xml encoding="UTF-8">' . $content;

    // Load HTML
    $dom->loadHTML($content);
    libxml_clear_errors();

    // Get title
    $titles = $dom->getElementsByTagName('title');
    if ($titles->length > 0) {
        $metadata['title'] = trim($titles->item(0)->textContent);
    }

    // Get meta tags
    $metas = $dom->getElementsByTagName('meta');
    foreach ($metas as $meta) {
        // OpenGraph tags
        if ($meta instanceof DOMElement && $meta->hasAttribute('property')) {
            $prop = $meta->getAttribute('property');
            $cont = $meta->getAttribute('content');

            if ($prop == 'og:title') {
                $metadata['title'] = $cont;
            } elseif ($prop == 'og:description') {
                $metadata['description'] = $cont;
            } elseif ($prop == 'og:image') {
                // Fix relative URLs
                if (strpos($cont, 'http') !== 0) {
                    $metadata['image'] = resolveUrl($cont, $url);
                } else {
                    $metadata['image'] = $cont;
                }
            } elseif ($prop == 'og:site_name') {
                $metadata['site_name'] = $cont;
            }
        }

        // Twitter tags
        if ($meta instanceof DOMElement && $meta->hasAttribute('name')) {
            $name = $meta->getAttribute('name');
            $cont = $meta->getAttribute('content');

            if ($name == 'twitter:title' && ! $metadata['title']) {
                $metadata['title'] = $cont;
            } elseif ($name == 'twitter:description' && ! $metadata['description']) {
                $metadata['description'] = $cont;
            } elseif ($name == 'twitter:image' && ! $metadata['image']) {
                // Fix relative URLs
                if (strpos($cont, 'http') !== 0) {
                    $metadata['image'] = resolveUrl($cont, $url);
                } else {
                    $metadata['image'] = $cont;
                }
            } elseif ($name == 'description' && ! $metadata['description']) {
                $metadata['description'] = $cont;
            }
        }
    }

    // Use domain as title if none found
    if (! $metadata['title']) {
        $parsedUrl = parse_url($url);
        $metadata['title'] = $parsedUrl['host'];
    }

    // Truncate description to a reasonable length
    if (! empty($metadata['description'])) {
        $metadata['description'] = truncateText($metadata['description'], 200);
    }

    return $metadata;
}

/**
 * Fix URL paths
 *
 * @param string $url URL to fix
 * @param string $baseUrl Base URL
 * @return string Fixed URL
 */
function resolveUrl($url, $baseUrl)
{
    // Already has http
    if (strpos($url, 'http') === 0) {
        return $url;
    }

    // Has // at start
    if (substr($url, 0, 2) == '//') {
        $base = parse_url($baseUrl);
        return $base['scheme'] . ':' . $url;
    }

    // Get base parts
    $base = parse_url($baseUrl);
    $scheme = $base['scheme'] . '://';
    $host = $base['host'];
    $port = isset($base['port']) ? ':' . $base['port'] : '';

    // Starts with /
    if ($url[0] == '/') {
        return $scheme . $host . $port . $url;
    }

    // Relative path
    $path = isset($base['path']) ? $base['path'] : '';
    // Fix path
    $path = preg_replace('/\/[^\/]*$/', '/', $path);

    return $scheme . $host . $port . $path . $url;
}

/**
 * Truncate text to a specified length
 *
 * @param string $text Text to truncate
 * @param int $length Maximum length
 * @return string Truncated text
 */
function truncateText($text, $length = 200)
{
    $text = strip_tags($text);

    if (strlen($text) <= $length) {
        return $text;
    }

    $text = substr($text, 0, $length);
    $pos = strrpos($text, ' ');

    if ($pos !== false) {
        $text = substr($text, 0, $pos);
    }

    return $text . '...';
}
