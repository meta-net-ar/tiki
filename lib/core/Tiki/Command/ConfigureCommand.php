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
    name: 'database:configure',
    description: 'Database: Configure (write local.php)',
)]
class ConfigureCommand extends Command
{
    protected function configure()
    {
        $this
            ->setHelp('Creates the db/local.php file with the specified database credentials')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'Username'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'Password'
            )
            ->addArgument(
                'database',
                InputArgument::REQUIRED,
                'Database name'
            )
            ->addOption(
                'host',
                null,
                InputOption::VALUE_REQUIRED,
                'Database hostname, localhost otherwise'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $username = $input->getArgument('username');
        $password = $input->getArgument('password');
        $database = $input->getArgument('database');
        if (! $hostname = $input->getOption('host', 'localhost')) {
            $hostname = 'localhost';
        }


        $twversion = new \TWVersion();
        $version = $twversion->getBaseVersion();

        $export_username = var_export($username, true);
        $export_password = var_export($password, true);
        $export_database = var_export($database, true);
        $export_hostname = var_export($hostname, true);
        $out = <<<LOCALPHP
<?php
\$host_tiki=$export_hostname;
\$user_tiki=$export_username;
\$pass_tiki=$export_password;
\$dbs_tiki=$export_database;
\$client_charset='utf8';
// If you experience text encoding issues after updating (e.g. apostrophes etc showing up as strange characters)
// \$client_charset='latin1';
// \$client_charset='utf8';
// See http://tiki.org/ReleaseNotes5.0#Known_Issues and http://doc.tiki.org/Understanding+Encoding for more info

// php installation should have pdo extension (adodb is deprecated in Tiki)

// Want configurations managed at the system level or restrict some preferences? http://doc.tiki.org/System+Configuration
// \$system_configuration_file = 'db/tiki.ini.php';
// \$system_configuration_file = '/etc/tiki.ini';
// \$system_configuration_identifier = 'example.com';

LOCALPHP;
        $local_php = \Tiki\TikiInit::getCredentialsFile();
        $result = file_put_contents($local_php, $out);

        if ($result === false) {
            $output->writeln("<error>Failed to write to $local_php</error>");
            return Command::FAILURE;
        }

        $output->writeln("Wrote $local_php");
        return Command::SUCCESS;
    }
}
