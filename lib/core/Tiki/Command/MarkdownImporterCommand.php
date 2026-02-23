<?php

namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Lib\Importer\Markdown\TikiMarkdownImporter;

#[AsCommand(
    name: 'markdown:importer',
    description: 'Import Markdown files into Tiki wiki pages from local zip or Git repository'
)]
class MarkdownImporterCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('Import Markdown files from a local .zip/.md or from a Git repository (HTTPS + token).')

            // Mode
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'preview|import', 'preview')

            // Source (local|git). We also accept "repo" as alias.
            ->addOption('source-type', null, InputOption::VALUE_REQUIRED, 'local|git', 'local')

            // Local
            ->addOption('local-path', null, InputOption::VALUE_REQUIRED, 'Path to local .zip OR single .md file')

            // Git (HTTPS + token)
            ->addOption('repo-url', null, InputOption::VALUE_REQUIRED, 'Git repository URL (HTTPS), e.g. https://github.com/owner/repo.git')
            ->addOption('repo-branch', null, InputOption::VALUE_REQUIRED, 'Branch name', 'main')
            ->addOption('repo-token', null, InputOption::VALUE_REQUIRED, 'Personal Access Token (PAT) if repository is private', '')
            ->addOption('git-pull', null, InputOption::VALUE_REQUIRED, 'Pull before import (1|0)', '1')
            ->addOption('git-timeout', null, InputOption::VALUE_REQUIRED, 'Git ops timeout (seconds)', '30')

            // Scanner / mapping
            ->addOption('roots', null, InputOption::VALUE_REQUIRED, 'Root(s) in the archive; semicolon/comma-separated', '.')
            ->addOption('recursive', null, InputOption::VALUE_REQUIRED, 'Recurse into subdirs (1|0)', '1')
            ->addOption('max-depth', null, InputOption::VALUE_REQUIRED, 'Max recursion depth (0 = unlimited)', '0')
            ->addOption('exclude-globs', null, InputOption::VALUE_REQUIRED, 'Glob patterns to exclude', '')
            ->addOption('title-strategy', null, InputOption::VALUE_REQUIRED, 'fm_h1_filename|filename_only|h1_fm_filename', 'fm_h1_filename')
            ->addOption('naming-mode', null, InputOption::VALUE_REQUIRED, 'basename|prefix|suffix', 'basename')
            ->addOption('dir-levels', null, InputOption::VALUE_REQUIRED, 'Used by prefix/suffix modes', '2')
            ->addOption('separator', null, InputOption::VALUE_REQUIRED, 'Separator for path → name', ' ')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Namespace prefix for created pages', '')
            ->addOption('detect-journal', null, InputOption::VALUE_REQUIRED, 'Detect journal pages (1|0)', '1')
            ->addOption('journal-lang', null, InputOption::VALUE_REQUIRED, 'en|fr', 'en')
            ->addOption('journal-ns', null, InputOption::VALUE_REQUIRED, 'Journal page prefix', 'Journal')

            // Markdown flavor / pipeline
            ->addOption('markdown-source', null, InputOption::VALUE_REQUIRED, 'logseq|gfm|commonmark', 'commonmark')
            ->addOption('strip-front-matter', null, InputOption::VALUE_REQUIRED, 'Remove YAML front matter (1|0)', '1')
            ->addOption('runtime-logseq', null, InputOption::VALUE_REQUIRED, 'Keep [[..]]/((..)) for runtime ext (1|0)', '0')
            ->addOption('blockref-mode', null, InputOption::VALUE_REQUIRED, 'inline|code|keep', 'inline')

            // Garbage collector delete/mark
            ->addOption('gc-mode', null, InputOption::VALUE_REQUIRED, 'off|mark|delete', 'mark')
            ->addOption('gc-safe-namespace', null, InputOption::VALUE_REQUIRED, 'Only delete inside this page prefix', '')

            // Output
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print JSON result instead of human text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;
        $io = new SymfonyStyle($input, $output);

        if (($prefs['markdown_enabled'] ?? 'n') !== 'y') {
            $io->error(tr('Markdown is not enabled in Editing settings.'));
            return Command::FAILURE;
        }

        $mode = strtolower((string)$input->getOption('mode'));
        if (! in_array($mode, ['preview', 'import'], true)) {
            $io->error('Invalid --mode. Use preview or import.');
            return Command::FAILURE;
        }

        // -------- Source config (même shape que l’UI/Importer) --------
        $srcTypeRaw = strtolower((string)$input->getOption('source-type') ?: 'local');
        $srcType = ($srcTypeRaw === 'repo') ? 'git' : $srcTypeRaw; // alias
        if (! in_array($srcType, ['local', 'git'], true)) {
            $srcType = 'local';
        }

        $sourceCfg = ['type' => $srcType];
        $localPath = (string)$input->getOption('local-path');

        if ($srcType === 'git') {
            $repoUrl = (string)$input->getOption('repo-url');
            if ($repoUrl === '') {
                $io->error('For --source-type=git you must provide --repo-url (HTTPS).');
                return Command::FAILURE;
            }

            $sourceCfg += [
                'repo_url'    => $repoUrl,
                'repo_branch' => (string)$input->getOption('repo-branch') ?: 'main',
                'git_pull'    => $this->toBool($input->getOption('git-pull'), true),
                'repo_token'  => (string)$input->getOption('repo-token') ?: '',
                'git_timeout' => (int)$input->getOption('git-timeout') ?: 30,
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
            'roots'          => (string)$input->getOption('roots') ?: '.',
            'recursive'      => $this->toBool($input->getOption('recursive'), true),
            'max_depth'      => (int)$input->getOption('max-depth'),
            'exclude_globs'  => (string)$input->getOption('exclude-globs'),
            'title_strategy' => (string)$input->getOption('title-strategy') ?: 'fm_h1_filename',
            'naming_mode'    => (string)$input->getOption('naming-mode') ?: 'basename',
            'dir_levels'     => (int)$input->getOption('dir-levels'),
            'separator'      => (string)$input->getOption('separator') ?? ' ',
            'namespace'      => (string)$input->getOption('namespace') ?? '',
            'detect_journal' => $this->toBool($input->getOption('detect-journal'), true),
            'journal_lang'   => (string)$input->getOption('journal-lang') ?: 'en',
            'journal_ns'     => (string)$input->getOption('journal-ns') ?: 'Journal',
        ];

        // -------- Markdown pipeline --------
        $flavor = (string)$input->getOption('markdown-source') ?: 'commonmark';
        if (! in_array($flavor, ['logseq', 'gfm', 'commonmark'], true)) {
            $flavor = 'commonmark';
        }
        $pipelineCfg = [
            'flavor'             => $flavor,
            'strip_front_matter' => $this->toBool($input->getOption('strip-front-matter'), true),
            'runtime_logseq'     => $this->toBool($input->getOption('runtime-logseq'), false),
            'blockref_mode'      => (string)$input->getOption('blockref-mode') ?: 'inline',
            'gc'                 => [
                'mode'           => (function () use ($input) {
                    $m = strtolower((string)$input->getOption('gc-mode') ?: 'mark');
                    return in_array($m, ['off', 'mark', 'delete'], true) ? $m : 'mark';
                })(),
                'safe_namespace' => (string)$input->getOption('gc-safe-namespace') ?? '',
            ],
        ];

        $jsonOut = (bool)$input->getOption('json');

        // -------- Importer --------
        $importer = new TikiMarkdownImporter();
        [$uploadTmp, $uploadName, $tmpZipToDelete] = $this->resolveLocalPayload($srcType, $localPath);

        try {
            if ($mode === 'preview') {
                $res = $importer->previewFromSource(
                    $sourceCfg,
                    array_merge($scanOpts, ['markdown_source' => $flavor]),
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
                @unlink($tmpZipToDelete);
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
