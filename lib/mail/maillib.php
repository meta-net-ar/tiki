<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/* Common shared mail functions */
/*
 * function encode_headers()
 *
 * Encode non-ASCII email headers for mail() function to display
 * them properly in email clients.
 * Original code by <gordon at kanazawa-gu dot ac dot jp>.
 * See 'User Contributed Notes' at
 * https://www.php.net/manual/en/function.mail.php
 * Rewritten for Tikiwiki by <luci at sh dot ground dot cz>
 *
 * For details on Message Header Extensions see
 * http://www.faqs.org/rfcs/rfc2047.html
 */

use bertoost\Mailer\ElasticEmail\Transport\ElasticEmailTransportFactory;
use Gam6itko\Symfony\Mailer\SparkPost\Transport\SparkPostTransportFactory;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Bridge\Amazon\Transport\SesTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailchimp\Transport\MandrillTransportFactory;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunTransportFactory;
use Symfony\Component\Mailer\Bridge\Postmark\Transport\PostmarkTransportFactory;
use Symfony\Component\Mailer\Bridge\Sendgrid\Transport\SendgridTransportFactory;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\SmtpTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Tiki\Lib\mail\FileTransport;
use Tiki\Lib\openpgp\OpenPGPTransport;

$charset = 'utf-8'; // What charset we do use in Tiki
$in_str = '';
function tiki_mail_setup()
{
    static $done = false;
    if ($done) {
        return;
    }

    global $tiki_maillib__mailer_default_transport;
    global $prefs;
    $dns = "sendmail://default";
    if (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'amazonSes') {
        $key = $prefs['mailer_amazon_ses_key'];
        $secret = $prefs['mailer_amazon_ses_secret'];
        $region = $prefs['mailer_amazon_ses_region'];
        $dns = "ses+api://$key:$secret@default?region=$region";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'elasticEmail') { // https://github.com/bertoost/ElasticEmail-Mailer
        $apiKey = $prefs['mailer_elastic_email_key'];
        $dns = "elasticemail+api://$apiKey@default";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'mailgun') {
//        Do not use Api key from mailgun. Instead, go to
//        Sending -> Domain -> Sending key and create key there.
//        That is the "API key" for establishing connection.
        $key = $prefs['mailer_mailgun_key'];
        $domain = $prefs['mailer_mailgun_domain'];
        $region = $prefs['mailer_mailgun_region'];
        $dns = "mailgun+api://$key:$domain@default?region=$region";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'mandrill') {
        $key = $prefs['mailer_mandrill_key'];
        $dns = "mandrill+api://$key@default";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'postmark') {
        $key = $prefs['mailer_postmark_key'];
        $dns = "postmark+api://$key@default";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'sendGrid') {
        $key = $prefs['mailer_send_grid_key'];
        $region = $prefs['mailer_send_grid_region'];
        $dns = "sendgrid+api://$key@default?region=$region";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'sparkPost') { // https://github.com/gam6itko/sparkpost-mailer
        $key = $prefs['mailer_spark_post_key'];
        $region = $prefs['mailer_spark_region'];
        $dns = "sparkpost+api://$key@default?region=$region";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'smtp') {
        $host = $prefs['mailer_smtp_server'] ?? 'localhost';
        $port = $prefs['mailer_smtp_port'] ?? 25;
        $username = $prefs['mailer_smtp_user'];
        $password = $prefs['mailer_smtp_pass'];
        $security = $prefs['mailer_smtp_security'];
        $auth = $prefs['mailer_smtp_auth'];
        $helo = $prefs['mailer_smtp_helo'];
        $dns = "smtp://";
        if (! empty($username) && ! empty($password)) {
            $dns .= urlencode($username) . ":" . urlencode($password) . "@";
        }
        $dns .= "$host";
        if (! empty($port)) {
            $dns .= ":$port";
        }

        $queryParameters = [];
        if (! empty($security)) {
            $queryParameters[] = "encryption=$security";
        }
        if (! empty($auth)) {
            $queryParameters[] = "auth_mode=$auth";
        }
        if (! empty($helo)) {
            $queryParameters[] = "helo=$helo";
        }

        if (! empty($queryParameters)) {
            $dns .= "?" . implode("&", $queryParameters);
        }
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'sendmail') {
        $dns = "sendmail://default";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'phpini') {
        $dns = "native://default";
    } elseif (! empty($prefs['mailer_handler']) && $prefs['mailer_handler'] === 'file') {
        $mail_debug_path = TIKI_PATH . '/' . TEMP_MAIL_DEBUG ;
        if (! file_exists($mail_debug_path)) {
            // is the parent temp dir group writable?
            $temp_dir = TIKI_PATH . '/' . TEMP_PATH;
            if (! file_exists($temp_dir)) {
                mkdir($temp_dir, 0775, true);
            }
            $group_write = (bool)((fileperms($temp_dir) & 0x0010));
            mkdir($mail_debug_path);
            chmod($mail_debug_path, $group_write ? 0771 : 0751); // no public read perm
        }
        $symfonyFileTransportInstance = new FileTransport($mail_debug_path,);
        $mailer = new Mailer($symfonyFileTransportInstance);
        $dns = $mailer;
    }

    if ($dns instanceof MailerInterface) {
        $tiki_maillib__mailer_default_transport = $dns;
    } else {
        try {
            $logger = new \Tiki_Log('Tiki mail setup', \Psr\Log\LogLevel::ERROR);
            $dispatcher = null;
            $httpClient = HttpClient::create();
            $allTransportFactories = iterator_to_array(
                Transport::getDefaultFactories($dispatcher, $httpClient, $logger)
            );

            $allTransportFactories[] = new SesTransportFactory($dispatcher, $httpClient, $logger);
            $allTransportFactories[] = new MailgunTransportFactory($dispatcher, $httpClient, $logger);
            $allTransportFactories[] = new PostmarkTransportFactory($dispatcher, $httpClient, $logger);
            $allTransportFactories[] = new SendgridTransportFactory($dispatcher, $httpClient, $logger);
            $allTransportFactories[] = new ElasticEmailTransportFactory($dispatcher, $httpClient, $logger);
            $allTransportFactories[] = new MandrillTransportFactory($dispatcher, $httpClient, $logger);
            $allTransportFactories[] = new SparkPostTransportFactory($dispatcher, $httpClient, $logger);
            // https://github.com/symfony/symfony/discussions/44763#discussioncomment-1944040
            // Instantiate the main Transport resolver with all known factories
            $transportResolver = new Transport($allTransportFactories);
            $resolvedTransport = $transportResolver->fromString($dns);
            if ($resolvedTransport instanceof SmtpTransport && $prefs['openpgp_gpg_pgpmimemail'] == 'y') {
                $resolvedTransport = new OpenPGPTransport($resolvedTransport, $dispatcher, $logger);
            }
            $tiki_maillib__mailer_default_transport = new Mailer($resolvedTransport);
        } catch (\Throwable  $e) {
            error_log("Error creating mail transport: " . $e->getMessage()); // @phpstan-ignore disallowedFunctions.errorLog (mailer exception reporting)
            // Fallback to a basic sendmail transport or null transport if configuration fails
            $tiki_maillib__mailer_default_transport = new Mailer(Transport::fromDsn('null://null'));
        }
    }

    $done = true;
}

/**
 * @return Email
 */
function tiki_get_basic_mail()
{
    tiki_mail_setup();
    $mail = new Email();
    $mail->getHeaders()->addTextHeader('X-Tiki', 'yes');
    return $mail;
}

/**
 * @param string|null $fromName Optional name to be used when sending emails
 *
 * @return Email
 */
function tiki_get_admin_mail($fromName = null)
{
    global $prefs;

    $mail = tiki_get_basic_mail();

    if (! empty($prefs['sender_email'])) {
        try {
            $mail->from(new Address($prefs['sender_email'], $fromName ?: $prefs['sender_name']));
        } catch (Throwable $e) {
            error_log("Mailer General Error: " . $e->getMessage()); // @phpstan-ignore disallowedFunctions.errorLog (mailer exception reporting)
        }
    }

    return $mail;
}

/**
 * @param $email
 * @param $recipientName
 * @param $subject
 * @param $textBody
 */
function tiki_send_admin_mail($email, $recipientName, $subject, $textBody)
{
    $mail = tiki_get_admin_mail();
    $mail->to(new Address($email, $recipientName));

    $mail->subject($subject);
    $mail->text($textBody);

    tiki_send_email($mail);
}

function tiki_send_email($email)
{
    global $prefs;

    if (! empty($prefs['mailer_redirect'])) {
        $email->to($prefs['mailer_redirect']);
        $email->getHeaders()->remove('Cc');
        $email->getHeaders()->remove('Bcc');
    }

    $envelope = null;
    if (! empty($prefs['sender_email'])) {
        try {
            $envelope = Envelope::create($email);
            $senderName = $prefs['sender_name'] ?? null;
            $envelope->setSender(new Address($prefs['sender_email'], $senderName));
        } catch (Throwable $e) {
            error_log("Mailer Envelope Error: " . $e->getMessage()); // @phpstan-ignore disallowedFunctions.errorLog (mailer exception reporting)
            $envelope = null;
        }
    }

    /* @var $tiki_maillib__mailer_default_transport  */
    global $tiki_maillib__mailer_default_transport;

    try {
        $tiki_maillib__mailer_default_transport->send($email, $envelope);
    } catch (TransportExceptionInterface $e) {
        error_log("Mailer Transport Error: " . $e->getMessage()); // @phpstan-ignore disallowedFunctions.errorLog (mailer exception reporting)
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'An email must have a "From" or a "Sender" header')) {
            Feedback::error(tr('Unable to send email: sender email not configured.'));
        } else {
            error_log("Mailer General Error: " . $e->getMessage()); // @phpstan-ignore disallowedFunctions.errorLog (mailer exception reporting)
        }
    }
}
