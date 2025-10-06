<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

/**
 * RepoZipProvider
 *
 * Handles 'repo' sources for GitHub and GitLab (including self-hosted).
 * Uses HTTP to fetch the zipball/archive for a given ref.
 */
class RepoZipProvider implements SourceProviderInterface
{
    public function __construct(
        private array $cfg,
        private array $ctx = []
    ) {
    }

    public function fetchToTempZip(): string
    {
        $provider = (string)($this->cfg['repo_provider'] ?? 'github');
        $owner = (string)($this->cfg['repo_owner'] ?? '');
        $repo = (string)($this->cfg['repo_name'] ?? '');
        $ref = (string)($this->cfg['repo_ref'] ?? 'main');
        $token = (string)($this->cfg['repo_token'] ?? '');

        if ($provider === 'gitlab') {
            $base = (string)($this->cfg['gitlab_base'] ?? 'https://gitlab.com');
            return $this->fetchGitlab($base, $owner, $repo, $ref, $token);
        }

        // default GitHub
        return $this->fetchGithub('https://api.github.com', $owner, $repo, $ref, $token);
    }

    private function fetchGithub(string $apiBase, string $owner, string $repo, string $ref, string $token): string
    {
        $ref = rawurlencode($ref);
        $url = rtrim($apiBase, '/') . "/repos/{$owner}/{$repo}/zipball/{$ref}";
        $headers = [
            $token ? "Authorization: Bearer {$token}" : null,
            "Accept: application/vnd.github+json",
            "User-Agent: Tiki-MarkdownImport",
        ];
        return $this->downloadToTempZip($url, $headers);
    }

    private function fetchGitlab(string $base, string $owner, string $repo, string $ref, string $token): string
    {
        $project = rawurlencode($owner . '/' . $repo);
        $sha = rawurlencode($ref);
        $url = rtrim($base, '/') . "/api/v4/projects/{$project}/repository/archive.zip?sha={$sha}";
        $headers = [
            $token ? "PRIVATE-TOKEN: {$token}" : null,
        ];
        return $this->downloadToTempZip($url, $headers);
    }

    private function downloadToTempZip(string $url, array $headers = [], int $timeout = 60): string
    {
        if (! class_exists(\ZipArchive::class)) {
            throw new \RuntimeException('ZipArchive extension is required.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'mdimp_') . '.zip';
        $fp  = @fopen($tmp, 'wb');
        if (! $fp) {
            throw new \RuntimeException('Cannot create temporary file.');
        }

        $ch = curl_init($url);
        $headers = array_values(array_filter($headers ?: []));
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_FILE            => $fp,
            CURLOPT_FOLLOWLOCATION  => true,
            CURLOPT_TIMEOUT         => $timeout,
            CURLOPT_USERAGENT       => 'Tiki-MarkdownImport',
            CURLOPT_FAILONERROR     => false,
        ]);

        $ok   = curl_exec($ch);
        $err  = curl_error($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        if (! $ok || $code >= 400) {
            @unlink($tmp);
            $msg = sprintf('HTTP %d while downloading %s', (int)$code, $url);
            if ($err) {
                $msg .= " — $err";
            }
            throw new \RuntimeException($msg);
        }

        return $tmp;
    }
}
