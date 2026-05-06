<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Mcp\Tools;

use Mcp\Capability\Attribute\McpTool;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * MCP tools for wiki page operations.
 *
 * All tools use the Services Broker to call controller actions,
 * inheriting permission checks, feature-flag guards (via setUp()),
 * input sanitization (JitFilter), and cross-cutting concerns
 * (multilingual, geolocation, categories, etc.).
 *
 * Output buffering wraps all calls to prevent stdout corruption of JSON-RPC.
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

    private string $user;
    private string $ip;

    public function __construct(string $user, string $ip = '')
    {
        $this->user = $user;
        $this->ip = $ip;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Read tools
    // ──────────────────────────────────────────────────────────────────────

    /**
     * List wiki pages with optional filtering and pagination.
     *
     * Uses the broker to call Services_Wiki_Controller::action_pages(),
     * which checks Perms::get()->view and returns list_pages() data.
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

        $result = $this->brokerCall('wiki', 'pages', [
            'offset' => $offset,
            'maxRecords' => $limit,
            'sortMode' => $sort,
            'find' => $search,
            'initial' => $initial,
        ]);

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
     * Uses the broker to call Services_Wiki_Controller::action_get_page()
     * with raw=1, which returns raw wiki syntax instead of parsed HTML.
     * The controller handles permission checks and feature-flag guards.
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
        $result = $this->brokerCall('wiki', 'get_page', [
            'page' => $page,
            'raw' => 1,
            'nocache' => 1,
        ]);

        return [
            'pageName' => $result['pageName'],
            'data' => $result['data'] ?? '',
            'description' => $result['description'] ?? '',
            'lastModif' => (int)($result['lastModif'] ?? 0),
            'user' => $result['user'] ?? '',
            'creator' => $result['creator'] ?? '',
            'version' => (int)($result['version'] ?? 0),
            'lang' => $result['lang'] ?? '',
            'is_html' => (bool)($result['is_html'] ?? false),
            'page_size' => (int)($result['page_size'] ?? 0),
            'comment' => $result['comment'] ?? '',
        ];
    }

    /**
     * Get version history for a wiki page.
     *
     * Uses the broker to call Services_Wiki_Controller::action_history(),
     * which checks permissions and feature-flag guards.
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

        $result = $this->brokerCall('wiki', 'history', [
            'page' => $page,
            'offset' => $offset,
            'maxRecords' => $limit,
        ]);

        return [
            'versions' => $result['versions'],
            'total' => $result['total'],
            'offset' => $offset,
            'limit' => $limit,
        ];
    }

    /**
     * Full-text search across wiki pages.
     *
     * Uses the broker to call Services_Search_Controller::action_lookup()
     * with a wiki page type filter. Falls back to listing pages by name
     * when unified search is unavailable.
     *
     * @param string $query Search query string (required)
     * @param int    $limit Maximum results to return (default: 10, max: 50)
     * @return array Search results with page names
     */
    #[McpTool(
        name: 'wiki_search',
        description: 'Full-text search across wiki pages.',
        annotations: new ToolAnnotations(readOnlyHint: true)
    )]
    public function search(string $query, int $limit = 10): array
    {
        $limit = min(max($limit, 1), 50);

        // Try unified search via search controller lookup
        global $prefs;
        if (! empty($prefs['feature_search']) && $prefs['feature_search'] === 'y') {
            try {
                $result = $this->brokerCall('search', 'lookup', [
                    'filter' => ['type' => 'wiki page', 'content' => $query],
                    'maxRecords' => $limit,
                ]);

                $resultset = $result['resultset'];
                $serialized = $resultset->jsonSerialize();
                $results = [];
                foreach ($serialized['result'] as $item) {
                    $results[] = [
                        'pageName' => $item['object_id'],
                        'title' => $item['title'] ?? $item['object_id'],
                    ];
                }
                return ['results' => $results, 'total' => (int)($serialized['count'] ?? 0)];
            } catch (ToolCallException $e) {
                // Fall through to list_pages fallback
            }
        }

        // Fallback: search page names via wiki controller
        $result = $this->brokerCall('wiki', 'pages', [
            'find' => $query,
            'maxRecords' => $limit,
            'sortMode' => 'lastModif_desc',
        ]);

        $results = [];
        foreach ($result['data'] ?? [] as $page) {
            $results[] = [
                'pageName' => $page['pageName'],
                'title' => $page['pageName'],
            ];
        }
        return ['results' => $results, 'total' => (int)($result['count'] ?? 0)];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Write tools -- via Services Broker (controller handles permissions)
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Create a new wiki page.
     *
     * Uses the broker to call Services_Wiki_Controller::action_create_update_page(),
     * which checks permissions and handles multilingual, geolocation, and other
     * cross-cutting concerns.
     *
     * @param string $page        Page name (required)
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
        if (trim($page) === '') {
            throw new ToolCallException("Page name cannot be empty.");
        }

        // Intentional behavior difference from the web UI: the controller's
        // create_update_page with create=1 does not check for existing pages
        // and would silently overwrite. For an AI-driven tool, an explicit
        // "already exists" error is safer than silent overwrite.
        $exists = $this->captureOutput(fn() => \TikiLib::lib('tiki')->page_exists($page));
        if ($exists) {
            throw new ToolCallException("Page '$page' already exists. Use wiki_update_page to modify it.");
        }

        $result = $this->brokerCall('wiki', 'create_update_page', [
            'create' => 1,
            'pageName' => $page,
            'data' => $content,
            'comment' => $comment,
            'description' => $description,
            'lang' => $lang,
        ]);

        return [
            'pageName' => $page,
            'version' => 1,
            'created' => true,
        ];
    }

    /**
     * Update an existing wiki page's content.
     *
     * Uses the broker to call Services_Wiki_Controller::action_create_update_page(),
     * which checks permissions and handles cross-cutting concerns.
     *
     * @param string $page        Page name (required)
     * @param string $content     New page content in wiki syntax (required)
     * @param string $comment     Edit comment describing the change (optional)
     * @param string $description Updated page description (optional, empty keeps existing)
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
        $result = $this->brokerCall('wiki', 'create_update_page', [
            'create' => 0,
            'page' => $page,
            'data' => $content,
            'comment' => $comment,
            'description' => $description,
        ]);

        $info = $result['info'] ?? [];

        return [
            'pageName' => $page,
            'version' => (int)($info['version'] ?? 0),
            'updated' => true,
        ];
    }

    /**
     * Delete a wiki page and all its versions.
     *
     * Uses the broker to call Services_Wiki_Controller::action_remove_pages(),
     * which checks per-page remove permissions via Perms::simpleFilter().
     * CSRF is automatically bypassed because TIKI_API=true (tikiaccesslib.php:459).
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
        // Verify page exists first for a clear error message
        $exists = $this->captureOutput(fn() => \TikiLib::lib('tiki')->page_exists($page));
        if (! $exists) {
            throw new ToolCallException("Page '$page' not found.");
        }

        $this->brokerCallConfirmed('wiki', 'remove_pages', [
            'items' => [$page],
            'version' => 'all',
            'one' => false,
        ]);

        return [
            'pageName' => $page,
            'deleted' => true,
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Call a controller action via the Services Broker, converting exceptions.
     *
     * Uses ServiceLib->internal() which calls Broker->internal() to invoke
     * the action method directly, returning the raw array output without
     * rendering. Input is automatically wrapped in JitFilter by the broker.
     *
     * @param string $controller Controller name (e.g. 'wiki')
     * @param string $action     Action name (e.g. 'pages', 'create_update_page')
     * @param array  $params     Parameters for the action
     * @return mixed Raw return value from the controller action
     * @throws ToolCallException on permission denied, not found, or other service errors
     */
    private function brokerCall(string $controller, string $action, array $params): mixed
    {
        try {
            return $this->captureOutput(
                fn() => \TikiLib::lib('service')->internal($controller, $action, $params)
            );
        } catch (\Services_Exception_Denied $e) {
            throw new ToolCallException("Permission denied: " . $e->getMessage());
        } catch (\Services_Exception_NotFound $e) {
            throw new ToolCallException($e->getMessage());
        } catch (\Services_Exception $e) {
            throw new ToolCallException("Error: " . $e->getMessage());
        } catch (\Throwable $e) {
            error_log('[MCP] Unexpected exception in brokerCall: ' . get_class($e) . ': ' . $e->getMessage());
            throw new ToolCallException("An unexpected error occurred.");
        }
    }

    /**
     * Call a controller action that uses the two-pass confirmation modal.
     *
     * Injects $_POST['confirmForm'] = 'y' to skip the modal and proceed
     * directly to the action. This is the same mechanism ApiBridge uses
     * (ApiBridge.php:36-38). CSRF is bypassed because TIKI_API=true
     * (tikiaccesslib.php:459). State is restored in a finally block.
     *
     * @param string $controller Controller name
     * @param string $action     Action name
     * @param array  $params     Parameters for the action
     * @return mixed Raw return value from the controller action
     * @throws ToolCallException on error
     */
    private function brokerCallConfirmed(string $controller, string $action, array $params): mixed
    {
        $savedPost = $_POST ?? [];
        $_POST = $_POST ?? [];
        $_POST['confirmForm'] = 'y';
        try {
            return $this->brokerCall($controller, $action, $params);
        } finally {
            $_POST = $savedPost;
        }
    }

    /**
     * Execute a callable while capturing any stray output.
     *
     * TikiLib methods sometimes echo directly -- this prevents that
     * from corrupting the JSON-RPC response body. Captured output
     * is redirected to error_log for debugging.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    private function captureOutput(callable $fn): mixed
    {
        ob_start();
        try {
            $result = $fn();
        } finally {
            $output = ob_get_clean();
            if ($output !== '' && $output !== false) {
                error_log('[MCP] Captured stray output: ' . $output);
            }
        }
        return $result;
    }
}
