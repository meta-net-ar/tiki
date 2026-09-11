<?php

namespace Tiki\Lib\Auth;

use EcPhp\CasLib\Cas;
use EcPhp\CasLib\Configuration\Properties;
use EcPhp\CasLib\Response\CasResponseBuilder;
use loophp\psr17\Psr17;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Uri;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class PhpCAS
{
    private static ?Cas $client = null;
    private static ?ServerRequestInterface $request = null;

    /** Base CAS URL kept for tiny inline JSON fallback. */
    private static ?string $casBaseUrl = null;

    private static ?string $authUser = null;

    public static function client(
        string $version,
        string $host,
        int $port,
        string $context,
        bool $serverValidation = true
    ): void {
        @ini_set('arg_separator.output', '&');
        @ini_set('arg_separator.input', '&');

        $scheme  = ($port === 443) ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $host . ($port !== 80 && $port !== 443 ? ':' . $port : '') . rtrim($context, '/');
        self::$casBaseUrl = $baseUrl;
        self::$authUser = null;

        // Prefer P3 JSON to avoid XML conversion issues.
        $config = [
            'base_url' => $baseUrl,
            'protocol' => [
                'login' => [
                    'path' => '/login',
                    'default_parameters' => ['service' => null],
                ],
                'logout' => [
                    'path' => '/logout',
                    'default_parameters' => ['service' => null],
                ],
                'serviceValidate' => [
                    'path' => '/p3/serviceValidate',
                    'default_parameters' => [
                        'service' => self::serviceUrl(),
                        'ticket'  => null,
                        'format'  => 'JSON',
                    ],
                ],
            ],
        ];

        $properties = new Properties($config);

        $nyholm = new Psr17Factory();
        $psr17  = new Psr17($nyholm, $nyholm, $nyholm, $nyholm, $nyholm, $nyholm);

        $laminasClient = \TikiLib::lib('tiki')->get_http_client();
        if ($serverValidation === false && method_exists($laminasClient, 'setOptions')) {
            $laminasClient->setOptions([
                'sslverifypeer' => false,
                'sslverifyhost' => false,
                'curloptions'   => [
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ],
            ]);
        }
        $httpClient = new TikiPsr18Client($laminasClient, $nyholm);

        $cache = new ArrayAdapter();
        $casResponseBuilder = new CasResponseBuilder();
        self::$client = new Cas($properties, $httpClient, $psr17, $cache, $casResponseBuilder);

        $creator = new ServerRequestCreator($nyholm, $nyholm, $nyholm, $nyholm);
        self::$request = $creator->fromGlobals();

        if ((string) self::$request->getUri() === '') {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $path = $_SERVER['REQUEST_URI'] ?? '/';
            $sch  = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            self::$request = self::$request->withUri(new Uri($sch . '://' . $host . $path));
        }
    }

    public static function forceAuthentication(): void
    {
        $supported = self::$client->supportAuthentication(self::$request) ? '1' : '0';

        if (! self::$client->supportAuthentication(self::$request)) {
            $svc = self::serviceUrl();
            $response = self::$client->login(self::$request, ['service' => $svc]);
            self::sendResponse($response);
        }
    }

    public static function checkAuthentication(): bool
    {
        // If already authenticated in this request, no need to call CAS again.
        if (is_string(self::$authUser) && self::$authUser !== '') {
            return true;
        }

        try {
            $svc = self::serviceUrl();
            $req = self::$request->withQueryParams(array_merge(
                self::$request->getQueryParams(),
                ['service' => $svc]
            ));

            $data = self::$client->authenticate($req);

            // Accept both flattened and nested P3 JSON structures.
            $user = '';
            if (isset($data['user']) && is_string($data['user'])) {
                $user = $data['user'];
            } elseif (
                isset($data['serviceResponse']['authenticationSuccess']['user']) &&
                is_string($data['serviceResponse']['authenticationSuccess']['user'])
            ) {
                $user = $data['serviceResponse']['authenticationSuccess']['user'];
            }

            // Inline JSON fallback only if still empty (rare mismatch).
            if ($user === '' && is_string(self::$casBaseUrl)) {
                $ticket = $req->getQueryParams()['ticket'] ?? null;
                if (is_string($ticket) && $ticket !== '') {
                    $u = self::inlineJsonUser(self::$casBaseUrl, $svc, $ticket);
                    if (is_string($u) && $u !== '') {
                        self::$authUser = $u;
                        return true;
                    }
                }
            }

            self::$authUser = ($user !== '') ? $user : null;
            return $user !== '';
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getUser(): ?string
    {
        // Return cached user if available to avoid re-validating the same ticket.
        if (is_string(self::$authUser) && self::$authUser !== '') {
            return self::$authUser;
        }

        try {
            $svc = self::serviceUrl();
            $req = self::$request->withQueryParams(array_merge(
                self::$request->getQueryParams(),
                ['service' => $svc]
            ));

            $data = self::$client->authenticate($req);

            $user = $data['user'] ?? null;
            if (! is_string($user) || $user === '') {
                if (
                    isset($data['serviceResponse']['authenticationSuccess']['user']) &&
                    is_string($data['serviceResponse']['authenticationSuccess']['user'])
                ) {
                    $user = $data['serviceResponse']['authenticationSuccess']['user'];
                }
            }

            if ((! is_string($user) || $user === '') && is_string(self::$casBaseUrl)) {
                $ticket = $req->getQueryParams()['ticket'] ?? null;
                if (is_string($ticket) && $ticket !== '') {
                    $u = self::inlineJsonUser(self::$casBaseUrl, $svc, $ticket);
                    if (is_string($u) && $u !== '') {
                        self::$authUser = $u;
                        return $u;
                    }
                }
            }

            if (is_string($user) && $user !== '') {
                self::$authUser = $user;
            }

            return is_string($user) && $user !== '' ? $user : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function logoutWithRedirectService(string $url): void
    {
        $response = self::$client->logout(self::$request, ['service' => $url]);
        self::sendResponse($response);
    }

    /**
     * Build the service URL (current URL without CAS artifacts).
     * */
    private static function serviceUrl(): string
    {
        $scheme = (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri    = $_SERVER['REQUEST_URI'] ?? '/';
        $parts  = parse_url($uri);
        $path   = $parts['path'] ?? '/';
        $qsArr  = [];
        if (! empty($parts['query'])) {
            parse_str($parts['query'], $qsArr);
            unset($qsArr['ticket']);
        }
        $newQs = http_build_query($qsArr, '', '&', PHP_QUERY_RFC3986);
        return $scheme . '://' . $host . $path . ($newQs !== '' ? ('?' . $newQs) : '');
    }


    /**
     * Minimal inline JSON read when cas-lib array is missing 'user'.
     */
    private static function inlineJsonUser(string $baseUrl, string $service, string $ticket): ?string
    {
        try {
            $qs  = http_build_query(['format' => 'JSON', 'service' => $service, 'ticket' => $ticket], '', '&', PHP_QUERY_RFC3986);
            $url = rtrim($baseUrl, '/') . '/p3/serviceValidate?' . $qs;

            $laminas = \TikiLib::lib('tiki')->get_http_client();
            if (method_exists($laminas, 'setOptions')) {
                $laminas->setOptions(['maxredirects' => 0, 'timeout' => 30]);
            }

            $req = new \Laminas\Http\Request();
            $req->setUri($url);
            $req->setMethod('GET');
            $hdr = new \Laminas\Http\Headers();
            $hdr->addHeaderLine('Accept', 'application/json');
            $req->setHeaders($hdr);

            $res  = $laminas->send($req);
            $body = (string) $res->getBody();

            $data = json_decode($body, true);
            if (! is_array($data)) {
                return null;
            }
            $sr = $data['serviceResponse'] ?? null;
            if (is_array($sr) && isset($sr['authenticationSuccess']['user'])) {
                $u = (string) $sr['authenticationSuccess']['user'];
                return $u !== '' ? $u : null;
            }
            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function sendResponse(\Psr\Http\Message\ResponseInterface $response): void
    {
        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }
        echo (string) $response->getBody();
        exit;
    }
}
