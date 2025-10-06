<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Importer\Markdown;

use Tiki\Lib\Importer\Markdown\Parser\CommonMarkPurifier;
use Tiki\Lib\Importer\Markdown\Parser\GfmPurifier;
use Tiki\Lib\Importer\Markdown\Parser\LogseqPurifier;
use Tiki\Lib\Importer\Markdown\Source\SourceManager;
use Tiki\Lib\Importer\Markdown\Scanner\ZipScanner;
use Tiki\Lib\Importer\Markdown\Parser\MarkdownPipeline;
use TikiLib;

/**
 * End-to-end orchestrator:
 *   SourceManager (-> temp zip) -> ZipScanner (-> files) -> MarkdownPipeline (purify) -> write pages (with history if enabled).
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
     * @param array $sourceCfg   SourceManager config (type, ftp*, repo*, …)
     * @param array $wikiOpts    Scanner/mapping options (roots, recursive…)
     * @param array $pipelineCfg ['flavor'=>'logseq|gfm|commonmark', …]
     * @param string|null $uploadTmp   optional uploaded temp file (zip/md)
     * @param string|null $uploadName  optional uploaded original name
     * @return array {written:int, counts:array, errors:[], notes:[]}
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
        if (! $zip) {
            $this->errors[] = $mgr->getError();
            return ['written' => 0, 'counts' => ['total' => 0], 'errors' => $this->errors, 'notes' => []];
        }

        try {
            $scanner  = new ZipScanner();
            $preview  = $scanner->scan($zip, $wikiOpts); // ['files'=>[], 'counts'=>...]
        } finally {
            @unlink($zip);
        }

        $files   = $preview['files'] ?? [];
        $counts  = $preview['counts'] ?? ['total' => count($files)];
        $written = 0;

        $flavor   = strtolower($pipelineCfg['flavor'] ?? 'gfm');

        $pipeline = (new MarkdownPipeline())
            ->registerPurifier(new CommonMarkPurifier())
            ->registerPurifier(new GfmPurifier())
            ->registerPurifier(new LogseqPurifier());

        // Context passed to purifiers (keep defaults friendly for Logseq)
        $ctx = [
            'journal_ns'         => $wikiOpts['journal_ns'] ?? 'Journal',
            'strip_front_matter' => $pipelineCfg['strip_front_matter'] ?? true,
            'runtime_logseq'     => $pipelineCfg['runtime_logseq'] ?? false,
            'blockref_mode'      => $pipelineCfg['blockref_mode'] ?? 'inline',
        ];

        foreach ($files as $f) {
            $page = $f['page_name'] ?? null;
            $raw  = $f['raw'] ?? null;
            if (! $page || ! is_string($raw)) {
                continue;
            }

            $res = $pipeline->purify($flavor, $raw, $ctx);
            $md  = $res['markdown'] ?? $raw;

            if (! empty($res['notes'])) {
                $this->notesByPage[$page] = $res['notes'];
            }

            if ($this->writeMarkdownPage($page, $md, 'Markdown import')) {
                $written++;
            }
        }

        return [
            'written' => $written,
            'counts'  => $counts,
            'errors'  => $this->errors,
            'notes'   => $this->notesByPage,
        ];
    }

    /**
     * Fetch -> Scan -> Purify preview (no write).
     *
     * @param array $sourceCfg SourceManager config (type, ftp*, repo*, …)
     * @param array $wikiOpts Options wiki/mapping + 'markdown_source'
     * @param string|null $uploadTmp  Temp path of uploaded ZIP/MD (optionnel)
     * @param string|null $uploadName  Original name of uploaded file (optionnel)
     * @param array $pipelineCtx Context passed to purifiers (journal_ns, strip_front_matter, runtime_logseq, blockref_mode)
     *
     * @return array ['ok'=>bool, 'counts'=>[], 'dialect'=>string, 'items'=>[], 'error'?=>string]
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
            // 1) Scan ZIP
            $scanner = new ZipScanner();
            $scan = $scanner->scan($zip, [
                'roots'          => $wikiOpts['roots'] ?? '.',
                'recursive'      => $wikiOpts['recursive'] ?? true,
                'max_depth'      => (int) ($wikiOpts['max_depth'] ?? 0),
                'exclude_globs'  => $wikiOpts['exclude_globs'] ?? '',
                'title_strategy' => $wikiOpts['title_strategy'] ?? 'fm_h1_filename',
                'naming_mode'    => $wikiOpts['naming_mode'] ?? 'basename',
                'dir_levels'     => (int) ($wikiOpts['dir_levels'] ?? 2),
                'separator'      => (string) ($wikiOpts['separator'] ?? ' '),
                'namespace'      => $wikiOpts['namespace'] ?? '',
                'detect_journal' => $wikiOpts['detect_journal'] ?? true,
                'journal_lang'   => $wikiOpts['journal_lang'] ?? 'en',
                'journal_ns'     => $wikiOpts['journal_ns'] ?? 'Journal',
            ]);
        } finally {
            @unlink($zip);
        }

        // If no files, return early
        if (empty($scan['files'])) {
            return [
                'ok'      => true,
                'counts'  => $scan['counts'] ?? ['pages' => 0, 'journals' => 0, 'total' => 0],
                'dialect' => $wikiOpts['markdown_source'] ?? 'logseq',
                'items'   => [],
            ];
        }

        // 2) Pipeline + purifiers
        $pipeline = (new MarkdownPipeline())
            ->registerPurifier(new CommonMarkPurifier())
            ->registerPurifier(new GfmPurifier())
            ->registerPurifier(new LogseqPurifier());

        $dialect = $wikiOpts['markdown_source'] ?? 'logseq';

        // Context passed to purifiers (keep defaults friendly for Logseq)
        $ctx = array_merge([
            'journal_ns' => $wikiOpts['journal_ns'] ?? 'Journal',
            'strip_front_matter' => true,
            'runtime_logseq' => false,
            'blockref_mode' => 'inline',   // inline | code | keep
        ], $pipelineCtx);

        // 3) Purify each file to preview cleaned excerpt (no write)
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
                'type'              => $f['type'] ?? 'pages',
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

        // Tiki Markdown wrapper
        $content = '{syntax type="markdown" editor="plain"}' . $markdown;

        if ($tikilib->page_exists($name)) {
            if (($prefs['feature_history'] ?? 'y') === 'y') {
                $info = $tikilib->get_page_info($name);
                $newVersion = (int) $info['version'] + 1;

                // push current into tiki_history
                $tikilib->query(
                    'INSERT INTO `tiki_history`
                      (`pageName`,`version`,`lastModif`,`user`,`ip`,`comment`,`data`,`description`)
                      VALUES (?,?,?,?,?,?,?,?)',
                    [
                        $name,
                        $newVersion,
                        (int) $tikilib->now,
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
                        (int) strlen($content),
                        (int) $tikilib->now,
                        $user,
                        $ip,
                        $comment,
                        $newVersion,
                        $name,
                    ]
                );
            } else {
                // update without history
                $tikilib->query(
                    'UPDATE `tiki_pages`
                         SET `data`=?, `page_size`=?, `lastModif`=?, `user`=?, `ip`=?, `comment`=?
                       WHERE `pageName`=?',
                    [
                        $content,
                        (int) strlen($content),
                        (int) $tikilib->now,
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

        // refresh relations/links if available
        if (method_exists($wikilib, 'update_wikicontent_relations')) {
            $wikilib->update_wikicontent_relations($content, 'wiki page', $name, true);
        }
        if (method_exists($wikilib, 'update_wikicontent_links')) {
            $wikilib->update_wikicontent_links($content, 'wiki page', $name, true);
        }

        return true;
    }
}
