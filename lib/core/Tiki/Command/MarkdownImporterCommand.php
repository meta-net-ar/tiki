<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Lib\Importer\Markdown\TikiMarkdownImporter;

#[AsCommand(
    name: 'markdown:importer',
    description: 'Import Markdown files into Tiki wiki pages from local zip/FTP/Git repo'
)]
class MarkdownImporterCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setHelp('
                Use this command to import Markdown files into Tiki wiki pages from a local zip file, an FTP server, or a Git repository.
            ')
            // High-level
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'preview|import', 'preview')

            // Source (choose one type)
            ->addOption('source-type', null, InputOption::VALUE_REQUIRED, 'local|ftp|repo', 'local')

            // Local zip (used when source-type=local)
            ->addOption('local-path', null, InputOption::VALUE_REQUIRED, 'Path to local .zip OR single .md file')

            // FTP options (used when source-type=ftp)
            ->addOption('ftp-host', null, InputOption::VALUE_REQUIRED, 'FTP hostname', '')
            ->addOption('ftp-port', null, InputOption::VALUE_REQUIRED, 'FTP port', '21')
            ->addOption('ftp-user', null, InputOption::VALUE_REQUIRED, 'FTP username', '')
            ->addOption('ftp-pass', null, InputOption::VALUE_REQUIRED, 'FTP password', '')
            ->addOption('ftp-path', null, InputOption::VALUE_REQUIRED, 'FTP base path (eg. /)', '/')
            ->addOption('ftp-ssl', null, InputOption::VALUE_REQUIRED, 'Use FTPS (1|0)', '0')
            ->addOption('ftp-passive', null, InputOption::VALUE_REQUIRED, 'Passive mode (1|0)', '1')

            // Repo options (used when source-type=repo)
            ->addOption('repo-provider', null, InputOption::VALUE_REQUIRED, 'github|gitlab', 'github')
            ->addOption('repo-owner', null, InputOption::VALUE_REQUIRED, 'Repository owner/namespace', '')
            ->addOption('repo-name', null, InputOption::VALUE_REQUIRED, 'Repository name', '')
            ->addOption('repo-ref', null, InputOption::VALUE_REQUIRED, 'Branch or tag', 'main')
            ->addOption('repo-token', null, InputOption::VALUE_REQUIRED, 'Access token (if needed)', '')

            // Scanner / mapping options
            ->addOption('roots', null, InputOption::VALUE_REQUIRED, 'Root(s) in the zip (comma-separated)', '.')
            ->addOption('recursive', null, InputOption::VALUE_REQUIRED, 'Recurse into subdirs (1|0)', '1')
            ->addOption('max-depth', null, InputOption::VALUE_REQUIRED, 'Max recursion depth (0 = unlimited)', '0')
            ->addOption('exclude-globs', null, InputOption::VALUE_REQUIRED, 'Glob patterns to exclude', '')
            ->addOption('title-strategy', null, InputOption::VALUE_REQUIRED, 'fm_h1_filename|filename_only|h1_only', 'fm_h1_filename')
            ->addOption('naming-mode', null, InputOption::VALUE_REQUIRED, 'basename|path|path_levels', 'basename')
            ->addOption('dir-levels', null, InputOption::VALUE_REQUIRED, 'Used by naming-mode=path_levels', '2')
            ->addOption('separator', null, InputOption::VALUE_REQUIRED, 'Separator for path → name', ' ')
            ->addOption('namespace', null, InputOption::VALUE_REQUIRED, 'Namespace prefix for created pages', '')
            ->addOption('detect-journal', null, InputOption::VALUE_REQUIRED, 'Detect Logseq journals (1|0)', '1')
            ->addOption('journal-lang', null, InputOption::VALUE_REQUIRED, 'en|fr', 'en')
            ->addOption('journal-ns', null, InputOption::VALUE_REQUIRED, 'Journal page prefix (e.g. "Journal")', 'Journal')

            // Markdown flavor / pipeline options
            ->addOption('markdown-source', null, InputOption::VALUE_REQUIRED, 'logseq|gfm|commonmark', 'logseq')
            ->addOption('strip-front-matter', null, InputOption::VALUE_REQUIRED, 'Remove YAML front matter (1|0)', '1')
            ->addOption('runtime-logseq', null, InputOption::VALUE_REQUIRED, 'Keep [[..]]/((..)) for runtime ext (1|0)', '0')
            ->addOption('blockref-mode', null, InputOption::VALUE_REQUIRED, 'inline|code|keep', 'inline')

            // Verbosity/output
            ->addOption('json', null, InputOption::VALUE_NONE, 'Print JSON result instead of human text')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $prefs;
        $io = new SymfonyStyle($input, $output);

        if ($prefs['markdown_enabled'] !== 'y') {
            $io->error(tr('Markdown is not enabled in Editing settings.'));
            return Command::FAILURE;
        }

        $mode = strtolower((string)$input->getOption('mode')); // preview|import
        if (! in_array($mode, ['preview', 'import'], true)) {
            $io->error('Invalid --mode. Use preview or import.');
            return Command::FAILURE;
        }

        // ---------- Build source config (same shape as UI) ----------
        $srcType = strtolower((string)$input->getOption('source-type') ?: 'local');
        if (! in_array($srcType, ['local', 'ftp', 'repo'], true)) {
            $srcType = 'local';
        }

        $sourceCfg = ['type' => $srcType];

        $localPath = (string)$input->getOption('local-path');

        if ($srcType === 'ftp') {
            $sourceCfg += [
                'ftp_host'    => (string)$input->getOption('ftp-host'),
                'ftp_port'    => (int)$input->getOption('ftp-port'),
                'ftp_user'    => (string)$input->getOption('ftp-user'),
                'ftp_pass'    => (string)$input->getOption('ftp-pass'),
                'ftp_path'    => (string)$input->getOption('ftp-path'),
                'ftp_ssl'     => $this->toBool($input->getOption('ftp-ssl')),
                'ftp_passive' => $this->toBool($input->getOption('ftp-passive')),
            ];
        } elseif ($srcType === 'repo') {
            $sourceCfg += [
                'repo_provider' => (string)$input->getOption('repo-provider') ?: 'github',
                'repo_owner'    => (string)$input->getOption('repo-owner'),
                'repo_name'     => (string)$input->getOption('repo-name'),
                'repo_ref'      => (string)$input->getOption('repo-ref') ?: 'main',
                'repo_token'    => (string)$input->getOption('repo-token'),
            ];
        } elseif ($srcType === 'local') {
            if (! $localPath) {
                $io->error('For --source-type=local you must provide --local-path pointing to a .zip or a .md file.');
                return Command::FAILURE;
            }
            if (! is_readable($localPath)) {
                $io->error('local-path not readable: ' . $localPath);
                return Command::FAILURE;
            }
        } else {
            $io->error('Invalid --source-type. Use local, ftp or repo.');
            return Command::FAILURE;
        }

        // ---------- Scanner options ----------
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

        // ---------- Markdown pipeline options ----------
        $flavor = (string)$input->getOption('markdown-source') ?: 'logseq';
        if (! in_array($flavor, ['logseq', 'gfm', 'commonmark'], true)) {
            $flavor = 'logseq';
        }

        $pipelineCfg = [
            'flavor'             => $flavor,
            'strip_front_matter' => $this->toBool($input->getOption('strip-front-matter'), true),
            'runtime_logseq'     => $this->toBool($input->getOption('runtime-logseq'), false),
            'blockref_mode'      => (string)$input->getOption('blockref-mode') ?: 'inline',
        ];

        $jsonOut = (bool)$input->getOption('json');

        // ---------- Run through importer (single entry-point) ----------
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
                    $io->writeln(json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                } else {
                    if (! empty($res['ok'])) {
                        $io->success('Preview OK');
                        if (! empty($res['counts'])) {
                            $io->writeln(sprintf(
                                "Counts: pages=%d journals=%d total=%d",
                                (int)($res['counts']['pages'] ?? 0),
                                (int)($res['counts']['journals'] ?? 0),
                                (int)($res['counts']['total'] ?? 0)
                            ));
                        }
                        if (! empty($res['sample'])) {
                            $io->section('Sample items');
                            foreach (array_slice($res['sample'], 0, 5) as $s) {
                                $io->writeln('- ' . ($s['relative'] ?? '(unknown)') . ' → ' . ($s['page_name'] ?? '?'));
                                if (! empty($s['purified_preview'])) {
                                    $io->writeln("  preview:\n" . $s['purified_preview']);
                                }
                            }
                        }
                    } else {
                        $io->error($importer->getError() ?: ($res['error'] ?? 'Preview failed.'));
                        return Command::FAILURE;
                    }
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
                    $io->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                } else {
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
                        // result['notes'] is typically keyed per page
                        foreach ($result['notes'] as $page => $notes) {
                            $io->writeln('• ' . $page);
                            foreach ((array)$notes as $n) {
                                $io->writeln('   - ' . $n);
                            }
                        }
                    }
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
            // Clean temporary zip if we created one for a single .md
            if ($tmpZipToDelete && file_exists($tmpZipToDelete)) {
                @unlink($tmpZipToDelete);
            }
        }

        return Command::SUCCESS;
    }

    /**
     * Accepts "1"/"0", "true"/"false", true/false. Default if null.
     */
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
     * - If $path ends with .zip, return it directly.
     * - If $path ends with .md, wrap it into a temporary zip so the importer can scan it.
     * Returns: [uploadTmp, uploadName, tmpZipCreated]
     */
    private function resolveLocalPayload(string $srcType, ?string $path): array
    {
        if ($srcType !== 'local' || ! $path) {
            return [null, null, null];
        }

        $lower = strtolower($path);
        if (str_ends_with($lower, '.zip')) {
            return [$path, basename($path), null];
        }

        if (str_ends_with($lower, '.md')) {
            // Create a temp zip and add the md under "pages/<basename>.md"
            $tmpZip = tempnam(sys_get_temp_dir(), 'md_import_') . '.zip';
            $zip = new \ZipArchive();
            if ($zip->open($tmpZip, \ZipArchive::CREATE) !== true) {
                throw new \RuntimeException('Cannot create temporary zip for single .md');
            }
            $mdName = basename($path);
            // place it inside a conventional root; scanner can target "pages" by default
            $zip->addFile($path, 'pages/' . $mdName);
            $zip->close();
            return [$tmpZip, basename($tmpZip), $tmpZip];
        }

        throw new \InvalidArgumentException('local-path must be a .zip or a .md file');
    }
}
