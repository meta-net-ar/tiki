<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

class SourceManager
{
    private string $error = '';
    private ?string $sourceId = null;

    public function getError(): string
    {
        return $this->error;
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

        if ($type === 'git') {
            $url = $cfg->getRepoUrl();
            $branch = $cfg->getRepoBranch();
            // Use hash of normalized repo info
            return 'git_' . hash('sha256', $url . ':' . $branch);
        }

        if ($type === 'local' && $cfg->getLocalPath() !== null) {
            $localPath = $cfg->getLocalPath();
            return 'local_' . hash('sha256', realpath($localPath) ?: $localPath);
        }

        // Last resort: generic identifier with timestamp
        return 'source_' . hash('sha256', serialize($cfg->toArray()));
    }

    /**
     * @param array $cfg        ['type' => 'local'|'git', 'local_path'? , 'repo_url'? , 'git_pull'?]
     * @param ?string $uploadTmp
     * @param ?string $uploadName
     */
    public function fetchToTempDir(array $cfg, ?string $uploadTmp = null, ?string $uploadName = null): ?string
    {
        $sourceConfig = SourceConfig::fromArray($cfg);
        $this->error = '';
        try {
            $type = $sourceConfig->getType();

            if ($type === 'local') {
                // Prioritize upload if provided, otherwise use local path
                if ($uploadTmp) {
                    $prov = new FilesystemProvider('upload', $cfg, [
                        'uploadedPath' => $uploadTmp,
                        'uploadedName' => $uploadName,
                    ]);
                    return $prov->fetchToTempDir();
                }
                $prov = new FilesystemProvider('path', $cfg, []);
                return $prov->fetchToTempDir();
            }

            if ($type === 'git') {
                $prov = new GitWorkingCopyProvider([
                    'repo_url'      => $sourceConfig->getRepoUrl(),
                    'repo_branch'   => $sourceConfig->getRepoBranch(),
                    'git_pull'      => $sourceConfig->shouldGitPull(),
                    'repo_token'    => $sourceConfig->getRepoToken(), // optional
                ], [
                    'timeout'       => $sourceConfig->getGitTimeout(),
                ]);
                return $prov->fetchToTempDir();
            }

            throw new \RuntimeException(tra('Unsupported source type:') . ' ' . $type);
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
