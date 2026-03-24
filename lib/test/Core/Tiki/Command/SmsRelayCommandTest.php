<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Test\Core\Tiki\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tiki\Command\Application;
use Tiki\Command\SmsRelayCommand;

class SmsRelayCommandTest extends TestCase
{
    public function testDryRunReportsMatch()
    {
        $commandTester = $this->getCommandTester(function () {
            $this->fail('The sender should not be called during a dry run.');
        });

        $inputFile = tempnam(sys_get_temp_dir(), 'sms-relay-');
        file_put_contents($inputFile, $this->getRawEmail());

        try {
            $commandTester->execute([
                'command' => 'sms:relay',
                '--input' => $inputFile,
                '--keyword' => ['visa direct', 'fraud'],
                '--dry-run' => true,
            ]);
        } finally {
            @unlink($inputFile);
        }

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Matched keywords: visa direct, fraud', $output);
        $this->assertStringContainsString('Dry run enabled. Relay message was not sent.', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testRelaysToConfiguredRecipients()
    {
        $calls = [];
        $commandTester = $this->getCommandTester(function (array $recipients, string $subject, string $body) use (&$calls) {
            $calls[] = [$recipients, $subject, $body];
            return true;
        });

        $inputFile = tempnam(sys_get_temp_dir(), 'sms-relay-');
        file_put_contents($inputFile, $this->getRawEmail());

        try {
            $commandTester->execute([
                'command' => 'sms:relay',
                '--input' => $inputFile,
                '--keyword' => ['visa direct'],
                '--recipient' => ['ghufran@example.com', 'maxs@wikisuite.chat'],
                '--source-label' => 'Axet SMS Gate',
            ]);
        } finally {
            @unlink($inputFile);
        }

        $this->assertCount(1, $calls);
        $this->assertSame(['ghufran@example.com', 'maxs@wikisuite.chat'], $calls[0][0]);
        $this->assertStringContainsString('[SMS Relay]', $calls[0][1]);
        $this->assertStringContainsString('Source: Axet SMS Gate', $calls[0][2]);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    private function getCommandTester(callable $sender): CommandTester
    {
        $application = new Application();
        $application->add(new SmsRelayCommand(null, $sender));

        $command = $application->find('sms:relay');

        return new CommandTester($command);
    }

    private function getRawEmail(): string
    {
        return <<<EOT
From: TD Alerts <alerts@td.com>
To: sms@evoludata.ca
Subject: TD security alert
Content-Type: text/plain; charset=UTF-8

Please confirm your Visa Direct transaction now. Fraud monitoring needs your reply.
EOT;
    }
}
