<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'database:backup',
    description: 'Create a database backup (with mysqldump)',
)]
class BackupDBCommand extends Command
{
    protected function configure()
    {
        $this
            ->addArgument(
                'path',
                InputArgument::REQUIRED,
                'Path to save backup (relative to console.php, or absolute)'
            )
            ->addArgument(
                'dateFormat',
                InputArgument::OPTIONAL,
                'Format to use for the date part of the backup file. Defaults to "Y-m-d_H-i-s" and uses the PHP date function format'
            )
            ->addOption(
                'no-hex',
                null,
                InputOption::VALUE_NONE,
                'Do not encode BLOB fields as hexadecimal characters'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        if (substr($path, -1) == '/') {
            $path = substr($path, 0, strlen($path) - 1);
        }

        if (is_file($path)) {
            $output->writeln("<error>Error: Provided path \"$path\" is a file.  A directory must be provided</error>");
            return Command::INVALID;
        } elseif (! is_dir($path)) {
            $output->writeln("<error>Error: Provided path \"$path\" not found</error>");
            return Command::FAILURE;
        }

        $local = \Tiki\TikiInit::getCredentialsFile();
        if (! is_readable($local)) {
            $output->writeln("<error>Error: \"$local\" not readable.</error>");
            return Command::FAILURE;
        }

        $dateFormat = $input->getArgument('dateFormat');
        if (! $dateFormat) {
            $dateFormat = 'Y-m-d_H-i-s';
        }

        $user_tiki = $pass_tiki = $host_tiki = $dbs_tiki = '';

        require $local;

        $args = [];
        if ($user_tiki) {
            $args[] = "-u" . escapeshellarg($user_tiki);
        }
        if ($pass_tiki) {
            $args[] = "-p" . escapeshellarg($pass_tiki);
        }
        if ($host_tiki) {
            $args[] = "-h" . escapeshellarg($host_tiki);
        }
        if (! $input->getOption('no-hex')) {
            $args[] = '--hex-blob';
        }

        // Find out how many non-InnoDB tables exist in the schema
        $db = \TikiDb::get();
        $query = "SELECT count(TABLE_NAME) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$dbs_tiki' AND engine <> 'InnoDB'";
        $numTables = $db->getOne($query);

        if ($numTables === '0') {
            $args[] = "--single-transaction";
        } else {
            $dbOpenFilesLimit = 0;
            $result = $db->fetchAll('SHOW GLOBAL VARIABLES LIKE "open_files_limit"');
            if (count($result) > 0) {
                $dbOpenFilesLimit = (int)$result[0]['Value'];
            }
            if ($dbOpenFilesLimit > 0 && $dbOpenFilesLimit < 2000) {
                // some distributions bring a lower limit of open files, so lock all tables during backup might fail the backup
                $output->writeln('<info>Mysql database has open_files_limit=' . $dbOpenFilesLimit . ', skipping lock tables to avoid failing the backup</info>');
            } else {
                $args[] = "--lock-tables";
            }
        }

        $args[] = $dbs_tiki;

        $args = implode(' ', $args);
        $outputFile = $path . '/' . $dbs_tiki . '_' . date($dateFormat) . '.sql.gz';
        $command = "mysqldump --quick --create-options --extended-insert $args | gzip -5 > " . escapeshellarg($outputFile);
        exec($command);
        $output->writeln('<comment>Database backup completed: ' . $outputFile . '</comment>');
        return Command::SUCCESS;
    }
}
