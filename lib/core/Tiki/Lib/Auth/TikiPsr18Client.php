<?php

namespace Tiki\Lib\Auth;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * PSR-18 bridge to Laminas\Http\Client used by Tiki.
 * - Preserves Tiki's central HTTP options (proxy/TLS/timeout).
 * - Normalizes CAS validation requests (serviceValidate) for JSON/XML.
 * - Fixes HTML-escaped query separators (&amp;) before sending the request.
 */
final class TikiPsr18Client implements ClientInterface
{
    private \Laminas\Http\Client $laminas;
    private Psr17Factory $psr17;

    public function __construct(\Laminas\Http\Client $laminasClient, Psr17Factory $psr17)
    {
        $this->laminas = $laminasClient;
        $this->psr17   = $psr17;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        // Do not auto-follow redirects. Keep reasonable defaults.
        if (method_exists($this->laminas, 'setOptions')) {
            $this->laminas->setOptions([
                'maxredirects' => 0,
                'timeout'      => 30,
            ]);
        }

        $uri    = (string) $request->getUri();
        $method = $request->getMethod();

        // Normalize HTML-escaped separators so CAS sees "ticket" (not "amp;ticket").
        $uri = str_replace('&amp;', '&', $uri);
        $uri = preg_replace('/([?&])amp;/', '$1', $uri);

        // Detect CAS validation endpoint and requested format.
        $needValidate = (stripos($uri, '/serviceValidate') !== false) || (stripos($uri, '/p3/serviceValidate') !== false);
        $wantsJson    = (stripos($uri, 'format=JSON') !== false);

        // Build Laminas request.
        $lReq = new \Laminas\Http\Request();
        $lReq->setUri($uri);
        $lReq->setMethod($method);

        // Copy headers; ensure an explicit Accept for validation endpoints.
        $headers   = new \Laminas\Http\Headers();
        $hasAccept = false;
        foreach ($request->getHeaders() as $name => $values) {
            foreach ($values as $v) {
                if (strcasecmp($name, 'Accept') === 0) {
                    $hasAccept = true;
                }
                $headers->addHeaderLine($name, $v);
            }
        }
        if ($needValidate && ! $hasAccept) {
            $headers->addHeaderLine('Accept', $wantsJson ? 'application/json' : 'application/xml');
        }
        $lReq->setHeaders($headers);

        // Forward request body if present.
        $body = (string) $request->getBody();
        if ($body !== '') {
            $lReq->setContent($body);
        }

        // Send request via Laminas.
        $lRes = $this->laminas->send($lReq);

        // Read raw response.
        $status   = $lRes->getStatusCode();
        $ctypeHdr = $lRes->getHeaders()->get('Content-Type');
        $ctypeVal = $ctypeHdr ? $ctypeHdr->getFieldValue() : '';
        $rawBody  = (string) $lRes->getBody();

        // Prepare PSR-7 response to return to cas-lib.
        $psrRes = $this->psr17->createResponse($status);

        // Heuristics for coercing Content-Type when CAS servers mislabel responses.
        $looksLikeCasXml = (stripos($rawBody, '<cas:serviceResponse') !== false);
        $looksLikeJson   = $wantsJson && preg_match('/^\s*[\{\[]/u', $rawBody) === 1;

        if ($needValidate && $wantsJson) {
            // Validation requested as JSON.
            if (stripos($ctypeVal, 'json') === false && $looksLikeJson) {
                // Copy all headers except Content-Type, then set a strict application/json.
                foreach ($lRes->getHeaders() as $h) {
                    $name = $h->getFieldName();
                    if (strcasecmp($name, 'Content-Type') !== 0) {
                        $psrRes = $psrRes->withAddedHeader($name, $h->getFieldValue());
                    }
                }
                $psrRes = $psrRes->withHeader('Content-Type', 'application/json');
            } else {
                // Pass through headers as-is.
                foreach ($lRes->getHeaders() as $h) {
                    $psrRes = $psrRes->withAddedHeader($h->getFieldName(), $h->getFieldValue());
                }
            }
        } else {
            // XML (or other) response handling.
            $shouldCoerceToXml = $needValidate && $looksLikeCasXml && (stripos($ctypeVal, 'xml') === false);
            $forceAppXml       = $needValidate && (stripos($ctypeVal, 'text/xml') !== false);

            if ($shouldCoerceToXml || $forceAppXml) {
                // Copy all headers except Content-Type, then set a strict application/xml.
                foreach ($lRes->getHeaders() as $h) {
                    $name = $h->getFieldName();
                    if (strcasecmp($name, 'Content-Type') !== 0) {
                        $psrRes = $psrRes->withAddedHeader($name, $h->getFieldValue());
                    }
                }
                $psrRes = $psrRes->withHeader('Content-Type', 'application/xml');
            } else {
                // Pass through headers as-is.
                foreach ($lRes->getHeaders() as $h) {
                    $psrRes = $psrRes->withAddedHeader($h->getFieldName(), $h->getFieldValue());
                }
            }
        }

        // Attach body and return.
        $stream = $this->psr17->createStream($rawBody);
        return $psrRes->withBody($stream);
    }
}
