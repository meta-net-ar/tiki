<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace TikiDevTools;

require_once __DIR__ . '/../../path_constants.php';

/**
 * Checks SQL migration files for unquoted integer comparisons on string columns.
 *
 * Context: https://gitlab.com/tikiwiki/tiki/-/merge_requests/5641#note_2123662594
 * When a varchar column is compared to an unquoted integer (e.g. WHERE `status`=0),
 * MySQL strict mode (mostly Linux) throws SQLSTATE[22007] type mismatch errors.
 *
 * We flag UPDATE statements where the same column gets a string in SET but
 * an unquoted integer in WHERE, since that's the pattern that breaks.
 *
 * Available commands:
 *   -f: auto-fix issues
 *   -o and --output: save results to a file
 */
class CheckSchemaFieldFormat
{
    /**
     * Execute check
     */
    public function execute()
    {
        $result = $this->checkSqlFiles();
        return $result['error_count'] - $result['fixed_count'];
    }

    protected function getOpts()
    {
        $short_opts = "o:f::";
        $long_opts = [
            "output",
        ];
        $options = getopt($short_opts, $long_opts);
        return ($options);
    }

    protected function printMessage($message, $outputPath = null)
    {
        echo "\033[0;32m" . $message . "\033[0m" . PHP_EOL;
        if (! empty($outputPath)) {
            file_put_contents($outputPath, $message . PHP_EOL, FILE_APPEND);
        }
    }

    protected function printMessageError($message, $outputPath = null)
    {
        echo "\033[0;31m" . $message . "\033[0m" . PHP_EOL;
        if (! empty($outputPath)) {
            file_put_contents($outputPath, $message . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Checks one SQL file for type-mismatch patterns.
     * Flags UPDATE queries where the same column gets a string in SET
     * but is compared against an unquoted integer in WHERE.
     *
     * @param string $path file to check
     * @param bool $shouldFix auto-fix issues
     * @param string|null $outputPath optional file to write results
     * @return array error_count and fixed_count
     */
    protected function checkFile($path, $shouldFix, $outputPath)
    {
        $sqlFile = file_get_contents($path);
        if ($sqlFile === false) {
            $this->printMessageError('Unable to open file: ' . $path);
            return ['error_count' => 0, 'fixed_count' => 0];
        }

        $this->printMessage('Checking ' . basename($path), $outputPath);

        $errorCount = 0;
        $fixedCount = 0;
        $queries = explode(';', $sqlFile);

        // Rejoin queries split on semicolons that were inside string literals
        // A query with an odd number of quotes means it was split mid-string
        $queryCount = count($queries);
        for ($i = 0; $i < $queryCount - 1; $i++) {
            $quoteCount = substr_count($queries[$i], "'");
            if ($quoteCount % 2 !== 0) {
                array_splice($queries, $i, 2, $queries[$i] . ";" . $queries[$i + 1]);
                $queryCount--;
                $i--; // re-check the merged query in case it still has odd quotes
            }
        }

        foreach ($queries as $idx => $query) {
            // Strip SQL comment lines before normalizing
            $cleanedQuery = preg_replace('/--.*$/m', '', $query);
            $normalizedQuery = str_replace(["\r", "\n"], ['', ' '], trim($cleanedQuery));

            // We only check UPDATE statements since DELETE has no SET clause to cross-reference
            if (! preg_match('/^\s*UPDATE/i', $normalizedQuery)) {
                continue;
            }

            // Step 1: Find columns that get string values in the SET part
            $stringSetColumns = [];
            if (preg_match_all('/`(\w+)`\s*=\s*\'[^\']*\'/i', $normalizedQuery, $setMatches)) {
                // Only look at columns before WHERE (i.e. in the SET part)
                $wherePos = stripos($normalizedQuery, 'WHERE');
                if ($wherePos === false) {
                    continue;
                }
                $setPart = substr($normalizedQuery, 0, $wherePos);
                if (preg_match_all('/`(\w+)`\s*=\s*\'[^\']*\'/i', $setPart, $setPartMatches)) {
                    $stringSetColumns = array_map('strtolower', $setPartMatches[1]);
                }
            }

            if (empty($stringSetColumns)) {
                continue;
            }

            // Step 2: Find unquoted integer comparisons in WHERE
            $wherePos = stripos($normalizedQuery, 'WHERE');
            if ($wherePos === false) {
                continue;
            }
            $wherePart = substr($normalizedQuery, $wherePos);

            // Match: `column`=<number> where number is NOT followed by a quote
            if (
                preg_match_all(
                    '/`(\w+)`\s*=\s*(\d+)(?!\s*\')/i',
                    $wherePart,
                    $whereMatches,
                    PREG_SET_ORDER
                )
            ) {
                foreach ($whereMatches as $match) {
                    $columnName = strtolower($match[1]);
                    $bareValue = $match[2];

                    // Only flag if this column also gets a string in SET
                    if (! in_array($columnName, $stringSetColumns)) {
                        continue;
                    }

                    $lineNum = $this->getLineNumber($sqlFile, $match[0]);
                    $errorCount++;

                    $message = "\t-- " . basename($path) . " (line ~$lineNum): "
                        . "WHERE `$columnName`=$bareValue should be WHERE `$columnName`='$bareValue' "
                        . "(same column receives string value in SET; unquoted integer causes type mismatch on MySQL strict mode)";
                    $this->printMessageError($message, $outputPath);

                    if ($shouldFix) {
                        $queries[$idx] = preg_replace(
                            '/(`' . preg_quote($columnName, '/') . '`\s*=\s*)' . preg_quote($bareValue, '/') . '(?!\s*\')/',
                            "$1'$bareValue'",
                            $queries[$idx]
                        );
                    }
                }
            }
        }


        if ($errorCount > 0 && $shouldFix) {
            $success = file_put_contents($path, implode(";", $queries));
            if ($success !== false) {
                $fixedCount = $errorCount;
                $this->printMessage('Saved fixed content', $outputPath);
            } else {
                $this->printMessageError('Failed to save fixed content', $outputPath);
            }
        }

        return ['error_count' => $errorCount, 'fixed_count' => $fixedCount];
    }

    /**
     * Gets the approximate line number for a match in the file.
     */
    private function getLineNumber($fileContent, $match)
    {
        $pos = strpos($fileContent, $match);
        if ($pos === false) {
            return 0;
        }
        return substr_count($fileContent, "\n", 0, $pos) + 1;
    }

    /**
     * Scans db/tiki.sql and all installer/schema/*.sql files.
     */
    protected function checkSqlFiles()
    {
        $this->printMessage('Checking started...');

        $options = $this->getOpts();
        $shouldFix = isset($options['f']);
        if ($shouldFix) {
            $this->printMessage('Will fix problems automatically if exist');
        } else {
            $this->printMessage('Just check and output problems');
        }

        $outputPath = null;
        if (! empty($options['o'])) {
            $outputPath = $options['o'];
        } elseif (! empty($options['output'])) {
            $outputPath = $options['output'];
        }
        if (! empty($outputPath)) {
            file_put_contents($outputPath, "");
        }

        $errorCount = 0;
        $fixedCount = 0;

        // Check db/tiki.sql
        $result = $this->checkFile(__DIR__ . '/../../db/tiki.sql', $shouldFix, $outputPath);
        $errorCount += $result['error_count'];
        $fixedCount += $result['fixed_count'];

        // Check all installer/schema/*.sql files
        $path = __DIR__ . '/../../' . TIKI_UPGRADE_SQL_SCHEMA_PATH;
        $filenameList = scandir($path);
        if ($filenameList === false) {
            $this->printMessageError("Scandir failed on $path");
        } else {
            foreach ($filenameList as $filename) {
                $ext = substr($filename, -4);
                if ($ext === '.sql') {
                    $result = $this->checkFile(
                        $path . '/' . $filename,
                        $shouldFix,
                        $outputPath
                    );
                    $errorCount += $result['error_count'];
                    $fixedCount += $result['fixed_count'];
                }
            }
        }

        $this->printMessageError(
            $errorCount . " errors found" . ($shouldFix ? ", " . $fixedCount . " errors fixed" : "")
        );
        $this->printMessage('Completed');
        return ['error_count' => $errorCount, 'fixed_count' => $fixedCount];
    }
}

// Make sure script is run from a shell
if (PHP_SAPI !== 'cli') {
    die("Please run from a shell");
}

$checker = new CheckSchemaFieldFormat();
$errorCount = $checker->execute();

if ($errorCount > 0) {
    exit(1);
}
exit(0);
