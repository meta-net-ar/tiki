<?php

namespace Tiki\Lib\Importer\Markdown;

use Perms;
use Tiki\Lib\Importer\Markdown\Parser\CommonMarkPurifier;
use Tiki\Lib\Importer\Markdown\Parser\GfmPurifier;
use Tiki\Lib\Importer\Markdown\Parser\LogseqPurifier;
use Tiki\Lib\Importer\Markdown\Parser\MarkdownPipeline;
use Tiki\Lib\Importer\Markdown\Registry\ImportRegistry;
use Tiki\Lib\Importer\Markdown\Scanner\ZipScanner;
use Tiki\Lib\Importer\Markdown\Source\SourceManager;
use TikiLib;

/**
 * End-to-end orchestrator:
 * SourceManager (-> temp zip) -> ZipScanner (-> files) -> MarkdownPipeline (purify) -> write pages (with history if enabled) -> Garbage Collect delete/mark
 */
class TikiMarkdownImporter
{
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
     * @return array {written:int, skipped:int, counts:array, errors:[], notes:[], gc_report:?array}
     */
    public function importFromSource(
        array $sourceCfg,
        array $wikiOpts,
        array $pipelineCfg = [],
        ?string $uploadTmp = null,
        ?string $uploadName = null
    ): array {
        $mgr = new SourceManager();
        $zip = $mgr->fetchToTempZip($sourceCfg, $uploadTmp, $uploadName);
        $seenRelpaths = [];

        if (! $zip) {
            $this->errors[] = $mgr->getError();
            return ['written' => 0, 'skipped' => 0, 'counts' => ['total' => 0], 'errors' => $this->errors, 'notes' => []];
        }

        try {
            // 1) Scan
            $scanner = new ZipScanner();
            $preview = $scanner->scan($zip, $wikiOpts); // ['files'=>[], 'counts'=>...]

            // 2) Registre avant import
            $registry  = new ImportRegistry();
            $sourceKey = ImportRegistry::buildSourceKey($sourceCfg);
            $existingMap = $registry->getAllBySourceKey($sourceKey); // relpath => row
            $isFirstRun  = empty($existingMap);
        } finally {
            @unlink($zip);
        }

        $files   = $preview['files'] ?? [];
        $counts  = $preview['counts'] ?? ['total' => count($files)];
        $written = 0;
        $skipped = 0;

        // 3) Pipeline
        $flavor = strtolower($pipelineCfg['flavor'] ?? 'gfm');
        $pipeline = (new MarkdownPipeline())
            ->registerPurifier(new CommonMarkPurifier())
            ->registerPurifier(new GfmPurifier())
            ->registerPurifier(new LogseqPurifier());

        $ctx = [
            'journal_ns'         => $wikiOpts['journal_ns'] ?? 'Journal',
            'strip_front_matter' => $pipelineCfg['strip_front_matter'] ?? true,
            'runtime_logseq'     => $pipelineCfg['runtime_logseq'] ?? false,
            'blockref_mode'      => $pipelineCfg['blockref_mode'] ?? 'inline',
        ];

        foreach ($files as $f) {
            // relpath robuste (aligne ZipScanner)
            $relpath = $f['relpath'] ?? $f['relative'] ?? ($f['path'] ?? $f['file'] ?? null);
            $page    = $f['page_name'] ?? null;
            $raw     = $f['raw'] ?? null;

            if (! $relpath || ! $page || ! is_string($raw)) {
                $this->notesByPage['__scanner__'][] = 'Skipping item (missing relpath/page/raw).';
                continue;
            }

            $seenRelpaths[$relpath] = true;

            // Purify
            $res = $pipeline->purify($flavor, $raw, $ctx);
            $md  = $res['markdown'] ?? $raw;

            // checksum du markdown purifié
            $checksum = hash('sha256', $this->canon($md));

            // Unchanged vs registre
            $row = $existingMap[$relpath] ?? null;
            if ($row && hash_equals($row['checksum'], $checksum)) {
                $skipped++;
                $this->notesByPage[$page][] = 'Skipped (no changes via registry).';
                $this->updateRegistry($registry, $sourceKey, $relpath, $page, $checksum, $f, $raw, 'skipped');
                continue;
            }

            // Write / Update page
            $ok = $this->writeMarkdownPage($page, $md, 'Markdown import');
            if ($ok) {
                $written++;
            } else {
                $skipped++;
                $this->notesByPage[$page][] = 'Skipped (no changes in DB).';
            }

            // Upsert registry (clear orphan status)
            $this->updateRegistry($registry, $sourceKey, $relpath, $page, $checksum, $f, $raw, $ok ? 'ok' : 'skipped');
        }

        // 4) Garbage collect
        $gcCfg  = $pipelineCfg['gc'] ?? [];
        $gcMode = $gcCfg['mode'] ?? 'mark'; // off|mark|delete
        if ($isFirstRun && $gcMode === 'delete') {
            $gcMode = 'mark'; // security for first run
            $this->notesByPage['__gc__'][] = 'GC mode forced to "mark" on first run.';
        }

        $gcReport = $this->runGarbageCollect(
            $registry,
            $sourceKey,
            array_keys($seenRelpaths),
            [
                'mode'           => $gcMode, // off|mark|delete
                'safe_namespace' => (string)($gcCfg['safe_namespace'] ?? ''),
            ]
        );

        $counts['gc_orphans'] = $gcReport['orphans'] ?? 0;
        $counts['gc_deleted'] = $gcReport['deleted'] ?? 0;
        $counts['gc_marked']  = $gcReport['marked'] ?? 0;

        if (! empty($gcReport['errors'])) {
            $this->errors = array_merge($this->errors, $gcReport['errors']);
        }

        return [
            'written'   => $written,
            'skipped'   => $skipped,
            'counts'    => $counts,
            'errors'    => $this->errors,
            'notes'     => $this->notesByPage,
            'gc_report' => $gcReport,
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
        $zip = $mgr->fetchToTempZip($sourceCfg, $uploadTmp, $uploadName);
        if (! $zip) {
            $this->lastError = $mgr->getError() ?: 'Unable to fetch source ZIP.';
            return ['ok' => false, 'error' => $this->lastError];
        }

        try {
            $scanner = new ZipScanner();
            $scan = $scanner->scan($zip, [
                'roots'          => $wikiOpts['roots'] ?? '.',
                'recursive'      => $wikiOpts['recursive'] ?? true,
                'max_depth'      => (int)($wikiOpts['max_depth'] ?? 0),
                'exclude_globs'  => $wikiOpts['exclude_globs'] ?? '',
                'title_strategy' => $wikiOpts['title_strategy'] ?? 'fm_h1_filename',
                'naming_mode'    => $wikiOpts['naming_mode'] ?? 'basename',
                'dir_levels'     => (int)($wikiOpts['dir_levels'] ?? 2),
                'separator'      => (string)($wikiOpts['separator'] ?? ' '),
                'namespace'      => $wikiOpts['namespace'] ?? '',
                'detect_journal' => $wikiOpts['detect_journal'] ?? true,
                'journal_lang'   => $wikiOpts['journal_lang'] ?? 'en',
                'journal_ns'     => $wikiOpts['journal_ns'] ?? 'Journal',
            ]);
        } finally {
            @unlink($zip);
        }

        if (empty($scan['files'])) {
            return [
                'ok'      => true,
                'counts'  => $scan['counts'] ?? ['pages' => 0, 'journals' => 0, 'total' => 0],
                'dialect' => $wikiOpts['markdown_source'] ?? 'commonmark',
                'items'   => [],
            ];
        }

        $pipeline = (new MarkdownPipeline())
            ->registerPurifier(new CommonMarkPurifier())
            ->registerPurifier(new GfmPurifier())
            ->registerPurifier(new LogseqPurifier());

        $dialect = $wikiOpts['markdown_source'] ?? 'commonmark';
        $ctx = array_merge([
            'journal_ns'         => $wikiOpts['journal_ns'] ?? 'Journal',
            'strip_front_matter' => true,
            'runtime_logseq'     => false,
            'blockref_mode'      => 'inline',
        ], $pipelineCtx);

        $items = [];
        foreach ($scan['files'] as $f) {
            $page = $f['page_name'] ?? null;
            $raw  = $f['raw'] ?? '';
            if (! $page || ! is_string($raw)) {
                continue;
            }

            $res = $pipeline->purify($dialect, $raw, $ctx);
            $md  = $res['markdown'] ?? $raw;

            $items[] = [
                'page'              => $page,
                'type'              => $f['type'] ?? 'page',
                'size_bytes'        => $f['size_bytes'] ?? strlen($raw),
                'has_front_matter'  => $f['has_front_matter'] ?? false,
                'original_preview'  => mb_substr(trim($raw), 0, 200),
                'purified_preview'  => mb_substr(trim($md), 0, 200),
                'notes'             => $res['notes'] ?? [],
            ];
        }

        return [
            'ok'      => true,
            'counts'  => $scan['counts'] ?? [],
            'dialect' => $dialect,
            'items'   => $items,
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

        $user = $GLOBALS['user'] ?? 'import';
        $ip   = method_exists($tikilib, 'get_ip_address') ? $tikilib->get_ip_address() : ($_SERVER['REMOTE_ADDR'] ?? '');

        $content = $this->wrapMarkdown($markdown); // wrapper fermé

        if ($tikilib->page_exists($name)) {
            $info = $tikilib->get_page_info($name);
            $oldPageContent = $this->extractMarkdownBody($info['data']);
            if ($this->canon($oldPageContent ?? '') === $this->canon($markdown)) {
                return false; // rien à écrire
            }

            if (($prefs['feature_history'] ?? 'y') === 'y') {
                $newVersion = (int)$info['version'] + 1;

                // push current into tiki_history
                $tikilib->query(
                    'INSERT INTO `tiki_history`
                       (`pageName`,`version`,`lastModif`,`user`,`ip`,`comment`,`data`,`description`)
                     VALUES (?,?,?,?,?,?,?,?)',
                    [
                        $name,
                        $newVersion,
                        (int)$tikilib->now,
                        $user,
                        $ip,
                        $comment,
                        $info['data'],
                        $info['description'] ?? '',
                    ]
                );

                // update page
                $tikilib->query(
                    'UPDATE `tiki_pages`
                       SET `data`=?, `page_size`=?, `lastModif`=?, `user`=?, `ip`=?, `comment`=?, `version`=?
                     WHERE `pageName`=?',
                    [
                        $content,
                        (int)strlen($content),
                        (int)$tikilib->now,
                        $user,
                        $ip,
                        $comment,
                        $newVersion,
                        $name,
                    ]
                );
            } else {
                // update sans historique
                $tikilib->query(
                    'UPDATE `tiki_pages`
                       SET `data`=?, `page_size`=?, `lastModif`=?, `user`=?, `ip`=?, `comment`=?
                     WHERE `pageName`=?',
                    [
                        $content,
                        (int)strlen($content),
                        (int)$tikilib->now,
                        $user,
                        $ip,
                        $comment,
                        $name,
                    ]
                );
            }
        } else {
            // create
            $tikilib->create_page(
                $name,
                0,
                $content,
                $tikilib->now,
                $comment,
                $user,
                $ip,
                '',
                '',
                0
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
                'is_journal'    => ($fileData['type'] ?? '') === 'journal' ? 1 : 0,
                'orphaned_at'   => null, // Clear orphan status
            ]);
        } catch (\Exception $e) {
            $this->errors[] = "Registry update failed for {$pageName}: " . $e->getMessage();
        }
    }

    /**
     * Garbage collect orphaned pages per registry (minimal options).
     * @param ImportRegistry $registry
     * @param string         $sourceKey
     * @param array          $seenRelpaths
     * @param array          $opts ['mode'=>'off|mark|delete', 'safe_namespace'=>'Journal']
     * @return array ['orphans'=>int, 'deleted'=>int, 'marked'=>int, 'errors'=>[]]
     */
    private function runGarbageCollect(ImportRegistry $registry, string $sourceKey, array $seenRelpaths, array $opts): array
    {
        $tikilib = TikiLib::lib('tiki');

        $mode   = $opts['mode'] ?? 'mark'; // off|mark|delete
        $safeNS = trim((string)($opts['safe_namespace'] ?? ''));

        if ($mode === 'off') {
            return ['orphans' => 0, 'deleted' => 0, 'marked' => 0, 'errors' => []];
        }
        if (empty($seenRelpaths)) {
            return ['orphans' => 0, 'deleted' => 0, 'marked' => 0, 'errors' => ['GC skipped: no files scanned']];
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

            if ($mode === 'mark') {
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

        return [
            'orphans' => count($orphans),
            'deleted' => $deleted,
            'marked'  => $marked,
            'errors'  => $errors,
        ];
    }
}
