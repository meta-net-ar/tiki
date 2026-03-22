<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

function wikiplugin_zoterobibliography_info()
{
    return [
        'name' => tra('Zotero Bibliography'),
        'documentation' => 'PluginZoteroBibliography',
        'description' => tra('Display bibliography for citations on the page (Pandoc mode)'),
        'prefs' => ['wikiplugin_zoterobibliography', 'zotero_pandoc_enabled'],
        'iconname' => 'book',
        'introduced' => 28,
        'params' => [
            'style' => [
                'required' => false,
                'name' => tra('CSL Style'),
                'description' => tra('Citation style for bibliography. Leave empty for site default, or type any CSL filename (without .csl extension).'),
                'filter' => 'text',
                'default' => '',
                'options' => getCommonCSLStyles(),
            ],
        ],
    ];
}

/**
 * Get common CSL styles for dropdown
 * Returns curated list of most-used academic citation styles
 */
function getCommonCSLStyles()
{
    return [
        ['text' => tra('Site Default'), 'value' => ''],
        ['text' => tra('Chicago (Author-Date)'), 'value' => 'chicago-author-date'],
        ['text' => tra('Chicago (Notes-Bibliography)'), 'value' => 'chicago-notes-bibliography'],
        ['text' => tra('MLA 9th Edition'), 'value' => 'modern-language-association'],
        ['text' => tra('MLA 8th Edition'), 'value' => 'modern-language-association-8th-edition'],
        ['text' => tra('Elsevier Harvard'), 'value' => 'elsevier-harvard2'],
        ['text' => tra('IEEE'), 'value' => 'ieee'],
        ['text' => tra('Nature'), 'value' => 'nature'],
        ['text' => tra('Science'), 'value' => 'science'],
        ['text' => tra('Cell'), 'value' => 'cell'],
        ['text' => tra('The Lancet'), 'value' => 'the-lancet'],
        ['text' => tra('Vancouver'), 'value' => 'vancouver'],
    ];
}

function zoteroBibliographyAlert(string $type, string $message, bool $sendGlobalFeedback = false)
{
    if ($sendGlobalFeedback && class_exists('Feedback')) {
        switch ($type) {
            case 'danger':
                Feedback::error($message);
                break;
            case 'warning':
                Feedback::warning($message);
                break;
            case 'success':
                Feedback::success($message);
                break;
            default:
                Feedback::note($message);
                break;
        }
    }

    return '<div class="alert alert-' . $type . '">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
}

function wikiplugin_zoterobibliography($data, $params)
{
    global $prefs;

    if (empty($prefs['zotero_pandoc_enabled']) || $prefs['zotero_pandoc_enabled'] !== 'y') {
        return zoteroBibliographyAlert('warning', tra('Pandoc citations not enabled'), true);
    }

    $content = getContentFromContext();

    if (empty($content)) {
        return zoteroBibliographyAlert('warning', tra('Could not read content for bibliography generation'), true);
    }

    $keys = extractCitationKeys($content);

    if (empty($keys)) {
        return zoteroBibliographyAlert('info', tra('No citations found in this content.'));
    }

    $libraryPath = $prefs['zotero_pandoc_library'] ?? 'storage/zotero/My Library.json';

    $stylePath = null;
    if (! empty($params['style'])) {
        $stylePath = 'storage/csl-styles/' . $params['style'] . '.csl';
    } else {
        $stylePath = 'storage/csl-styles/' . ($prefs['zotero_pandoc_style'] ?? 'chicago-author-date') . '.csl';
    }

    $pandocPath = $prefs['zotero_pandoc_path'] ?? 'pandoc';
    $cachelib = class_exists('TikiLib') ? TikiLib::lib('cache') : null;
    $cacheKey = zoteroBibliographyBuildCacheKey($keys, $libraryPath, $stylePath, $pandocPath);

    if ($cachelib) {
        $cachedBibliography = $cachelib->getCached($cacheKey, 'zotero_pandoc_bibliography_');
        if ($cachedBibliography !== false) {
            return $cachedBibliography;
        }
    }

    $renderSucceeded = false;
    $bibliography = generateBibliography($keys, $libraryPath, $stylePath, $renderSucceeded);

    if ($renderSucceeded && $cachelib) {
        $cachelib->cacheItem($cacheKey, $bibliography, 'zotero_pandoc_bibliography_');
    }

    return $bibliography;
}

function zoteroBibliographyBuildCacheKey(array $keys, string $libraryPath, string $stylePath, string $pandocPath): string
{
    return hash(
        'sha256',
        '1'
        . "\n" . $pandocPath
        . "\n" . zoteroBibliographyFileFingerprint($libraryPath)
        . "\n" . zoteroBibliographyFileFingerprint($stylePath)
        . "\n" . implode(';', $keys)
    );
}

function zoteroBibliographyFileFingerprint(string $path): string
{
    $resolvedPath = realpath($path);
    $normalizedPath = $resolvedPath !== false ? $resolvedPath : $path;

    if (! is_file($normalizedPath)) {
        return $normalizedPath . '|missing';
    }

    $mtime = @filemtime($normalizedPath) ?: 0;
    $size = @filesize($normalizedPath) ?: 0;

    return $normalizedPath . '|' . $mtime . '|' . $size;
}

/**
 * Get content from current Tiki context
 * Supports: Wiki pages, Blog posts, Articles
 */
function getContentFromContext()
{
    global $page;

    $tikilib = TikiLib::lib('tiki');

    if (! empty($page)) {
        $pageInfo = $tikilib->get_page_info($page);
        if (! empty($pageInfo['data'])) {
            return $pageInfo['data'];
        }
    }

    $postId = isset($_REQUEST['postId']) ? (int) $_REQUEST['postId'] : 0;
    if ($postId > 0) {
        $bloglib = TikiLib::lib('blog');
        $post = $bloglib->get_post($postId);

        if (! empty($post['data'])) {
            return $post['data'];
        }
    }

    $articleId = isset($_REQUEST['articleId']) ? (int) $_REQUEST['articleId'] : 0;
    if ($articleId > 0) {
        $artlib = TikiLib::lib('art');
        $article = $artlib->get_article($articleId);

        if (! empty($article['body'])) {
            return $article['heading'] . "\n\n" . $article['body'];
        }
    }

    return '';
}

/**
 * Extract citation keys from content
 * Matches: {@key}, {@key, p. 42}, {@key1; @key2}, {-@key}
 */
function extractCitationKeys($content)
{
    $keys = [];

    if (preg_match_all('/\{-?@([^}]+)\}/', $content, $matches)) {
        foreach ($matches[1] as $match) {
            $parts = explode(';', $match);

            foreach ($parts as $part) {
                $part = trim($part);
                $part = ltrim($part, '@');

                if (preg_match('/^([a-zA-Z0-9_\-:.$]+)/', $part, $keyMatch)) {
                    $keys[] = $keyMatch[1];
                }
            }
        }
    }

    return array_unique($keys);
}

/**
 * Generate bibliography HTML using Pandoc
 */
function generateBibliography($keys, $libraryPath, $stylePath, &$renderSucceeded = false)
{
    global $prefs;

    $markdown = "---\nnocite: |\n";
    foreach ($keys as $key) {
        $markdown .= "  @" . $key . "\n";
    }
    $markdown .= "---\n\n";

    $pandocPath = $prefs['zotero_pandoc_path'] ?? 'pandoc';

    $cmd = escapeshellcmd($pandocPath);
    $cmd .= ' --citeproc';
    $cmd .= ' --bibliography=' . escapeshellarg($libraryPath);
    $cmd .= ' --csl=' . escapeshellarg($stylePath);
    $cmd .= ' -f markdown';
    $cmd .= ' -t html';

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($cmd, $descriptors, $pipes);

    if (! is_resource($process)) {
        $renderSucceeded = false;
        return zoteroBibliographyAlert('danger', tra('Bibliography rendering failed: Could not start Pandoc'), true);
    }

    fwrite($pipes[0], $markdown);
    fclose($pipes[0]);

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $returnCode = proc_close($process);

    if ($returnCode !== 0) {
        $renderSucceeded = false;
        return zoteroBibliographyAlert(
            'danger',
            tr('Bibliography rendering failed: %0', trim($errors)),
            true
        );
    }

    $renderSucceeded = true;
    return trim($output);
}
