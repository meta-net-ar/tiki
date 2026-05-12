<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
function prefs_openidconnect_list()
{
    return [
        'openidconnect_name' => [
            'name' => tr('Provider name'),
            'description' => tr('Display name shown on the OpenID Connect login button (for example, Google or Azure AD).'),
            'type' => 'text',
            'default' => '',
            'help' => 'OpenID',
        ],
        'openidconnect_issuer' => [
            'name' => tr('Issuer URL'),
            'description' => tr('OpenID Connect issuer identifier used to validate ID tokens from the provider.'),
            'type' => 'text',
            'default' => '',
            'help' => 'https://openid.net/specs/openid-connect-discovery-1_0.html',
        ],
        'openidconnect_auth_url' => [
            'name' => tr('Provider URL authorization'),
            'description' => tr('Authorization URL from the OpenId provider.'),
            'type' => 'text',
            'default' => '',
            'help' => 'https://openid.net/specs/openid-connect-discovery-1_0.html',
        ],
        'openidconnect_access_token_url' => [
            'name' => tr('Provider URL user access token url'),
            'description' => tr('URL from the OpenId provider to fetch the access_token'),
            'type' => 'text',
            'default' => '',
            'help' => 'OpenID-Connect',
        ],
        'openidconnect_details_url' => [
            'name' => tr('Provider URL resource owner details'),
            'description' => tr('URL from the OpenId provider that provides information on the granted user.'),
            'type' => 'text',
            'default' => '',
            'help' => 'OpenID-Connect',
        ],
        'openidconnect_client_id' => [
            'name' => tr('Client ID'),
            'description' => tr('OAuth 2.0 Client ID registered with the OpenID Connect provider.'),
            'type' => 'text',
            'default' => '',
            'help' => 'OpenID-Connect',
        ],
        'openidconnect_client_secret' => [
            'name' => tr('Client Secret'),
            'description' => tr('OAuth 2.0 Client Secret valid at the Authorization Server'),
            'type' => 'text',
            'default' => '',
            'help' => 'OpenID-Connect',
        ],
        'openidconnect_verify_method' => [
            'name' => tra('Verification method'),
            'description' => tra('How to obtain the public key used to verify ID tokens: from a JWKS endpoint or a static certificate.'),
            'type' => 'list',
            'options' => [
                'jwks' => tra('JWKS'),
                'cert' => tra('Certificate')
            ],
            'default' => 'jwks',
            'help' => 'OpenID-Connect',
        ],
        'openidconnect_create_user_tiki' => [
            'name' => tra('Create user if not registered in Tiki'),
            'description' => tr('If a user was externally authenticated, but not found in the Tiki user database, Tiki will create an entry in its user database.'),
            'type' => 'flag',
            'help' => 'OpenID-Connect',
            'default' => 'n',
        ],
        'openidconnect_jwks_url' => [
            'name' => tr('JWKS URL'),
            'description' => tr('Read-only endpoint that contains the public keys information in the JWKS format'),
            'type' => 'text',
            'default' => '',
            'help' => 'OpenID-Connect',
        ],
        'openidconnect_cert' => [
            'name' => tr('Public certificate'),
            'description' => tr('Public certificate used to verify ID tokens when Verification method is Certificate.'),
            'type' => 'textarea',
            'default' => '',
            'help' => 'OpenID-Connect',
        ],
    ]; // TODO: Update help page for this preference
}
