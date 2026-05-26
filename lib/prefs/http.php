<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_http_list()
{
    return [
        'http_port' => [
            'name' => tra('HTTP port'),
            'description' => tra('The port used to access this server; if not specified, port %0 will be used', 80),
            'type' => 'text',
            'size' => 5,
            'filter' => 'digits',
            'default' => '',
            'shorthint' => tra('If not specified, port %0 will be used', 80),
        ],
        'http_skip_frameset' => [
            'name' => tra('HTTP lookup: skip framesets'),
            'description' => tra('When performing an HTTP request to an external source, verify if the result is a frameset and use heuristic to provide the real content.'),
            'type' => 'flag',
            'default' => 'n',
        ],
        'http_referer_registration_check' => [
            'name' => tra('Registration referrer check'),
            'description' => tra('Use the HTTP referrer to check registration POST is sent from same host. (May not work on some setups.)'),
            'type' => 'flag',
            'default' => 'y',
        ],
        'http_header_frame_options' => [
            'name' => tra('HTTP header x-frame options'),
            'description' => tra('The x-frame-options HTTP response header can be used to indicate whether or not a browser should be allowed to render a page in a &lt;frame&gt;, &lt;iframe&gt; or &lt;object&gt;'),
            'type' => 'flag',
            'default' => 'y',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_frame_options_value' => [
            'name' => tra('Header value'),
            'type' => 'list',
            'options' => [
                'DENY' => tra('DENY'),
                'SAMEORIGIN' => tra('SAMEORIGIN'),
            ],
            'default' => 'DENY',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_frame_options',
            ],
        ],
        'http_header_access_control_allow_credentials' => [
            'name' => tra('HTTP header allow credentials'),
            'description' => tra('The Access-Control-Allow-Credentials response header tells browsers whether the server allows cross-origin HTTP requests to include credentials.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced']
        ],
        'http_header_xss_protection' => [
            'name' => tra('HTTP header x-xss-protection'),
            'description' => tra('The x-xss-protection header is designed to enable the cross-site scripting (XSS) filter built into modern web browsers'),
            'type' => 'flag',
            'default' => 'y',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_xss_protection_value' => [
            'name' => tra('Header value'),
            'type' => 'list',
            'options' => [
                '0' => '0',
                '1' => '1',
                '1;mode=block' => tra('1;mode=block'),
            ],
            'default' => '1;mode=block',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_xss_protection',
            ],
        ],
        'http_header_cross_origin_embedder_policy' => [
            'name' => tra('HTTP header cross-origin-embedder-policy'),
            'description' => tra('Controls the loading of cross-origin resources in a document. Setting this header helps enhance security by ensuring that loaded resources explicitly grant permission to be loaded.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_cross_origin_embedder_policy_value' => [
            'name' => tra('Header value'),
            'description' => tra('Specifies the policy for loading cross-origin resources. "Require-CORP" requires cross-origin resources to have CORP headers. "Credentialless" allows loading cross-origin resources without credentials. "Unsafe-none" applies no restrictions.'),
            'type' => 'list',
            'options' => [
                'unsafe-none' => tra('None'),
                'require-corp' => tra('Require-CORP'),
                'credentialless' => tra('Credentialless'),
            ],
            'default' => 'unsafe-none',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_cross_origin_resource_policy' => [
            'name' => tra('HTTP header Cross-Origin-Resource-Policy'),
            'description' => tra('Defines which cross-origin requests are allowed to access resources on your site. This header can help prevent other sites from reading or loading your site\'s resources without permission.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_cross_origin_resource_policy_value' => [
            'name' => tra('Header value'),
            'description' => tra('Determines which origins are allowed to access resources. "Same-Origin" only allows your own site to access resources. "Same-Site" extends this to your entire site, including subdomains. "Cross-Origin" allows any site to access the resources.'),
            'type' => 'list',
            'options' => [
                'same-origin' => tra('Same-Origin: Only same-origin requests are allowed.'),
                'same-site' => tra('Same-Site: Only requests from the same site are allowed.'),
                'cross-origin' => tra('Cross-Origin: Allows requests from any origin.'),
            ],
            'default' => 'same-site',
            'perspective' => false,
            'tags' => ['advanced'],
            'help' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Resource-Policy',
        ],
        'http_header_cross_origin_opener_policy' => [
            'name' => tra('HTTP header Cross-Origin-Opener-Policy'),
            'description' => tra('Enables or disables the sending of the Cross-Origin-Opener-Policy header in HTTP responses from your site. This header controls how the document may interact with other browsing contexts.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_cross_origin_opener_policy_value' => [
            'name' => tra('Header value'),
            'description' => tra('Specifies the policy for cross-origin opener policy header.'),
            'type' => 'list',
            'options' => [
                'same-origin' => tra('Same-Origin: Allows the document to be opened only by pages from the same origin.'),
                'same-origin-allow-popups' => tra('Same-Origin-Allow-Popups: Allows the document to be opened by pages from the same origin, and allows those pages to open popups.'),
                'same-origin-plus-coep' => tra('Same-Origin-Plus-COEP: Allows the document to be opened only by pages from the same origin, and sets the Cross-Origin-Embedder-Policy header to `require-corp`.'),
                'unsafe-none' => tra('None: No specific policy is set.'),
            ],
            'default' => 'same-origin-allow-popups',
            'perspective' => false,
            'tags' => ['advanced'],
            'help' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Cross-Origin-Opener-Policy',
        ],
        'http_header_referrer_policy' => [
            'name' => tra('HTTP header Referrer-Policy'),
            'description' => tra('The Referrer-Policy HTTP header controls how much referrer information (sent via the Referer header) should be included with requests.'),
            'type' => 'flag',
            'default' => 'y',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_referrer_policy_value' => [
            'name' => tra('Header value'),
            'description' => tra('Specifies the referrer policy. "strict-origin-when-cross-origin" is the recommended safe default.'),
            'type' => 'list',
            'options' => [
                'no-referrer' => tra('no-referrer: Never send the Referer header.'),
                'no-referrer-when-downgrade' => tra('no-referrer-when-downgrade: Send full URL for same-origin, omit on downgrade.'),
                'origin' => tra('origin: Send only the origin.'),
                'origin-when-cross-origin' => tra('origin-when-cross-origin: Full URL for same-origin, origin only for cross-origin.'),
                'same-origin' => tra('same-origin: Send referrer only to same-origin requests.'),
                'strict-origin' => tra('strict-origin: Send origin only when protocol security is maintained.'),
                'strict-origin-when-cross-origin' => tra('strict-origin-when-cross-origin: Full URL same-origin, origin cross-origin, nothing on downgrade. (Recommended)'),
                'unsafe-url' => tra('unsafe-url: Always send full URL (not recommended).'),
            ],
            'default' => 'strict-origin-when-cross-origin',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_referrer_policy',
            ],
            'help' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Referrer-Policy',
        ],
        'http_header_permitted_cross_domain_policies' => [
            'name' => tra('HTTP header X-Permitted-Cross-Domain-Policies'),
            'description' => tra('The X-Permitted-Cross-Domain-Policies header controls how Adobe products (Flash, Acrobat) and Silverlight may access cross-domain data.'),
            'type' => 'flag',
            'default' => 'y',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_permitted_cross_domain_policies_value' => [
            'name' => tra('Header value'),
            'type' => 'list',
            'options' => [
                'none' => tra('none: No cross-domain policies allowed. (Recommended)'),
                'master-only' => tra('master-only: Only the master policy file is allowed.'),
                'by-content-type' => tra('by-content-type: Only policy files served with Content-Type: text/x-cross-domain-policy are allowed.'),
                'all' => tra('all: All policy files are allowed.'),
            ],
            'default' => 'none',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_permitted_cross_domain_policies',
            ],
            'help' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Permitted-Cross-Domain-Policies',
        ],
        'http_header_content_type_options' => [
            'name' => tra('HTTP header x-content-type-options'),
            'description' => tra('The x-content-type-options header is a marker used by the server to indicate that the MIME types advertised in the Content-Type headers should not be changed and be followed.'),
            'type' => 'flag',
            'default' => 'y',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_content_security_policy' => [
            'name' => tra('HTTP header content-security-policy'),
            'description' => tra('The Content-Security-Policy header allows web site administrators to control resources the user agent is allowed to load for a given page.'),
            'type' => 'flag',
            'default' => 'y',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_content_security_policy_value' => [
            'name' => tra('Header value'),
            'type' => 'text',
            'default' => '',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_content_security_policy',
            ],
            'description' => tr(
                'For example, to allow your Tiki to appear in an iframe on example.com set this value to %0',
                '<code>frame-ancestors https://example.com/</code>'
            ),
            'help' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy',
        ],
        'http_header_access_control_allow_methods' => [
            'name' => tra('HTTP header access-control-allow-methods'),
            'description' => tra('Enables or disables the sending of the Access-Control-Allow-Methods header in HTTP responses from your Tiki site. This header is crucial for Cross-Origin Resource Sharing (CORS) and specifies the HTTP methods that are allowed when accessing resources in response to a preflight request.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_access_control_allow_methods_value' => [
            'name' => tra('Header value'),
            'type' => 'text',
            'default' => '',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_access_control_allow_methods',
            ],
            'description' => tr(
                'Specifies the HTTP methods that are allowed for cross-origin requests. This setting takes effect only if the HTTP header access-control-allow-methods is enabled. Separate multiple methods with commas. For example, to allow GET, POST, and PUT methods, set this value to %0',
                '<code>GET, POST, PUT</code>'
            ),
            'help' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods',
        ],
        'http_header_access_control_allow_headers' => [
            'name' => tra('HTTP header access-control-allow-headers'),
            'description' => tra('Enables or disables the sending of the Access-Control-Allow-Headers header in HTTP responses from your Tiki site. This header is crucial for Cross-Origin Resource Sharing (CORS) and specifies the headers that are allowed when making actual requests (after the preflight has been accepted).'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_access_control_allow_headers_value' => [
            'name' => tra('Header value'),
            'type' => 'text',
            'default' => '',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_access_control_allow_headers',
            ],
            'description' => tr(
                'Specifies the HTTP headers that can be used when making the actual request. This setting takes effect only if the HTTP header access-control-allow-headers is enabled. Separate multiple header names with commas. For example, to allow headers such as Content-Type, Accept, and X-Requested-With, set this value to %0',
                '<code>Content-Type, Accept, X-Requested-With</code>'
            ),
            'help' => 'https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers',
        ],
        'http_header_strict_transport_security' => [
            'name' => tra('HTTP header strict-transport-security'),
            'description' => tra('The Strict-Transport-Security header (often abbreviated as HSTS) is a security feature that lets a web site tell browsers that it should only be communicated with using HTTPS, instead of using HTTP.'),
            'type' => 'flag',
            'default' => 'y',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_strict_transport_security_value' => [
            'name' => tra('Header value'),
            'description' => tra('Add includeSubDomains only if every subdomain is served over HTTPS. Add preload only after careful review at hstspreload.org.'),
            'type' => 'text',
            'default' => 'max-age=63072000',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_strict_transport_security',
            ],
        ],
        'http_header_public_key_pins' => [
            'name' => tra('HTTP header public-key-pins'),
            'description' => tra('The public-key-pins header associates a specific cryptographic public key with a certain web server to decrease the risk of MITM attacks with forged certificates. If one or several keys are pinned and none of them are used by the server, the browser will not accept the response as legitimate, and will not display it.'),
            'type' => 'flag',
            'default' => 'n',
            'perspective' => false,
            'tags' => ['advanced'],
        ],
        'http_header_public_key_pins_value' => [
            'name' => tra('Header value'),
            'type' => 'textarea',
            'default' => '',
            'perspective' => false,
            'tags' => ['advanced'],
            'dependencies' => [
                'http_header_public_key_pins',
            ],
        ],
        'http_sslverifypeer' => [
            'name' => tra('Verify HTTPS certificates of remote servers'),
            'description' => tra('When set to enforce, the server will fail to connect over HTTPS to a remote server that do not have a SSL certificate that is valid and can be verified against the local list of Certificate Authority (CA)'),
            'type' => 'list',
            'options' => [
                '' => tra('Do not enforce verification'),
                'y' => tra('Enforce verification'),
            ],
            'default' => '',
        ],
        'http_use_curl'      => [
            'name'        => tra('Use CURL for HTTP connections'),
            'description' => tra(
                'Use CURL instead of sockets for server to server HTTP connections, when sockets are not available.'
            ),
            'type'        => 'flag',
            'default'     => 'n',
            'extensions'  => ['curl'],
        ],
    ];
}
