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
    // Kept in sync with SourceConfig::DEF_TIMEOUT — SourceManager::createProvider()
    // always supplies ctx['timeout'] explicitly, so this only matters for direct construction
    private const DEF_TIMEOUT = 30;
    // Short, independent timeout for informational-only git calls (metadata display).
    // These must never be allowed to inflate the wall-clock cost of a fetch.
    private const DEF_METADATA_TIMEOUT = 10;
    // Grace period after SIGTERM before escalating to SIGKILL.
    private const TERM_GRACE_SECONDS = 2;

    private array $fetchMeta = [];

    public function __construct(
        private array $cfg,   // ['repo_url'=>..., 'repo_branch'=>'main', 'repo_token'=>'', 'git_pull'=>true]
        private array $ctx = [] // ex: ['timeout'=>30]
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

        // Prevent a manual "Import now" and the scheduled cron run from racing
        // on the same working copy (both rrmdir/clone/scan the same directory).
        $lock = $this->acquireLock($wcPath);
        try {
            $hasCheckout = is_dir($wcPath . '/.git');
            $checkedOutBranch = $hasCheckout ? $this->getCheckedOutBranch($git, $wcPath, $token) : null;
            $branchMatches = $hasCheckout && $checkedOutBranch === $branch;

            if ($hasCheckout && $branchMatches && $gitPull) {
                // Real incremental update instead of a fresh clone every time.
                try {
                    $this->gitPull($git, $wcPath, $token, $timeout);
                    $this->fetchMeta['sync_mode'] = 'pull';
                } catch (\Throwable) {
                    // Diverged history / corrupted checkout: fall back to a fresh clone.
                    $this->freshClone($git, $repoUrl, $branch, $wcPath, $token, $timeout);
                    $this->fetchMeta['sync_mode'] = 'fresh_clone';
                    $this->fetchMeta['repo_reset'] = true;
                }
            } elseif ($hasCheckout && $branchMatches) {
                // git_pull is off: reuse the existing checkout, no network call at all.
                $this->fetchMeta['sync_mode'] = 'reuse_no_pull';
            } else {
                // No usable checkout yet (missing, wrong branch, or corrupted).
                $this->fetchMeta['repo_reset'] = $hasCheckout;
                $this->freshClone($git, $repoUrl, $branch, $wcPath, $token, $timeout);
                $this->fetchMeta['sync_mode'] = 'fresh_clone';
            }

            $this->fetchMeta = array_merge(
                $this->fetchMeta,
                $this->collectRepoState($git, $wcPath, $token, $repoUrl, $branch, $checkedOutBranch)
            );

            return $wcPath;
        } finally {
            $this->releaseLock($lock);
        }
    }

    public function getFetchMeta(): array
    {
        return $this->fetchMeta;
    }

    /* ---------- Git helpers ---------- */

    /**
     * Wipe (if present) and clone fresh, with one retry if the first attempt
     * leaves a partial/corrupted checkout behind.
     */
    private function freshClone(string $git, string $repoUrl, string $branch, string $wcPath, string $token, int $timeout): void
    {
        if (is_dir($wcPath)) {
            $this->rrmdir($wcPath);
        }
        $this->ensureDir(\dirname($wcPath));

        try {
            $this->gitClone($git, $repoUrl, $branch, $wcPath, $token, $timeout);
            $this->waitForCloneReady($wcPath);
            $this->markRepoSafe($git, $wcPath, $token);
        } catch (\Throwable) {
            if (is_dir($wcPath . '/.git')) {
                $this->markRepoSafe($git, $wcPath, $token);
                return;
            }
            $this->rrmdir($wcPath);
            $this->ensureDir(\dirname($wcPath));
            $this->gitClone($git, $repoUrl, $branch, $wcPath, $token, $timeout);
            $this->waitForCloneReady($wcPath);
            $this->markRepoSafe($git, $wcPath, $token);
        }
    }

    private function getCheckedOutBranch(string $git, string $wcPath, string $token): ?string
    {
        $env = $this->gitEnv($token, null);
        $res = $this->execGit($git, '-C ' . escapeshellarg($wcPath) . ' rev-parse --abbrev-ref HEAD', self::DEF_METADATA_TIMEOUT, $env, null);
        return $res['code'] === 0 ? trim($res['output']) : null;
    }

    /**
     * Acquire an exclusive, non-blocking lock for this working-copy path so a
     * manual "Import now" and the scheduled cron run can never operate on the
     * same directory at once. Fails fast rather than queueing, so a stuck run
     * cannot cause a second one to hang waiting for the lock.
     *
     * @return resource
     */
    private function acquireLock(string $wcPath)
    {
        $lockPath = rtrim($wcPath, '/') . '.lock';
        $this->ensureDir(\dirname($lockPath));
        $handle = fopen($lockPath, 'c');
        if ($handle === false) {
            throw new \RuntimeException(tra('Cannot open lock file:') . ' ' . $lockPath);
        }
        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            throw new \RuntimeException(tra('Another import is already running for this source. Try again once it finishes.'));
        }
        return $handle;
    }

    private function releaseLock($handle): void
    {
        if (is_resource($handle)) {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

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

        // Avoid growing .gitconfig with a duplicate entry on every single run.
        $existing = $this->execGit($git, 'config --global --get-all safe.directory', self::DEF_METADATA_TIMEOUT, $env, null);
        $already = $existing['code'] === 0 && in_array($wcPath, preg_split('/\R/', $existing['output']) ?: [], true);
        if ($already) {
            return;
        }

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

    /**
     * Run a git subprocess, polling for completion with a hard timeout.
     * Escalates to SIGKILL if the process is still alive shortly after SIGTERM,
     * so a stuck git process can never outlive PHP's own timeout logic (and,
     * critically, can never hold .git/index.lock into the next run).
     *
     * $maskSecrets must stay false for output that is parsed and compared
     * programmatically (branch names, config listings, hashes) — the mask
     * regex blanks out any run of 12+ alnum/dash/underscore characters, which
     * matches ordinary branch names just as readily as a real secret, and
     * comparing a masked value against an unmasked one silently breaks the
     * comparison. Only pass true for output that is exclusively surfaced to a
     * human (e.g. a thrown exception's message).
     *
     * @return array{code:int, output:string}
     */
    private function execGit(string $git, string $args, int $timeout, array $env, ?string $cwd, bool $maskSecrets = false): array
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
        $timedOut = false;
        // proc_close()'s return value is unreliable once proc_get_status() has
        // already observed the process as exited (a well-known PHP quirk: the
        // exit code is reaped on that first observation and proc_close() then
        // reports -1). Capture it here, at the moment we see running:false.
        $exitCode = null;
        while (true) {
            $status = proc_get_status($proc);
            if (! $status['running']) {
                $exitCode = $status['exitcode'];
                break;
            }
            if (time() - $start > $timeout) {
                $timedOut = true;
                proc_terminate($proc); // SIGTERM
                $graceUntil = microtime(true) + self::TERM_GRACE_SECONDS;
                while (microtime(true) < $graceUntil) {
                    $status = proc_get_status($proc);
                    if (! $status['running']) {
                        $exitCode = $status['exitcode'];
                        break;
                    }
                    usleep(100_000);
                }
                if ($exitCode === null) {
                    proc_terminate($proc, 9); // SIGKILL — still alive after the grace period
                }
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

        proc_close($proc); // release the resource; exit code was already captured above

        $code = $timedOut ? 124 : ($exitCode ?? 1);
        $out = trim($out);

        return ['code' => $code, 'output' => $maskSecrets ? $this->maskSecrets($out) : $out];
    }

    private function runGit(string $git, string $args, int $timeout, array $env, ?string $cwd, string $errLabel, bool $fatal = true): void
    {
        // Masked: this output only ever reaches a thrown exception message.
        $result = $this->execGit($git, $args, $timeout, $env, $cwd, true);
        if ($fatal && $result['code'] !== 0) {
            throw new \RuntimeException($errLabel . ":\n" . escapeshellarg($git) . ' ' . $args . "\n-- output --\n" . $result['output']);
        }
    }

    private function gitClone(string $git, string $url, string $branch, string $dst, string $token, int $timeout): void
    {
        $env = $this->gitEnv($token, $url);

        // Shallow clone: Tiki only reads working-tree file contents (DirectoryScanner),
        // never git log/blame, so full history is pure network/disk cost with no benefit
        // — significant for Logseq vaults that accumulate years of daily journal commits.
        $args = 'clone --depth=1 --branch ' . escapeshellarg($branch) . ' --single-branch '
            . escapeshellarg($url) . ' ' . escapeshellarg($dst);
        $this->runGit($git, $args, $timeout, $env, null, 'Git clone failed', true);
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
        $branch = $this->getCheckedOutBranch($git, $path, $token);
        if ($branch === null) {
            throw new \RuntimeException(tra('Git pull failed: could not determine the checked-out branch.'));
        }

        // The working copy is a shallow (--depth=1) clone: a plain `pull --ff-only`
        // can spuriously report "diverging branches" whenever the remote's shallow
        // history boundary shifts between fetches, even though there is no real
        // conflict — Tiki only ever needs the latest file contents, never merge or
        // fast-forward semantics. Fetch the new tip and hard-reset onto it instead.
        $this->runGit($git, '-C ' . escapeshellarg($path) . ' fetch --depth=1 origin ' . escapeshellarg($branch), $timeout, $env, null, 'Git fetch failed', true);
        $this->runGit($git, '-C ' . escapeshellarg($path) . ' reset --hard ' . escapeshellarg('origin/' . $branch), 15, $env, null, 'Git reset failed', true);
    }

    /**
     * Collect informational-only metadata for display (CLI/UI). Best-effort:
     * never allowed to fail the fetch, and bounded by its own short timeout so
     * it can't inflate the wall-clock cost of an otherwise-successful fetch.
     *
     * @param ?string $knownCheckedOutBranch Reuse the branch already resolved by
     *   fetchToTempDir() instead of paying for a second identical git call.
     */
    private function collectRepoState(string $git, string $wcPath, string $token, string $repoUrl, string $branch, ?string $knownCheckedOutBranch = null): array
    {
        $localEnv = $this->gitEnv($token, null);
        $remoteEnv = $this->gitEnv($token, $repoUrl);

        $head = $this->execGit($git, '-C ' . escapeshellarg($wcPath) . ' rev-parse --short HEAD', self::DEF_METADATA_TIMEOUT, $localEnv, null);
        $headBranch = $knownCheckedOutBranch ?? $this->getCheckedOutBranch($git, $wcPath, $token);
        $remote = $this->execGit($git, 'ls-remote --heads ' . escapeshellarg($repoUrl) . ' ' . escapeshellarg($branch), self::DEF_METADATA_TIMEOUT, $remoteEnv, null);

        $remoteHead = '';
        if ($remote['code'] === 0 && $remote['output'] !== '') {
            $parts = preg_split('/\s+/', $remote['output']);
            $remoteHead = $parts[0] ?? '';
        }

        return [
            'head' => $head['code'] === 0 ? trim($head['output']) : null,
            'checked_out_branch' => $headBranch,
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
