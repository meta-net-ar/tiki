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

    if (($prefs['zotero_pandoc_enabled'] ?? 'n') !== 'y') {
        return zoteroBibliographyAlert('warning', tra('Pandoc citations not enabled'), true);
    }

    $content = getContentFromContext();

    if (empty($content)) {
        return zoteroBibliographyAlert('warning', tra('Could not read content for bibliography generation'));
    }

    $keys = extractCitationKeys($content);

    if (empty($keys)) {
        return zoteroBibliographyAlert('info', tra('No citations found in this content.'));
    }

    $libraryPreference = $prefs['zotero_pandoc_library'] ?? 'storage/zotero/My Library.json';
    $libraryPath = zoteroResolveFilePath($libraryPreference, ['storage/zotero/', zoteroStorageBaseDir() . 'zotero/']);
    if ($libraryPath === null || ! is_file($libraryPath)) {
        return zoteroBibliographyAlert(
            'danger',
            tr('Bibliography rendering failed: Library file not found: %0', $libraryPreference),
            true
        );
    }

    $styleName = ! empty($params['style']) ? (string) $params['style'] : ($prefs['zotero_pandoc_style'] ?? 'chicago-author-date');
    $styleName = preg_replace('/\.csl$/i', '', trim($styleName));
    if ($styleName === '' || preg_match('/[^a-zA-Z0-9._-]/', $styleName)) {
        return zoteroBibliographyAlert('danger', tra('Bibliography rendering failed: Invalid CSL style name.'), true);
    }

    $stylePath = zoteroResolveFilePath($styleName . '.csl', ['storage/csl-styles/', zoteroStorageBaseDir() . 'csl-styles/']);
    if ($stylePath === null || ! is_file($stylePath)) {
        return zoteroBibliographyAlert(
            'danger',
            tr('Bibliography rendering failed: CSL style not found: %0', $styleName),
            true
        );
    }

    $pandocPath = $prefs['zotero_pandoc_path'] ?? 'pandoc';
    $cachelib = TikiLib::lib('cache');
    $cacheKey = zoteroBibliographyBuildCacheKey($keys, $libraryPath, $stylePath, $pandocPath);

    $cachedBibliography = $cachelib->getCached($cacheKey, 'zotero_pandoc_bibliography_');
    if ($cachedBibliography !== false) {
        return $cachedBibliography;
    }

    $renderSucceeded = false;
    $bibliography = generateBibliography($keys, $libraryPath, $stylePath, $renderSucceeded);

    if ($renderSucceeded) {
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

function zoteroStorageBaseDir(): string
{
    global $tikidomainslash;

    return DEPRECATED_STORAGE_PATH . '/' . ($tikidomainslash ?? '');
}

function zoteroResolveFilePath(string $path, array $prefixCandidates = []): ?string
{
    $trimmed = trim($path);
    if ($trimmed === '') {
        return null;
    }

    $candidates = [$trimmed];
    foreach ($prefixCandidates as $prefix) {
        $candidates[] = rtrim($prefix, '/') . '/' . ltrim($trimmed, '/');
    }

    if (defined('TIKI_PATH')) {
        foreach ($candidates as $candidate) {
            if (! preg_match('/^(\/|[A-Za-z]:[\/\\\\])/', $candidate)) {
                $candidates[] = rtrim(TIKI_PATH, '/') . '/' . ltrim($candidate, '/');
            }
        }
    }

    foreach ($candidates as $candidate) {
        $resolved = realpath($candidate);
        if ($resolved !== false && is_file($resolved)) {
            return $resolved;
        }
    }

    return null;
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

    // Save flow fallback where global $page may be empty, but request still carries page name.
    $requestPage = isset($_REQUEST['page']) ? trim((string) $_REQUEST['page']) : '';
    if ($requestPage !== '') {
        $pageInfo = $tikilib->get_page_info($requestPage);
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

    // Preview/edit fallback
    if (! empty($_REQUEST['data']) && is_string($_REQUEST['data'])) {
        return $_REQUEST['data'];
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
                $part = preg_replace('/^[-@]+/', '', $part);
                if ($part === '') {
                    continue;
                }

                if (preg_match('/^([a-zA-Z0-9_\-:.$]+)/', $part, $keyMatch)) {
                    $keys[] = $keyMatch[1];
                }
            }
        }
    }

    return array_values(array_unique($keys));
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
