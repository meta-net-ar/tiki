<?php

namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Tiki\TikiInit;

#[AsCommand(
    name: 'database:convert',
    description: 'Convert database engine from MyISAM to InnoDB',
)]
class DatabaseEngineConvertCommand extends Command
{
    /**
     * @var ConsoleLogger
     */
    protected ConsoleLogger $logger;

    protected function configure(): void
    {
        $this
            ->setHelp(
                'Use this command to convert database engine from MyISAM to InnoDB. Important: this can cause a number of troubles to your database, proceed with caution!'
            )
            ->addOption(
                'database',
                null,
                InputOption::VALUE_NONE,
                'Database name to be convert.'
            )->addOption(
                'username',
                null,
                InputOption::VALUE_NONE,
                'Database username',
            )->addOption(
                'password',
                null,
                InputOption::VALUE_NONE,
                'Database password.'
            )->addOption(
                'host',
                null,
                InputOption::VALUE_NONE,
                'Database host.'
            )->addOption(
                'path',
                null,
                InputOption::VALUE_NONE,
                'path for database backup.'
            )
            ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $local_php = TikiInit::getCredentialsFile();
        global $host_tiki, $user_tiki, $pass_tiki, $dbs_tiki;

        if (file_exists($local_php)) {
            include($local_php);
        }

        $io = new SymfonyStyle($input, $output);
        $username = $input->getOption('username') ? $input->getOption('username') : $user_tiki;
        $password = $input->getOption('password') ? $input->getOption('password') : $pass_tiki;
        $database = $input->getOption('database') ? $input->getOption('database') : $dbs_tiki;
        $host = $input->getOption('host') ? $input->getOption('host') : $host_tiki;
        $backup_path = $input->hasOption('path') ? $input->getOption('path') : TIKI_PATH . '/temp/db_backups';

        if (! $this->databaseConnect($username, $password, $database, $host)) {
            $msg = tr('Failed to connect to database `%0` . Please check your credentials.', $database);
            $io->error($msg);
            return Command::FAILURE;
        }
        $script_file = 'db/tiki_convert_myisam_to_innodb.sql';

        $msg = tra('Checking script file');
        $io->info($msg);
        if (! file_exists($script_file)) {
            $msg = tra('Script file `%0` does not exist', $script_file);
            $io->error($msg);
            return Command::FAILURE;
        }
        try {
            # backup database
            $output->writeln('<info>' . tra('Backup database') . '</info>');
            if (! is_dir($backup_path)) {
                mkdir($backup_path, 0666, true);
            }
            $backupDatabaseInput = new ArrayInput([
                'command' => 'database:backup',
                'path'    => $backup_path,
            ]);
            $this->getApplication()->doRun($backupDatabaseInput, $output);

            $msg = tra('Conversion start');
            $output->writeln("<info>$msg</info>");
            $command = "mysql -u $username --password=$password $database <  " . escapeshellarg($script_file);
            $output->writeln('<info>' . $command . '</info>');
            $result = $this->executeCommand($command, $io);
            if (! $result) {
                return Command::FAILURE;
            } else {
                $output->writeln("<info>" . tra('Finish conversion') . "</info>");

                # update database
                $output->writeln("<info>" . tra('Update Database') . "</info>");
                $databaseUpdateInput = new ArrayInput(['command' => 'database:update']);
                $this->getApplication()->doRun($databaseUpdateInput, $output);

                # rebuild admin index
                $output->writeln("<info>" . tra('Rebuilding index') . "</info>");
                \TikiLib::lib('prefs')->rebuildIndex();
            }
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            $io->error($msg);
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }

    /**
     * @param string $username database username
     * @param string $password database password
     * @param string $database database name
     * @param string $host     database host
     *
     * @return bool
     */
    private function databaseConnect(string $username, string $password, string $database, string $host): bool
    {
        if (! $username || ! $password || ! $database || ! $host) {
            return false;
        }
        $link = mysqli_connect($host, $username, $password, $database);

        if (! $link) {
            return false;
        }
        mysqli_close($link);
        return true;
    }

    /**
     * @param string       $command
     * @param SymfonyStyle $io
     *
     * @return bool
     */
    protected function executeCommand(string $command, SymfonyStyle $io): bool
    {
        exec($command, $output, $result_code);
        if ($result_code > 0) {
            $io->error($output);
            return false;
        }
        return true;
    }
}
