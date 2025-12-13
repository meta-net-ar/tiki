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

    return [
        'mailer_amazon_ses_key' => [
            'name' => tra('Amazon SES Key'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_amazon_ses_secret' => [
            'name' => tra('Amazon SES Secret'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_amazon_ses_region' => [
            'name' => tra('Amazon SES Region'),
            'type' => 'text',
            'perspective' => false,
            'default' => 'eu-west-1',
        ],
        'mailer_elastic_email_key' => [
            'name' => tra('Elastic Email Key'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_mailgun_domain' => [
            'name' => tra('Mailgun Domain'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_mailgun_key' => [
            'name' => tra('Mailgun Key'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_mailgun_region' => [
            'name' => tra('Mailgun Region'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_mandrill_key' => [
            'name' => tra('Mandrill Key'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_postmark_key' => [
            'name' => tra('Postmark Key'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_send_grid_region' => [
            'name' => tra('SendGrid Region'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_send_grid_key' => [
            'name' => tra('SendGrid Key'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_spark_post_key' => [
            'name' => tra('SparkPost Key'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_spark_post_region' => [
            'name' => tra('SparkPost Region'),
            'type' => 'text',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_smtp_server' => [
            'name' => tra('SMTP server'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => '',
        ],
        'mailer_smtp_user' => [
            'name' => tra('Username'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'autocomplete' => 'off',
            'default' => '',
        ],
        'mailer_smtp_pass' => [
            'name' => tra('Password'),
            'type' => 'password',
            'size' => '20',
            'perspective' => false,
            'autocomplete' => 'off',
            'default' => '',
        ],
        'mailer_smtp_port' => [
            'name' => tra('Port'),
            'type' => 'text',
            'size' => '5',
            'perspective' => false,
            'default' => 25,
        ],
        'mailer_smtp_security' => [
            'name' => tra('Security'),
            'type' => 'list',
            'perspective' => false,
            'options' => [
                '' => tra('None'),
                'ssl' => tra('SSL'),
                'tls' => tra('TLS'),
            ],
            'default' => '',
        ],
        'mailer_handler' => [
            'name' => tra('Mail sender'),
            'description' => tra('Specify if Tiki should use the system Sendmail binary, the PHP mail() settings from php.ini, SMTP, or File (Debug) (to debug email sending by means of storing emails as files on disk at ./temp/Mail_yyyymmddhhmmss_randomstring.tmp ) to send mail notifications.'),
            'type' => 'list',
            'options' => $emailOptions,
            'default' => 'sendmail',
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
        ],
        'mailer_smtp_helo' => [
            'name' => tra('Local server name'),
            'description' => tra('Name of the local server. Will be reported to SMTP relay on the HELO/EHLO line.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => 'localhost',
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
        ],
        'mailer_redirect' => [
            'name' => tra('Catch-all email address'),
            'description' => tra('Tiki will send all emails to this email address instead of the target recipients. This will actually rewrite the recipient TO, CC and BCC email headers.'),
            'type' => 'text',
            'size' => '20',
            'perspective' => false,
            'default' => '',
            'keywords' => 'catchall',
        ],
        'mailer_queue_max_retries' => [
            'name'        => tra('Number of retries to send emails when there are errors'),
            'description' => tra('Email queue process will not pick the email to retry after this number of attempts'),
            'type'        => 'text',
            'default'     => '10',
            'units'       => tra('attempts'),
        ],
    ];
}
