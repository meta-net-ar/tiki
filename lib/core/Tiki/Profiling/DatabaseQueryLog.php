<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Profiling;

/**
 * Allows displaying the relative time spent in every SQL queries, relative to each other, and relative to total php processing time.  Allows finding slow queries, unecessary duplicate queries, and aggregate time of prepared statements.
 *
 * Be carefull, this necessarily leaks private data onscreen.
 *
 * See documentation of isEnabled() to activate
 */
class DatabaseQueryLog
{
    private static $queryLog = [
        0 => [], //Queries with their parameters
        1 => [], //Prepared statements without their parameters
        //TODO:  Implement heuristics to separate unified index, and read vs writes - benoitg- 2024-09-30
        2 => [], //Query groups
        3 => [] //Global time for all queries
        ];
    private static $startTimesInProgress = [];



    public const TOTAL_TIME_MS = 'TOTAL_TIME_MS';
    public const TOTAL_COUNT = 'TOTAL_COUNT';
    public const DESCRIPTION_TEXT = 'DESCRIPTION_TEXT';
    public const EXECUTABLE_SQL_TEXT = 'EXECUTABLE_SQL_TEXT';
    public const PERCENT_OF_REQUEST_TIME = 'PERCENT_OF_REQUEST_TIME';
    public const PERCENT_OF_LEVEL_TIME = 'PERCENT_OF_LEVEL_TIME';
    public const PERCENT_OF_PARENT_GROUP_TIME = 'PERCENT_OF_PARENT_GROUP_TIME';

    public const SORT_BY_TIME = 'SORT_BY_TIME';
    public const SORT_BY_COUNT = 'SORT_BY_COUNT';
    private static string $sortField = self::TOTAL_TIME_MS;
    /**
    * Is the logging system enabled.
    *
    * Someone needs to plug this into the preference system. * - benoitg - 2024-09-30
    *
    * In the meantime, you have to put:
    * define("LOG_SQL_QUERIES", true);
    * somewhere, such as db/local.php
    * @return boolean
    */
    public static function isEnabled(): bool
    {
        return defined("LOG_SQL_QUERIES") && constant("LOG_SQL_QUERIES") == true;
    }

    public static function getLabels(): array
    {
        return [
        self::PERCENT_OF_REQUEST_TIME => tr('% of PHP time'),
        self::PERCENT_OF_PARENT_GROUP_TIME => tr('% of group time'),
        self::PERCENT_OF_LEVEL_TIME => tr('% of SQL time'),
        self::TOTAL_TIME_MS => tr('Total time (ms)'),
        self::TOTAL_COUNT => tr('Count'),
        self::DESCRIPTION_TEXT => tr('Description'),
        self::EXECUTABLE_SQL_TEXT => tr('Executable SQL')
        ];
    }
    /** Logs a sql query for profiling purposes */
    public static function logStart(string $queryText, ?array $queryParams = null, string $queryGroup = 'Ungrouped'): string
    {
        if (self::isEnabled()) {
            $queryParams = $queryParams ?? [];
            $paramsText = implode(',', array_map(
                function ($v, $k) {
                    return $k . ':' . $v;
                },
                $queryParams,
                array_keys($queryParams)
            ));
            $queryAndParamsHash = hash('xxh3', $queryGroup . $queryText . $paramsText);
            //As early as possible...
            self::$startTimesInProgress[$queryAndParamsHash] = microtime(true);

            $queryHash = hash('xxh3', $queryGroup . $queryText);

            $levels[0] = [
            'hash' => $queryAndParamsHash,
            self::DESCRIPTION_TEXT => $paramsText ? $paramsText : tr("Not a prepared statement"),
            'parentLevelEntryHash' => $queryHash
            ];

            $queryGroupHash = hash('xxh3', $queryGroup);

            $levels[1] = [
            'hash' => $queryHash,
            self::DESCRIPTION_TEXT => $queryText,
            'parentLevelEntryHash' => $queryGroupHash
            ];

            $globalHash = hash('xxh3', "Global");

            $levels[2] = [
            'hash' => $queryGroupHash,
            self::DESCRIPTION_TEXT => $queryGroup,
            'parentLevelEntryHash' => $globalHash
            ];

            $levels[3] = [
            'hash' => $globalHash,
            self::DESCRIPTION_TEXT => tr('All database queries'),
            'parentLevelEntryHash' => null
            ];

            foreach ($levels as $level => $levelInfo) {
                if (! array_key_exists($levelInfo['hash'], self::$queryLog[$level])) {
                    self::$queryLog[$level][$levelInfo['hash']] = [];
                    self::$queryLog[$level][$levelInfo['hash']]['parentLevelEntryHash'] = $levelInfo['parentLevelEntryHash'];
                    self::$queryLog[$level][$levelInfo['hash']][self::DESCRIPTION_TEXT] = $levelInfo[self::DESCRIPTION_TEXT];
                    if ($level === 0) {
                        $executableText = self::inlineSqlParams($queryText, $queryParams);
                    } else {
                        $executableText = null;
                    }
                    self::$queryLog[$level][$levelInfo['hash']][self::EXECUTABLE_SQL_TEXT] = $executableText;
                    self::$queryLog[$level][$levelInfo['hash']][self::TOTAL_COUNT] = 0;
                    self::$queryLog[$level][$levelInfo['hash']][self::TOTAL_TIME_MS] = 0.0;
                }
                self::$queryLog[$level][$levelInfo['hash']][self::TOTAL_COUNT]++;
            }
            return $queryAndParamsHash;
        } else {
            return '';
        }
    }
    public static function logEnd(string $handle): void
    {
        if (self::isEnabled()) {
            $end = microtime(true);
            $hash = $handle;
            $start = self::$startTimesInProgress[$hash];
            unset(self::$startTimesInProgress[$hash]);
            $elapsed = $end - $start;

            //This is implemented this way so we can do per level min, max, variance, etc.
            for ($level = 0; $level <= 3; $level++) {
                self::$queryLog[$level][$hash][self::TOTAL_TIME_MS] = self::$queryLog[$level][$hash][self::TOTAL_TIME_MS] + $elapsed * 1000;
                //var_dump(self::$queryLog[$level][$hash]);
                $hash = self::$queryLog[$level][$hash]['parentLevelEntryHash'];
            }
            return;
        }
    }

    public static function processLog(bool $inlineParameters = false): array
    {
        //TODO:  This does not exclude time spent logging the queries, which may or may not be significant.
        $phpTotalWallClockMS = (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000;
        if (! self::isEnabled()) {
            return [];
        }
        $log = self::$queryLog;

        //Sort by sortfield, default is most time consuming first
        $sortFunction = function ($a, $b) {
            if ($a[self::$sortField] == $b[self::$sortField]) {
                return 0;
            }
                    return ($a[self::$sortField] > $b[self::$sortField]) ? -1 : 1;
        };

        for ($level = 0; $level <= 3; $level++) {
            uasort($log[$level], $sortFunction);

            $levelTotalTime = 0.0;
            foreach ($log[$level] as $entry) {
                //Compute total time for level
                $levelTotalTime += $entry[self::TOTAL_TIME_MS];
            }
            //TODO:  Compute fraction of time for group


            foreach ($log[$level] as $hash => $entry) {
                //Compute fraction of time for level
                $log[$level][$hash][self::PERCENT_OF_LEVEL_TIME] = ($entry[self::TOTAL_TIME_MS] / $levelTotalTime) * 100;

                //Compute fraction total request time
                $log[$level][$hash][self::PERCENT_OF_REQUEST_TIME] = ($entry[self::TOTAL_TIME_MS] / $phpTotalWallClockMS) * 100;
                if ($entry['parentLevelEntryHash']) {
                    $parentLevelEntry =& $log[$level + 1][$entry['parentLevelEntryHash']];

                    //Compute fraction of parent entry time
                    $log[$level][$hash][self::PERCENT_OF_PARENT_GROUP_TIME] = ($entry[self::TOTAL_TIME_MS] / $parentLevelEntry[self::TOTAL_TIME_MS]) * 100;

                    //Double-link the list so it can be navigated top down
                    if (! isset($parentLevelEntry['children'])) {
                        $parentLevelEntry['children'] = [];
                    }
                    //Store by reference to save RAM
                    $parentLevelEntry['children'][$hash] =& $log[$level][$hash];
                }
            }
        }
        return $log;
    }

    private static function inlineSqlParams($sql, ?array $params = null): string
    {
        if ($params) {
            $indexed = $params == array_values($params);
            foreach ($params as $k => $v) {
                if (is_object($v)) {
                    if ($v instanceof \DateTime) {
                        $v = $v->format('Y-m-d H:i:s');
                    } else {
                        continue;
                    }
                } elseif (is_string($v)) {
                    $v = "'$v'";
                } elseif ($v === null) {
                    $v = 'NULL';
                } elseif (is_array($v)) {
                    $v = implode(',', $v);
                }

                if ($indexed) {
                    $sql = preg_replace('/\?/', $v, $sql, 1);
                } else {
                    if ($k[0] != ':') {
                        $k = ':' . $k; //add leading colon if it was left out
                    }
                    $sql = str_replace($k, $v, $sql);
                }
            }
        }
        return $sql;
    }
}
