<?php

namespace Tiki\Lib\Importer\Markdown;

use Perms;
use Tiki\Lib\Importer\Markdown\Parser\CommonMarkPurifier;
use Tiki\Lib\Importer\Markdown\Parser\GfmPurifier;
use Tiki\Lib\Importer\Markdown\Parser\LogseqPurifier;
use Tiki\Lib\Importer\Markdown\Parser\MarkdownPipeline;
use Tiki\Lib\Importer\Markdown\Registry\ImportRegistry;
use Tiki\Lib\Importer\Markdown\Scanner\DirectoryScanner;
use Tiki\Lib\Importer\Markdown\Source\SourceManager;
use TikiLib;

/**
 * End-to-end orchestrator:
 * SourceManager (-> temp dir) -> DirectoryScanner (-> files) -> MarkdownPipeline (purify) -> write pages (with history if enabled) -> Garbage Collect delete/mark
 */
class TikiMarkdownImporter
{
    private const STATUS_ERROR = 'error';
    private const STATUS_SKIPPED = 'skipped';

    private const GC_OFF = 'off';
    private const GC_MARK = 'mark';
    private const GC_DELETE = 'delete';

    private const RES_WRITTEN = 'written';
    private const RES_SKIPPED = 'skipped';
    private const RES_FAILED = 'failed';
    private const RES_COUNTS = 'counts';
    private const RES_ERRORS = 'errors';
    private const RES_NOTES = 'notes';
    private const RES_GC_REPORT = 'gc_report';
    private const RES_ORPHANED = 'orphaned';
    private const RES_SOURCE_META = 'source_meta';

    private const CNT_TOTAL = 'total';
    private const CNT_GC_ORPHANS = 'gc_orphans';
    private const CNT_GC_DELETED = 'gc_deleted';
    private const CNT_GC_MARKED = 'gc_marked';

    private const CFG_TYPE = 'type';
    private const CFG_LOCAL_PATH = 'local_path';
    private const GC_OPT_MODE = 'mode';
    private const GC_OPT_SAFE_NS = 'safe_namespace';
    private const PIPE_OPT_FLAVOR = 'flavor';
    private const PIPE_OPT_GC = 'gc';
    private const PIPE_OPT_STRIP_FM = 'strip_front_matter';
    private const PIPE_OPT_RUNTIME_LOGSEQ = 'runtime_logseq';
    private const PIPE_OPT_BLOCKREF = 'blockref_mode';
    private const PIPE_OPT_JOURNAL_NS = 'journal_ns';

    private const SCAN_RELPATH = 'relpath';
    private const SCAN_RELATIVE = 'relative';
    private const SCAN_PAGE_NAME = 'page_name';
    private const SCAN_RAW = 'raw';
    private const SCAN_FILES = 'files';
    private const SCAN_COUNTS = 'counts';

    private const OPT_ROOTS = 'roots';
    private const OPT_RECURSIVE = 'recursive';
    private const OPT_MAX_DEPTH = 'max_depth';
    private const OPT_EXCLUDE_GLOBS = 'exclude_globs';
    private const OPT_TITLE_STRATEGY = 'title_strategy';
    private const OPT_NAMING_MODE = 'naming_mode';
    private const OPT_DIR_LEVELS = 'dir_levels';
    private const OPT_SEPARATOR = 'separator';
    private const OPT_NAMESPACE = 'namespace';
    private const OPT_DETECT_JOURNAL = 'detect_journal';
    private const OPT_JOURNAL_LANG = 'journal_lang';
    private const OPT_MARKDOWN_SOURCE = 'markdown_source';

    private const WIKI_OPT_ROOTS = 'roots';
    private const WIKI_OPT_RECURSIVE = 'recursive';
    private const WIKI_OPT_MAX_DEPTH = 'max_depth';
    private const WIKI_OPT_EXCLUDE_GLOBS = 'exclude_globs';
    private const WIKI_OPT_TITLE_STRATEGY = 'title_strategy';
    private const WIKI_OPT_NAMING_MODE = 'naming_mode';
    private const WIKI_OPT_DIR_LEVELS = 'dir_levels';
    private const WIKI_OPT_SEPARATOR = 'separator';
    private const WIKI_OPT_NAMESPACE = 'namespace';
    private const WIKI_OPT_DETECT_JOURNAL = 'detect_journal';
    private const WIKI_OPT_JOURNAL_LANG = 'journal_lang';
    private const WIKI_OPT_MARKDOWN_SOURCE = 'markdown_source';

    private const FILE_TYPE_JOURNAL = 'journal';
    private const DEFAULT_FLAVOR = 'gfm';
    private const DEFAULT_GC_MODE = self::GC_MARK;
    private const DEFAULT_JOURNAL_NS = 'Journal';

    private array $errors = [];
    private array $notesByPage = [];
    private string $lastError = '';

    public function getErrors(): array
    {
        return $this->errors;
    }
    public function getNotes(): array
    {
        return $this->notesByPage;
    }
    public function getError(): string
    {
        return $this->lastError;
    }

    /**
     * High-level import entrypoint.
     *
     * @param array       $sourceCfg    SourceManager config (type, repo*, …)
     * @param array       $wikiOpts     Scanner/mapping options (roots, recursive…)
     * @param array       $pipelineCfg  ['flavor'=>'logseq|gfm|commonmark', 'gc'=>['mode','safe_namespace']]
     * @param string|null $uploadTmp    optional uploaded temp file (zip/md)
     * @param string|null $uploadName   optional uploaded original name
     * @return array {written:int, skipped:int, counts:array, errors:[], notes:[], gc_report:?array, orphaned:array}
     */
    public function importFromSource(
        array $sourceCfg,
        array $wikiOpts,
        array $pipelineCfg = [],
        ?string $uploadTmp = null,
        ?string $uploadName = null
    ): array {
        $mgr = new SourceManager();
        $dirPath = $mgr->fetchToTempDir($sourceCfg, $uploadTmp, $uploadName);
        $sourceMeta = $mgr->getLastFetchMeta();
        $seenRelpaths = [];
        $isUploadedTemp = false;

        if (! $dirPath) {
            $this->errors[] = $mgr->getError();
            return [self::RES_WRITTEN => 0, self::RES_SKIPPED => 0, self::RES_COUNTS => [self::CNT_TOTAL => 0], self::RES_ERRORS => $this->errors, self::RES_NOTES => [], self::RES_SOURCE_META => $sourceMeta];
        }

        // Detect if temp directory should be cleaned up
        if ($uploadTmp || (isset($sourceCfg[self::CFG_TYPE]) && $sourceCfg[self::CFG_TYPE] === SourceManager::SRC_LOCAL && isset($sourceCfg[self::CFG_LOCAL_PATH]))) {
            $localPath = $sourceCfg[self::CFG_LOCAL_PATH] ?? null;
            if ($uploadTmp || ($localPath && ! is_dir($localPath))) {
                $isUploadedTemp = true; // Extracted ZIP or copied single file
            }
        }

        try {
            // 1) Scan
            $scanner = new DirectoryScanner();
            $preview = $scanner->scan($dirPath, $wikiOpts); // ['files'=>[], 'counts'=>...]

            // 2) Load registry
            $registry  = new ImportRegistry();
            $sourceKey = ImportRegistry::buildSourceKey($sourceCfg);
            $existingMap = $registry->getAllBySourceKey($sourceKey); // relpath => row
            $isFirstRun  = empty($existingMap);
        } finally {
            // Cleanup temp directory if it was created for uploads/extracted ZIPs
            if ($isUploadedTemp && is_dir($dirPath)) {
                $this->rrmdir($dirPath);
            }
        }

        $files   = $preview[self::SCAN_FILES] ?? [];
        $counts  = $preview[self::SCAN_COUNTS] ?? [self::CNT_TOTAL => count($files)];
        $written = 0;
        $skipped = 0;
        $errors  = 0;

        // 3) Pipeline
        $flavor = strtolower($pipelineCfg[self::PIPE_OPT_FLAVOR] ?? self::DEFAULT_FLAVOR);
        $pipeline = (new MarkdownPipeline())
            ->registerPurifier(new CommonMarkPurifier())
            ->registerPurifier(new GfmPurifier())
            ->registerPurifier(new LogseqPurifier());

        $ctx = [
            self::PIPE_OPT_JOURNAL_NS => $wikiOpts[self::PIPE_OPT_JOURNAL_NS] ?? self::DEFAULT_JOURNAL_NS,
            self::PIPE_OPT_STRIP_FM => $pipelineCfg[self::PIPE_OPT_STRIP_FM] ?? true,
            self::PIPE_OPT_RUNTIME_LOGSEQ => $pipelineCfg[self::PIPE_OPT_RUNTIME_LOGSEQ] ?? false,
            self::PIPE_OPT_BLOCKREF => $pipelineCfg[self::PIPE_OPT_BLOCKREF] ?? 'inline',
        ];

        foreach ($files as $f) {
            $relpath = $f[self::SCAN_RELPATH] ?? $f[self::SCAN_RELATIVE] ?? ($f['path'] ?? $f['file'] ?? null);
            $page    = $f[self::SCAN_PAGE_NAME] ?? null;
            $raw     = $f[self::SCAN_RAW] ?? null;

            if (! $relpath || ! $page || ! is_string($raw)) {
                $this->notesByPage['__scanner__'][] = tra('Skipping item (missing relpath/page/raw).');
                continue;
            }

            $seenRelpaths[$relpath] = true;

            // Purify
            $res = $pipeline->purify($flavor, $raw, $ctx);
            $md  = $res['markdown'] ?? $raw;

            // checksum
            $checksum = hash('sha256', $this->canon($md));

            // Unchanged vs registre
            $row = $existingMap[$relpath] ?? null;
            if ($row && hash_equals($row['checksum'], $checksum)) {
                // Retry if last import failed, even if content unchanged
                $lastStatus = $row['last_status'] ?? '';
                if ($lastStatus !== self::STATUS_ERROR) {
                    $skipped++;
                    $this->notesByPage[$page][] = tra('Skipped (no changes via registry).');
                    $this->updateRegistry($registry, $sourceKey, $relpath, $page, $checksum, $f, $raw, self::STATUS_SKIPPED);
                    continue;
                }
                // If last_status='error', fall through to retry the write
            }

            // Write / Update page (with error handling)
            try {
                $ok = $this->writeMarkdownPage($page, $md, tra('Imported from Markdown source'));
                if ($ok) {
                    $written++;
                } else {
                    $skipped++;
                    $this->notesByPage[$page][] = tra('Skipped (no content changes).');
                }

                // Upsert registry (clear orphan status)
                $this->updateRegistry($registry, $sourceKey, $relpath, $page, $checksum, $f, $raw, $ok ? ImportRegistry::STATUS_OK : self::STATUS_SKIPPED);
            } catch (\Exception $e) {
                $errors++;
                $this->notesByPage[$page][] = tra('Error: %0', $e->getMessage());
                $this->updateRegistry($registry, $sourceKey, $relpath, $page, $checksum, $f, $raw, self::STATUS_ERROR);
            }
        }

        // 4) Garbage collect
        $gcCfg  = $pipelineCfg[self::PIPE_OPT_GC] ?? [];
        $gcMode = $gcCfg[self::GC_OPT_MODE] ?? self::DEFAULT_GC_MODE; // off|mark|delete
        if ($isFirstRun && $gcMode === self::GC_DELETE) {
            $gcMode = self::GC_MARK; // security for first run
            $this->notesByPage['__gc__'][] = tra('GC mode downgraded to "mark" on first run for safety.');
        }

        $gcReport = $this->runGarbageCollect(
            $registry,
            $sourceKey,
            array_keys($seenRelpaths),
            [
                self::GC_OPT_MODE => $gcMode, // off|mark|delete
                self::GC_OPT_SAFE_NS => (string)($gcCfg[self::GC_OPT_SAFE_NS] ?? ''),
            ]
        );

        $counts[self::CNT_GC_ORPHANS] = $gcReport['orphans'] ?? 0;
        $counts[self::CNT_GC_DELETED] = $gcReport['deleted'] ?? 0;
        $counts[self::CNT_GC_MARKED]  = $gcReport['marked'] ?? 0;

        if (! empty($gcReport['errors'])) {
            $this->errors = array_merge($this->errors, $gcReport['errors']);
        }

        $orphanedEntries = $registry->getOrphanedBySourceKey($sourceKey);

        return [
            self::RES_WRITTEN => $written,
            self::RES_SKIPPED => $skipped,
            self::RES_FAILED => $errors,
            self::RES_COUNTS => $counts,
            self::RES_ERRORS => $this->errors,
            self::RES_NOTES => $this->notesByPage,
            self::RES_GC_REPORT => $gcReport,
            self::RES_ORPHANED => $orphanedEntries,
            self::RES_SOURCE_META => $sourceMeta,
        ];
    }

    /**
     * Preview only (no write).
     */
    public function previewFromSource(
        array $sourceCfg,
        array $wikiOpts,
        ?string $uploadTmp = null,
        ?string $uploadName = null,
        array $pipelineCtx = []
    ): array {
        $mgr = new SourceManager();
        $dirPath = $mgr->fetchToTempDir($sourceCfg, $uploadTmp, $uploadName);
        $sourceMeta = $mgr->getLastFetchMeta();
        if (! $dirPath) {
            $this->lastError = $mgr->getError() ?: tra('Unknown error fetching source.');
            return ['ok' => false, 'error' => $this->lastError, self::RES_SOURCE_META => $sourceMeta];
        }

        $isUploadedTemp = false;
        if ($uploadTmp || (isset($sourceCfg[self::CFG_TYPE]) && $sourceCfg[self::CFG_TYPE] === SourceManager::SRC_LOCAL && isset($sourceCfg[self::CFG_LOCAL_PATH]))) {
            $localPath = $sourceCfg[self::CFG_LOCAL_PATH] ?? null;
            if ($uploadTmp || ($localPath && ! is_dir($localPath))) {
                $isUploadedTemp = true;
            }
        }

        try {
            $scanner = new DirectoryScanner();
            $scan = $scanner->scan($dirPath, [
                self::OPT_ROOTS => $wikiOpts[self::WIKI_OPT_ROOTS] ?? '.',
                self::OPT_RECURSIVE => $wikiOpts[self::WIKI_OPT_RECURSIVE] ?? true,
                self::OPT_MAX_DEPTH => (int)($wikiOpts[self::WIKI_OPT_MAX_DEPTH] ?? 0),
                self::OPT_EXCLUDE_GLOBS => $wikiOpts[self::WIKI_OPT_EXCLUDE_GLOBS] ?? '',
                self::OPT_TITLE_STRATEGY => $wikiOpts[self::WIKI_OPT_TITLE_STRATEGY] ?? 'fm_h1_filename',
                self::OPT_NAMING_MODE => $wikiOpts[self::WIKI_OPT_NAMING_MODE] ?? 'basename',
                self::OPT_DIR_LEVELS => (int)($wikiOpts[self::WIKI_OPT_DIR_LEVELS] ?? 2),
                self::OPT_SEPARATOR => (string)($wikiOpts[self::WIKI_OPT_SEPARATOR] ?? ' '),
                self::OPT_NAMESPACE => $wikiOpts[self::WIKI_OPT_NAMESPACE] ?? '',
                self::OPT_DETECT_JOURNAL => $wikiOpts[self::WIKI_OPT_DETECT_JOURNAL] ?? true,
                self::OPT_JOURNAL_LANG => $wikiOpts[self::WIKI_OPT_JOURNAL_LANG] ?? 'en',
                self::PIPE_OPT_JOURNAL_NS => $wikiOpts[self::PIPE_OPT_JOURNAL_NS] ?? self::DEFAULT_JOURNAL_NS,
            ]);
        } finally {
            if ($isUploadedTemp && is_dir($dirPath)) {
                $this->rrmdir($dirPath);
            }
        }

        if (empty($scan[self::SCAN_FILES])) {
            return [
                'ok'      => true,
                'counts'  => $scan[self::SCAN_COUNTS] ?? ['pages' => 0, 'journals' => 0, 'total' => 0],
                'dialect' => $wikiOpts[self::WIKI_OPT_MARKDOWN_SOURCE] ?? 'commonmark',
                'items'   => [],
                self::RES_SOURCE_META => $sourceMeta,
            ];
        }

        $pipeline = (new MarkdownPipeline())
            ->registerPurifier(new CommonMarkPurifier())
            ->registerPurifier(new GfmPurifier())
            ->registerPurifier(new LogseqPurifier());

        $dialect = $wikiOpts[self::WIKI_OPT_MARKDOWN_SOURCE] ?? 'commonmark';
        $ctx = array_merge([
            self::PIPE_OPT_JOURNAL_NS => $wikiOpts[self::PIPE_OPT_JOURNAL_NS] ?? self::DEFAULT_JOURNAL_NS,
            self::PIPE_OPT_STRIP_FM => true,
            self::PIPE_OPT_RUNTIME_LOGSEQ => false,
            self::PIPE_OPT_BLOCKREF => 'inline',
        ], $pipelineCtx);

        $items = [];
        foreach ($scan[self::SCAN_FILES] as $f) {
            $page = $f[self::SCAN_PAGE_NAME] ?? null;
            $raw  = $f[self::SCAN_RAW] ?? '';
            if (! $page || ! is_string($raw)) {
                continue;
            }

            $res = $pipeline->purify($dialect, $raw, $ctx);
            $md  = $res['markdown'] ?? $raw;

            $items[] = [
                'page' => $page,
                'type' => $f['type'] ?? 'page',
                'size_bytes' => $f['size_bytes'] ?? strlen($raw),
                'has_front_matter' => $f['has_front_matter'] ?? false,
                'original_preview' => mb_substr(trim($raw), 0, 200),
                'purified_preview' => mb_substr(trim($md), 0, 200),
                'notes' => $res['notes'] ?? [],
            ];
        }

        return [
            'ok'      => true,
            'counts'  => $scan[self::SCAN_COUNTS] ?? [],
            'dialect' => $dialect,
            'items'   => $items,
            self::RES_SOURCE_META => $sourceMeta,
        ];
    }

    /**
     * Create or update a Tiki page with Markdown content, keeping history if enabled.
     */
    private function writeMarkdownPage(string $name, string $markdown, string $comment): bool
    {
        global $prefs;
        $tikilib = TikiLib::lib('tiki');
        $wikilib = TikiLib::lib('wiki');

        // Validate UTF-8 encoding before writing to database
        if (! mb_check_encoding($markdown, 'UTF-8') || str_contains($markdown, "\0")) {
            throw new \RuntimeException(tra('Invalid content encoding for page: %0', $name));
        }

        $user = $GLOBALS['user'] ?? 'import';
        $ip   = method_exists($tikilib, 'get_ip_address') ? $tikilib->get_ip_address() : ($_SERVER['REMOTE_ADDR'] ?? '');

        $content = $this->wrapMarkdown($markdown);

        if ($tikilib->page_exists($name)) {
            $info = $tikilib->get_page_info($name);
            $oldPageContent = $this->extractMarkdownBody($info['data']);
            if ($this->canon($oldPageContent ?? '') === $this->canon($markdown)) {
                return false; // no changes to write
            }

            // Use Tiki's native update_page() which handles history automatically
            $tikilib->update_page(
                $name,           // pageName
                $content,        // edit_data
                $comment,        // edit_comment
                $user,           // edit_user
                $ip              // edit_ip
            );
        } else {
            // Use Tiki's native create_page()
            $tikilib->create_page(
                $name,           // name
                0,               // hits
                $content,        // data
                $tikilib->now,   // lastModif
                $comment,        // comment
                $user,           // user
                $ip,             // ip
                '',              // description
                '',              // lang
                0                // is_html
            );
        }

        // refresh relations/links if supported
        if (method_exists($wikilib, 'update_wikicontent_relations')) {
            $wikilib->update_wikicontent_relations($content, 'wiki page', $name, true);
        }
        if (method_exists($wikilib, 'update_wikicontent_links')) {
            $wikilib->update_wikicontent_links($content, 'wiki page', $name, true);
        }

        return true;
    }

    /**
     * Wrap raw markdown in Tiki syntax tags.
     */
    private function wrapMarkdown(string $body): string
    {
        return '{syntax type="markdown" editor="plain"}' . $body;
    }

    /**
     * Extract markdown body from Tiki syntax wrapper.
     */
    private function extractMarkdownBody(string $data): ?string
    {
        if (preg_match('~^\{syntax\s+type="markdown"[^}]*\}(.*)\z~s', $data, $m)) {
            return $m[1];
        }
        return null;
    }

    private function canon(string $s): string
    {
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = preg_replace('/[ \t]+$/m', '', $s);
        $s = rtrim($s, "\n");
        return $s;
    }

    /**
     * Update registry entry for a file
     */
    private function updateRegistry(
        ImportRegistry $registry,
        string $sourceKey,
        string $relpath,
        string $pageName,
        string $checksum,
        array $fileData,
        string $rawContent,
        string $status
    ): void {
        try {
            $registry->upsert([
                'source_key'    => $sourceKey,
                'relpath'       => $relpath,
                'page_name'     => $pageName,
                'checksum'      => $checksum,
                'size_bytes'    => $fileData['size_bytes'] ?? strlen($rawContent),
                'mtime_utc'     => $fileData['mtime_utc'] ?? null,
                'last_imported' => TikiLib::lib('tiki')->now,
                'last_status'   => $status,
                'error_note'    => null,
                'is_journal'    => ($fileData['type'] ?? '') === self::FILE_TYPE_JOURNAL ? 1 : 0,
                'orphaned_at'   => null, // Clear orphan status
            ]);
        } catch (\Exception $e) {
            $this->errors[] = tra('Failed to update registry for %0: %1', $relpath, $e->getMessage());
        }
    }

    /**
     * Garbage collect orphaned pages from registry.
     *
     * GARBAGE COLLECTION WORKFLOW:
     * Runs automatically after each import to detect and handle files that were
     * present in previous imports but are missing from the current import.
     *
     * Detection:
     * - Compares files seen in current import ($seenRelpaths) against all registry
     *   entries for this source ($registry->getAllBySourceKey($sourceKey))
     * - Files in registry but not in current import = orphans
     *
     * Handling Modes:
     * - 'mark' (default): Sets last_status='orphan' and records orphaned_at timestamp
     *   via markOrphan(). Pages remain in Tiki, registry entry preserved for audit.
     * - 'delete': Deletes the actual Tiki page AND removes registry entry via deleteEntry().
     *   This is the only place deleteEntry() is called - it's part of GC cleanup.
     * - 'off': No garbage collection performed.
     *
     * Safety Mechanisms:
     * - First run protection: Always uses 'mark' mode on first import (even if 'delete'
     *   is configured) to prevent accidental deletion of existing pages.
     * - safe_namespace: Restricts deletion to specific page namespace prefix (e.g., 'Journal-').
     *   Pages outside this namespace are marked as orphans but never deleted, even in 'delete' mode.
     *
     * Registry Cleanup:
     * - When page is deleted (delete mode), registry entry is removed via deleteEntry().
     * - Marked entries (mark mode) remain in registry for audit/recovery purposes.
     *
     * @param ImportRegistry $registry
     * @param string         $sourceKey
     * @param array          $seenRelpaths  Relpaths of files found in current import
     * @param array          $opts          ['mode'=>'off|mark|delete', 'safe_namespace'=>'Journal']
     * @return array ['orphans'=>int, 'deleted'=>int, 'marked'=>int, 'errors'=>[]]
     */
    private function runGarbageCollect(ImportRegistry $registry, string $sourceKey, array $seenRelpaths, array $opts): array
    {
        $tikilib = TikiLib::lib('tiki');

        $mode   = $opts[self::GC_OPT_MODE] ?? self::DEFAULT_GC_MODE; // off|mark|delete
        $safeNS = trim((string)($opts[self::GC_OPT_SAFE_NS] ?? ''));

        if ($mode === self::GC_OFF) {
            return ['orphans' => 0, 'deleted' => 0, 'marked' => 0, 'errors' => []];
        }
        if (empty($seenRelpaths)) {
            return ['orphans' => 0, 'deleted' => 0, 'marked' => 0, 'errors' => [tra('No seen relpaths provided for garbage collection.')]];
        }

        $now      = $tikilib->now;
        $seenMap  = array_flip($seenRelpaths);
        $existing = $registry->getAllBySourceKey($sourceKey); // relpath => row

        $orphans = [];
        foreach ($existing as $rel => $row) {
            if (! isset($seenMap[$rel])) {
                $orphans[] = $row;
            }
        }

        $deleted = 0;
        $marked  = 0;
        $errors  = [];

        foreach ($orphans as $row) {
            $page = $row['page_name'] ?? '';
            $rel = $row['relpath'] ?? '';

            if ($mode === self::GC_MARK) {
                $registry->markOrphan($sourceKey, $rel, $now);
                $marked++;
                continue;
            }

            // mode delete : limit on authorized namespace
            if ($safeNS !== '' && ! str_starts_with((string)$page, $safeNS)) {
                $registry->markOrphan($sourceKey, $rel, $now);
                $marked++;
                continue;
            }

            try {
                $ok = $tikilib->remove_all_versions($page);
                if ($ok) {
                    $deleted++;
                    $registry->deleteEntry($sourceKey, $rel);
                } else {
                    $errors[] = "Delete failed: $page";
                    $registry->markOrphan($sourceKey, $rel, $now);
                }
            } catch (\Throwable $e) {
                $errors[] = "Delete error: $page - " . $e->getMessage();
                $registry->markOrphan($sourceKey, $rel, $now);
            }
        }

        if ($deleted > 0) {
            $cachelib = TikiLib::lib('cache');
            $cachelib->invalidateAll('menu');
            $cachelib->invalidateAll('structure');
        }

        return ['orphans' => count($orphans), 'deleted' => $deleted, 'marked' => $marked, 'errors' => $errors];
    }

    /**
     * Recursively remove directory.
     */
    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            if ($file->isDir()) {
                if (! rmdir($file->getPathname())) {
                    trigger_error(tra('Failed to remove directory: %0', $file->getPathname()), E_USER_WARNING);
                }
            } else {
                if (! unlink($file->getPathname())) {
                    trigger_error(tra('Failed to remove file: %0', $file->getPathname()), E_USER_WARNING);
                }
            }
        }
        if (! rmdir($dir)) {
            trigger_error(tra('Failed to remove directory: %0', $dir), E_USER_WARNING);
        }
    }
}
