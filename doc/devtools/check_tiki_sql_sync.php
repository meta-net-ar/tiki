<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace TikiDevTools;

/**
 * Check if db/tiki.sql needs updating when schema patches contain structural changes.
 * Structural SQL (CREATE/ALTER/DROP TABLE) requires tiki.sql update.
 * Data-only SQL (INSERT/UPDATE/DELETE) does not.
 */
class CheckTikiSqlSync
{
    /** Structural SQL patterns that require tiki.sql update */
    protected const STRUCTURAL_PATTERNS = [
        '/\bCREATE\s+TABLE\b/i',
        '/\bALTER\s+TABLE\b/i',
        '/\bDROP\s+TABLE\b/i',
        '/\bCREATE\s+INDEX\b/i',
        '/\bDROP\s+INDEX\b/i',
        '/\bCREATE\s+UNIQUE\s+INDEX\b/i',
        '/\bADD\s+COLUMN\b/i',
        '/\bDROP\s+COLUMN\b/i',
        '/\bMODIFY\s+COLUMN\b/i',
        '/\bCHANGE\s+COLUMN\b/i',
        '/\bADD\s+PRIMARY\s+KEY\b/i',
        '/\bDROP\s+PRIMARY\s+KEY\b/i',
        '/\bADD\s+FOREIGN\s+KEY\b/i',
        '/\bDROP\s+FOREIGN\s+KEY\b/i',
        '/\bADD\s+CONSTRAINT\b/i',
        '/\bDROP\s+CONSTRAINT\b/i',
        '/\bRENAME\s+TABLE\b/i',
        '/\bRENAME\s+COLUMN\b/i',
        '/\bTRUNCATE\s+TABLE\b/i',
    ];

    protected $verbose = false;
    protected $schemaPath;
    protected $tikiSqlPath;
    protected $tikiRoot;

    public function __construct()
    {
        $this->tikiRoot = dirname(dirname(__DIR__));
        $this->schemaPath = $this->tikiRoot . '/installer/schema';
        $this->tikiSqlPath = $this->tikiRoot . '/db/tiki.sql';
    }

    /** @return int Exit code (0=pass, 1=fail) */
    public function execute()
    {
        $options = $this->parseOptions();
        $this->verbose = isset($options['verbose']) || isset($options['v']);

        $this->printMessage('Checking schema patches for structural changes...');
        $changedSchemaFiles = $this->getChangedSchemaFiles($options);

        if (empty($changedSchemaFiles)) {
            $this->printMessage('No schema patch files found in changeset.');
            return 0;
        }

        $this->printVerbose('Found ' . count($changedSchemaFiles) . ' schema file(s) in changeset:');
        foreach ($changedSchemaFiles as $file) {
            $this->printVerbose('  - ' . basename($file));
        }


        $filesWithStructuralChanges = [];
        foreach ($changedSchemaFiles as $file) {
            $structuralStatements = $this->findStructuralStatements($file);
            if (! empty($structuralStatements)) {
                $filesWithStructuralChanges[$file] = $structuralStatements;
            }
        }

        if (empty($filesWithStructuralChanges)) {
            $this->printMessage('No structural changes detected. db/tiki.sql update not required.');
            return 0;
        }

        $tikiSqlChanged = $this->isTikiSqlChanged($options);

        if ($tikiSqlChanged) {
            $this->printMessage('Structural changes detected and db/tiki.sql is modified. OK');
            return 0;
        }

        $this->printError('');
        $this->printError('ERROR: db/tiki.sql may need to be updated');
        $this->printError('');
        $this->printError('Schema patches with structural changes:');
        foreach ($filesWithStructuralChanges as $file => $statements) {
            $this->printError('  ' . basename($file) . ': ' . implode(', ', $statements));
        }
        $this->printError('');
        $this->printError('Please update db/tiki.sql or add a comment explaining why not.');
        $this->printError('See: https://dev.tiki.org/Database-Schema-Upgrade#Update_tiki.sql_file');
        $this->printError('');

        return 1;
    }


    protected function parseOptions()
    {
        $shortOpts = 'v';
        $longOpts = [
            'changed-files:',
            'verbose',
        ];
        return getopt($shortOpts, $longOpts);
    }


    protected function getChangedSchemaFiles($options)
    {
        if (! empty($options['changed-files'])) {
            $files = explode(',', $options['changed-files']);
            return array_filter($files, function ($file) {
                return $this->isSchemaFile($file);
            });
        }

        return $this->getGitChangedSchemaFiles();
    }


    protected function isSchemaFile($file)
    {
        $basename = basename($file);
        return preg_match('/^\d{8}_.*_tiki\.(sql|php)$/', $basename) === 1;
    }


    protected function getGitChangedSchemaFiles()
    {
        $files = array_merge(
            $this->runGitCommand('git diff --cached --name-only'),
            $this->runGitCommand('git diff --name-only'),
            $this->runGitCommand('git ls-files --others --exclude-standard installer/schema/')
        );
        $schemaFiles = [];
        foreach (array_unique($files) as $file) {
            if ($this->isSchemaFile($file)) {
                $fullPath = $this->tikiRoot . '/' . $file;
                if (file_exists($fullPath)) {
                    $schemaFiles[] = $fullPath;
                }
            }
        }

        return $schemaFiles;
    }


    protected function runGitCommand($command)
    {
        $oldDir = getcwd();
        chdir($this->tikiRoot);

        exec($command, $output, $returnCode);

        chdir($oldDir);

        if ($returnCode !== 0) {
            return [];
        }

        return array_filter($output);
    }


    protected function findStructuralStatements($filePath)
    {
        if (! file_exists($filePath)) {
            return [];
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($extension === 'sql') {
            return $this->findStructuralStatementsInSql($filePath);
        } elseif ($extension === 'php') {
            return $this->findStructuralStatementsInPhp($filePath);
        }

        return [];
    }


    protected function findStructuralStatementsInSql($filePath)
    {
        $content = file_get_contents($filePath);
        return $this->matchStructuralPatterns($content);
    }


    protected function findStructuralStatementsInPhp($filePath)
    {
        $content = file_get_contents($filePath);
        return $this->matchStructuralPatterns($content);
    }

    protected function matchStructuralPatterns($content)
    {
        $content = preg_replace('/--[^\n]*/', '', $content);
        $content = preg_replace('/\/\*.*?\*\//s', '', $content);

        $found = [];
        foreach (self::STRUCTURAL_PATTERNS as $pattern) {
            if (preg_match($pattern, $content, $matches)) {
                $found[] = trim($matches[0]);
            }
        }

        return array_unique($found);
    }

    protected function isTikiSqlChanged($options)
    {
        if (! empty($options['changed-files'])) {
            $files = explode(',', $options['changed-files']);
            foreach ($files as $file) {
                if (basename($file) === 'tiki.sql' || str_contains($file, 'db/tiki.sql')) {
                    return true;
                }
            }
            return false;
        }

        $staged = $this->runGitCommand('git diff --cached --name-only db/tiki.sql');
        if (! empty($staged)) {
            return true;
        }

        $modified = $this->runGitCommand('git diff --name-only db/tiki.sql');
        if (! empty($modified)) {
            return true;
        }

        return false;
    }

    protected function printMessage($message)
    {
        echo "\033[0;32m" . $message . "\033[0m" . PHP_EOL;
    }

    protected function printVerbose($message)
    {
        if ($this->verbose) {
            echo $message . PHP_EOL;
        }
    }

    protected function printError($message)
    {
        echo "\033[0;31m" . $message . "\033[0m" . PHP_EOL;
    }
}


if (PHP_SAPI !== 'cli') {
    die("Please run from a shell");
}

$checker = new CheckTikiSqlSync();
$exitCode = $checker->execute();
exit($exitCode);
