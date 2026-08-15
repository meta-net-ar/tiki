<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

use Tiki\Process\Process;
use Tiki\Zotero\CslStyleManager;
use Tiki\Zotero\PandocLibraryFile;

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
                'options' => zoteroBibliographyCommonCslStyles(),
            ],
        ],
    ];
}

/**
 * Get common CSL styles for dropdown
 * Returns curated list of most-used academic citation styles
 */
function zoteroBibliographyCommonCslStyles()
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
    if ($sendGlobalFeedback) {
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

    $content = zoteroBibliographyContentFromContext();

    if (empty($content)) {
        return zoteroBibliographyAlert('warning', tra('Could not read content for bibliography generation'));
    }

    $keys = zoteroBibliographyExtractCitationKeys($content);

    if (empty($keys)) {
        return zoteroBibliographyAlert('info', tra('No citations found in this content.'));
    }

    $styleName = ! empty($params['style']) ? (string) $params['style'] : ($prefs['zotero_pandoc_style'] ?? 'chicago-author-date');
    try {
        $library = PandocLibraryFile::fromPreference($prefs['zotero_pandoc_library'] ?? '');
        $libraryPath = $library->getPath();
        $styleManager = new CslStyleManager();
        $stylePath = $styleManager->resolveStylePath($styleName);
    } catch (RuntimeException $e) {
        return zoteroBibliographyAlert(
            'danger',
            tr('Bibliography rendering failed: %0', $e->getMessage()),
            true
        );
    }

    $pandocPath = $prefs['zotero_pandoc_path'] ?? 'pandoc';
    $cachelib = TikiLib::lib('cache');
    $cacheKey = zoteroBibliographyBuildCacheKey($keys, $library->getFingerprint(), $styleManager->fileFingerprint($stylePath), $pandocPath);

    $cachedBibliography = $cachelib->getCached($cacheKey, 'zotero_pandoc_bibliography_');
    if ($cachedBibliography !== false) {
        return $cachedBibliography;
    }

    $renderSucceeded = false;
    $bibliography = zoteroBibliographyGenerate($keys, $libraryPath, $stylePath, $renderSucceeded);

    if ($renderSucceeded) {
        $cachelib->cacheItem($cacheKey, $bibliography, 'zotero_pandoc_bibliography_');
    }

    return $bibliography;
}

function zoteroBibliographyBuildCacheKey(array $keys, string $libraryFingerprint, string $styleFingerprint, string $pandocPath): string
{
    return hash(
        'sha256',
        '1'
        . "\n" . $pandocPath
        . "\n" . $libraryFingerprint
        . "\n" . $styleFingerprint
        . "\n" . implode(';', $keys)
    );
}

/**
 * Get content from current Tiki context
 * Supports: Wiki pages, Blog posts, Articles
 */
function zoteroBibliographyContentFromContext()
{
    global $page;

    $tikilib = TikiLib::lib('tiki');

    if (zoteroBibliographyHasSubmittedContent()) {
        return $_REQUEST['data'];
    }

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

    // Generic preview/edit fallback for non-wiki content types.
    if (! empty($_REQUEST['data']) && is_string($_REQUEST['data'])) {
        return $_REQUEST['data'];
    }

    return '';
}

function zoteroBibliographyHasSubmittedContent(): bool
{
    return isset($_REQUEST['data'])
        && is_string($_REQUEST['data'])
        && (isset($_REQUEST['preview']) || isset($_REQUEST['save']));
}

/**
 * Extract citation keys from content
 * Matches: {@key}, {@key, p. 42}, {@key1; @key2}, {-@key}
 */
function zoteroBibliographyExtractCitationKeys($content)
{
    $keys = [];
    $content = zoteroBibliographyStripMarkdownCode((string) $content);

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

function zoteroBibliographyStripMarkdownCode(string $content): string
{
    $withoutFencedCode = preg_replace('/(^|\R)[ \t]*(`{3,}|~{3,})[^\r\n]*\R.*?\R[ \t]*\2[ \t]*(?=\R|$)/s', "\n", $content);
    if ($withoutFencedCode !== null) {
        $content = $withoutFencedCode;
    }

    $withoutIndentedCode = preg_replace('/(^|\R)(?: {4}|\t).*(?=\R|$)/', "\n", $content);
    if ($withoutIndentedCode !== null) {
        $content = $withoutIndentedCode;
    }

    return preg_replace('/`+[^`\r\n]*`+/', '', $content) ?? $content;
}

/**
 * Generate bibliography HTML using Pandoc
 */
function zoteroBibliographyGenerate($keys, $libraryPath, $stylePath, &$renderSucceeded = false)
{
    global $prefs;

    $markdown = "---\nnocite: |\n";
    foreach ($keys as $key) {
        $markdown .= '  @' . $key . "\n";
    }
    $markdown .= "---\n\n";

    $pandocPath = $prefs['zotero_pandoc_path'] ?? 'pandoc';

    $command = [
        $pandocPath,
        '--citeproc',
        '--bibliography=' . $libraryPath,
        '--csl=' . $stylePath,
        '-f',
        'markdown',
        '-t',
        'html',
    ];

    try {
        $process = new Process($command, null, null, $markdown, null);
        $process->run();
    } catch (Throwable $e) {
        $renderSucceeded = false;
        return zoteroBibliographyAlert('danger', tra('Bibliography rendering failed: Could not start Pandoc'), true);
    }

    if (! $process->isSuccessful()) {
        $renderSucceeded = false;
        return zoteroBibliographyAlert(
            'danger',
            tr('Bibliography rendering failed: %0', trim($process->getErrorOutput())),
            true
        );
    }

    $renderSucceeded = true;
    return trim($process->getOutput());
}
