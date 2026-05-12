<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

class SourceManager
{
    public const SRC_GIT = 'git';
    public const SRC_LOCAL = 'local';

    private const MODE_UPLOAD = 'upload';
    private const MODE_PATH = 'path';

    private const CFG_REPO_URL = 'repo_url';
    private const CFG_REPO_BRANCH = 'repo_branch';
    private const CFG_GIT_PULL = 'git_pull';
    private const CFG_REPO_TOKEN = 'repo_token';

    private const CTX_UPLOADED_PATH = 'uploadedPath';
    private const CTX_UPLOADED_NAME = 'uploadedName';
    private const CTX_TIMEOUT = 'timeout';

    private string $error = '';
    private ?string $sourceId = null;
    private array $lastFetchMeta = [];

    public function getError(): string
    {
        return $this->error;
    }

    public function getLastFetchMeta(): array
    {
        return $this->lastFetchMeta;
    }

    /**
     * Get a stable, unique identifier for this source.
     * Should be set explicitly in config via 'source_id' key.
     * Falls back to a hash-based identifier if not provided (not recommended).
     *
     * @param array|SourceConfig $cfg
     */
    public function getSourceIdentifier($cfg): string
    {
        return $this->getSourceIdentifierFromConfig($this->normalizeConfig($cfg));
    }

    /**
     * Get a stable, unique identifier for this source from a typed configuration object.
     */
    public function getSourceIdentifierFromConfig(SourceConfig $cfg): string
    {
        // Prefer explicit source_id from configuration
        $sourceId = $cfg->getSourceId();
        if ($sourceId !== null) {
            return $sourceId;
        }

        // Fallback: generate stable hash
        $type = $cfg->getType();

        if ($type === self::SRC_GIT) {
            $url = $cfg->getRepoUrl();
            $branch = $cfg->getRepoBranch();
            // Use hash of normalized repo info
            return 'git_' . hash('sha256', $url . ':' . $branch);
        }

        if ($type === self::SRC_LOCAL && $cfg->getLocalPath() !== null) {
            $localPath = $cfg->getLocalPath();
            return 'local_' . hash('sha256', realpath($localPath) ?: $localPath);
        }

        // Last resort: generic identifier with timestamp
        return 'source_' . hash('sha256', serialize($cfg->toArray()));
    }

    /**
     * Create a pre-configured SourceProvider based on configuration and context.
     * Separates Provider instantiation from execution for cleaner architecture.
     *
     * @param array $cfg Source configuration
     * @param ?string $uploadTmp Uploaded file path (for FilesystemProvider upload mode)
     * @param ?string $uploadName Uploaded file name (for FilesystemProvider upload mode)
     * @return SourceProviderInterface Pre-configured provider ready to fetch
     * @throws \RuntimeException if source type is unsupported
     */
    public function createProvider(array $cfg, ?string $uploadTmp = null, ?string $uploadName = null): SourceProviderInterface
    {
        $sourceConfig = SourceConfig::fromArray($cfg);
        $type = $sourceConfig->getType();

        if ($type === self::SRC_LOCAL) {
            // Prioritize upload if provided, otherwise use local path
            if ($uploadTmp) {
                return new FilesystemProvider(self::MODE_UPLOAD, $cfg, [
                    self::CTX_UPLOADED_PATH => $uploadTmp,
                    self::CTX_UPLOADED_NAME => $uploadName,
                ]);
            }
            return new FilesystemProvider(self::MODE_PATH, $cfg, []);
        }

        if ($type === self::SRC_GIT) {
            return new GitWorkingCopyProvider([
                self::CFG_REPO_URL      => $sourceConfig->getRepoUrl(),
                self::CFG_REPO_BRANCH   => $sourceConfig->getRepoBranch(),
                self::CFG_GIT_PULL      => $sourceConfig->shouldGitPull(),
                self::CFG_REPO_TOKEN    => $sourceConfig->getRepoToken(),
            ], [
                self::CTX_TIMEOUT       => $sourceConfig->getGitTimeout(),
            ]);
        }

        throw new \RuntimeException(tra('Unsupported source type:') . ' ' . $type);
    }

    /**
     * Fetch source to temporary directory using a pre-configured Provider.
     * Preferred method for clean separation of concerns.
     *
     * @param SourceProviderInterface $provider Pre-configured provider
     * @return ?string Path to temporary directory, or null on error
     */
    public function fetchWithProvider(SourceProviderInterface $provider): ?string
    {
        $this->error = '';
        $this->lastFetchMeta = [];
        try {
            $path = $provider->fetchToTempDir();
            if (method_exists($provider, 'getFetchMeta')) {
                $this->lastFetchMeta = (array) $provider->getFetchMeta();
            }
            return $path;
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            if (method_exists($provider, 'getFetchMeta')) {
                $this->lastFetchMeta = (array) $provider->getFetchMeta();
            }
            return null;
        }
    }

    /**
     * Convenience method that combines createProvider() + fetchWithProvider().
     * Kept for backward compatibility with existing callers.
     *
     * @param array $cfg Source configuration
     * @param ?string $uploadTmp Uploaded file path (optional)
     * @param ?string $uploadName Uploaded file name (optional)
     * @return ?string Path to temporary directory, or null on error
     */
    public function fetchToTempDir(array $cfg, ?string $uploadTmp = null, ?string $uploadName = null): ?string
    {
        try {
            $provider = $this->createProvider($cfg, $uploadTmp, $uploadName);
            return $this->fetchWithProvider($provider);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            return null;
        }
    }

    /**
     * @param array|SourceConfig $cfg
     */
    private function normalizeConfig($cfg): SourceConfig
    {
        if ($cfg instanceof SourceConfig) {
            return $cfg;
        }

        if (is_array($cfg)) {
            return SourceConfig::fromArray($cfg);
        }

        throw new \InvalidArgumentException('SourceManager expects source config as array or SourceConfig.');
    }
}
