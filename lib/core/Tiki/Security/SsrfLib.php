<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Security;

/**
 * SSRF protection utilities.
 *
 * Validates hosts and URLs before server-side fetches to prevent
 * Server-Side Request Forgery via private/reserved IP ranges,
 * DNS rebinding, and redirect chains.
 *
 * Supports a configurable whitelist so that specific internal hosts
 * can be explicitly permitted when needed.
 */
class SsrfLib
{
    /** @var string[] Hosts that are always allowed regardless of IP checks */
    private array $whitelistedHosts = [];

    /**
     * @param string[] $whitelistedHosts Hostnames that bypass the private IP check
     */
    public function __construct(array $whitelistedHosts = [])
    {
        $this->whitelistedHosts = array_map('strtolower', $whitelistedHosts);
    }

    /**
     * Return an instance pre-configured with the Tiki admin whitelist preference.
     *
     * The preference `ssrf_whitelisted_hosts` is expected to be a
     * comma-separated string of hostnames stored in the Tiki preferences
     * (Admin → Security).  When the preference does not exist the
     * whitelist is empty.
     */
    public static function fromPrefs(): self
    {
        global $prefs;
        $raw = ! empty($prefs['ssrf_whitelisted_hosts']) ? $prefs['ssrf_whitelisted_hosts'] : '';
        $hosts = array_filter(array_map('trim', explode(',', $raw)));
        return new self($hosts);
    }

    /**
     * Check whether a host is allowed to be fetched by the server.
     *
     * Rejects hosts that resolve to private, loopback, link-local or
     * other reserved IP ranges.  If a hostname resolves to multiple
     * addresses, **any** private/reserved address causes rejection.
     *
     * Whitelisted hosts bypass the check entirely.
     *
     * @param string $host Hostname or IP literal
     * @return bool True if allowed, false if disallowed
     */
    public function isHostAllowed(string $host): bool
    {
        if ($host === '') {
            return false;
        }

        // Whitelist check (case-insensitive)
        if (in_array(strtolower($host), $this->whitelistedHosts, true)) {
            return true;
        }

        // IP literal — validate directly
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return filter_var(
                $host,
                FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
            ) !== false;
        }

        $ips = $this->resolveHost($host);

        // If DNS returned nothing, allow (let the fetch itself fail)
        if (empty($ips)) {
            return true;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate a full URL: scheme must be http(s), and the resolved
     * host must pass {@see isHostAllowed()}.
     *
     * @param string $url
     * @return bool
     */
    public function isUrlAllowed(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $parsed = parse_url($url);
        if (! $parsed || empty($parsed['scheme']) || empty($parsed['host'])) {
            return false;
        }

        if (! in_array(strtolower($parsed['scheme']), ['http', 'https'], true)) {
            return false;
        }

        return $this->isHostAllowed($parsed['host']);
    }

    /**
     * Resolve a hostname to all its A and AAAA records.
     *
     * @param string $host
     * @return string[] IP addresses
     */
    private function resolveHost(string $host): array
    {
        $ips = [];

        // IPv4 A records
        $a = @gethostbynamel($host);
        if (is_array($a)) {
            $ips = $a;
        }

        // IPv6 AAAA records
        if (function_exists('dns_get_record')) {
            $aaaa = @dns_get_record($host, DNS_AAAA);
            if (is_array($aaaa)) {
                foreach ($aaaa as $rec) {
                    if (! empty($rec['ipv6'])) {
                        $ips[] = $rec['ipv6'];
                    }
                }
            }
        }

        return $ips;
    }
}
