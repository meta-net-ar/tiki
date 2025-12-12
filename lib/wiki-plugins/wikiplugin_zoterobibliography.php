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
        ['text' => 'Site Default', 'value' => ''],
        ['text' => 'Chicago (Author-Date)', 'value' => 'chicago-author-date'],
        ['text' => 'Chicago (Notes-Bibliography)', 'value' => 'chicago-notes-bibliography'],
        ['text' => 'MLA 9th Edition', 'value' => 'modern-language-association'],
        ['text' => 'MLA 8th Edition', 'value' => 'modern-language-association-8th-edition'],
        ['text' => 'Elsevier Harvard', 'value' => 'elsevier-harvard2'],
        ['text' => 'IEEE', 'value' => 'ieee'],
        ['text' => 'Nature', 'value' => 'nature'],
        ['text' => 'Science', 'value' => 'science'],
        ['text' => 'Cell', 'value' => 'cell'],
        ['text' => 'The Lancet', 'value' => 'the-lancet'],
        ['text' => 'Vancouver', 'value' => 'vancouver'],
    ];
}

function wikiplugin_zoterobibliography($data, $params)
{
    global $prefs;
    
    if (empty($prefs['zotero_pandoc_enabled']) || $prefs['zotero_pandoc_enabled'] !== 'y') {
        return '<div class="alert alert-warning">Pandoc citations not enabled</div>';
    }
    
    $content = getContentFromContext();
    
    if (empty($content)) {
        return '<div class="alert alert-warning">Could not read content for bibliography generation</div>';
    }
    
    $keys = extractCitationKeys($content);
    
    if (empty($keys)) {
        return '<div class="alert alert-info">No citations found in this content.</div>';
    }
    
    $libraryPath = $prefs['zotero_pandoc_library'] ?? 'storage/zotero/My Library.json';
    
    $stylePath = null;
    if (!empty($params['style'])) {
        $stylePath = 'storage/csl-styles/' . $params['style'] . '.csl';
    } else {
        $stylePath = 'storage/csl-styles/' . ($prefs['zotero_pandoc_style'] ?? 'chicago-author-date') . '.csl';
    }
    
    $bibliography = generateBibliography($keys, $libraryPath, $stylePath);
    
    return $bibliography;
}

/**
 * Get content from current Tiki context
 * Supports: Wiki pages, Blog posts, Articles
 */
function getContentFromContext()
{
    global $page;
    
    $tikilib = TikiLib::lib('tiki');
    
    if (!empty($page)) {
        $pageInfo = $tikilib->get_page_info($page);
        if (!empty($pageInfo['data'])) {
            return $pageInfo['data'];
        }
    }
    
    if (!empty($_REQUEST['postId'])) {
        $postId = $_REQUEST['postId'];
        $bloglib = TikiLib::lib('blog');
        $post = $bloglib->get_post($postId);
        
        if (!empty($post['data'])) {
            return $post['data'];
        }
    }
    
    if (!empty($_REQUEST['articleId'])) {
        $articleId = $_REQUEST['articleId'];
        $artlib = TikiLib::lib('art');
        $article = $artlib->get_article($articleId);
        
        if (!empty($article['body'])) {
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
function generateBibliography($keys, $libraryPath, $stylePath)
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
    
    if (!is_resource($process)) {
        return '<div class="alert alert-danger">Bibliography rendering failed: Could not start Pandoc</div>';
    }
    
    fwrite($pipes[0], $markdown);
    fclose($pipes[0]);
    
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    
    $returnCode = proc_close($process);
    
    if ($returnCode !== 0) {
        return '<div class="alert alert-danger">Bibliography rendering failed: ' . htmlspecialchars($errors) . '</div>';
    }
    
    return '<div id="refs" class="references csl-bib-body">' . "\n" . $output . "\n" . '</div>';
}