<?php

namespace Tiki\Command\Utilities;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\Profiling\DatabaseQueryLog;
use Symfony\Component\Console\Helper\FormatterHelper;

class DatabaseQueryLogConsoleFormatter
{
    public const NUM_TOP_RESULTS = 100;
    private static function format(array $logEntries, InputInterface $input, OutputInterface $output): void
    {


            $table = new Table($output);
            $table->setStyle('borderless');
            $labels = DatabaseQueryLog::getLabels();

            $table
            ->setHeaders([
                $labels[DatabaseQueryLog::PERCENT_OF_LEVEL_TIME],
                $labels[DatabaseQueryLog::PERCENT_OF_REQUEST_TIME],
                $labels[DatabaseQueryLog::TOTAL_COUNT],
                $labels[DatabaseQueryLog::EXECUTABLE_SQL_TEXT]
            ]);
            $table->setColumnMaxWidth(3, 60);
            $rows = [];
        foreach ($logEntries as $entry) {
            if ($entry[DatabaseQueryLog::EXECUTABLE_SQL_TEXT] ?? false) {
                $entryText = $entry[DatabaseQueryLog::EXECUTABLE_SQL_TEXT];
            } else {
                $entryText = $entry[DatabaseQueryLog::DESCRIPTION_TEXT];
            }

            //Because symfony barfs on invalid UTF-8 sequences in tables
            //https://github.com/symfony/symfony/issues/58286
            //But this is especially terrible for theoritically executable SQL... benoitg- 2024-09-30
            $entryText = mb_convert_encoding($entryText, 'UTF-8', 'ASCII');

            $row = [
                round($entry[DatabaseQueryLog::PERCENT_OF_LEVEL_TIME], 2) ?? '',
                round($entry[DatabaseQueryLog::PERCENT_OF_REQUEST_TIME], 2),
                $entry[DatabaseQueryLog::TOTAL_COUNT],
                $entryText
            ];

            $rows[] = $row;
        }

        $table->setRows($rows);
        $table->render();
    }

    public static function render(InputInterface $input, OutputInterface $output): void
    {

        if (DatabaseQueryLog::isEnabled()) {
            $queryLogData = DatabaseQueryLog::processLog();

            $io = new SymfonyStyle($input, $output);
            $io->section(tr('Top %0 queries by aggregate time', self::NUM_TOP_RESULTS));
            self::format(array_slice($queryLogData[0], 0, self::NUM_TOP_RESULTS), $input, $output);

            $io->section(tr('General query statistics by query group'));
            self::format($queryLogData[2], $input, $output);
        }
    }
}
