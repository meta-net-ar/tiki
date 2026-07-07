<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Importer\Markdown\Source;

/**
 * Value object for markdown source configuration.
 *
 * This wraps the legacy config array so callers can progressively move away
 * from raw arrays while preserving backward compatibility.
 */
class SourceConfig
{
    private const CFG_TYPE = 'type';
    private const CFG_SOURCE_ID = 'source_id';
    private const CFG_LOCAL_PATH = 'local_path';
    private const CFG_REPO_URL = 'repo_url';
    private const CFG_REPO_BRANCH = 'repo_branch';
    private const CFG_GIT_PULL = 'git_pull';
    private const CFG_REPO_TOKEN = 'repo_token';
    private const CFG_GIT_TIMEOUT = 'git_timeout';

    private const DEF_TYPE = 'local';
    private const DEF_BRANCH = 'main';
    private const DEF_TIMEOUT = 30;

    private array $values;

    private function __construct(array $values)
    {
        $this->values = $values;
    }

    public static function fromArray(array $cfg): self
    {
        return new self($cfg);
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function getType(): string
    {
        return (string) ($this->values[self::CFG_TYPE] ?? self::DEF_TYPE);
    }

    public function getSourceId(): ?string
    {
        $sourceId = $this->values[self::CFG_SOURCE_ID] ?? null;
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        return (string) $sourceId;
    }

    public function getLocalPath(): ?string
    {
        $localPath = $this->values[self::CFG_LOCAL_PATH] ?? null;
        if ($localPath === null || $localPath === '') {
            return null;
        }

        return (string) $localPath;
    }

    public function getRepoUrl(): string
    {
        return (string) ($this->values[self::CFG_REPO_URL] ?? '');
    }

    public function getRepoBranch(): string
    {
        return (string) ($this->values[self::CFG_REPO_BRANCH] ?? self::DEF_BRANCH);
    }

    public function shouldGitPull(): bool
    {
        return ! empty($this->values[self::CFG_GIT_PULL]);
    }

    public function getRepoToken(): ?string
    {
        $token = $this->values[self::CFG_REPO_TOKEN] ?? null;
        if ($token === null || $token === '') {
            return null;
        }

        return (string) $token;
    }

    public function getGitTimeout(): int
    {
        return (int) ($this->values[self::CFG_GIT_TIMEOUT] ?? self::DEF_TIMEOUT);
    }
}
