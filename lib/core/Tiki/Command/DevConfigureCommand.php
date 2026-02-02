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
use Symfony\Component\Console\Output\OutputInterface;
use TikiLib;

/**
 * Install or update Tiki development files
 *
 * Installs composer development files and configures Tiki for unit testing.
 *
 * @package Tiki\Command
 */
#[AsCommand(
    name: 'dev:configure',
    description: 'Install or update development files'
)]
class DevConfigureCommand extends Command
{
    protected $user_tiki;
    protected $pass_tiki;
    protected $dbs_tiki;
    protected $host_tiki;

    protected function configure()
    {
        $this
            ->setHelp(
                tra('Install or update and configure composer development vendor files and unit test config & database.')
            )
            ->addArgument(
                'db_user',
                InputArgument::OPTIONAL,
                tra('User for the PHPUnit database')
            )
            ->addArgument(
                'db_pass',
                InputArgument::OPTIONAL,
                tra('Password for the PHPUnit database')
            )
            ->addArgument(
                'db_name',
                InputArgument::OPTIONAL,
                tra('Name of the PHPUnit database')
            )
            ->addArgument(
                'db_host',
                InputArgument::OPTIONAL,
                tra('Host of the PHPUnit database')
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // If any argument is missing,
        if (! $input->getArgument('db_user') || ! $input->getArgument('db_pass') || ! $input->getArgument('db_name') || ! $input->getArgument('db_host')) {
            // and the file lib/test/local.php exists,
            if (file_exists('lib/test/local.php')) {
                // try to load from it
                include 'lib/test/local.php';

                // If the argument "db_user" was not passed, set it to the value of $user_tiki from local.php
                if (! $input->getArgument('db_user')) {
                    $input->setArgument('db_user', $user_tiki);
                }

                // If the argument "db_pass" was not passed, set it to the value of $pass_tiki from local.php
                if (! $input->getArgument('db_pass')) {
                    $input->setArgument('db_pass', $pass_tiki);
                }

                // If the argument "db_name" was not passed, set it to the value of $dbs_tiki from local.php
                if (! $input->getArgument('db_name')) {
                    $input->setArgument('db_name', $dbs_tiki);
                }

                // If the argument "db_host" was not passed, set it to the value of $host_tiki from local.php
                if (! $input->getArgument('db_host')) {
                    $input->setArgument('db_host', $host_tiki);
                }
            }
        }

        $this->user_tiki = $input->getArgument('db_user');
        $this->pass_tiki = $input->getArgument('db_pass');
        $this->dbs_tiki = $input->getArgument('db_name');
        $this->host_tiki = $input->getArgument('db_host');

        // Lets first check that some requirements are met.
        if (! is_callable('exec')) {
            $output->writeln('<error>' . tra('Must enable exec() for this command') . '</error>');
            exit(1);
        }

        $output->writeln('Checking composer development files');
        if (! class_exists(\PHPUnit\Framework\TestCase::class)) {
            exec(
                'php temp/composer.phar --ansi install -d vendor_bundled --no-progress --prefer-dist -n 2>&1',
                $raw,
                $error
            );
            if ($error) {
                $output->writeln(
                    '<error>' . tra('Error: composer files not installed. Check temp/composer.phar') . '</error>'
                );
            } else {
                $output->writeln($raw, OutputInterface::VERBOSITY_VERY_VERBOSE);
                $output->writeln(
                    '<info>' . tra('Done: Composer dev files installed') . '</info>'
                );
            }
        } else {
            $output->writeln(
                '<info>' . tra('Done: Composer dev files already installed') . '</info>'
            );
        }

        $output->writeln(tra('Checking phplint'));
        if (file_exists('phplint')) {
            $output->writeln(
                '<info>' . tra('Done: phplint was already callable via "php phplint" in the project root') . '</info>'
            );
        } elseif (
            symlink('vendor_bundled/vendor/overtrue/phplint/bin/phplint', 'phplint')
        ) {
            $output->writeln(
                '<info>' . tra('Done: phplint is now callable via "php phplint" in the project root') . '</info>'
            );
        } else {
            $output->writeln('<error>' . tra('Could not create symlink') . '</error>');
            $output->writeln(
                tra('Try using the following command:') . ' ln -s vendor_bundled/vendor/overtrue/phplint/bin/phplint phplint'
            );
        }

        $config = <<<EOT
path: ./
jobs: 8
cache: temp/phplint.cache
extensions:
  - php
exclude:
  - vendor
  - vendor_bundled

EOT;

        if (file_exists('.phplint.yml')) {
            $output->writeln('<info>' . tra('Done: .phplint.yml was already present in the project root') . '</info>', OutputInterface::VERBOSITY_VERBOSE);
        } elseif (file_put_contents('.phplint.yml', $config)) {
            $output->writeln('<info>' . tra('Done: phplint config written') . '</info>');
        } else {
            $output->writeln('<error>' . tra('Could not create') . ' .phplint.yml</error>');
        }

        $output->writeln('Checking phpunit');
        if (file_exists('phpunit')) {
            $output->writeln('<info>' . tra('Done: phpunit was already callable via "php phpunit" in the project root') . '</info>');
        } elseif (symlink('vendor_bundled/vendor/phpunit/phpunit/phpunit', 'phpunit')) {
            $output->writeln('<info>' . tra('Done: phpunit is now callable via "php phpunit" in the project root') . '</info>');
        } else {
            $output->writeln('<error>' . tra('Could not create symlink') . '</error>');
            $output->writeln(tra('Try using the following command:') . ' ln -s vendor_bundled/vendor/phpunit/phpunit/phpunit phpunit');
        }

        $output->writeln(tra('Checking PHPUnit local.php file'));
        if (file_exists('lib/test/local.php')) {
            $output->writeln('<info>' . tra('Done: PHPUnit database credentials file already present') . '</info>');
            $output->writeln('* ' . tra('You many configure lib/test/local.php manually if needed') . '</error>', OutputInterface::VERBOSITY_VERBOSE);
        } else {
            $output->writeln(tra('No unit test config file found'), OutputInterface::VERBOSITY_VERY_VERBOSE);
            $config = <<<EOT
<?php
/*
File written by php console.php dev:configure
*/

\$host_tiki='localhost';
\$user_tiki='tiki_tester';
\$pass_tiki='tiki_tester_pass';
\$dbs_tiki='tiki_unit_test';
\$client_charset='utf8mb4';

EOT;
            if (file_put_contents('lib/test/local.php', $config)) {
                $output->writeln('<info>' . tra('Done:') . ' lib/test/local.php written</info>');
            } else {
                $output->writeln('<error>' . tra('Error: Could not write') . ' lib/test/local.php</error>');
            }
        }

        $output->writeln('Checking PHPUnit database status');
        if ($this->databaseConnect()) {
            $output->writeln('<info>' . tra('Done: Database already connecting') . '</info>');
        } elseif ($this->user_tiki && $this->pass_tiki && $this->dbs_tiki && $this->host_tiki) {
            if (DB_STATUS) {
                $tikilib = TikiLib::lib('tiki');
                $error = '';

                $output->writeln('* ' . tra('Creating Database User'), OutputInterface::VERBOSITY_VERBOSE);
                $query = "CREATE USER IF NOT EXISTS `$this->user_tiki`@`$this->host_tiki` IDENTIFIED BY '$this->pass_tiki';";
                $tikilib->queryError($query, $error);
                if (! empty($error)) {
                    $output->writeln('<comment>* ' . tra('Could not create user') . '</comment>', OutputInterface::VERBOSITY_VERBOSE);
                    $output->writeln($error, OutputInterface::VERBOSITY_DEBUG);
                }

                $output->writeln('* Creating Database', OutputInterface::VERBOSITY_VERBOSE);
                $query = "CREATE DATABASE IF NOT EXISTS `$this->dbs_tiki` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
                $tikilib->queryError($query, $error);
                if (! empty($error)) {
                    $output->writeln('<comment>* ' . tra('Could not create database') . '</comment>', OutputInterface::VERBOSITY_VERBOSE);
                    $output->writeln($error, OutputInterface::VERBOSITY_DEBUG);
                }

                $output->writeln('* ' . tra('Assigning user rights on database'), OutputInterface::VERBOSITY_VERBOSE);
                $query = "GRANT ALL ON $this->dbs_tiki.* TO `$this->user_tiki`@`$this->host_tiki`;";
                $tikilib->queryError($query, $error);
                if (! empty($error)) {
                    $output->writeln('<comment>* ' . tra('Could not assign user rights') . '</comment>', OutputInterface::VERBOSITY_VERBOSE);
                    $output->writeln($error, OutputInterface::VERBOSITY_DEBUG);
                }
            }
            if ($this->databaseConnect()) {
                $output->writeln('<info>' . tra('Done: PHPUnit database configured') . '</info>');
            } else {
                if (DB_STATUS) {
                    $output->writeln('<error>' . tra('Error: PHPUnit database setup error') . '</error>');
                } else {
                    $output->writeln('<comment>' . tra('Could not detect that PHPUnit database has been setup') . '</comment>');
                    $output->writeln(tra('Tiki database is not connecting, are you sure that mysql is running?'));
                }
                $output->writeln(tra('You may try the following:'));
                $output->writeln(tra('1. Ensure Tiki database connection root credentials and run this command again.'));
                $output->writeln(tra('2. Open PHPMyAdmin and run the following commands:'));
                $output->writeln("  CREATE USER IF NOT EXISTS `$this->user_tiki`@`$this->host_tiki` IDENTIFIED BY '$this->pass_tiki';");
                $output->writeln("  CREATE DATABASE IF NOT EXISTS `$this->dbs_tiki` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
                $output->writeln("  GRANT ALL ON $this->dbs_tiki.* TO `$this->user_tiki`@`$this->host_tiki`;");
                $output->writeln(tra('3. Install via the terminal:'));
                $output->writeln(tra('Hints: The default mysql password is "root". Your mysql $PATH must be configured for the below to work.'));
                $output->writeln('  mysql -u root -p');
                $output->writeln("  create database $this->dbs_tiki;");
                $output->writeln("  grant all privileges on $this->dbs_tiki.* TO '$this->user_tiki'@'$this->host_tiki' identified by '$this->pass_tiki';");
                $output->writeln('  flush privileges;');
                $output->writeln('  \q');
            }
        } else {
            $output->writeln('<error>Error: database config not found</error>');
            $output->writeln('* Try running this command again, or follow instructions in lib/test/local.php.dist', OutputInterface::VERBOSITY_VERBOSE);
        }
        return Command::SUCCESS;
    }

    /**
     * Checks if a database connection can be made to PHPUnit database
     *
     * @return bool true on success, false on failure.
     */

    private function databaseConnect(): bool
    {
        if (! $this->user_tiki || ! $this->pass_tiki || ! $this->dbs_tiki || ! $this->host_tiki) {
            return false;
        }
        $link = @mysqli_connect($this->host_tiki, $this->user_tiki, $this->pass_tiki, $this->dbs_tiki);

        if (! $link) {
            return false;
        }
        mysqli_close($link);
        return true;
    }
}
