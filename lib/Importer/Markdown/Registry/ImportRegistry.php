<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Importer\Markdown\Registry;

use TikiLib;

/**
 * ImportRegistry
 * Manage the import registry table for markdown imports.
 */
class ImportRegistry
{
    public const TABLE = 'tiki_markdown_imports';

    /** @var \TikiLib */
    private $tikilib;

    public function __construct()
    {
        $this->tikilib = TikiLib::lib('tiki');
    }

    /**
     * Build a unique source key from source configuration.
     */
    public static function buildSourceKey(array $sourceCfg): string
    {
        $type = $sourceCfg['type'] ?? 'zip';

        if ($type === 'git') {
            $repoUrl = $sourceCfg['repo_url'];
            $host  = parse_url($repoUrl, PHP_URL_HOST) ?: 'unknown';
            //get owner/repo from url
            if (preg_match('~[:/](?P<owner>[^/]+)/(?P<repo>[^/.]+)(\.git)?$~', $repoUrl, $matches)) {
                $owner = $matches['owner'];
                $repo  = $matches['repo'];
            } else {
                $owner = 'unknown';
                $repo  = 'unknown';
            }
            $ref   = $sourceCfg['repo_branch'] ?? 'main';
            return "git:$host/$owner/$repo@$ref";
        }

        if ($type === 'filesystem') {
            $root = $sourceCfg['root'] ?? '';
            return "fs:$root";
        }

        // fallback zip
        $zip = $sourceCfg['zip_path'] ?? ($sourceCfg['tmp_zip'] ?? '');
        return "zip:$zip";
    }

    public function getAllBySourceKey(string $sourceKey): array
    {
        $out = [];
        $rs = $this->tikilib->query(
            "SELECT * FROM " . self::TABLE . " WHERE source_key = ?",
            [$sourceKey]
        );
        while ($row = $rs->fetchRow()) {
            $out[$row['relpath']] = $row;
        }
        return $out;
    }

    public function getOne(string $sourceKey, string $relpath): ?array
    {
        $rs = $this->tikilib->query(
            "SELECT * FROM " . self::TABLE . " WHERE source_key = ? AND relpath = ?",
            [$sourceKey, $relpath]
        );
        return $rs->numRows() ? $rs->fetchRow() : null;
    }

    /**
     * Insert or update an entry in the registry.
     */
    public function upsert(array $row): void
    {
        $now = $this->tikilib->now;

        $q = "INSERT INTO " . self::TABLE . "
                (source_key, relpath, page_name, checksum, size_bytes, mtime_utc, last_imported, last_status, error_note, is_journal, orphaned_at)
              VALUES (?,?,?,?,?,?,?,?,?,?,NULL)
              ON DUPLICATE KEY UPDATE
                page_name     = VALUES(page_name),
                checksum      = VALUES(checksum),
                size_bytes    = VALUES(size_bytes),
                mtime_utc     = VALUES(mtime_utc),
                last_imported = VALUES(last_imported),
                last_status   = VALUES(last_status),
                error_note    = VALUES(error_note),
                is_journal    = VALUES(is_journal),
                orphaned_at   = NULL";

        $this->tikilib->query($q, [
            (string) $row['source_key'],
            (string) $row['relpath'],
            (string) $row['page_name'],
            (string) $row['checksum'],
            isset($row['size_bytes']) ? (int) $row['size_bytes'] : null,
            isset($row['mtime_utc']) ? (int) $row['mtime_utc'] : null,
            isset($row['last_imported']) ? (int) $row['last_imported'] : $now,
            (string) ($row['last_status'] ?? 'ok'),
            $row['error_note'] ?? null,
            isset($row['is_journal']) ? (int) $row['is_journal'] : 0,
        ]);
    }

    public function markOrphan(string $sourceKey, string $relpath, int $timestamp): void
    {
        $q = "UPDATE " . self::TABLE . "
                SET last_status='orphan',
                    orphaned_at = IFNULL(orphaned_at, ?)
              WHERE source_key=? AND relpath=?";
        $this->tikilib->query($q, [$timestamp, $sourceKey, $relpath]);
    }

    public function setStatus(string $sourceKey, string $relpath, string $status, ?string $note = null): void
    {
        $q = "UPDATE " . self::TABLE . "
                SET last_status = ?, error_note = ?
              WHERE source_key=? AND relpath=?";
        $this->tikilib->query($q, [$status, $note, $sourceKey, $relpath]);
    }

    public function deleteEntry(string $sourceKey, string $relpath): void
    {
        $this->tikilib->query(
            "DELETE FROM " . self::TABLE . " WHERE source_key=? AND relpath=?",
            [$sourceKey, $relpath]
        );
    }
}
