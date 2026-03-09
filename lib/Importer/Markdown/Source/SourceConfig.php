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
        return (string) ($this->values['type'] ?? 'local');
    }

    public function getSourceId(): ?string
    {
        $sourceId = $this->values['source_id'] ?? null;
        if ($sourceId === null || $sourceId === '') {
            return null;
        }

        return (string) $sourceId;
    }

    public function getLocalPath(): ?string
    {
        $localPath = $this->values['local_path'] ?? null;
        if ($localPath === null || $localPath === '') {
            return null;
        }

        return (string) $localPath;
    }

    public function getRepoUrl(): string
    {
        return (string) ($this->values['repo_url'] ?? '');
    }

    public function getRepoBranch(): string
    {
        return (string) ($this->values['repo_branch'] ?? 'main');
    }

    public function shouldGitPull(): bool
    {
        return ! empty($this->values['git_pull']);
    }

    public function getRepoToken(): ?string
    {
        $token = $this->values['repo_token'] ?? null;
        if ($token === null || $token === '') {
            return null;
        }

        return (string) $token;
    }

    public function getGitTimeout(): int
    {
        return (int) ($this->values['git_timeout'] ?? 45);
    }
}
