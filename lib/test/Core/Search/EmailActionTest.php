<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
class SearchActionEmailActionTest extends TikiTestCase
{
    /**
     * @var Search_Action_EmailAction
     */
    private $action;

    /**
     * @var PHPUnit\Framework\MockObject\MockObject|TikiMail
     */
    private $mail;

    /**
     * @var TestableTikiLib|null
     */
    private $overrideLibs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mail = $this->getMockBuilder(TikiMail::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setReplyTo', 'setFrom', 'setSender', 'setSubject', 'setHtml', 'addAttachment', 'send'])
            ->getMock();

        $mail = $this->mail;
        $this->action = new class ($mail) extends Search_Action_EmailAction {
            /**
             * @var TikiMail
             */
            private $mail;

            public function __construct(TikiMail $mail)
            {
                $this->mail = $mail;
            }

            protected function createMail(): TikiMail
            {
                return $this->mail;
            }
        };
        $this->overrideLibs = new TestableTikiLib();

        $parserLib = $this->createMock(get_class(TikiLib::lib('parser')));
        $parserLib->method('parse_data')
            ->willReturnCallback(function ($content, $options = null) {
                return str_replace(['~np~', '~/np~'], '', $content);
            });

        $this->overrideLibs->overrideLibs(['parser' => $parserLib]);
    }

    protected function tearDown(): void
    {
        $this->overrideLibs = null;
        parent::tearDown();
    }

    public function testExecuteParsesSimpleRfcAddressList()
    {
        $this->expectSend(['alice@example.org', 'bob@example.org'], true);

        $this->assertTrue($this->action->execute($this->createInput([
            'to' => ['alice@example.org, bob@example.org'],
        ])));
    }

    public function testExecuteParsesNamedRfcAddressListWithQuotedCommas()
    {
        $this->expectSend(['john.doe@example.org', 'jane@example.org'], true);

        $this->assertTrue($this->action->execute($this->createInput([
            'to' => ['"Doe, John" <john.doe@example.org>, Jane Doe <jane@example.org>'],
        ])));
    }

    public function testExecuteParsesSemicolonSeparatedNamedRfcAddressListWithQuotedCommas()
    {
        $this->expectSend(['john.doe@example.org', 'jane@example.org'], true);

        $this->assertTrue($this->action->execute($this->createInput([
            'to' => ['"Doe, John" <john.doe@example.org>; "Smith, Jane" <jane@example.org>'],
        ])));
    }

    public function testExecuteParsesRfcAddressListAcrossMultipleLines()
    {
        $this->expectSend(['alice@example.org', 'bob@example.org'], true);

        $this->assertTrue($this->action->execute($this->createInput([
            'to' => ["Alice <alice@example.org>\nBob <bob@example.org>"],
        ])));
    }

    public function testExecuteParsesMixedNamedAndPlainRfcAddresses()
    {
        $this->expectSend(['alice@example.org', 'bob@example.org'], true);

        $this->assertTrue($this->action->execute($this->createInput([
            'to' => ['Alice <alice@example.org>, bob@example.org'],
        ])));
    }

    public function testExecuteFallsBackToCommaSeparatedTikiUsernames()
    {
        $trkLib = $this->createMock(get_class(TikiLib::lib('trk')));
        $trkLib->expects($this->once())
            ->method('parse_user_field')
            ->with('alice,bob')
            ->willReturn(['alice', 'bob']);

        $userLib = $this->createMock(get_class(TikiLib::lib('user')));
        $userLib->expects($this->exactly(2))
            ->method('get_user_email')
            ->willReturnMap([
                ['alice', 'alice@example.org'],
                ['bob', 'bob@example.org'],
            ]);

        $this->overrideLibs->overrideLibs([
            'trk' => $trkLib,
            'user' => $userLib,
        ]);

        $this->expectSend(['alice@example.org', 'bob@example.org'], true);

        $this->assertTrue($this->action->execute($this->createInput([
            'to' => ['alice,bob'],
        ])));
    }

    public function testExecuteReturnsFalseForInvalidInputContainingAtSign()
    {
        $this->expectSend([], false);

        $this->assertFalse($this->action->execute($this->createInput([
            'to' => ['not-an-email@'],
        ])));
    }

    public function testExecuteStripsNoParseMarkersBeforeParsing()
    {
        $this->expectSend(['alice@example.org'], true);

        $this->assertTrue($this->action->execute($this->createInput([
            'to' => ['~np~"Alice, Test" <alice@example.org>~/np~'],
        ])));
    }

    private function createInput(array $overrides = []): JitFilter
    {
        return new JitFilter(array_merge([
            'object_type' => '',
            'object_id' => 0,
            'replyto' => null,
            'to' => [],
            'cc' => [],
            'bcc' => [],
            'from' => null,
            'subject' => 'Subject',
            'content' => 'Body',
            'is_html' => 0,
            'pdf_page_attachment' => '',
            'file_attachments' => [],
            'file_attachment_field' => '',
            'file_attachment_gal' => '',
        ], $overrides));
    }

    private function expectSend(array $recipients, bool $result): void
    {
        $this->mail->expects($this->once())
            ->method('send')
            ->with($recipients)
            ->willReturn($result);
    }
}
