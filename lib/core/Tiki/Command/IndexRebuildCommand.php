<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Profiling\Timer;
use Tiki\Search\SearchIndexRebuilder;

#[AsCommand(
    name: 'index:rebuild',
    description: 'Fully rebuild the unified search index'
)]
class IndexRebuildCommand extends Command
{
    private const EXIT_INTEGRITY_ERRORS = 2;
    private const EXIT_SKIPPED_ERRORS = 3;

    protected function configure()
    {
        $this
            ->addOption(
                'log',
                null,
                InputOption::VALUE_NONE,
                'Generate a log of the indexed documents, useful to track down failures or memory issues'
            )
            ->addOption(
                'cron',
                null,
                InputOption::VALUE_NONE,
                'Only output error messages'
            )
            ->addOption(
                'progress',
                'p',
                InputOption::VALUE_NONE,
                'Show progress bar'
            )
            ->addOption(
                'skip-error-tracking',
                null,
                InputOption::VALUE_NONE,
                'Skip Tiki error tracking and only log errors to log file (if enabled). Useful if you rebuild generates a lot of noise in your external error tracking system.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $num_queries;
        global $prefs;

        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('log')) {
            $log = 2;
        } else {
            $log = 0;
        }
        $cron = $input->getOption('cron');

        $unifiedsearchlib = \TikiLib::lib('unifiedsearch');
        $currentEngine = $unifiedsearchlib->getCurrentEngineDetails();
        $fallbackEngine = $unifiedsearchlib->getFallbackEngineDetails();
        $unusedIndices = $unifiedsearchlib->listAllUnusedIndexes($currentEngine);
        $logFiles = [];

        if (! $cron) {
            $message = '[' . \TikiLib::lib('tiki')->get_short_datetime(0) . '] Started rebuilding index...';
            $output->writeln($message);

            if ($log) {
                $output->writeln('Logging to file(s):');
                $logFiles[] = $unifiedsearchlib->getLogFilename($log);

                if ($fallbackEngine) {
                    list($engine, $engineName, $version, $index) = $fallbackEngine;
                    $logFiles[] = $unifiedsearchlib->getLogFilename($log, $engine);
                }

                $io->listing($logFiles);
            }

            $io->section('Unified search');

            list($engine, $version) = $unifiedsearchlib->getCurrentEngineDetails();

            if (! empty($engine)) {
                $engineMessage = 'Engine: ' . $engine;
                if (! empty($version)) {
                    $engineMessage .= ', version ' . $version;
                }
                $output->writeln($engineMessage);
            }
        }

        $timer = new Timer();
        $timer->start();

        $memory_peak_usage_before = memory_get_peak_usage();

        $num_queries_before = $num_queries;

        // Set up progress bar if requested
        $progress = null;
        if ($input->getOption('progress') && ! $cron) {
            $lastStats = \TikiLib::lib('tiki')->get_preference('unified_last_rebuild_stats_' . $prefs['unified_engine'], [], true);
            if (isset($lastStats['default']['counts'])) {
                if (isset($lastStats['default']['times']['total'])) {
                    $steps = $lastStats['default']['times']['total'] * 1000 + 5000; // milliseconds plus 5 seconds for prefs (guess)
                } else {
                    $steps = array_sum($lastStats['default']['counts']);
                }
            } else {
                $steps = 0;
            }

            $progress = new ProgressBar($output, round($steps));   // TODO consider the prefs indexing time that happens after the main one
            if ($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE) {
                $progress->setOverwrite(false);
            }
            $progress->setRedrawFrequency(10);
            if ($steps) {
                $progress->setFormatDefinition('custom', ' %elapsed%/%estimated% [%bar%] -- %message%');
            } else {
                $progress->setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message%');
            }
            $progress->setFormat('custom');
            $progress->setMessage(tr('Rebuilding...'));
            $progress->start();
        }
        $skipErrorTracking = $input->getOption('skip-error-tracking') ? true : false;

        // Use shared service to rebuild index directly (console commands always execute directly)
        $searchIndexRebuilder = new SearchIndexRebuilder();
        $result = $searchIndexRebuilder->rebuildIndex($log, false, $progress, $skipErrorTracking);

        $queries_after = $num_queries;

        if (! is_array($result)) {
            $output->writeln("\n<error>Search index rebuild failed. Last messages shown above.</error>");
            \TikiLib::lib('logs')->add_action('rebuild indexes', 'Search index rebuild failed.', 'system');
            return Command::FAILURE;
        }

        $integrityErrors = [];
        $skippedErrors = [];
        $catastrophicErrors = [];
        foreach (['default', 'fallback'] as $scope) {
            $stats = $result[$scope] ?? [];
            $integrityErrors = array_merge($integrityErrors, $this->formatErrorEntries($stats['integrity_errors'] ?? []));
            $skippedErrors = array_merge($skippedErrors, $this->formatErrorEntries($stats['skipped_errors'] ?? []));
            if (! empty($stats['error'])) {
                $catastrophicErrors[] = $stats['error_message'] ?? 'Search index rebuild failed.';
            }
        }
        $integrityErrors = array_values(array_unique($integrityErrors));
        $skippedErrors = array_values(array_unique($skippedErrors));
        $catastrophicErrors = array_values(array_unique($catastrophicErrors));
        $defaultError = ! empty($result['default']['error']);

        \Feedback::printToConsole($output, $cron);

        if ($defaultError) {
            $output->writeln("\n<error>" . ($result['default']['error_message'] ?? 'Search index rebuild failed.') . "</error>");
            \TikiLib::lib('logs')->add_action('rebuild indexes', 'Search index rebuild failed.', 'system');
        }
        if (! $cron) {
            if ($progress) {
                $output->writeln('');
            }
            if (! $defaultError) {
                $unifiedsearchlib->formatStats($result, function ($line) use ($output) {
                    $output->writeln($line);
                });
                $output->writeln('Rebuilding index done');

                list($engine, $version, $index) = $unifiedsearchlib->getCurrentEngineDetails();
                $output->writeln('Index: ' . $index);
            }

            if ($fallbackEngineDetails = \TikiLib::lib('unifiedsearch')->getFallbackEngineDetails()) {
                list($engine, $engineName, $version, $index) = $fallbackEngineDetails;
                $io->section("\nFallback unified search");

                if (empty($result['fallback'])) {
                    $output->writeln('<error>Fallback index was not rebuilt</error>');
                } else {
                    $fallbackEngineMessage = 'Engine: ' . $engineName;
                    if (! empty($version)) {
                        $fallbackEngineMessage .= ', version ' . $version;
                    }
                    $output->writeln($fallbackEngineMessage);
                    $output->writeln('Index: ' . $index);
                }
            }

            if (isset($unusedIndices['indices']) && count($unusedIndices['indices'])) {
                $io->section("\nUnused Indexes Detected");
                $io->listing($unusedIndices['indices']);
                $io->note("If you don't need them (for debugging), run the following command:");
                $io->writeln("<info>php console.php index:cleanup</info> (Delete unused indexes)");
                $io->writeln("<comment> --dry-run </comment> List unused indexes without deleting");
                $io->writeln("<comment> --all </comment> Delete all indexes, ignoring prefix");
                $io->writeln("<comment> --all --dry-run </comment> List all indexes without deleting");
                $io->writeln("<comment> -i <index_name> </comment> Remove a specific index");
            } elseif (isset($unusedIndices['error'])) {
                $io->error($unusedIndices['error']);
            }

            $executionTime = $timer->stop();
            if ($executionTime < 60) {
                $seconds = floor($executionTime);
                $executionTime = $seconds . ' ' . ($seconds == 1 ? 'sec' : 'secs');
            } elseif ($executionTime < 3600) {
                $minutes = floor($executionTime / 60);
                $executionTime = $minutes . ' ' . ($minutes == 1 ? 'min' : 'mins');
            } else {
                $hours = round($executionTime / 3600, 1);
                $executionTime = $hours . ' ' . ($hours == 1 ? 'hr' : 'hrs');
            }

            if ($log && is_array($currentEngine) && count($currentEngine)) {
                list($engine) = $currentEngine;
                $logToFile = new \Monolog\Handler\StreamHandler($unifiedsearchlib->getLogFilename($log, strtolower($engine)), \Monolog\Logger::INFO);
                $loggerInstance = new \Monolog\Logger('index_rebuild');
                $loggerInstance->pushHandler($logToFile);
                $loggerInstance->info("Execution time: " . $executionTime);
            }

            $io->section("\nExecution Statistics");
            $output->writeln('Execution time: ' . $executionTime);
            $output->writeln('Current Memory usage: ' . FormatterHelper::formatMemory(memory_get_usage()));
            $output->writeln('Memory peak usage before indexing: ' . FormatterHelper::formatMemory($memory_peak_usage_before));
            $output->writeln('Memory peak usage after indexing: ' . FormatterHelper::formatMemory(memory_get_peak_usage()));
            $output->writeln('Number of queries: ' . ($queries_after - $num_queries_before));

            $this->renderFailureSummary($output, $integrityErrors, $skippedErrors, $catastrophicErrors);
        }

        return $this->resolveExitCode($integrityErrors, $skippedErrors, $catastrophicErrors);
    }

    private function resolveExitCode(array $integrityErrors, array $skippedErrors, array $catastrophicErrors): int
    {
        if (! empty($catastrophicErrors)) {
            return Command::FAILURE;
        }

        if (! empty($skippedErrors)) {
            return self::EXIT_SKIPPED_ERRORS;
        }

        if (! empty($integrityErrors)) {
            return self::EXIT_INTEGRITY_ERRORS;
        }

        return Command::SUCCESS;
    }

    private function renderFailureSummary(OutputInterface $output, array $integrityErrors, array $skippedErrors, array $catastrophicErrors): void
    {
        if (empty($integrityErrors) && empty($skippedErrors) && empty($catastrophicErrors)) {
            return;
        }

        $output->writeln("\n<comment>Index rebuild failure summary</comment>");
        $output->writeln(' - Integrity/non-indexable: ' . count($integrityErrors));
        $output->writeln(' - Skipped/unknown: ' . count($skippedErrors));
        $output->writeln(' - Catastrophic: ' . count($catastrophicErrors));

        if (! empty($integrityErrors)) {
            $output->writeln("\n<comment>Integrity/non-indexable elements</comment>");
            foreach ($integrityErrors as $line) {
                $output->writeln(' - ' . $line);
            }
        }

        if (! empty($skippedErrors)) {
            $output->writeln("\n<comment>Skipped elements (unknown errors)</comment>");
            foreach ($skippedErrors as $line) {
                $output->writeln(' - ' . $line);
            }
        }

        if (! empty($catastrophicErrors)) {
            $output->writeln("\n<error>Catastrophic failures</error>");
            foreach ($catastrophicErrors as $line) {
                $output->writeln(' - ' . $line);
            }
        }
    }

    private function formatErrorEntries(array $entries): array
    {
        $base = \TikiLib::lib('tiki')->tikiUrl();
        $lines = [];

        foreach ($entries as $entry) {
            if (is_string($entry)) {
                $lines[] = $entry;
                continue;
            }

            if (! is_array($entry)) {
                continue;
            }

            $label = $entry['label'] ?? $entry['object'] ?? $entry['id'] ?? 'unknown';
            $message = $entry['message'] ?? $entry['error'] ?? 'unknown error';
            $url = $entry['url'] ?? $entry['href'] ?? null;

            if ($url && strpos($url, 'http') !== 0) {
                $url = rtrim($base, '/') . '/' . ltrim((string)$url, '/');
            }

            if ($url) {
                $lines[] = $label . ' - ' . $message . ' - ' . $url;
            } else {
                $lines[] = $label . ' - ' . $message;
            }
        }

        return $lines;
    }
}
