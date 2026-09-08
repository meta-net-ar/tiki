<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Xmpp;

use RuntimeException;
use XmppPrebind;

/** BOSH transport for user-configured external servers. */
class TikiXmppExternalPrebind extends XmppPrebind
{
    protected function send($xml)
    {
        $endpoint = parse_url($this->boshUri);
        if (
            ! is_array($endpoint)
            || strtolower($endpoint['scheme'] ?? '') !== 'https'
            || empty($endpoint['host'])
            || isset($endpoint['user']) || isset($endpoint['pass'])
            || isset($endpoint['fragment'])
        ) {
            throw new RuntimeException('A public HTTPS BOSH endpoint is required');
        }
        $host = $endpoint['host'];
        // Resolve once per request and pin cURL to the validated public address.
        $literal = trim($host, '[]');
        if (filter_var($literal, FILTER_VALIDATE_IP)) {
            $addresses = [$literal];
        } else {
            $addresses = gethostbynamel($host) ?: [];
            foreach (dns_get_record($host, DNS_AAAA) ?: [] as $record) {
                if (isset($record['ipv6'])) {
                    $addresses[] = $record['ipv6'];
                }
            }
        }
        if (! $addresses) {
            throw new RuntimeException('Unable to resolve the BOSH endpoint');
        }
        foreach ($addresses as $address) {
            if (! filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('The BOSH endpoint must use public addresses');
            }
        }
        $port = $endpoint['port'] ?? 443;
        $target = str_contains($addresses[0], ':') ? '[' . $addresses[0] . ']' : $addresses[0];
        $handle = curl_init($this->boshUri);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xml,
            CURLOPT_HTTPHEADER => ['Content-Type: ' . self::CONTENT_TYPE],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROXY => '',
            CURLOPT_RESOLVE => [$host . ':' . $port . ':' . $target],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($handle);
        $status = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        unset($handle);
        if ($response === false || $status !== 200) {
            throw new RuntimeException('The BOSH request failed');
        }
        return $response;
    }
}
