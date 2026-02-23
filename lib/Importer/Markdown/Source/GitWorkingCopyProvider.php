<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

use ZipArchive;

class GitWorkingCopyProvider implements SourceProviderInterface
{
    public function __construct(
        private array $cfg,   // ['repo_url'=>..., 'repo_branch'=>'main', 'repo_token'=>'', 'git_pull'=>true]
        private array $ctx = [] // ex: ['timeout'=>45]
    ) {
    }

    public function fetchToTempZip(): string
    {
        $repoUrl   = trim((string)($this->cfg['repo_url'] ?? ''));
        $branch    = trim((string)($this->cfg['repo_branch'] ?? 'main'));
        $gitPull   = ! empty($this->cfg['git_pull']);
        $token     = (string)($this->cfg['repo_token'] ?? '');
        $timeout   = (int)($this->ctx['timeout'] ?? 45);

        if ($repoUrl === '') {
            throw new \RuntimeException('Missing repo_url.');
        }

        // Prepare dirs + .gitconfig
        $this->preflightInit();

        $git = $this->whichGit();

        [$repoRoot, /* $homeDir */] = $this->resolveRoots();
        $wcPath = $this->computeRepoPath($repoRoot, $repoUrl);
        $this->ensureDir(\dirname($wcPath));

        // Clone initial (with retry) or pull
        if (! is_dir($wcPath . '/.git')) {
            try {
                $this->gitClone($git, $repoUrl, $branch, $wcPath, $token, $timeout);
                $this->waitForCloneReady($wcPath);
                $this->markRepoSafe($git, $wcPath, $token);
            } catch (\Throwable $e) {
                if (is_dir($wcPath . '/.git')) {
                    $this->markRepoSafe($git, $wcPath, $token);
                } else {
                    $this->rrmdir($wcPath);
                    $this->ensureDir(\dirname($wcPath));
                    $this->gitClone($git, $repoUrl, $branch, $wcPath, $token, $timeout);
                    $this->waitForCloneReady($wcPath);
                    $this->markRepoSafe($git, $wcPath, $token);
                }
            }
        } elseif ($gitPull) {
            $this->gitPull($git, $wcPath, $token, $timeout);
        }

        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is required.');
        }

        $zipPath = $this->tmpZip();
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new \RuntimeException('Cannot create temporary ZIP.');
        }
        $this->addDirMarkdown($zip, $wcPath, '');
        $zip->close();

        return $zipPath;
    }

    /* ---------- Git helpers ---------- */

    private function waitForCloneReady(string $wcPath, int $ms = 1500): void
    {
        $deadline = microtime(true) + ($ms / 1000);
        do {
            if (@is_file($wcPath . '/.git/HEAD')) {
                clearstatcache();
                return;
            }
            usleep(100_000);
        } while (microtime(true) < $deadline);
    }

    private function markRepoSafe(string $git, string $wcPath, string $token): void
    {
        $env = $this->gitEnv($token, null);
        $this->runGit($git, '-C ' . escapeshellarg($wcPath) . ' config --global --add safe.directory ' . escapeshellarg($wcPath), 15, $env, null, 'Git safe.directory failed', false);
    }

    private function whichGit(): string
    {
        $candidates = ['/usr/bin/git', '/usr/local/bin/git', '/opt/homebrew/bin/git', '/opt/local/bin/git'];
        foreach ($candidates as $p) {
            if (is_executable($p)) {
                return $p;
            }
        }
        $out = [];
        @exec('which git 2>/dev/null', $out);
        if (! empty($out[0]) && is_executable($out[0])) {
            return $out[0];
        }
        throw new \RuntimeException('git not found. Install command line tools and ensure git is in PATH.');
    }

    private function baseEnv(): array
    {
        // HOME = storage/markdown-importer/.home
        $home = $this->resolveHomeDir();
        if (! is_dir($home)) {
            @mkdir($home, 0775, true);
            @chmod($home, 0775);
        }
        return [
            'HOME' => $home,
            'PATH' => '/usr/bin:/usr/local/bin:/opt/homebrew/bin:/opt/local/bin',
        ];
    }

    /**
     * Build GIT_* env for git commands, including token-based HTTPS auth if needed.
     */
    private function gitEnv(string $token, ?string $repoUrl): array
    {
        $env = $this->baseEnv();

        if ($token !== '' && $repoUrl && preg_match('~^https?://~i', $repoUrl)) {
            $env = $this->withHttpsTokenEnv($env, $repoUrl, $token);
        }

        // Avoid les prompts interactifs
        $env['GIT_TERMINAL_PROMPT'] = '0';
        return $env;
    }

    private function runGit(string $git, string $args, int $timeout, array $env, ?string $cwd, string $errLabel, bool $fatal = true): void
    {
        $cmd = escapeshellarg($git) . ' ' . $args . ' 2>&1';
        $desc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $proc = @proc_open($cmd, $desc, $pipes, $cwd, $env);
        if (! \is_resource($proc)) {
            if ($fatal) {
                throw new \RuntimeException($errLabel . ' (spawn failed)');
            }
            return;
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $start = time();
        $out = '';
        while (true) {
            $status = proc_get_status($proc);
            if (! $status['running']) {
                break;
            }
            if (time() - $start > $timeout) {
                @proc_terminate($proc);
                $out .= "\n[timeout]";
                break;
            }
            $out .= (string)stream_get_contents($pipes[1]);
            $out .= (string)stream_get_contents($pipes[2]);
            usleep(100_000);
        }
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        $code = @proc_close($proc);

        if ($fatal && $code !== 0) {
            // Mask secrets in output
            $outSafe = $this->maskSecrets($out);
            throw new \RuntimeException($errLabel . ":\n$cmd\n-- output --\n" . trim($outSafe));
        }
    }

    private function gitClone(string $git, string $url, string $branch, string $dst, string $token, int $timeout): void
    {
        $env = $this->gitEnv($token, $url);

        // Check remote exists
        $this->runGit($git, 'ls-remote ' . escapeshellarg($url) . ' ' . escapeshellarg($branch), 25, $env, null, 'Git ls-remote failed', false);

        $args = 'clone --branch ' . escapeshellarg($branch) . ' --single-branch '
            . escapeshellarg($url) . ' ' . escapeshellarg($dst);
        $this->runGit($git, $args, $timeout, $env, null, 'Git clone failed', true);
        $this->runGit($git, '-C ' . escapeshellarg($dst) . ' config --global --add safe.directory ' . escapeshellarg($dst), 15, $env, null, 'Git safe.directory failed', false);
    }

    private function gitPull(string $git, string $path, string $token, int $timeout): void
    {
        // Determine origin URL to setup auth if needed
        $originUrl = @file_get_contents($path . '/.git/config');
        $origin = null;
        if ($originUrl && preg_match('~url\s*=\s*(.+)~i', $originUrl, $m)) {
            $origin = trim($m[1]);
        }

        $env = $this->gitEnv($token, $origin);
        $this->runGit($git, '-C ' . escapeshellarg($path) . ' pull --ff-only', $timeout, $env, null, 'Git pull failed', false);
    }

    /* ---------- Files helpers ---------- */

    private function addDirMarkdown(ZipArchive $zip, string $root, string $rel): void
    {
        $dir = $rel ? ($root . '/' . $rel) : $root;
        $list = @scandir($dir);
        if ($list === false) {
            return; // permission denied → on ignore
        }
        foreach ($list as $name) {
            if ($name === '.' || $name === '..' || $name === '.git') {
                continue;
            }

            $full = $dir . '/' . $name;
            $relPath = ltrim($rel . '/' . $name, '/');

            if (is_dir($full)) {
                $this->addDirMarkdown($zip, $root, $relPath);
                continue;
            }

            $lower = strtolower($name);
            if (! str_ends_with($lower, '.md') && ! str_ends_with($lower, '.markdown') && ! str_ends_with($lower, '.zip')) {
                continue;
            }

            $data = @file_get_contents($full);
            if ($data !== false) {
                $zip->addFromString($relPath, $data);

                // set mtime in zip if possible
                if (method_exists($zip, 'setMtimeName')) {
                    $mt = @filemtime($full);
                    if ($mt !== false) {
                        // aide le scanner à remplir 'mtime_utc'
                        $zip->setMtimeName($relPath, (int) $mt);
                    }
                }
            }
        }
    }

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            $old = umask(0002);
            $ok = @mkdir($dir, 0775, true);
            umask($old);
            if (! $ok && ! is_dir($dir)) {
                $e = error_get_last();
                $msg = 'Cannot create dir: ' . $dir;
                if ($e && ! empty($e['message'])) {
                    $msg .= ' — ' . $e['message'];
                }
                throw new \RuntimeException($msg);
            }
            @chmod($dir, 0775);
        }
        if (! is_writable($dir)) {
            throw new \RuntimeException('Dir not writable: ' . $dir);
        }
    }

    private function rrmdir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $f) {
            $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
        }
        @rmdir($dir);
    }

    private function computeRepoPath(string $rootDir, string $repoUrl): string
    {
        $url = trim($repoUrl);
        $host = null;
        $path = null;

        // 1) HTTPS/HTTP/SSH explicite
        $u = @parse_url($url);
        if (is_array($u) && ! empty($u['host'])) {
            $host = $u['host'];
            $path = ltrim($u['path'] ?? '', '/');
        } else {
            // 2) scp-like: git@github.com:owner/repo.git
            if (preg_match('~^git@([^:]+):(.+)$~', $url, $m)) {
                $host = $m[1];
                $path = $m[2];
            } else {
                throw new \RuntimeException('Invalid repo_url: ' . $repoUrl);
            }
        }

        // Nettoyage
        $path = preg_replace('~^/+~', '', $path);
        $path = preg_replace('~\.git$~i', '', $path);
        $safe = preg_replace('~[^A-Za-z0-9/_\.-]+~', '-', $path);
        if ($safe === '' || $safe === '-') {
            $safe = 'wk-' . substr(sha1($repoUrl), 0, 12);
        }

        $dir = rtrim($rootDir, '/') . '/' . $host . '/' . $safe;
        $this->ensureDir(\dirname($dir));
        return $dir;
    }

    private function tmpZip(): string
    {
        return tempnam(sys_get_temp_dir(), 'mdimp_') . '.zip';
    }

    private function resolveRoots(): array
    {
        $base = $this->resolveBaseDir();
        $repoRoot = str_ends_with($base, '/repo') ? $base : ($base . '/repo');
        $homeDir  = $this->resolveHomeDir();
        return [$repoRoot, $homeDir];
    }

    private function resolveBaseDir(): string
    {
        global $prefs;
        $base = trim((string)($prefs['md_import_git_root'] ?? ''));
        if ($base === '') {
            $base = TIKI_PATH . '/storage/markdown-importer';
        }
        return rtrim($base, '/');
    }

    private function resolveHomeDir(): string
    {
        return $this->resolveBaseDir() . '/.home';
    }

    /**
     * Prapare minimal .gitconfig .
     */
    private function preflightInit(): void
    {
        [$repoRoot, $homeDir] = $this->resolveRoots();
        $this->ensureDir($repoRoot);
        $this->ensureDir($homeDir);

        // Test écriture
        $probe = $repoRoot . '/.write-test';
        if (@file_put_contents($probe, 'ok') === false) {
            throw new \RuntimeException('Repo root not writable: ' . $repoRoot);
        }
        @unlink($probe);

        // .gitconfig minimal (conservé car tu veux le garder)
        $gitconfig = $homeDir . '/.gitconfig';
        if (! file_exists($gitconfig)) {
            $cfg = <<<INI
[user]
    name = tiki-importer
    email = noreply@local
[http]
    sslVerify = true
[core]
    askPass = 
INI;
            @file_put_contents($gitconfig, $cfg);
            @chmod($gitconfig, 0600);
        }
    }

    /* ======= Auth HTTPS via GIT_ASKPASS (token) ======= */
    private function withHttpsTokenEnv(array $env, string $repoUrl, string $token): array
    {
        $home = $this->resolveHomeDir();
        $ask  = $home . '/git-askpass.sh';

        $user = $this->inferTokenUsername($repoUrl); // github → x-access-token, gitlab → oauth2, sinon token
        $script = "#!/bin/sh\n" .
            "case \"\$1\" in\n" .
            "  *Username*) echo \"$user\" ;;\n" .
            "  *Password*) echo \"$token\" ;;\n" .
            "  *) echo \"\" ;;\n" .
            "esac\n";
        @file_put_contents($ask, $script);
        @chmod($ask, 0700);

        $env['GIT_ASKPASS'] = $ask;
        return $env;
    }

    private function inferTokenUsername(string $repoUrl): string
    {
        $h = strtolower(parse_url($repoUrl, PHP_URL_HOST) ?: '');
        if (str_contains($h, 'github')) {
            return 'x-access-token';
        }
        if (str_contains($h, 'gitlab')) {
            return 'oauth2';
        }
        return 'token';
    }

    private function maskSecrets(string $s): string
    {
        $s = preg_replace('~([A-Za-z0-9_\-]{12,})~', '***', (string)$s);
        return $s;
    }
}
