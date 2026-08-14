<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Package\VendorHelper;

function prefs_mailer_list()
{
    $emailOptions = [
        'sendmail' => tra('Sendmail (sendmail binary)'),
        'phpini' => tra('Php.ini mail settings'),
        'smtp' => tra('SMTP'),
        'file' => tra('File (debug)'),
    ];

    $isAwsSdkInstalled = VendorHelper::getAvailableVendorPath('AwsSDK', 'aws/aws-sdk-php/src/Credentials/Credentials.php') !== false;
    if ($isAwsSdkInstalled) {
        $emailOptions = array_merge($emailOptions, ['amazonSes' => tra('Amazon SES')]);
    }
    $slmMailOptions = [
//        'elasticEmail' => tra('Elastic Email'),
        'mailgun' => tra('Mailgun'),
        'mandrill' => tra('Mandrill'),
        'postal' => tra('Postal'),
        'postmark' => tra('Postmark'),
        'sendGrid' => tra('SendGrid'),
        'sparkPost' => tra('SparkPost')
    ];
    $emailOptions = array_merge($emailOptions, $slmMailOptions);

    $generalPreferencesHelp = 'General-Preferences';

    return [
        'mailer_amazon_ses_key' => [
            'name' => tra('Amazon SES Key'),
            'description' => tra('AWS access key ID used to authenticate with Amazon SES when Amazon SES is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_amazon_ses_secret' => [
            'name' => tra('Amazon SES Secret'),
            'description' => tra('AWS secret access key used to authenticate with Amazon SES when Amazon SES is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_amazon_ses_region' => [
            'name' => tra('Amazon SES Region'),
            'description' => tra('AWS region for the Amazon SES API endpoint (for example: eu-west-1).'),
            'type' => 'text',
            'perspective' => false,
            'default' => 'eu-west-1',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_elastic_email_key' => [
            'name' => tra('Elastic Email Key'),
            'description' => tra('API key used to authenticate with Elastic Email when Elastic Email is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_mailgun_domain' => [
            'name' => tra('Mailgun Domain'),
            'description' => tra('Mailgun sending domain used when Mailgun is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_mailgun_key' => [
            'name' => tra('Mailgun Key'),
            'description' => tra('Mailgun sending API key used when Mailgun is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_mailgun_region' => [
            'name' => tra('Mailgun Region'),
            'description' => tra('Mailgun API region used when Mailgun is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_mandrill_key' => [
            'name' => tra('Mandrill Key'),
            'description' => tra('Mandrill API key used when Mandrill is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_postmark_key' => [
            'name' => tra('Postmark Key'),
            'description' => tra('Postmark server API token used when Postmark is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_send_grid_region' => [
            'name' => tra('SendGrid Region'),
            'description' => tra('SendGrid API region used when SendGrid is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_send_grid_key' => [
            'name' => tra('SendGrid Key'),
            'description' => tra('SendGrid API key used when SendGrid is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_spark_post_key' => [
            'name' => tra('SparkPost Key'),
            'description' => tra('SparkPost API key used when SparkPost is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_spark_post_region' => [
            'name' => tra('SparkPost Region'),
            'description' => tra('SparkPost API region used when SparkPost is selected as the mail handler.'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_smtp_server' => [
            'name' => tra('SMTP server'),
            'description' => tra('SMTP server hostname used when SMTP is selected as the mail handler.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_smtp_user' => [
            'name' => tra('Username'),
            'description' => tra('SMTP username for authentication when SMTP is selected as the mail handler.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'autocomplete' => 'off',
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_smtp_pass' => [
            'name' => tra('Password'),
            'description' => tra('SMTP password for authentication when SMTP is selected as the mail handler.'),
            'type' => 'password',
            'size' => '20',
            'perspective' => false,
            'autocomplete' => 'off',
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_smtp_port' => [
            'name' => tra('Port'),
            'description' => tra('SMTP port number used when SMTP is selected as the mail handler.'),
            'type' => 'text',
            'size' => '5',
            'perspective' => false,
            'default' => 25,
            'help' => $generalPreferencesHelp,
        ],
        'mailer_smtp_security' => [
            'name' => tra('Security'),
            'description' => tra('Encryption mode for SMTP connections (none, SSL, or TLS) when SMTP is selected as the mail handler.'),
            'type' => 'list',
            'perspective' => false,
            'options' => [
                '' => tra('None'),
                'ssl' => tra('SSL'),
                'tls' => tra('TLS'),
            ],
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_handler' => [
            'name' => tra('Mail sender'),
            'description' => tra('Specify if Tiki should use the system Sendmail binary, the PHP mail() settings from php.ini, SMTP, or File (Debug) (to debug email sending by means of storing emails as files on disk at ./temp/Mail_yyyymmddhhmmss_randomstring.tmp ) to send mail notifications.'),
            'type' => 'list',
            'options' => $emailOptions,
            'default' => 'sendmail',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_smtp_auth' => [
            'name' => tra('Authentication'),
            'description' => tra('Mail server authentication'),
            'type' => 'list',
            'options' => [
                '' => tra('None'),
                'login' => tra('LOGIN'),
                'plain' => tra('PLAIN'),
                'crammd5' => tra('CRAM-MD5'),
            ],
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_smtp_helo' => [
            'name' => tra('Local server name'),
            'description' => tra('Name of the local server. Will be reported to SMTP relay on the HELO/EHLO line.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => 'localhost',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_queue'         => [
            'name' => tra('Mail delivery'),
            'description' => tr(
                'When set to Queue, messages will be stored in the database. Requires using the shell script %0 to be run for actual delivery.',
                '<code>php console.php mail-queue:send</code>'
            ),
            'type' => 'list',
            'options' => [
                '' => tra('Send immediately'),
                'y' => tra('Queue')
            ],
            'default' => '',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_redirect' => [
            'name' => tra('Catch-all email address'),
            'description' => tra('Tiki will send all emails to this email address instead of the target recipients. This will actually rewrite the recipient TO, CC and BCC email headers.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => '',
            'keywords' => 'catchall',
            'help' => $generalPreferencesHelp,
        ],
        'mailer_queue_max_retries' => [
            'name'        => tra('Number of retries to send emails when there are errors'),
            'description' => tra('Email queue process will not pick the email to retry after this number of attempts'),
            'type'        => 'text',
            'default'     => '10',
            'units'       => tra('attempts'),
            'help' => $generalPreferencesHelp,
        ],
    ];
}
