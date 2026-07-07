<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

class GitWorkingCopyProvider implements SourceProviderInterface
{
    private const CFG_REPO_URL = 'repo_url';
    private const CFG_REPO_BRANCH = 'repo_branch';
    private const CFG_GIT_PULL = 'git_pull';
    private const CFG_REPO_TOKEN = 'repo_token';

    private const CTX_TIMEOUT = 'timeout';

    private const DEF_BRANCH = 'main';
    private const DEF_TIMEOUT = 45;

    private array $fetchMeta = [];

    public function __construct(
        private array $cfg,   // ['repo_url'=>..., 'repo_branch'=>'main', 'repo_token'=>'', 'git_pull'=>true]
        private array $ctx = [] // ex: ['timeout'=>45]
    ) {
    }

    public function fetchToTempDir(): string
    {
        $repoUrl   = trim((string)($this->cfg[self::CFG_REPO_URL] ?? ''));
        $branch    = trim((string)($this->cfg[self::CFG_REPO_BRANCH] ?? self::DEF_BRANCH));
        $gitPull   = ! empty($this->cfg[self::CFG_GIT_PULL]);
        $token     = (string)($this->cfg[self::CFG_REPO_TOKEN] ?? '');
        $timeout   = (int)($this->ctx[self::CTX_TIMEOUT] ?? self::DEF_TIMEOUT);

        if ($repoUrl === '') {
            throw new \RuntimeException(tra('Missing repo_url.'));
        }

        // Prepare dirs + .gitconfig
        $this->preflightInit();

        $git = $this->whichGit();

        [$repoRoot, /* $homeDir */] = $this->resolveRoots();
        $wcPath = $this->computeRepoPath($repoRoot, $repoUrl);
        $this->ensureDir(\dirname($wcPath));

        $this->fetchMeta = [
            'type' => 'git',
            'repo_url' => $repoUrl,
            'repo_path' => $wcPath,
            'requested_branch' => $branch,
            'git_pull' => $gitPull,
            'sync_mode' => 'unknown',
            'repo_reset' => false,
        ];

        // The working copy is transient by design: always start from a fresh clone.
        if (is_dir($wcPath)) {
            $this->rrmdir($wcPath);
            $this->fetchMeta['repo_reset'] = true;
            $this->fetchMeta['sync_mode'] = 'fresh_clone';
        }

        // Clone initial (with retry) or pull
        if (! is_dir($wcPath . '/.git')) {
            try {
                $this->gitClone($git, $repoUrl, $branch, $wcPath, $token, $timeout);
                $this->waitForCloneReady($wcPath);
                $this->markRepoSafe($git, $wcPath, $token);
                $this->fetchMeta['sync_mode'] = 'fresh_clone';
            } catch (\Throwable $e) {
                if (is_dir($wcPath . '/.git')) {
                    $this->markRepoSafe($git, $wcPath, $token);
                } else {
                    $this->rrmdir($wcPath);
                    $this->ensureDir(\dirname($wcPath));
                    $this->gitClone($git, $repoUrl, $branch, $wcPath, $token, $timeout);
                    $this->waitForCloneReady($wcPath);
                    $this->markRepoSafe($git, $wcPath, $token);
                    $this->fetchMeta['sync_mode'] = 'fresh_clone';
                }
            }
        } elseif ($gitPull) {
            $this->gitPull($git, $wcPath, $token, $timeout);
            $this->fetchMeta['sync_mode'] = 'pull';
        } else {
            $this->fetchMeta['sync_mode'] = 'reuse_no_pull';
        }

        $this->fetchMeta = array_merge($this->fetchMeta, $this->collectRepoState($git, $wcPath, $token, $repoUrl, $branch, $timeout));

        // Return working copy directory directly
        return $wcPath;
    }

    public function getFetchMeta(): array
    {
        return $this->fetchMeta;
    }

    /* ---------- Git helpers ---------- */

    private function waitForCloneReady(string $wcPath, int $ms = 1500): void
    {
        $deadline = microtime(true) + ($ms / 1000);
        do {
            if (is_file($wcPath . '/.git/HEAD')) {
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
        exec('which git 2>/dev/null', $out);
        if (! empty($out[0]) && is_executable($out[0])) {
            return $out[0];
        }
        throw new \RuntimeException(tra('git not found. Install command line tools and ensure git is in PATH.'));
    }

    private function baseEnv(): array
    {
        // HOME = storage/markdown-importer/.home
        $home = $this->resolveHomeDir();
        if (! is_dir($home)) {
            if (! mkdir($home, 0775, true) && ! is_dir($home)) {
                throw new \RuntimeException(tra('Cannot create HOME dir:') . ' ' . $home);
            }
            if (! chmod($home, 0775)) {
                trigger_error(tra('Failed to set directory permissions: %0', $home), E_USER_WARNING);
            }
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
        $proc = proc_open($cmd, $desc, $pipes, $cwd, $env);
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
                proc_terminate($proc);
                $out .= "\n[timeout]";
                break;
            }
            $out .= (string)stream_get_contents($pipes[1]);
            $out .= (string)stream_get_contents($pipes[2]);
            usleep(100_000);
        }
        // Drain any remaining buffered output after process exit.
        $out .= (string)stream_get_contents($pipes[1]);
        $out .= (string)stream_get_contents($pipes[2]);
        if (is_resource($pipes[1])) {
            fclose($pipes[1]);
        }
        if (is_resource($pipes[2])) {
            fclose($pipes[2]);
        }
        $code = proc_close($proc);

        if ($fatal && $code !== 0) {
            // Mask secrets in output
            $outSafe = $this->maskSecrets($out);
            throw new \RuntimeException($errLabel . ":\n$cmd\n-- output --\n" . trim($outSafe));
        }
    }

    private function runGitCapture(string $git, string $args, int $timeout, array $env, ?string $cwd): array
    {
        $cmd = escapeshellarg($git) . ' ' . $args . ' 2>&1';
        $desc = [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']];
        $proc = proc_open($cmd, $desc, $pipes, $cwd, $env);
        if (! \is_resource($proc)) {
            return ['code' => 1, 'output' => ''];
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
                proc_terminate($proc);
                $out .= "\n[timeout]";
                break;
            }
            $out .= (string)stream_get_contents($pipes[1]);
            $out .= (string)stream_get_contents($pipes[2]);
            usleep(100_000);
        }

        // Drain any remaining buffered output after process exit.
        $out .= (string)stream_get_contents($pipes[1]);
        $out .= (string)stream_get_contents($pipes[2]);

        if (is_resource($pipes[1])) {
            fclose($pipes[1]);
        }
        if (is_resource($pipes[2])) {
            fclose($pipes[2]);
        }

        return ['code' => proc_close($proc), 'output' => trim($this->maskSecrets($out))];
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
        $configPath = $path . '/.git/config';
        $originUrl = is_readable($configPath) ? file_get_contents($configPath) : false;
        $origin = null;
        if ($originUrl && preg_match('~url\s*=\s*(.+)~i', $originUrl, $m)) {
            $origin = trim($m[1]);
        }

        $env = $this->gitEnv($token, $origin);
        $this->runGit($git, '-C ' . escapeshellarg($path) . ' pull --ff-only', $timeout, $env, null, 'Git pull failed', true);
    }

    private function collectRepoState(string $git, string $wcPath, string $token, string $repoUrl, string $branch, int $timeout): array
    {
        $localEnv = $this->gitEnv($token, null);
        $remoteEnv = $this->gitEnv($token, $repoUrl);

        $head = $this->runGitCapture($git, '-C ' . escapeshellarg($wcPath) . ' rev-parse --short HEAD', $timeout, $localEnv, null);
        $headBranch = $this->runGitCapture($git, '-C ' . escapeshellarg($wcPath) . ' rev-parse --abbrev-ref HEAD', $timeout, $localEnv, null);
        $remote = $this->runGitCapture($git, 'ls-remote --heads ' . escapeshellarg($repoUrl) . ' ' . escapeshellarg($branch), $timeout, $remoteEnv, null);

        $remoteHead = '';
        if ($remote['code'] === 0 && $remote['output'] !== '') {
            $parts = preg_split('/\s+/', $remote['output']);
            $remoteHead = $parts[0] ?? '';
        }

        return [
            'head' => $head['code'] === 0 ? trim($head['output']) : null,
            'checked_out_branch' => $headBranch['code'] === 0 ? trim($headBranch['output']) : null,
            'remote_head' => $remoteHead !== '' ? substr($remoteHead, 0, 12) : null,
            'is_up_to_date' => ($remoteHead !== '' && $head['code'] === 0) ? str_starts_with($remoteHead, trim($head['output'])) : null,
        ];
    }

    /* ---------- Files helpers ---------- */

    private function ensureDir(string $dir): void
    {
        if (! is_dir($dir)) {
            $old = umask(0002);
            $ok = mkdir($dir, 0775, true);
            umask($old);
            if (! $ok && ! is_dir($dir)) {
                $e = error_get_last();
                $msg = tra('Cannot create dir:') . ' ' . $dir;
                if ($e && ! empty($e['message'])) {
                    $msg .= ' — ' . $e['message'];
                }
                throw new \RuntimeException($msg);
            }
            if (! chmod($dir, 0775)) {
                trigger_error(tra('Failed to set directory permissions: %0', $dir), E_USER_WARNING);
            }
        }
        if (! is_writable($dir)) {
            throw new \RuntimeException(tra('Dir not writable:') . ' ' . $dir);
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
            if ($f->isDir()) {
                if (! rmdir($f->getPathname())) {
                    trigger_error(tra('Failed to remove directory: %0', $f->getPathname()), E_USER_WARNING);
                }
            } else {
                if (! unlink($f->getPathname())) {
                    trigger_error(tra('Failed to remove file: %0', $f->getPathname()), E_USER_WARNING);
                }
            }
        }
        if (! rmdir($dir)) {
            trigger_error(tra('Failed to remove directory: %0', $dir), E_USER_WARNING);
        }
    }

    private function computeRepoPath(string $rootDir, string $repoUrl): string
    {
        $url = trim($repoUrl);
        $host = null;
        $path = null;

        // 1) HTTPS/HTTP/SSH explicite
        $u = parse_url($url);
        if (is_array($u) && ! empty($u['host'])) {
            $host = $u['host'];
            $path = ltrim($u['path'] ?? '', '/');
        } else {
            // 2) scp-like: git@github.com:owner/repo.git
            if (preg_match('~^git@([^:]+):(.+)$~', $url, $m)) {
                $host = $m[1];
                $path = $m[2];
            } else {
                throw new \RuntimeException(tra('Invalid repo_url:') . ' ' . $repoUrl);
            }
        }

        // Cleanup
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

        // Test write permissions
        $probe = $repoRoot . '/.write-test';
        if (file_put_contents($probe, 'ok') === false) {
            throw new \RuntimeException(tra('Repo root not writable:') . ' ' . $repoRoot);
        }
        if (file_exists($probe) && ! unlink($probe)) {
            trigger_error(tra('Failed to remove probe file: %0', $probe), E_USER_WARNING);
        }

        // Minimal .gitconfig
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
            if (file_put_contents($gitconfig, $cfg) === false) {
                throw new \RuntimeException(tra('Failed to write git config:') . ' ' . $gitconfig);
            }
            if (! chmod($gitconfig, 0600)) {
                trigger_error(tra('Failed to set file permissions: %0', $gitconfig), E_USER_WARNING);
            }
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
        if (file_put_contents($ask, $script) === false) {
            throw new \RuntimeException(tra('Failed to write askpass script:') . ' ' . $ask);
        }
        if (! chmod($ask, 0700)) {
            trigger_error(tra('Failed to set file permissions: %0', $ask), E_USER_WARNING);
        }

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
