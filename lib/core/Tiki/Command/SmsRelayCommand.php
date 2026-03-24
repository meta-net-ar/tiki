<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Tiki\SmsRelay\Processor;

#[AsCommand(
    name: 'sms:relay',
    description: 'Filter forwarded SMS emails and relay relevant ones to configured recipients'
)]
class SmsRelayCommand extends Command
{
    private Processor $processor;
    private $sender;

    public function __construct(?Processor $processor = null, ?callable $sender = null)
    {
        parent::__construct();

        $this->processor = $processor ?? new Processor();
        $this->sender = $sender ?? [$this, 'sendRelayMessage'];
    }

    protected function configure()
    {
        $this
            ->addOption('input', null, InputOption::VALUE_REQUIRED, 'Path to the raw email (.eml). Reads STDIN when omitted.')
            ->addOption('config', null, InputOption::VALUE_REQUIRED, 'Path to a JSON config file.')
            ->addOption('keyword', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Keyword to match in the subject or body.')
            ->addOption('sender', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Sender fragment to match in the From header.')
            ->addOption('recipient', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Recipient that will receive the relayed notification.')
            ->addOption('subject-prefix', null, InputOption::VALUE_REQUIRED, 'Subject prefix for relayed notifications.')
            ->addOption('source-label', null, InputOption::VALUE_REQUIRED, 'Human-readable source label shown in the forwarded notification.')
            ->addOption('require-sender-match', null, InputOption::VALUE_REQUIRED, 'Require a sender match before relaying (true/false).')
            ->addOption('require-keyword-match', null, InputOption::VALUE_REQUIRED, 'Require a keyword match before relaying (true/false).')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Parse and match the message without sending any notification.')
            ->setHelp('Use this command with a raw email produced by Axet SMS Gate or another SMS-forwarding app. The command detects relevant SMS content and forwards it to one or more recipients such as Ghufran or MAXS.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->loadConfig($input);
        $rawEmail = $this->loadRawEmail($input);

        if (empty($config['keywords']) && empty($config['senders'])) {
            $output->writeln('<error>At least one keyword or sender must be configured.</error>');
            return Command::FAILURE;
        }

        $evaluation = $this->processor->evaluate($rawEmail, $config);
        $message = $evaluation['message'];
        $matches = $evaluation['matches'];

        $output->writeln('Parsed message:');
        $output->writeln('  From: ' . ($message['from'] ?: '(empty)'));
        $output->writeln('  Subject: ' . ($message['subject'] ?: '(empty)'));
        $output->writeln('  Matched senders: ' . $this->formatList($matches['matchedSenders']));
        $output->writeln('  Matched keywords: ' . $this->formatList($matches['matchedKeywords']));

        if (! $matches['shouldRelay']) {
            $output->writeln('No relay rule matched. Nothing was sent.');
            return Command::SUCCESS;
        }

        if ($input->getOption('dry-run')) {
            $output->writeln('Dry run enabled. Relay message was not sent.');
            return Command::SUCCESS;
        }

        $recipients = $config['recipients'] ?? [];
        if (empty($recipients)) {
            $output->writeln('<error>No recipients configured for relaying.</error>');
            return Command::FAILURE;
        }

        $subject = $this->processor->buildRelaySubject($message, $config);
        $body = $this->processor->buildRelayBody($message, $matches, $config);
        $sent = call_user_func($this->sender, $recipients, $subject, $body, $rawEmail);

        if (! $sent) {
            $output->writeln('<error>Matched SMS could not be relayed.</error>');
            return Command::FAILURE;
        }

        $output->writeln('Relayed to: ' . implode(', ', $recipients));

        return Command::SUCCESS;
    }

    public function sendRelayMessage(array $recipients, string $subject, string $body, string $rawEmail): bool
    {
        require_once 'lib/webmail/tikimaillib.php';

        $mail = new \TikiMail();
        $mail->setSubject($subject);
        $mail->setText($body);
        $mail->addAttachment($rawEmail, 'original-sms.eml', 'message/rfc822');

        return $mail->send($recipients, 'mail');
    }

    private function loadConfig(InputInterface $input): array
    {
        $config = [];
        $configPath = $input->getOption('config');

        if ($configPath) {
            if (! is_file($configPath) || ! is_readable($configPath)) {
                throw new \InvalidArgumentException("Unable to read config file: {$configPath}");
            }

            $content = file_get_contents($configPath);
            $decoded = json_decode($content, true);
            if (! is_array($decoded)) {
                throw new \InvalidArgumentException("Invalid JSON config file: {$configPath}");
            }

            $config = $decoded;
        }

        foreach (
            [
                'keywords' => 'keyword',
                'senders' => 'sender',
                'recipients' => 'recipient',
            ] as $configKey => $optionName
        ) {
            $optionValues = $input->getOption($optionName);
            if (! empty($optionValues)) {
                $config[$configKey] = $optionValues;
            }
        }

        foreach (
            [
                'subject_prefix' => 'subject-prefix',
                'source_label' => 'source-label',
                'require_sender_match' => 'require-sender-match',
                'require_keyword_match' => 'require-keyword-match',
            ] as $configKey => $optionName
        ) {
            $optionValue = $input->getOption($optionName);
            if ($optionValue !== null) {
                $config[$configKey] = $optionValue;
            }
        }

        $config['keywords'] = $this->normalizeList($config['keywords'] ?? []);
        $config['senders'] = $this->normalizeList($config['senders'] ?? []);
        $config['recipients'] = $this->normalizeList($config['recipients'] ?? []);

        return $config;
    }

    private function loadRawEmail(InputInterface $input): string
    {
        $inputFile = $input->getOption('input');
        if ($inputFile) {
            if (! is_file($inputFile) || ! is_readable($inputFile)) {
                throw new \InvalidArgumentException("Unable to read input file: {$inputFile}");
            }

            $rawEmail = file_get_contents($inputFile);
        } else {
            $rawEmail = stream_get_contents(STDIN);
        }

        $rawEmail = (string) $rawEmail;
        if (trim($rawEmail) === '') {
            throw new \InvalidArgumentException('No raw email content was provided.');
        }

        return $rawEmail;
    }

    private function normalizeList($values): array
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        $normalized = [];
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                $normalized[] = $value;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function formatList(array $values): string
    {
        return empty($values) ? '(none)' : implode(', ', $values);
    }
}
