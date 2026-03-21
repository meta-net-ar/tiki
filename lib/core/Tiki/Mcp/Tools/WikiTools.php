<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Mcp\Tools;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Tiki\Mcp\Context\TikiContext;

/**
 * MCP tools for wiki page operations.
 *
 * All tools use TikiLib/WikiLib service layer directly (in-process).
 * Permission checks happen automatically via Perms_Context set at bootstrap.
 * Output buffering wraps TikiLib calls to prevent stdout corruption.
 */
class WikiTools
{
    private const ALLOWED_SORT_MODES = [
        'lastModif_desc', 'lastModif_asc',
        'pageName_desc', 'pageName_asc',
        'user_desc', 'user_asc',
        'version_desc', 'version_asc',
        'hits_desc', 'hits_asc',
    ];

    private TikiContext $context;
    private string $user;
    private string $ip;

    public function __construct(string $user, string $ip = '0.0.0.0')
    {
        $this->user = $user;
        $this->ip = $ip;
        $this->context = new TikiContext($user);
    }

    /**
     * List wiki pages with optional filtering and pagination.
     *
     * @param string $search  Search term to filter page names (optional)
     * @param int    $offset  Pagination offset (default: 0)
     * @param int    $limit   Maximum pages to return (default: 25, max: 100)
     * @param string $sort    Sort mode e.g. lastModif_desc, pageName_asc (default: lastModif_desc)
     * @param string $initial Filter by initial letter(s) (optional)
     * @return array List of pages with pagination metadata
     */
    #[McpTool(
        name: 'wiki_list_pages',
        description: 'List wiki pages with optional filtering and pagination.',
        annotations: new ToolAnnotations(readOnlyHint: true)
    )]
    public function listPages(
        string $search = '',
        int $offset = 0,
        int $limit = 25,
        string $sort = 'lastModif_desc',
        string $initial = ''
    ): array {
        $limit = min(max($limit, 1), 100);
        $offset = max($offset, 0);

        if (! in_array($sort, self::ALLOWED_SORT_MODES, true)) {
            $sort = 'lastModif_desc';
        }

        $result = $this->context->captureOutput(
            function () use ($offset, $limit, $sort, $search, $initial) {
                return \TikiLib::lib('tiki')->list_pages($offset, $limit, $sort, $search, $initial, false);
            }
        );

        $pages = [];
        if (! empty($result['data'])) {
            foreach ($result['data'] as $page) {
                $pages[] = [
                    'pageName' => $page['pageName'],
                    'description' => $page['description'] ?? '',
                    'lastModif' => (int)($page['lastModif'] ?? 0),
                    'user' => $page['user'] ?? '',
                    'version' => (int)($page['version'] ?? 0),
                    'page_size' => (int)($page['page_size'] ?? 0),
                ];
            }
        }

        return [
            'pages' => $pages,
            'total' => (int)($result['count'] ?? 0),
            'offset' => $offset,
            'limit' => $limit,
        ];
    }

    /**
     * Get full page content (raw wiki syntax) and metadata.
     *
     * @param string $page Page name (required)
     * @return array Page content and metadata
     */
    #[McpTool(
        name: 'wiki_get_page',
        description: 'Get full page content in raw wiki syntax and metadata.',
        annotations: new ToolAnnotations(readOnlyHint: true)
    )]
    public function getPage(string $page): array
    {
        $info = $this->context->captureOutput(
            function () use ($page) {
                return \TikiLib::lib('tiki')->get_page_info($page);
            }
        );

        if (! $info) {
            throw new ToolCallException("Page '$page' not found.");
        }

        return [
            'pageName' => $info['pageName'],
            'data' => $info['data'] ?? '',
            'description' => $info['description'] ?? '',
            'lastModif' => (int)($info['lastModif'] ?? 0),
            'user' => $info['user'] ?? '',
            'creator' => $info['creator'] ?? '',
            'version' => (int)($info['version'] ?? 0),
            'lang' => $info['lang'] ?? '',
            'is_html' => (bool)($info['is_html'] ?? false),
            'page_size' => (int)($info['page_size'] ?? 0),
            'comment' => $info['comment'] ?? '',
        ];
    }

    /**
     * Get version history for a wiki page.
     *
     * @param string $page   Page name (required)
     * @param int    $offset Pagination offset (default: 0)
     * @param int    $limit  Maximum versions to return (default: 10, max: 50)
     * @return array Version history with pagination metadata
     */
    #[McpTool(
        name: 'wiki_get_page_history',
        description: 'Get version history for a wiki page.',
        annotations: new ToolAnnotations(readOnlyHint: true)
    )]
    public function getPageHistory(
        string $page,
        int $offset = 0,
        int $limit = 10
    ): array {
        $limit = min(max($limit, 1), 50);
        $offset = max($offset, 0);

        // Verify page exists
        $exists = $this->context->captureOutput(
            function () use ($page) {
                return \TikiLib::lib('tiki')->page_exists($page);
            }
        );

        if (! $exists) {
            throw new ToolCallException("Page '$page' not found.");
        }

        $history = $this->context->captureOutput(
            function () use ($page, $offset, $limit) {
                // fetchdata=false to skip content for each version (performance)
                return \TikiLib::lib('hist')->get_page_history($page, false, $offset, $limit);
            }
        );

        $versions = [];
        if (! empty($history)) {
            foreach ($history as $entry) {
                $versions[] = [
                    'version' => (int)($entry['version'] ?? 0),
                    'lastModif' => (int)($entry['lastModif'] ?? 0),
                    'user' => $entry['user'] ?? '',
                    'comment' => $entry['comment'] ?? '',
                ];
            }
        }

        // Get total count from page info
        $pageInfo = $this->context->captureOutput(
            function () use ($page) {
                return \TikiLib::lib('tiki')->get_page_info($page, false);
            }
        );
        $total = (int)($pageInfo['version'] ?? count($versions));

        return [
            'versions' => $versions,
            'total' => $total,
            'offset' => $offset,
            'limit' => $limit,
        ];
    }

    /**
     * Full-text search across wiki pages.
     *
     * Uses Tiki's unified search if available, falls back to list_pages with
     * the find parameter for basic title/content matching.
     *
     * @param string $query Search query string (required)
     * @param int    $limit Maximum results to return (default: 10, max: 50)
     * @return array Search results with page names and relevance info
     */
    #[McpTool(
        name: 'wiki_search',
        description: 'Full-text search across wiki pages.',
        annotations: new ToolAnnotations(readOnlyHint: true)
    )]
    public function search(string $query, int $limit = 10): array
    {
        $limit = min(max($limit, 1), 50);

        // Try unified search if available
        global $prefs;
        if (! empty($prefs['feature_search']) && $prefs['feature_search'] === 'y') {
            try {
                return $this->unifiedSearch($query, $limit);
            } catch (\Throwable $e) {
                // Fall through to list_pages fallback
                fwrite(STDERR, "Unified search failed, falling back to list_pages: " . $e->getMessage() . "\n");
            }
        }

        // Fallback: use list_pages with find parameter
        return $this->listPagesSearch($query, $limit);
    }

    /**
     * Create a new wiki page.
     *
     * @param string $page        Page name (required, max 158 characters)
     * @param string $content     Page content in wiki syntax (required)
     * @param string $comment     Creation comment (optional)
     * @param string $description Page description (optional)
     * @param string $lang        Language code e.g. en, fr (optional)
     * @return array Created page info
     */
    #[McpTool(
        name: 'wiki_create_page',
        description: 'Create a new wiki page with content in wiki syntax.',
        annotations: new ToolAnnotations(destructiveHint: false, idempotentHint: false)
    )]
    public function createPage(
        string $page,
        string $content,
        string $comment = '',
        string $description = '',
        string $lang = ''
    ): array {
        // Validate page name length
        if (strlen($page) > 158) {
            throw new ToolCallException("Page name must be 158 characters or less (got " . strlen($page) . ").");
        }

        if (trim($page) === '') {
            throw new ToolCallException("Page name cannot be empty.");
        }

        // Check page doesn't already exist
        $exists = $this->context->captureOutput(
            function () use ($page) {
                return \TikiLib::lib('tiki')->page_exists($page);
            }
        );

        if ($exists) {
            throw new ToolCallException("Page '$page' already exists. Use wiki_update_page to modify it.");
        }

        // Create the page
        $result = $this->context->captureOutput(
            function () use ($page, $content, $comment, $description, $lang) {
                return \TikiLib::lib('tiki')->create_page(
                    $page,
                    0,              // hits
                    $content,
                    time(),         // lastModif
                    $comment,
                    $this->user,
                    $this->ip,       // ip
                    $description,
                    $lang
                );
            }
        );

        if (! $result) {
            throw new ToolCallException("Failed to create page '$page'.");
        }

        return [
            'pageName' => $page,
            'version' => 1,
            'created' => true,
        ];
    }

    /**
     * Update an existing wiki page's content.
     *
     * @param string $page        Page name (required)
     * @param string $content     New page content in wiki syntax (required)
     * @param string $comment     Edit comment describing the change (optional)
     * @param string $description Updated page description (optional, null keeps existing)
     * @return array Updated page info
     */
    #[McpTool(
        name: 'wiki_update_page',
        description: 'Update an existing wiki page with new content.',
        annotations: new ToolAnnotations(destructiveHint: false, idempotentHint: true)
    )]
    public function updatePage(
        string $page,
        string $content,
        string $comment = '',
        string $description = ''
    ): array {
        // Verify page exists
        $info = $this->context->captureOutput(
            function () use ($page) {
                return \TikiLib::lib('tiki')->get_page_info($page, false);
            }
        );

        if (! $info) {
            throw new ToolCallException("Page '$page' not found. Use wiki_create_page to create it.");
        }

        // Update the page
        $this->context->captureOutput(
            function () use ($page, $content, $comment, $description) {
                \TikiLib::lib('tiki')->update_page(
                    $page,
                    $content,
                    $comment,
                    $this->user,
                    $this->ip,                               // ip
                    $description ?: null,                    // null keeps existing
                    0,                                       // is_minor
                    '',                                      // lang (keep existing)
                    null,                                    // is_html (keep existing)
                    null,                                    // hash
                    null,                                    // saveLastModif (use current time)
                    '',                                      // wysiwyg (keep existing)
                    ''                                       // wiki_authors_style
                );
            }
        );

        // Get updated version (skipCache=true to bypass stale cache after write)
        $updatedInfo = $this->context->captureOutput(
            function () use ($page) {
                return \TikiLib::lib('tiki')->get_page_info($page, false, true);
            }
        );

        return [
            'pageName' => $page,
            'version' => (int)($updatedInfo['version'] ?? 0),
            'updated' => true,
        ];
    }

    /**
     * Delete a wiki page and all its versions.
     *
     * @param string $page Page name to delete (required)
     * @return array Deletion confirmation
     */
    #[McpTool(
        name: 'wiki_delete_page',
        description: 'Delete a wiki page and all its version history. This action cannot be undone.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true)
    )]
    public function deletePage(string $page): array
    {
        // Verify page exists
        $exists = $this->context->captureOutput(
            function () use ($page) {
                return \TikiLib::lib('tiki')->page_exists($page);
            }
        );

        if (! $exists) {
            throw new ToolCallException("Page '$page' not found.");
        }

        // Delete all versions
        $result = $this->context->captureOutput(
            function () use ($page) {
                return \TikiLib::lib('tiki')->remove_all_versions($page);
            }
        );

        if (! $result) {
            throw new ToolCallException("Failed to delete page '$page'.");
        }

        return [
            'pageName' => $page,
            'deleted' => true,
        ];
    }

    /**
     * Perform unified search for wiki pages.
     */
    private function unifiedSearch(string $query, int $limit): array
    {
        $searchlib = \TikiLib::lib('unifiedsearch');
        $index = $searchlib->getIndex();

        $searchQuery = new \Search_Query($query);
        $searchQuery->filterType('wiki page');
        $searchQuery->setRange(0, $limit);

        $searchResult = $searchQuery->search($index);

        $results = [];
        foreach ($searchResult as $entry) {
            $results[] = [
                'pageName' => $entry['title'] ?? '',
                'description' => $entry['description'] ?? '',
                'lastModif' => (int)($entry['modification_date'] ?? 0),
                'relevance' => $entry['score'] ?? null,
            ];
        }

        return [
            'results' => $results,
            'total' => $searchResult->count(),
        ];
    }

    /**
     * Fallback search using list_pages with find parameter.
     */
    private function listPagesSearch(string $query, int $limit): array
    {
        $result = $this->context->captureOutput(
            function () use ($query, $limit) {
                return \TikiLib::lib('tiki')->list_pages(0, $limit, 'lastModif_desc', $query, '', false);
            }
        );

        $results = [];
        if (! empty($result['data'])) {
            foreach ($result['data'] as $page) {
                $results[] = [
                    'pageName' => $page['pageName'],
                    'description' => $page['description'] ?? '',
                    'lastModif' => (int)($page['lastModif'] ?? 0),
                    'relevance' => null,
                ];
            }
        }

        return [
            'results' => $results,
            'total' => (int)($result['count'] ?? 0),
        ];
    }
}
