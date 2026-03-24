<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Test\Core\Tiki\SmsRelay;

use PHPUnit\Framework\TestCase;
use Tiki\SmsRelay\Processor;

class ProcessorTest extends TestCase
{
    public function testEvaluateMatchesKeywordWithoutSenderRequirement()
    {
        $processor = new Processor();

        $result = $processor->evaluate($this->getRawEmail(), [
            'keywords' => ['visa direct', 'fraud'],
            'senders' => ['paypal'],
            'require_sender_match' => false,
            'require_keyword_match' => true,
        ]);

        $this->assertTrue($result['matches']['shouldRelay']);
        $this->assertSame(['visa direct', 'fraud'], $result['matches']['matchedKeywords']);
        $this->assertSame([], $result['matches']['matchedSenders']);
    }

    public function testEvaluateRequiresSenderWhenConfigured()
    {
        $processor = new Processor();

        $result = $processor->evaluate($this->getRawEmail(), [
            'keywords' => ['visa direct'],
            'senders' => ['paypal'],
            'require_sender_match' => true,
            'require_keyword_match' => true,
        ]);

        $this->assertFalse($result['matches']['shouldRelay']);
        $this->assertSame(['visa direct'], $result['matches']['matchedKeywords']);
        $this->assertSame([], $result['matches']['matchedSenders']);
    }

    public function testBuildRelaySubjectFallsBackToBody()
    {
        $processor = new Processor();

        $subject = $processor->buildRelaySubject([
            'subject' => '',
            'body' => 'Please confirm your TD Visa Direct transaction immediately.',
        ], [
            'subject_prefix' => '[Relay]',
        ]);

        $this->assertStringStartsWith('[Relay] Please confirm your TD Visa Direct transaction', $subject);
    }

    private function getRawEmail(): string
    {
        return <<<EOT
From: TD Alerts <alerts@td.com>
To: sms@evoludata.ca
Subject: Security alert
Content-Type: text/plain; charset=UTF-8

Please confirm your Visa Direct transaction. Fraud monitoring needs your reply.
EOT;
    }
}
