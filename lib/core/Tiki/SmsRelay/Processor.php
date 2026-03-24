<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\SmsRelay;

use ZBateson\MailMimeParser\MailMimeParser;

class Processor
{
    private MailMimeParser $mailMimeParser;

    public function __construct(?MailMimeParser $mailMimeParser = null)
    {
        $this->mailMimeParser = $mailMimeParser ?? new MailMimeParser();
    }

    public function evaluate(string $rawEmail, array $config): array
    {
        $message = $this->parseRawEmail($rawEmail);
        $matches = $this->match($message, $config);

        return [
            'message' => $message,
            'matches' => $matches,
        ];
    }

    public function parseRawEmail(string $rawEmail): array
    {
        $parsed = $this->mailMimeParser->parse($rawEmail, true);

        $textBody = $this->normalizeBody((string) $parsed->getTextContent());
        $htmlBody = (string) $parsed->getHtmlContent();

        if ($textBody === '' && $htmlBody !== '') {
            $textBody = $this->normalizeBody(html_entity_decode(strip_tags($htmlBody)));
        }

        return [
            'from' => trim((string) ($parsed->getHeaderValue('From') ?: $parsed->getHeaderValue('Return-Path'))),
            'to' => trim((string) $parsed->getHeaderValue('To')),
            'subject' => trim((string) $parsed->getHeaderValue('Subject')),
            'body' => $textBody,
        ];
    }

    public function match(array $message, array $config): array
    {
        $senders = $this->normalizeList($config['senders'] ?? []);
        $keywords = $this->normalizeList($config['keywords'] ?? []);

        $from = strtolower($message['from'] ?? '');
        $content = strtolower(trim(($message['subject'] ?? '') . "\n" . ($message['body'] ?? '')));

        $matchedSenders = array_values(array_filter($senders, static function ($sender) use ($from) {
            return str_contains($from, strtolower($sender));
        }));
        $matchedKeywords = array_values(array_filter($keywords, static function ($keyword) use ($content) {
            return str_contains($content, strtolower($keyword));
        }));

        $requireSenderMatch = $this->toBool($config['require_sender_match'] ?? false);
        $requireKeywordMatch = array_key_exists('require_keyword_match', $config)
            ? $this->toBool($config['require_keyword_match'])
            : ! empty($keywords);

        $senderPass = ! $requireSenderMatch || empty($senders) || ! empty($matchedSenders);
        $keywordPass = ! $requireKeywordMatch || empty($keywords) || ! empty($matchedKeywords);
        $hasAtLeastOneSignal = ! empty($matchedSenders) || ! empty($matchedKeywords);

        return [
            'shouldRelay' => $hasAtLeastOneSignal && $senderPass && $keywordPass,
            'matchedSenders' => $matchedSenders,
            'matchedKeywords' => $matchedKeywords,
        ];
    }

    public function buildRelaySubject(array $message, array $config): string
    {
        $prefix = trim((string) ($config['subject_prefix'] ?? '[SMS Relay]'));
        $subject = trim((string) ($message['subject'] ?? ''));

        if ($subject === '') {
            $body = trim((string) ($message['body'] ?? ''));
            $subject = $body === ''
                ? 'Relevant SMS detected'
                : (function_exists('mb_substr') ? mb_substr($body, 0, 80) : substr($body, 0, 80));
        }

        return trim($prefix . ' ' . $subject);
    }

    public function buildRelayBody(array $message, array $matches, array $config): string
    {
        $lines = [
            'A relevant SMS message was detected.',
            '',
            'Source: ' . ($config['source_label'] ?? 'SMS relay'),
            'From: ' . ($message['from'] ?? ''),
            'To: ' . ($message['to'] ?? ''),
            'Subject: ' . ($message['subject'] ?? ''),
            'Matched senders: ' . $this->formatList($matches['matchedSenders'] ?? []),
            'Matched keywords: ' . $this->formatList($matches['matchedKeywords'] ?? []),
            '',
            'Message body:',
            $message['body'] ?? '',
        ];

        return implode("\n", $lines);
    }

    private function normalizeBody(string $body): string
    {
        $body = preg_replace("/\r\n?/", "\n", $body);
        $body = preg_replace("/[ \t]+/", ' ', $body);
        $body = preg_replace("/\n{3,}/", "\n\n", $body);

        return trim((string) $body);
    }

    private function normalizeList($values): array
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        $normalized = [];
        foreach ($values as $value) {
            if ($value === null) {
                continue;
            }

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

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
