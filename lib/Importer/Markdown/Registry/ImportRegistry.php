<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Importer\Markdown\Registry;

use Tiki\Lib\Importer\Markdown\Source\SourceConfig;
use Tiki\Lib\Importer\Markdown\Source\SourceManager;
use TikiLib;

/**
 * ImportRegistry
 * Manage the import registry table for markdown imports.
 */
class ImportRegistry
{
    public const TABLE = 'tiki_markdown_imports';
    private const COL_SOURCE_KEY = 'source_key';
    private const COL_RELPATH = 'relpath';
    private const COL_PAGE_NAME = 'page_name';
    private const COL_CHECKSUM = 'checksum';
    private const COL_SIZE_BYTES = 'size_bytes';
    private const COL_MTIME_UTC = 'mtime_utc';
    private const COL_LAST_IMPORTED = 'last_imported';
    private const COL_LAST_STATUS = 'last_status';
    private const COL_ERROR_NOTE = 'error_note';
    private const COL_IS_JOURNAL = 'is_journal';
    private const COL_ORPHANED_AT = 'orphaned_at';

    public const STATUS_OK = 'ok';
    public const STATUS_ORPHAN = 'orphan';

    /** @var \TikiLib */
    private $tikilib;

    public function __construct()
    {
        $this->tikilib = TikiLib::lib('tiki');
    }

    /**
     * Build a unique source key from source configuration.
     * @param array|SourceConfig $sourceCfg
     */
    public static function buildSourceKey($sourceCfg): string
    {
        $manager = new SourceManager();
        return $manager->getSourceIdentifier($sourceCfg);
    }

    public function getAllBySourceKey(string $sourceKey): array
    {
        $out = [];
        $rs = $this->tikilib->query(
            "SELECT * FROM " . self::TABLE . " WHERE " . self::COL_SOURCE_KEY . " = ?",
            [$sourceKey]
        );
        while ($row = $rs->fetchRow()) {
            $out[$row[self::COL_RELPATH]] = $row;
        }
        return $out;
    }

    public function getOne(string $sourceKey, string $relpath): ?array
    {
        $rs = $this->tikilib->query(
            "SELECT * FROM " . self::TABLE . " WHERE " . self::COL_SOURCE_KEY . " = ? AND " . self::COL_RELPATH . " = ?",
            [$sourceKey, $relpath]
        );
        return $rs->numRows() ? $rs->fetchRow() : null;
    }

    /**
     * Get all orphaned entries for a given source.
     * Returns pages that were present in previous imports but missing from recent ones.
     * Useful for audit/recovery and showing marked-but-not-deleted pages outside safe_namespace.
     *
     * @param string $sourceKey Source identifier
     * @return array Associative array keyed by relpath => row data
     */
    public function getOrphanedBySourceKey(string $sourceKey): array
    {
        $out = [];
        $rs = $this->tikilib->query(
            "SELECT * FROM " . self::TABLE . " WHERE " . self::COL_SOURCE_KEY . " = ? AND " . self::COL_LAST_STATUS . " = ? ORDER BY " . self::COL_ORPHANED_AT . " DESC",
            [$sourceKey, self::STATUS_ORPHAN]
        );
        while ($row = $rs->fetchRow()) {
            $out[$row[self::COL_RELPATH]] = $row;
        }
        return $out;
    }

    /**
     * Insert or update an entry in the registry.
     * @return bool True if the operation succeeded, false otherwise.
     * @throws \Exception if the query fails critically.
     */
    public function upsert(array $row): bool
    {
        $now = $this->tikilib->now;

        $q = "INSERT INTO " . self::TABLE . "
                (" . self::COL_SOURCE_KEY . ", " . self::COL_RELPATH . ", " . self::COL_PAGE_NAME . ", " . self::COL_CHECKSUM . ", " . self::COL_SIZE_BYTES . ", " . self::COL_MTIME_UTC . ", " . self::COL_LAST_IMPORTED . ", " . self::COL_LAST_STATUS . ", " . self::COL_ERROR_NOTE . ", " . self::COL_IS_JOURNAL . ", " . self::COL_ORPHANED_AT . ")
              VALUES (?,?,?,?,?,?,?,?,?,?,NULL)
              ON DUPLICATE KEY UPDATE
                " . self::COL_PAGE_NAME . "     = VALUES(" . self::COL_PAGE_NAME . "),
                " . self::COL_CHECKSUM . "      = VALUES(" . self::COL_CHECKSUM . "),
                " . self::COL_SIZE_BYTES . "    = VALUES(" . self::COL_SIZE_BYTES . "),
                " . self::COL_MTIME_UTC . "     = VALUES(" . self::COL_MTIME_UTC . "),
                " . self::COL_LAST_IMPORTED . " = VALUES(" . self::COL_LAST_IMPORTED . "),
                " . self::COL_LAST_STATUS . "   = VALUES(" . self::COL_LAST_STATUS . "),
                " . self::COL_ERROR_NOTE . "    = VALUES(" . self::COL_ERROR_NOTE . "),
                " . self::COL_IS_JOURNAL . "    = VALUES(" . self::COL_IS_JOURNAL . "),
                " . self::COL_ORPHANED_AT . "   = NULL";

        try {
            $result = $this->tikilib->query($q, [
                (string) $row[self::COL_SOURCE_KEY],
                (string) $row[self::COL_RELPATH],
                (string) $row[self::COL_PAGE_NAME],
                (string) $row[self::COL_CHECKSUM],
                isset($row[self::COL_SIZE_BYTES]) ? (int) $row[self::COL_SIZE_BYTES] : null,
                isset($row[self::COL_MTIME_UTC]) ? (int) $row[self::COL_MTIME_UTC] : null,
                isset($row[self::COL_LAST_IMPORTED]) ? (int) $row[self::COL_LAST_IMPORTED] : $now,
                (string) ($row[self::COL_LAST_STATUS] ?? self::STATUS_OK),
                $row[self::COL_ERROR_NOTE] ?? null,
                isset($row[self::COL_IS_JOURNAL]) ? (int) $row[self::COL_IS_JOURNAL] : 0,
            ]);
            return $result !== false;
        } catch (\Exception $e) {
            throw new \Exception("Failed to upsert registry entry for {$row[self::COL_RELPATH]}: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Mark a registry entry as orphaned.
     * Called by garbage collector (TikiMarkdownImporter::runGarbageCollect)
     * when a file is missing from the current import.
     */
    public function markOrphan(string $sourceKey, string $relpath, int $timestamp): void
    {
        $q = "UPDATE " . self::TABLE . "
                SET " . self::COL_LAST_STATUS . "='" . self::STATUS_ORPHAN . "',
                    " . self::COL_ORPHANED_AT . " = IFNULL(" . self::COL_ORPHANED_AT . ", ?)
              WHERE " . self::COL_SOURCE_KEY . "=? AND " . self::COL_RELPATH . "=?";
        $this->tikilib->query($q, [$timestamp, $sourceKey, $relpath]);
    }

    public function setStatus(string $sourceKey, string $relpath, string $status, ?string $note = null): void
    {
        $q = "UPDATE " . self::TABLE . "
                SET " . self::COL_LAST_STATUS . " = ?, " . self::COL_ERROR_NOTE . " = ?
              WHERE " . self::COL_SOURCE_KEY . "=? AND " . self::COL_RELPATH . "=?";
        $this->tikilib->query($q, [$status, $note, $sourceKey, $relpath]);
    }

    /**
     * Delete a registry entry.
     * Called by garbage collector (TikiMarkdownImporter::runGarbageCollect)
     * in 'delete' mode after the corresponding Tiki page has been deleted.
     * This removes the orphaned entry from tracking.
     */
    public function deleteEntry(string $sourceKey, string $relpath): void
    {
        $this->tikilib->query(
            "DELETE FROM " . self::TABLE . " WHERE source_key=? AND relpath=?",
            [$sourceKey, $relpath]
        );
    }
}
