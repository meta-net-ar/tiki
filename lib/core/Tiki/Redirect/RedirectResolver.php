<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Redirect;

/**
 * Resolves object-relation redirects stored in tiki_object_relations.
 *
 * The admin "Redirects" panel stores redirect rules with the relation
 * `tiki.link.alias`.  This resolver is used by the routing layer to turn a
 * request path into an HTTP redirect to the configured target.
 */
class RedirectResolver
{
    public const RELATION = 'tiki.link.alias';
    public const DEFAULT_CODE = 301;

    /**
     * Look for a redirect whose source matches the requested path.
     *
     * @param string $path Request path relative to the Tiki root.
     * @return array|null Array with `url` and `code`, or null when no redirect applies.
     */
    public function findRedirectForPath(string $path): ?array
    {
        global $prefs;

        if (empty($prefs['feature_redirects']) || $prefs['feature_redirects'] !== 'y') {
            return null;
        }

        $rows = $this->getRedirectRows();
        if (empty($rows)) {
            return null;
        }

        $normalizedPath = $this->normalizePath($path);

        foreach ($rows as $row) {
            if (! $this->sourceMatches($normalizedPath, $row)) {
                continue;
            }

            $targetUrl = $this->getTargetUrl($row);
            if ($targetUrl === null || $targetUrl === '') {
                continue;
            }

            $targetPath = parse_url($targetUrl, PHP_URL_PATH) ?: $targetUrl;
            if ($this->normalizePath($targetPath) === $normalizedPath) {
                // Avoid redirecting a path to itself.
                continue;
            }

            return [
                'url' => $targetUrl,
                'code' => $this->getRedirectCode($row),
            ];
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getRedirectRows(): array
    {
        global $tikilib;

        if (empty($tikilib)) {
            return [];
        }

        return $tikilib->fetchAll('SELECT * FROM `tiki_object_relations` WHERE `relation` = ?', [self::RELATION]);
    }

    protected function normalizePath(string $path): string
    {
        $path = urldecode($path);
        return ltrim($path, '/');
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function sourceMatches(string $normalizedPath, array $row): bool
    {
        $sourceType = $row['source_type'] ?? '';
        $sourceId = (string) ($row['source_itemId'] ?? '');

        if ($sourceType === 'custom') {
            return $this->normalizePath($sourceId) === $normalizedPath;
        }

        return $this->sourceObjectMatches($normalizedPath, $sourceType, $sourceId);
    }

    protected function sourceObjectMatches(string $normalizedPath, string $type, string $id): bool
    {
        switch ($type) {
            case 'wiki page':
                if ($normalizedPath === $this->normalizePath($id)) {
                    return true;
                }
                $slug = $this->getPageSlug($id);
                return $slug !== null && $this->normalizePath($slug) === $normalizedPath;

            case 'article':
                return (bool) preg_match('/^(?:article|art)' . preg_quote($id, '/') . '(-.*)?$/', $normalizedPath);

            case 'blog post':
                return (bool) preg_match('/^blogpost' . preg_quote($id, '/') . '(-.*)?$/', $normalizedPath);

            case 'blog':
                return (bool) preg_match('/^blog' . preg_quote($id, '/') . '(-.*)?$/', $normalizedPath);
        }

        return false;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function getTargetUrl(array $row): ?string
    {
        $targetType = $row['target_type'] ?? '';
        $targetId = (string) ($row['target_itemId'] ?? '');

        if ($targetType === 'custom') {
            return $targetId ?: null;
        }

        $sefurl = ! empty($GLOBALS['prefs']['feature_sefurl']) && $GLOBALS['prefs']['feature_sefurl'] === 'y';

        switch ($targetType) {
            case 'wiki page':
                if ($sefurl) {
                    $slug = $this->getPageSlug($targetId);
                    return $slug ?: rawurlencode($targetId);
                }
                return 'tiki-index.php?page=' . urlencode($targetId);

            case 'article':
                return $sefurl ? "art{$targetId}" : 'tiki-read_article.php?articleId=' . (int) $targetId;

            case 'blog post':
                return $sefurl ? "blogpost{$targetId}" : 'tiki-view_blog_post.php?postId=' . (int) $targetId;

            case 'blog':
                return $sefurl ? "blog{$targetId}" : 'tiki-view_blog.php?blogId=' . (int) $targetId;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function getRedirectCode(array $row): int
    {
        $code = (int) ($row['source_fieldId'] ?? self::DEFAULT_CODE);
        return in_array($code, [301, 302, 307, 308], true) ? $code : self::DEFAULT_CODE;
    }

    protected function getPageSlug(string $pageName): ?string
    {
        global $tikilib;

        if (empty($tikilib)) {
            return null;
        }

        $slug = $tikilib->getOne('SELECT `pageSlug` FROM `tiki_pages` WHERE `pageName` = ?', [$pageName]);

        return $slug ?: null;
    }
}
