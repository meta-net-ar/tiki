<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'security:generate',
    description: 'Generate security.txt file'
)]
class SecurityFileGenerateCommand extends Command
{
    private const CONTACTS = [
        'https://security.tiki.org/tiki-contact.php',
    ];
    private const ACKNOWLEDGEMENTS = [
        'https://tiki.org/article514',
        'https://tiki.org/article515',
        'https://tiki.org/article518'
    ];
    private const PREFERED_LANGUAGES = ['en', 'fr'];
    private const POLICIES = ['https://security.tiki.org/Disclose-a-Vulnerability'];

    protected function configure()
    {
        $this
            ->addOption(
                'admin-contact',
                'c',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Site admin contact information to use for reporting vulnerabilities (must start with tel: or mailto:)'
            )
            ->addOption(
                'expires',
                null,
                InputOption::VALUE_REQUIRED,
                'Date and time after which this file is considered stale'
            )
            ->addOption(
                'encryption',
                null,
                InputOption::VALUE_OPTIONAL,
                'A link to a key which security researchers must use to securely talk to you.'
            )
            ->addOption(
                'hiring',
                null,
                InputOption::VALUE_OPTIONAL,
                'A link to any security-related job openings in your organisation.'
            )
            ->addOption(
                'signature-path',
                null,
                InputOption::VALUE_OPTIONAL,
                'Path to the file with an OpenPGP cleartext signature'
            )
            ->setHelp(
                'Implementation of RFC 9116. The security.txt generated file will help reseachers to report security vulnerabilities to Tiki developers.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        global $tikipath;

        $securityTxtContent = '';

        $contacts = [];
        foreach ($input->getOption('admin-contact') as $contact) {
            if (! preg_match('/^(tel:|mailto:|https?:\/\/)/', $contact)) {
                $output->writeln("<error>Admin contact $contact must either start with tel:, mailto:, or http(s)://</error>");
                return false;
            }
            if (preg_match('/^mailto:/', $contact)) {
                $output->writeln("<error>WARNING: Using email as contact may lead to spam.</error>");
            }
            $contacts[] = $contact;
        }

        $contacts = array_merge(self::CONTACTS, $contacts);
        $contacts = array_unique($contacts);

        foreach ($contacts as $contact) {
            $securityTxtContent .= "Contact: $contact\n";
        }

        $days = (int) $input->getOption('expires');
        if ($days <= 0) {
            $output->writeln("<error>Expiry days must be greater than 0</error>");
            return false;
        }
        $date = new \DateTime();
        $date->add(new \DateInterval("P{$days}D"));
        $formattedExpiryDate = $date->format('Y-m-d\TH:i:s\Z');
        $securityTxtContent .= "\nExpires: $formattedExpiryDate";

        $hiring = $input->getOption('hiring');
        if ($hiring) {
            if (! filter_var($hiring, FILTER_VALIDATE_URL)) {
                $output->writeln("<error>Hiring must be a valid URL</error>");
                return false;
            }
            $securityTxtContent .= "\nHiring: $hiring";
        }

        $encryption = $input->getOption('encryption');
        if ($encryption) {
            if (! filter_var($encryption, FILTER_VALIDATE_URL)) {
                $output->writeln("<error>Encryption must be a valid URL</error>");
                return false;
            }
            $securityTxtContent .= "\nEncryption: $encryption";
        }

        $securityTxtContent .= "\nPreferred-Languages: " . implode(', ', self::PREFERED_LANGUAGES) . "\n";

        foreach (self::POLICIES as $policy) {
            $securityTxtContent .= "\nPolicy: $policy";
        }
        $securityTxtContent .= "\n";

        // TODO: find a way to get those dynamically
        foreach (self::ACKNOWLEDGEMENTS as $ack) {
            $securityTxtContent .= "\nAcknowledgments: $ack";
        }
        $securityTxtContent .= "\n";

        $signaturePath = $input->getOption('signature-path');
        if ($signaturePath) {
            if (! filter_var($signaturePath, FILTER_VALIDATE_URL) && ! file_exists($signaturePath)) {
                $output->writeln("<error>Signin key must either be a valid URL or valid file path</error>");
                return false;
            }
            if (! extension_loaded('gnupg')) {
                $output->writeln("<error>Signin security.txt requires gnupg extension to be loaded</error>");
                return false;
            }
            $signatureKey = file_get_contents($signaturePath);
            if (empty($signatureKey)) {
                $output->writeln("<error>Signature key is empty</error>");
                return false;
            }
            $gnupg = new \gnupg();
            $gnupg->setsignmode(\gnupg::SIG_MODE_CLEAR);
            $gnupg->addsignkey($signatureKey);
            $securityTxtContent = $gnupg->sign($securityTxtContent);
        }

        if (! is_dir($tikipath . '/.well-known')) {
            $output->writeln("<error>.well-known directory is missing.</error>");
            return false;
        }

        file_put_contents($tikipath . '/.well-known/security.txt', $securityTxtContent);

        return Command::SUCCESS;
    }
}
