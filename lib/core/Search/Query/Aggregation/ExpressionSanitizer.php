<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Search\Query\Aggregation;

use InvalidArgumentException;

/**
 * Minimal SQL-injection guard for user-authored expressions that the
 * compiler interpolates into the temp-table aggregation queries (formula
 * metrics, {having} clauses).
 *
 *  - no statement separators (;)
 *  - no comments (--, #, slash-star)
 *  - no backticks
 *  - no @-variables
 *  - no backslash escapes
 *  - no statement-level keywords (SELECT, UNION, DROP, INTO OUTFILE, ...)
 */
class ExpressionSanitizer
{
    private const MAX_LENGTH = 4096;

    private const FORBIDDEN_SUBSTRINGS = [
        ';'   => 'multiple statements not allowed',
        '--'  => 'SQL comments not allowed',
        '/*'  => 'SQL comments not allowed',
        '*/'  => 'SQL comments not allowed',
        '#'   => 'SQL comments not allowed',
        '`'   => 'backtick-quoted identifiers not allowed',
        '@'   => 'session/user variables not allowed',
        "\\"  => 'backslash escapes not allowed',
    ];

    private const FORBIDDEN_WORDS = [
        'select', 'insert', 'update', 'delete',
        'drop', 'create', 'alter', 'truncate', 'rename',
        'grant', 'revoke', 'union', 'intersect', 'except',
        'use', 'do', 'call', 'exec', 'execute',
        'lock', 'unlock', 'set', 'reset', 'flush',
        'into', 'outfile', 'dumpfile', 'infile',
        'load_file', 'load',
        'benchmark', 'sleep', 'get_lock', 'release_lock',
        'is_used_lock', 'is_free_lock',
        'database', 'schema', 'user', 'current_user',
        'session_user', 'system_user', 'version', 'datadir',
    ];

    private const AGG_FUNCTIONS = [
        'sum', 'avg', 'count', 'min', 'max',
        'group_concat', 'std', 'stddev', 'stddev_pop', 'stddev_samp',
        'variance', 'var_samp', 'var_pop',
        'json_arrayagg', 'json_objectagg',
        'bit_and', 'bit_or', 'bit_xor',
    ];

    private const KNOWN_NON_FIELD_TOKENS = [
        'and', 'or', 'not', 'xor', 'is', 'in', 'between', 'like', 'distinct', 'all',
        'true', 'false', 'null',
        'case', 'when', 'then', 'else', 'end', 'as', 'cast', 'convert',
        'asc', 'desc',
        'over', 'partition', 'by', 'rows', 'range',
        'unbounded', 'preceding', 'following', 'current', 'row',
        'from', 'where', 'group', 'having', 'order',
        'join', 'inner', 'outer', 'left', 'right', 'full', 'cross', 'on', 'using',
        'limit', 'offset',
        'interval', 'day', 'month', 'year', 'hour', 'minute', 'second', 'quarter', 'week',
        'decimal', 'float', 'double', 'integer', 'int', 'unsigned', 'signed',
        'char', 'varchar', 'binary', 'date', 'datetime', 'time', 'timestamp',
        'sum', 'avg', 'count', 'min', 'max', 'group_concat',
        'std', 'stddev', 'stddev_pop', 'stddev_samp',
        'variance', 'var_samp', 'var_pop',
        'json_arrayagg', 'json_objectagg', 'bit_and', 'bit_or', 'bit_xor',
        'abs', 'ceil', 'ceiling', 'floor', 'round', 'truncate', 'mod', 'sign',
        'power', 'pow', 'sqrt', 'exp', 'ln', 'log', 'log2', 'log10',
        'pi', 'rand', 'greatest', 'least',
        'coalesce', 'ifnull', 'nullif', 'if',
        'concat', 'concat_ws', 'length', 'char_length', 'substring', 'substr',
        'upper', 'lower', 'trim', 'ltrim', 'rtrim', 'replace', 'lpad', 'rpad',
        'reverse', 'locate', 'instr', 'format',
        'now', 'curdate', 'curtime', 'unix_timestamp', 'from_unixtime',
        'dayofweek', 'dayofmonth', 'dayofyear',
        'date_add', 'date_sub', 'date_format', 'datediff', 'timestampdiff', 'extract',
        'row_number', 'rank', 'dense_rank', 'percent_rank', 'cume_dist', 'ntile',
        'lag', 'lead', 'first_value', 'last_value', 'nth_value',
        'json_object', 'json_array', 'json_extract',
    ];

    /**
     * @throws InvalidArgumentException with a user-facing reason
     */
    public function sanitize(string $expression): string
    {
        $expression = trim($expression);
        if ($expression === '') {
            throw new InvalidArgumentException('expression is empty');
        }
        if (strlen($expression) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(sprintf(
                'expression too long (max %d chars)',
                self::MAX_LENGTH
            ));
        }
        foreach (self::FORBIDDEN_SUBSTRINGS as $needle => $why) {
            if (str_contains($expression, $needle)) {
                throw new InvalidArgumentException($why);
            }
        }
        $lower = strtolower($expression);
        foreach (self::FORBIDDEN_WORDS as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $lower) === 1) {
                throw new InvalidArgumentException(sprintf(
                    'expression contains forbidden keyword "%s"',
                    $word
                ));
            }
        }
        if (substr_count($expression, '(') !== substr_count($expression, ')')) {
            throw new InvalidArgumentException('unbalanced parentheses in expression');
        }
        return $expression;
    }

    public function isAggregate(string $expression): bool
    {
        $lower = strtolower($expression);
        foreach (self::AGG_FUNCTIONS as $fn) {
            if (preg_match('/\b' . preg_quote($fn, '/') . '\s*\(/', $lower) === 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string[]
     */
    public function extractIdentifiers(string $expression): array
    {
        if (! preg_match_all('/\b([a-z_][a-z0-9_]*)\b/i', $expression, $m)) {
            return [];
        }
        $skip = array_flip(self::KNOWN_NON_FIELD_TOKENS);
        $forbidden = array_flip(self::FORBIDDEN_WORDS);
        $seen = [];
        $out = [];
        foreach ($m[1] as $tok) {
            $low = strtolower($tok);
            if (isset($skip[$low]) || isset($forbidden[$low])) {
                continue;
            }
            if (isset($seen[$low])) {
                continue;
            }
            $seen[$low] = true;
            $out[] = $tok;
        }
        return $out;
    }
}
