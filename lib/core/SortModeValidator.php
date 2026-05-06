<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//
// This class provides validation utilities for sort modes used in queries.

namespace Tiki;

use Feedback;
use TikiDb;

class SortModeValidator
{
    /**
     * Validates a sort mode against physical database columns.
     *
     * The base column name is extracted by stripping the optional sort direction
     * suffix ("_asc" or "_desc"). The resulting column is then validated against
     * the actual columns of the provided database tables.
     *
     * This method performs **schema-based validation only**:
     * - It does not validate aliases, computed fields, or expressions.
     * - It does not perform SQL syntax validation.
     * - It does not apply defaults or fallbacks.
     *
     * @param ?string $requestedSortMode User-provided sort mode (e.g. "lastModif_desc").
     * @param array $tablesToValidate List of database tables whose columns are allowed.
     * @param ?string &$errorMessage Receives a translated error message on failure.
     * @return ?string The original sort mode if valid, or null if invalid.
     */
    public static function validateAgainstTables(
        ?string $requestedSortMode,
        array $tablesToValidate,
        ?string &$errorMessage = null
    ): ?string {
        global $db;

        if (empty($requestedSortMode)) {
            return null;
        }

        // Strip direction suffix (_asc / _desc)
        $column = preg_replace('/_(asc|desc)$/i', '', $requestedSortMode);

        if ($column === '') {
            $errorMessage = tra('Invalid sort mode.');
            return null;
        }

        $columns = $db->getColumnNamesForTable($tablesToValidate);

        if (in_array($column, $columns, true)) {
            return $requestedSortMode;
        }

        $errorMessage = tra('Invalid sort column.');
        return null;
    }


    /**
     * Validates a sort mode against an explicit whitelist of allowed column names.
     *
     * The base column name is extracted by stripping the optional sort direction
     * suffix ("_asc" or "_desc"). The resulting column is then checked against the
     * provided whitelist.
     *
     * This method performs **explicit whitelist validation only**:
     * - It does not access the database.
     * - It does not validate physical table columns.
     * - It is intended for aliases or computed sort keys defined by the caller.
     *
     * @param ?string $requestedSortMode User-provided sort mode (e.g. "hits_asc").
     * @param array $allowedSortModes List of allowed base column names.
     * @param ?string &$errorMessage Receives a translated error message on failure.
     * @return ?string The original sort mode if valid, or null if invalid.
     */
    public static function validateAgainstWhitelist(
        ?string $requestedSortMode,
        array $allowedSortModes,
        ?string &$errorMessage = null
    ): ?string {
        if (empty($requestedSortMode)) {
            return null;
        }

        $column = preg_replace('/_(asc|desc)$/i', '', $requestedSortMode);

        if ($column === '') {
            $errorMessage = tra('Invalid sort mode.');
            return null;
        }

        if (in_array($column, $allowedSortModes, true)) {
            return $requestedSortMode;
        }

        $errorMessage = tra('Invalid sort column.');
        return null;
    }

    /**
     * High-level validation helper for interactive pages.
     *
     * This method centralizes sort mode validation and user feedback.
     * A sort mode is considered valid if it matches:
     *   1) A physical column in the provided database tables, OR
     *   2) A whitelisted non-physical alias (pureAliases).
     *
     * pureAliases should only be provided when the result set exposes
     * computed or non-physical columns that can safely be used for sorting.
     * In typical cases, only physical table validation is required.
     *
     * If validation fails, a warning is displayed and an empty string is returned.
     *
     * @param ?string $requestedSortMode User-provided sort mode (e.g. "column_desc").
     * @param array   $tablesToValidate  Database tables to validate against.
     * @param array   $pureAliases       Optional whitelist of allowed aliases.
     * @return string Valid sort mode or empty string if invalid.
     */
    public static function validateSortModeOrFeedback(
        ?string $requestedSortMode,
        array $tablesToValidate,
        array $pureAliases = []
    ): string {
        $errorMessage = null;

        // Try database-backed validation (explicit)
        $validatedSortMode = self::validateAgainstTables(
            $requestedSortMode,
            $tablesToValidate,
            $errorMessage
        );

        //  If DB validation failed AND aliases are explicitly allowed, try whitelist
        if ($validatedSortMode === null && ! empty($pureAliases)) {
            $validatedSortMode = self::validateAgainstWhitelist(
                $requestedSortMode,
                $pureAliases,
                $errorMessage
            );
        }

        // Feedback once, if everything failed
        if ($validatedSortMode === null) {
            global $db;

            $allowedModes = [];

            if ($db && ! empty($tablesToValidate)) {
                $allowedModes = $db->getColumnNamesForTable($tablesToValidate);
            }

            if (! empty($pureAliases)) {
                $allowedModes = array_merge($allowedModes, $pureAliases);
            }

            $allowedModes = array_unique($allowedModes);

            if (! empty($allowedModes)) {
                Feedback::warning(sprintf(
                    tra(
                        "The requested sort mode %s is not supported and has been ignored. Allowed sort modes are: %s."
                    ),
                    $requestedSortMode,
                    implode(', ', $allowedModes)
                ));
            } else {
                Feedback::warning(
                    tra("The requested sort mode is not supported and has been ignored.")
                );
            }

            return '';
        }

        return $validatedSortMode;
    }
}
