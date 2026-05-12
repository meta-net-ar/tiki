<?php

namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Lib\Importer\Markdown\TikiMarkdownImporter;
use Tiki\Lib\Importer\Markdown\Source\SourceManager;

#[AsCommand(
    name: 'markdown:importer',
    description: 'Import Markdown files into Tiki wiki pages from local zip or Git repository'
)]
class MarkdownImporterCommand extends Command
{
    // ========== CLI OPTION NAMES ==========
    private const OPT_MODE = 'mode';
    private const OPT_SOURCE_TYPE = 'source-type';
    private const OPT_LOCAL_PATH = 'local-path';
    private const OPT_REPO_URL = 'repo-url';
    private const OPT_REPO_BRANCH = 'repo-branch';
    private const OPT_REPO_TOKEN = 'repo-token';
    private const OPT_GIT_PULL = 'git-pull';
    private const OPT_GIT_TIMEOUT = 'git-timeout';
    private const OPT_ROOTS = 'roots';
    private const OPT_RECURSIVE = 'recursive';
    private const OPT_MAX_DEPTH = 'max-depth';
    private const OPT_EXCLUDE_GLOBS = 'exclude-globs';
    private const OPT_TITLE_STRATEGY = 'title-strategy';
    private const OPT_NAMING_MODE = 'naming-mode';
    private const OPT_DIR_LEVELS = 'dir-levels';
    private const OPT_SEPARATOR = 'separator';
    private const OPT_NAMESPACE = 'namespace';
    private const OPT_DETECT_JOURNAL = 'detect-journal';
    private const OPT_JOURNAL_LANG = 'journal-lang';
    private const OPT_JOURNAL_NS = 'journal-ns';
    private const OPT_MARKDOWN_SOURCE = 'markdown-source';
    private const OPT_STRIP_FM = 'strip-front-matter';
    private const OPT_RUNTIME_LOGSEQ = 'runtime-logseq';
    private const OPT_BLOCKREF = 'blockref-mode';
    private const OPT_GC_MODE = 'gc-mode';
    private const OPT_GC_SAFE_NS = 'gc-safe-namespace';
    private const OPT_JSON = 'json';

    // ========== MODES ==========
    private const MODE_PREVIEW = 'preview';
    private const MODE_IMPORT = 'import';

    // ========== FLAVORS ==========
    private const FLAVOR_COMMONMARK = 'commonmark';
    private const FLAVOR_GFM = 'gfm';
    private const FLAVOR_LOGSEQ = 'logseq';

    // ========== GC MODES ==========
    private const GC_OFF = 'off';
    private const GC_MARK = 'mark';
    private const GC_DELETE = 'delete';
    protected function configure(): void
    {
        $this
            ->setHelp('Import Markdown files from a local .zip/.md or from a Git repository (HTTPS + token).')

            // Mode
            ->addOption(self::OPT_MODE, null, InputOption::VALUE_REQUIRED, 'preview|import', self::MODE_PREVIEW)

            // Source (local|git). We also accept "repo" as alias.
            ->addOption(self::OPT_SOURCE_TYPE, null, InputOption::VALUE_REQUIRED, 'local|git', SourceManager::SRC_LOCAL)

            // Local
            ->addOption(self::OPT_LOCAL_PATH, null, InputOption::VALUE_REQUIRED, 'Path to local .zip OR single .md file')

            // Git (HTTPS + token)
            ->addOption(self::OPT_REPO_URL, null, InputOption::VALUE_REQUIRED, 'Git repository URL (HTTPS), e.g. https://github.com/owner/repo.git')
            ->addOption(self::OPT_REPO_BRANCH, null, InputOption::VALUE_REQUIRED, 'Branch name', 'main')
            ->addOption(self::OPT_REPO_TOKEN, null, InputOption::VALUE_REQUIRED, 'Personal Access Token (PAT) if repository is private', '')
            ->addOption(self::OPT_GIT_PULL, null, InputOption::VALUE_REQUIRED, 'Pull before import (1|0)', '1')
            ->addOption(self::OPT_GIT_TIMEOUT, null, InputOption::VALUE_REQUIRED, 'Git ops timeout (seconds)', '30')

            // Scanner / mapping
            ->addOption(self::OPT_ROOTS, null, InputOption::VALUE_REQUIRED, 'Root(s) in the archive; semicolon/comma-separated', '.')
            ->addOption(self::OPT_RECURSIVE, null, InputOption::VALUE_REQUIRED, 'Recurse into subdirs (1|0)', '1')
            ->addOption(self::OPT_MAX_DEPTH, null, InputOption::VALUE_REQUIRED, 'Max recursion depth (0 = unlimited)', '0')
            ->addOption(self::OPT_EXCLUDE_GLOBS, null, InputOption::VALUE_REQUIRED, 'Glob patterns to exclude', '')
            ->addOption(self::OPT_TITLE_STRATEGY, null, InputOption::VALUE_REQUIRED, 'fm_h1_filename|filename_only|h1_fm_filename', 'fm_h1_filename')
            ->addOption(self::OPT_NAMING_MODE, null, InputOption::VALUE_REQUIRED, 'basename|prefix|suffix', 'basename')
            ->addOption(self::OPT_DIR_LEVELS, null, InputOption::VALUE_REQUIRED, 'Used by prefix/suffix modes', '2')
            ->addOption(self::OPT_SEPARATOR, null, InputOption::VALUE_REQUIRED, 'Separator for path → name', ' ')
            ->addOption(self::OPT_NAMESPACE, null, InputOption::VALUE_REQUIRED, 'Namespace prefix for created pages', '')
            ->addOption(self::OPT_DETECT_JOURNAL, null, InputOption::VALUE_REQUIRED, 'Detect journal pages (1|0)', '1')
            ->addOption(self::OPT_JOURNAL_LANG, null, InputOption::VALUE_REQUIRED, 'en|fr', 'en')
            ->addOption(self::OPT_JOURNAL_NS, null, InputOption::VALUE_REQUIRED, 'Journal page prefix', 'Journal')

            // Markdown flavor / pipeline
            ->addOption(self::OPT_MARKDOWN_SOURCE, null, InputOption::VALUE_REQUIRED, 'logseq|gfm|commonmark', self::FLAVOR_COMMONMARK)
            ->addOption(self::OPT_STRIP_FM, null, InputOption::VALUE_REQUIRED, 'Remove YAML front matter (1|0)', '1')
            ->addOption(self::OPT_RUNTIME_LOGSEQ, null, InputOption::VALUE_REQUIRED, 'Keep [[..]]/((..)) for runtime ext (1|0)', '0')
            ->addOption(self::OPT_BLOCKREF, null, InputOption::VALUE_REQUIRED, 'inline|code|keep', 'inline')

            // Garbage collector delete/mark
            ->addOption(self::OPT_GC_MODE, null, InputOption::VALUE_REQUIRED, 'off|mark|delete', self::GC_MARK)
            ->addOption(self::OPT_GC_SAFE_NS, null, InputOption::VALUE_REQUIRED, 'Only delete inside this page prefix', '')

            // Output
            ->addOption(self::OPT_JSON, null, InputOption::VALUE_NONE, 'Print JSON result instead of human text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;
        $io = new SymfonyStyle($input, $output);

        if (($prefs['markdown_enabled'] ?? 'n') !== 'y') {
            $io->error(tr('Markdown is not enabled in Editing settings.'));
            return Command::FAILURE;
        }

        $mode = strtolower((string)$input->getOption(self::OPT_MODE));
        if (! in_array($mode, [self::MODE_PREVIEW, self::MODE_IMPORT], true)) {
            $io->error('Invalid --mode. Use preview or import.');
            return Command::FAILURE;
        }

        // -------- Source config (même shape que l’UI/Importer) --------
        $srcTypeRaw = strtolower((string)$input->getOption(self::OPT_SOURCE_TYPE) ?: SourceManager::SRC_LOCAL);
        $srcType = ($srcTypeRaw === 'repo') ? SourceManager::SRC_GIT : $srcTypeRaw; // 'repo' is a CLI alias for 'git'
        if (! in_array($srcType, [SourceManager::SRC_LOCAL, SourceManager::SRC_GIT], true)) {
            $srcType = SourceManager::SRC_LOCAL;
        }

        $sourceCfg = ['type' => $srcType];
        $localPath = (string)$input->getOption(self::OPT_LOCAL_PATH);

        if ($srcType === SourceManager::SRC_GIT) {
            $repoUrl = (string)$input->getOption(self::OPT_REPO_URL);
            if ($repoUrl === '') {
                $io->error('For --source-type=git you must provide --repo-url (HTTPS).');
                return Command::FAILURE;
            }

            $sourceCfg += [
                'repo_url'    => $repoUrl,
                'repo_branch' => (string)$input->getOption(self::OPT_REPO_BRANCH) ?: 'main',
                'git_pull'    => $this->toBool($input->getOption(self::OPT_GIT_PULL), true),
                'repo_token'  => (string)$input->getOption(self::OPT_REPO_TOKEN) ?: '',
                'git_timeout' => (int)$input->getOption(self::OPT_GIT_TIMEOUT) ?: 30,
            ];
        } else { // local
            if (! $localPath) {
                $io->error('For --source-type=local you must provide --local-path pointing to a .zip or a .md file.');
                return Command::FAILURE;
            }
            if (! is_readable($localPath)) {
                $io->error('local-path not readable: ' . $localPath);
                return Command::FAILURE;
            }
        }

        // -------- Scanner options --------
        $scanOpts = [
            'roots'          => (string)$input->getOption(self::OPT_ROOTS) ?: '.',
            'recursive'      => $this->toBool($input->getOption(self::OPT_RECURSIVE), true),
            'max_depth'      => (int)$input->getOption(self::OPT_MAX_DEPTH),
            'exclude_globs'  => (string)$input->getOption(self::OPT_EXCLUDE_GLOBS),
            'title_strategy' => (string)$input->getOption(self::OPT_TITLE_STRATEGY) ?: 'fm_h1_filename',
            'naming_mode'    => (string)$input->getOption(self::OPT_NAMING_MODE) ?: 'basename',
            'dir_levels'     => (int)$input->getOption(self::OPT_DIR_LEVELS),
            'separator'      => (string)$input->getOption(self::OPT_SEPARATOR) ?? ' ',
            'namespace'      => (string)$input->getOption(self::OPT_NAMESPACE) ?? '',
            'detect_journal' => $this->toBool($input->getOption(self::OPT_DETECT_JOURNAL), true),
            'journal_lang'   => (string)$input->getOption(self::OPT_JOURNAL_LANG) ?: 'en',
            'journal_ns'     => (string)$input->getOption(self::OPT_JOURNAL_NS) ?: 'Journal',
        ];

        // -------- Markdown pipeline --------
        $flavor = (string)$input->getOption(self::OPT_MARKDOWN_SOURCE) ?: self::FLAVOR_COMMONMARK;
        if (! in_array($flavor, [self::FLAVOR_LOGSEQ, self::FLAVOR_GFM, self::FLAVOR_COMMONMARK], true)) {
            $flavor = self::FLAVOR_COMMONMARK;
        }
        $pipelineCfg = [
            'flavor'             => $flavor,
            'strip_front_matter' => $this->toBool($input->getOption(self::OPT_STRIP_FM), true),
            'runtime_logseq'     => $this->toBool($input->getOption(self::OPT_RUNTIME_LOGSEQ), false),
            'blockref_mode'      => (string)$input->getOption(self::OPT_BLOCKREF) ?: 'inline',
            'gc'                 => [
                'mode'           => (function () use ($input) {
                    $m = strtolower((string)$input->getOption(self::OPT_GC_MODE) ?: self::GC_MARK);
                    return in_array($m, [self::GC_OFF, self::GC_MARK, self::GC_DELETE], true) ? $m : self::GC_MARK;
                })(),
                'safe_namespace' => (string)$input->getOption(self::OPT_GC_SAFE_NS) ?? '',
            ],
        ];

        $jsonOut = (bool)$input->getOption(self::OPT_JSON);

        // -------- Importer --------
        $importer = new TikiMarkdownImporter();
        [$uploadTmp, $uploadName, $tmpZipToDelete] = $this->resolveLocalPayload($srcType, $localPath);

        try {
            if ($mode === self::MODE_PREVIEW) {
                $res = $importer->previewFromSource(
                    $sourceCfg,
                    array_merge($scanOpts, [self::OPT_MARKDOWN_SOURCE => $flavor]),
                    $uploadTmp,
                    $uploadName
                );

                if ($jsonOut) {
                    $this->printJson($io, $res);
                } else {
                    $this->printPreviewHuman($io, $importer, $res);
                }
            } else {
                $result = $importer->importFromSource(
                    $sourceCfg,
                    $scanOpts,
                    $pipelineCfg,
                    $uploadTmp,
                    $uploadName
                );

                if ($jsonOut) {
                    $this->printJson($io, $result);
                } else {
                    $this->printImportHuman($io, $result);
                }
            }
        } catch (\Throwable $e) {
            if ($jsonOut) {
                $io->writeln(json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT));
            } else {
                $io->error($e->getMessage());
            }
            return Command::FAILURE;
        } finally {
            if ($tmpZipToDelete && file_exists($tmpZipToDelete)) {
                unlink($tmpZipToDelete);
            }
        }

        return Command::SUCCESS;
    }

    private function printJson(SymfonyStyle $io, array $payload): void
    {
        $io->writeln(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function printPreviewHuman(SymfonyStyle $io, TikiMarkdownImporter $importer, array $res): void
    {
        if (empty($res['ok'])) {
            $io->error($importer->getError() ?: ($res['error'] ?? 'Preview failed.'));
            return;
        }

        $io->success('Preview OK');
        if (! empty($res['counts'])) {
            $io->writeln(sprintf(
                "Counts: pages=%d journals=%d total=%d",
                (int)($res['counts']['pages'] ?? 0),
                (int)($res['counts']['journals'] ?? 0),
                (int)($res['counts']['total'] ?? 0)
            ));
        }

        if (! empty($res['items'])) {
            $io->section('Sample items');
            foreach (array_slice($res['items'], 0, 5) as $s) {
                $rel = $s['relative'] ?? ($s['page'] ?? '(unknown)');
                $pg  = $s['page'] ?? ($s['page_name'] ?? '?');
                $io->writeln('- ' . $rel . ' → ' . $pg);
                if (! empty($s['purified_preview'])) {
                    $io->writeln("  preview:\n" . $s['purified_preview']);
                }
            }
        }
    }

    private function printImportHuman(SymfonyStyle $io, array $result): void
    {
        $written = (int)($result['written'] ?? 0);
        $io->success(sprintf('%d page(s) imported or updated.', $written));

        if (! empty($result['source_meta']) && is_array($result['source_meta'])) {
            $meta = $result['source_meta'];
            $io->writeln(sprintf(
                'Git sync: mode=%s branch=%s head=%s remote=%s',
                (string)($meta['sync_mode'] ?? 'n/a'),
                (string)($meta['checked_out_branch'] ?? ($meta['requested_branch'] ?? 'n/a')),
                (string)($meta['head'] ?? 'n/a'),
                (string)($meta['remote_head'] ?? 'n/a')
            ));
        }

        if (! empty($result['errors'])) {
            $io->warning('Errors:');
            foreach ($result['errors'] as $e) {
                if ($e) {
                    $io->writeln(' - ' . $e);
                }
            }
        }

        if (! empty($result['notes'])) {
            $io->section('Purifier notes (by page)');
            foreach ($result['notes'] as $page => $notes) {
                $io->writeln('• ' . $page);
                foreach ((array)$notes as $n) {
                    $io->writeln('   - ' . $n);
                }
            }
        }
    }

    /** Accepts "1"/"0", "true"/"false", true/false. Default if null. */
    private function toBool($val, bool $default = false): bool
    {
        if ($val === null) {
            return $default;
        }
        if (is_bool($val)) {
            return $val;
        }
        $v = strtolower((string)$val);
        if (in_array($v, ['1', 'true', 'yes', 'y'], true)) {
            return true;
        }
        if (in_array($v, ['0', 'false', 'no', 'n'], true)) {
            return false;
        }
        return $default;
    }

    /**
     * For source-type=local:
     * - If .zip → pass-through
     * - If .md  → wrap into temp zip (scanner-friendly)
     * Returns [uploadTmp, uploadName, tmpZipCreated]
     */
    private function resolveLocalPayload(string $srcType, ?string $path): array
    {
        if ($srcType !== 'local' || ! $path) {
            return [null, null, null];
        }

        $lower = strtolower((string)$path);
        if (str_ends_with($lower, '.zip')) {
            return [$path, basename($path), null];
        }

        if (str_ends_with($lower, '.md')) {
            $tmpZip = tempnam(sys_get_temp_dir(), 'md_import_') . '.zip';
            $zip = new \ZipArchive();
            if ($zip->open($tmpZip, \ZipArchive::CREATE) !== true) {
                throw new \RuntimeException('Cannot create temporary zip for single .md');
            }
            $zip->addFile($path, 'pages/' . basename($path));
            $zip->close();
            return [$tmpZip, basename($tmpZip), $tmpZip];
        }

        throw new \InvalidArgumentException('local-path must be a .zip or a .md file');
    }
}
