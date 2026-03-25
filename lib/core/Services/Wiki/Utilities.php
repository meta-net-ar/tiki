<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Wiki page utilities for programmatic access.
 *
 * Provides operations that the wiki controller either doesn't expose or
 * returns in a web-specific format (e.g. parsed HTML). This is a proof
 * of concept following the Services_Tracker_Utilities pattern. A follow-up
 * MR will add write methods and migrate the controller to delegate here.
 *
 * Does NOT check permissions -- callers must verify access before calling.
 */
class Services_Wiki_Utilities
{
    public function __construct()
    {
        // Same feature guard as Services_Wiki_Controller::setUp().
        // Ensures wiki data is not exposed when the feature is disabled.
        Services_Exception_Disabled::check('feature_wiki');
    }

    /**
     * Get version history for a wiki page.
     *
     * @return array{versions: array, total: int}
     * @throws Services_Exception_NotFound if page does not exist
     */
    public function getPageHistory(string $page, int $offset = 0, int $limit = 10): array
    {
        if (! TikiLib::lib('tiki')->page_exists($page)) {
            throw new Services_Exception_NotFound(tr('Page "%0" not found', $page));
        }

        // fetchdata=false to skip content for each version (performance)
        $history = TikiLib::lib('hist')->get_page_history($page, false, $offset, $limit);

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

        // Total count from current page version number
        $pageInfo = TikiLib::lib('tiki')->get_page_info($page, false);
        $total = (int)($pageInfo['version'] ?? count($versions));

        return ['versions' => $versions, 'total' => $total];
    }

    /**
     * Search wiki pages.
     *
     * Uses unified search if available, falls back to list_pages with find parameter.
     *
     * @return array{results: array, total: int}
     */
    public function searchPages(string $query, int $limit = 10): array
    {
        global $prefs;

        if (! empty($prefs['feature_search']) && $prefs['feature_search'] === 'y') {
            try {
                return $this->unifiedSearch($query, $limit);
            } catch (\Throwable $e) {
                error_log('[WikiUtilities] Unified search failed, falling back: ' . $e->getMessage());
            }
        }

        return $this->listPagesSearch($query, $limit);
    }

    /**
     * Perform unified search for wiki pages.
     */
    private function unifiedSearch(string $query, int $limit): array
    {
        $searchlib = TikiLib::lib('unifiedsearch');
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
        $result = TikiLib::lib('tiki')->list_pages(0, $limit, 'lastModif_desc', $query, '', false);

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
