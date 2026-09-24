<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function wikiplugin_rss_info()
{
    return [
        'name' => tra('RSS Feed'),
        'documentation' => 'PluginRSS',
        'description' => tra('Display items from one or more RSS feeds'),
        'prefs' => [ 'wikiplugin_rss' ],
        'iconname' => 'rss',
        'introduced' => 1,
        'format' => 'html',
        'filter' => 'striptags',
        'tags' => [ 'basic' ],
        'params' => [
            'id' => [
                'required' => false,
                'name' => tra('IDs'),
                'separator' => ':',
                'filter' => 'int',
                'description' => tr('List of feed IDs separated by colons (e.g., %0). You can find the IDs in the RSS Administration page:  %1', '<code>feedId:feedId2</code>', '<code>tiki-admin_rssmodules.php</code>.'),
                'since' => '1',
                'profile_reference' => 'rss',
            ],
            'url' => [
                'required' => false,
                'name' => tra('URL'),
                'filter' => 'url',
                'description' => tr('The full URL of the RSS feed. Use this parameter if you want to directly link to an RSS feed without adding it to the RSS Administration page.'),
                'since' => '29.0',
                'default' => '',
                'profile_reference' => 'rss',
            ],
            'refresh' => [
                'required' => false,
                'name' => tra('Refresh Interval'),
                'filter' => 'digits',
                'description' => tra('Refresh period in minutes, determining how frequently the RSS feed is updated.'),
                'since' => '29.0',
                'default' => 60,
                'profile_reference' => 'rss',
            ],
            'max' => [
                'required' => false,
                'name' => tra('Result Count'),
                'filter' => 'int',
                'description' => tra('Number of results displayed.'),
                'since' => '1',
                'default' => 10,
            ],
            'date' => [
                'required' => false,
                'name' => tra('Date'),
                'filter' => 'digits',
                'description' => tra('Show date of each item. If empty, uses the External Feed admin setting for this feed (not shown by default)'),
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'desc' => [
                'required' => false,
                'name' => tra('Description'),
                'filter' => 'digits',
                'description' => tra('Show feed descriptions. If empty, uses the External Feed admin setting for this feed (not shown by default)'),
                'since' => '1',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'author' => [
                'required' => false,
                'name' => tra('Author'),
                'filter' => 'digits',
                'description' => tra('Show authors (not shown by default)'),
                'since' => '1',
                'default' => 0,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'icon' => [
                'required' => false,
                'name' => tra('Icon'),
                'filter' => 'url',
                'description' => tra('URL to a favicon to put before each entry'),
                'since' => '5.0',
                'default' => '',
            ],
            'image' => [
                'required' => false,
                'name' => tra('Item Image'),
                'filter' => 'digits',
                'description' => tra('Show the first image found in each feed item. If empty, uses the External Feed admin setting for this feed (not shown by default)'),
                'since' => '31.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'layout' => [
                'required' => false,
                'name' => tra('Layout'),
                'filter' => 'alpha',
                'description' => tra('Display feed items as a list or as cards. If empty, uses the External Feed admin setting for this feed (list by default)'),
                'since' => '31.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('List'), 'value' => 'list'],
                    ['text' => tra('Cards'), 'value' => 'cards'],
                ],
            ],
            'showtitle' => [
                'required' => false,
                'name' => tra('Show Title'),
                'filter' => 'digits',
                'description' => tra('Show the title of the feed. If empty, uses the External Feed admin setting for this feed (shown by default)'),
                'since' => '6.0',
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'ticker' => [
                'required' => false,
                'name' => tra('Ticker'),
                'filter' => 'digits',
                'description' => tra('Turn static feed display into ticker news like'),
                'since' => '10.1',
                'default' => 1,
                'options' => [
                    ['text' => '', 'value' => ''],
                    ['text' => tra('Yes'), 'value' => 1],
                    ['text' => tra('No'), 'value' => 0]
                ]
            ],
            'desclen' => [
                'required' => false,
                'name' => tra('Description Length'),
                'filter' => 'digits',
                'description' => tra('Max characters/length, truncates text to fit design'),
                'since' => '10.1',
                'default' => 0,
            ],
            'sortBy' => [
                'required' => false,
                'name' => tra('Sort By'),
                'filter' => 'text',
                'description' => tra('Sort by field'),
                'options' => [
                    ['text' => tra('Title'), 'value' => 'title'],
                    ['text' => tra('Date'), 'value' => 'publication_date'],
                    ['text' => tra('Author'), 'value' => 'author'],
                ],
                'default' => 'publication_date',
                'since' => '28.0',
                'advanced' => true,
            ],
            'sortOrder' => [
                'required' => false,
                'name' => tra('Sort Order'),
                'filter' => 'text',
                'description' => tra('Sort order'),
                'options' => [
                    ['text' => tra('Ascending'), 'value' => 'ASC'],
                    ['text' => tra('Descending'), 'value' => 'DESC'],
                ],
                'default' => 'DESC',
                'since' => '28.0',
                'advanced' => true,
            ],
            'tplWiki' => [
                'required' => false,
                'name' => tra('Template Wiki Page'),
                'description' => tra('Custom wiki page with smarty content to use for displaying feed items instead of the default template'),
                'filter' => 'text',
                'since' => '28.0',
                'advanced' => true,
            ]
        ],
    ];
}

function wikiplugin_rss($data, $params)
{
    $userParams = $params;
    $params = WikiPlugin_Helper::applySeparators($params, wikiplugin_rss_info());

    $rsslib = TikiLib::lib('rss');
    $params = array_merge(
        [
            'ticker' => 0,
        ],
        $params
    );

    if (is_null($params['id']) && empty($params['url'])) {
        return WikiParser_PluginOutput::argumentError([ 'id or url' ]);
    }

    if (! is_null($params['id']) && ! empty($params['url'])) {
        return WikiParser_PluginOutput::argumentError(['id or url, not both']);
    }

    $items = [];
    $title = null;

    if (! is_null($params['id'])) {
        $params['id'] = (array) $params['id'];
        $items = $rsslib->get_feed_items($params['id'], $params['max'], $params['sortBy'], $params['sortOrder']);
        if (count($params['id']) == 1) {
            $module = $rsslib->get_rss_module(reset($params['id']));
            if (isset($module['sitetitle'])) {
                $title = [
                    'title' => $module['sitetitle'],
                    'link' => $module['siteurl'],
                ];
            }
            if ($module) {
                if (($userParams['showtitle'] ?? null) === null && isset($module['showTitle'])) {
                    $params['showtitle'] = $module['showTitle'] === 'y' ? 1 : 0;
                }
                if (($userParams['date'] ?? null) === null && isset($module['showPubDate'])) {
                    $params['date'] = $module['showPubDate'] === 'y' ? 1 : 0;
                }
                if (($userParams['desc'] ?? null) === null && isset($module['showDesc'])) {
                    $params['desc'] = $module['showDesc'] === 'y' ? 1 : 0;
                }
                if (($userParams['image'] ?? null) === null && isset($module['showImage'])) {
                    $params['image'] = $module['showImage'] === 'y' ? 1 : 0;
                }
                if (($userParams['layout'] ?? null) === null && ! empty($module['displayMode'])) {
                    $params['layout'] = $module['displayMode'];
                }
            }
        }
    } elseif (! empty($params['url'])) {
        ['params' => $params, 'items' => $items, 'meta' => $title] = $rsslib->loadRss($params);
    }

    // Params not explicitly set and not resolved from an External Feed admin setting above
    // (multi-feed id, url-based feed, or missing admin setting) fall back to these defaults.
    $params['showtitle'] = $params['showtitle'] ?? 1;
    $params['date'] = $params['date'] ?? 0;
    $params['desc'] = $params['desc'] ?? 0;
    $params['image'] = $params['image'] ?? 0;
    $params['layout'] = $params['layout'] ?? 'list';

    $showImage = $params['image'] > 0 || $params['layout'] === 'cards';

    if ($showImage) {
        wikiplugin_rss_apply_images($items);
    }

    if ($params['desc'] > 0) {
        foreach ($items as &$item) {
            $description = wikiplugin_rss_clean_description($item['description'] ?? '');
            if ($params['desclen'] > 0) {
                $description = limit_text($description, $params['desclen']);
            }
            $item['description'] = $description;
        }
        unset($item);
    }

    $smarty = TikiLib::lib('smarty');
    $smarty->assign('rsstitle', $title);
    $smarty->assign('items', $items);
    $smarty->assign('showdate', $params['date'] > 0);
    $smarty->assign('showtitle', $params['showtitle'] > 0);
    $smarty->assign('showdesc', $params['desc'] > 0);
    $smarty->assign('showauthor', $params['author'] > 0);
    $smarty->assign('icon', $params['icon']);
    $smarty->assign('showimage', $showImage);
    $smarty->assign('layout', in_array($params['layout'], ['list', 'cards'], true) ? $params['layout'] : 'list');
    $smarty->assign('ticker', $params['ticker']);

    $template = isset($params['tplWiki']) ? ('tplwiki:' . $params['tplWiki']) : 'wiki-plugins/wikiplugin_rss.tpl';
    return $smarty->fetch($template);
}

function wikiplugin_rss_clean_description($description)
{
    $description = html_entity_decode((string) $description, ENT_QUOTES | ENT_HTML5);
    $description = preg_replace('/<img\b[^>]*>/i', ' ', $description);
    return trim(preg_replace('/\s+/u', ' ', strip_tags($description)));
}

function wikiplugin_rss_apply_images(array &$items): void
{
    $fallbackPool = wikiplugin_rss_fallback_images();
    $fallbackIndex = 0;

    foreach ($items as &$item) {
        $fallbackImage = $fallbackPool[$fallbackIndex % count($fallbackPool)];
        $item['image'] = wikiplugin_rss_extract_image($item);
        if ($item['image'] === '' && ! empty($item['url'])) {
            $item['image'] = wikiplugin_rss_extract_tiki_article_page_image($item['url']);
        }
        $item['fallback_image'] = $fallbackImage;
        if ($item['image'] === '') {
            $item['image'] = $fallbackImage;
            $fallbackIndex++;
        }
    }
    unset($item);
}

function wikiplugin_rss_extract_image(array $item): string
{
    $imageSource = implode(' ', array_filter([
        $item['content'] ?? '',
        $item['raw_description'] ?? '',
        $item['description'] ?? '',
    ]));

    $patterns = [
        '/<img[^>]+src=["\']([^"\']+)["\']/i',
        '/<media:thumbnail[^>]+url=["\']([^"\']+)["\']/i',
        '/<media:content[^>]+url=["\']([^"\']+)["\'][^>]*medium=["\']image["\']/i',
        '/<enclosure[^>]+url=["\']([^"\']+)["\'][^>]*type=["\']image\/[^"\']+["\']/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $imageSource, $matches)) {
            $url = wikiplugin_rss_normalize_article_image_url(wikiplugin_rss_resolve_url($matches[1], $item['url'] ?? ''));
            if (wikiplugin_rss_is_supported_image_url($url)) {
                return $url;
            }
        }
    }

    return '';
}

function wikiplugin_rss_extract_tiki_article_page_image(string $url): string
{
    static $cache = [];

    if (isset($cache[$url])) {
        return $cache[$url];
    }

    if (! wikiplugin_rss_is_tiki_article_url($url) || ! wikiplugin_rss_is_public_http_url($url)) {
        return $cache[$url] = '';
    }

    $cacheLib = TikiLib::lib('cache');
    $cacheKey = 'rss_tiki_article_image_' . md5($url);
    $cached = $cacheLib->getCached($cacheKey, '', time() - 86400);
    if ($cached !== false) {
        return $cache[$url] = $cached;
    }

    try {
        $client = TikiLib::lib('tiki')->get_http_client($url, ['timeout' => 2]);
        $client->setHeaders([
            'Accept' => 'text/html,application/xhtml+xml',
            'User-Agent' => 'Tiki RSS image extractor',
        ]);
        $response = TikiLib::lib('tiki')->http_perform_request($client);
    } catch (Throwable $e) {
        return $cache[$url] = '';
    }

    if (! $response->isSuccess()) {
        $cacheLib->cacheItem($cacheKey, '');
        return $cache[$url] = '';
    }

    $image = wikiplugin_rss_extract_image_from_html((string) $response->getBody(), $url);
    $cacheLib->cacheItem($cacheKey, $image);
    return $cache[$url] = $image;
}

function wikiplugin_rss_is_tiki_article_url(string $url): bool
{
    $parts = parse_url($url);
    if (empty($parts['path'])) {
        return false;
    }

    if (basename($parts['path']) === 'tiki-read_article.php') {
        parse_str($parts['query'] ?? '', $query);
        return ! empty($query['articleId']) && ctype_digit((string) $query['articleId']);
    }

    return preg_match('#/article\d+(?:\D|$)#', $parts['path']) === 1;
}

function wikiplugin_rss_extract_image_from_html(string $html, string $pageUrl): string
{
    if ($html === '') {
        return '';
    }

    $dom = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    if (! $loaded) {
        return '';
    }

    foreach ($dom->getElementsByTagName('meta') as $meta) {
        if (! $meta instanceof DOMElement) {
            continue;
        }

        $key = strtolower($meta->getAttribute('property') ?: $meta->getAttribute('name'));
        if (in_array($key, ['og:image', 'og:image:url', 'twitter:image', 'twitter:image:src'], true)) {
            $imageUrl = wikiplugin_rss_normalize_article_image_url(wikiplugin_rss_resolve_url($meta->getAttribute('content'), $pageUrl));
            if (wikiplugin_rss_is_supported_image_url($imageUrl)) {
                return $imageUrl;
            }
        }
    }

    foreach ($dom->getElementsByTagName('img') as $image) {
        if (! $image instanceof DOMElement) {
            continue;
        }

        $class = $image->getAttribute('class');
        if (strpos($class, 'article-image') === false && strpos($class, 'topic-image') === false) {
            continue;
        }

        $imageUrl = wikiplugin_rss_normalize_article_image_url(wikiplugin_rss_resolve_url($image->getAttribute('src'), $pageUrl));
        if (wikiplugin_rss_is_supported_image_url($imageUrl)) {
            return $imageUrl;
        }
    }

    return '';
}

function wikiplugin_rss_resolve_url(string $url, string $baseUrl): string
{
    $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5));
    if ($url === '' || str_starts_with($url, 'data:')) {
        return '';
    }

    if (filter_var($url, FILTER_VALIDATE_URL)) {
        return $url;
    }

    $base = parse_url($baseUrl);
    if (empty($base['scheme']) || empty($base['host'])) {
        return '';
    }

    if (str_starts_with($url, '//')) {
        return $base['scheme'] . ':' . $url;
    }

    $root = $base['scheme'] . '://' . $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '');
    if (str_starts_with($url, '/')) {
        return $root . $url;
    }

    $path = isset($base['path']) ? preg_replace('#/[^/]*$#', '/', $base['path']) : '/';
    return $root . $path . $url;
}

function wikiplugin_rss_normalize_article_image_url(string $url): string
{
    $parts = parse_url($url);
    if (empty($parts['path']) || basename($parts['path']) !== 'article_image.php' || empty($parts['query'])) {
        return $url;
    }

    parse_str($parts['query'], $query);
    if (empty($query['id'])) {
        if (! empty($query['image_topicId'])) {
            $query['id'] = $query['image_topicId'];
            unset($query['image_topicId']);
        } elseif (! empty($query['image_articleId'])) {
            $query['id'] = $query['image_articleId'];
            unset($query['image_articleId']);
        }
    }

    $normalized = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . $parts['path'];
    if (! empty($query)) {
        $normalized .= '?' . http_build_query($query, '', '&');
    }

    return $normalized;
}

function wikiplugin_rss_is_supported_image_url(string $url): bool
{
    if (! wikiplugin_rss_is_http_url($url)) {
        return false;
    }

    $path = parse_url($url, PHP_URL_PATH) ?: '';
    if (preg_match('#/(?:icons?|pics?)/#i', $path)) {
        return false;
    }

    if (preg_match('/\.(?:avif|gif|jpe?g|png|svg|webp)(?:$|\?)/i', $url)) {
        return true;
    }

    return preg_match('#/(?:article_image\.php|display\d+)(?:$|\?)#i', $path) === 1
        || str_contains(parse_url($url, PHP_URL_QUERY) ?: '', 'image_type=');
}

function wikiplugin_rss_is_http_url(string $url): bool
{
    $parts = parse_url($url);
    return in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true) && ! empty($parts['host']);
}

function wikiplugin_rss_is_public_http_url(string $url): bool
{
    if (! wikiplugin_rss_is_http_url($url)) {
        return false;
    }

    $parts = parse_url($url);
    $host = strtolower($parts['host']);
    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return false;
    }

    $isIp = filter_var($host, FILTER_VALIDATE_IP);
    $isPublicIp = filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    if ($isIp && $isPublicIp === false) {
        return false;
    }

    if (class_exists('\\Tiki\\Security\\SsrfLib')) {
        try {
            return \Tiki\Security\SsrfLib::fromPrefs()->isUrlAllowed($url);
        } catch (Throwable $e) {
            return false;
        }
    }

    return true;
}

function wikiplugin_rss_fallback_images(): array
{
    return [
        'img/rss-fallback-news.svg',
        'img/rss-fallback-article.svg',
        'img/rss-fallback-update.svg',
        'img/rss-fallback-insight.svg',
        'img/rss-fallback-report.svg',
        'img/rss-fallback-signal.svg',
    ];
}

function limit_text($text, $limit)
{
    if (str_word_count($text, 0) > $limit) {
        $words = str_word_count($text, 2);
        $pos = array_keys($words);
        $text = substr($text, 0, $pos[$limit]) . '...';
    }
    return $text;
}
