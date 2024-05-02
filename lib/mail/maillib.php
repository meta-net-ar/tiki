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
 * http://php.benscom.com/manual/en/function.mail.php
 * Rewritten for Tikiwiki by <luci at sh dot ground dot cz>
 *
 * For details on Message Header Extensions see
 * http://www.faqs.org/rfcs/rfc2047.html
 */

use Laminas\Mail\Transport\TransportInterface;
use SlmMail\Service\ElasticEmailService;
use SlmMail\Service\MailgunService;
use SlmMail\Service\MailServiceInterface;
use SlmMail\Service\MandrillService;
use SlmMail\Service\PostageService;
use SlmMail\Service\PostmarkService;
use SlmMail\Service\SendGridService;
use SlmMail\Service\SesService;
use SlmMail\Service\SparkPostService;

$charset = 'utf-8'; // What charset we do use in Tiki
$in_str = '';

/**
 * @param $in_str
 * @param $charset
 * @return string
 */
function encode_headers($in_str, $charset)
{
    $out_str = $in_str;
    if ($out_str && $charset) {
        // define start delimimter, end delimiter and spacer
        $end = "?=";
        $start = "=?" . $charset . "?b?";
        $spacer = $end . "\r\n" . $start;

        // determine length of encoded text within chunks
        // and ensure length is even
        $length = 71 - strlen($spacer); // no idea why 71 but 75 didn't work
        $length = floor($length / 2) * 2;

        // encode the string and split it into chunks
        // with spacers after each chunk
        $out_str = base64_encode($out_str);
        $out_str = chunk_split($out_str, $length, $spacer);

        // remove trailing spacer and
        // add start and end delimiters
        $spacer = preg_quote($spacer);
        $out_str = preg_replace("/" . $spacer . "$/", "", $out_str);
        $out_str = $start . $out_str . $end;
    }
    return $out_str;
}

function tiki_mail_setup()
{
    static $done = false;
    if ($done) {
        return;
    }

    global $tiki_maillib__zend_mail_default_transport;
    global $prefs;

    if ($prefs['zend_mail_handler'] === 'amazonSes') {
        $credentials = new \Aws\Credentials\Credentials(
            $prefs['zend_mail_amazon_ses_key'],
            $prefs['zend_mail_amazon_ses_secret']
        );
        $sesClient = new \Aws\Ses\SesClient([
            'credentials' => $credentials,
            'region' => $prefs['zend_mail_amazon_ses_region'],
            'version' => $prefs['zend_mail_amazon_ses_version']
        ]);
        $transport = new SesService($sesClient);
    } elseif ($prefs['zend_mail_handler'] === 'elasticEmail') {
        $transport = new ElasticEmailService(
            $prefs['zend_mail_elastic_email_username'],
            $prefs['zend_mail_elastic_email_key']
        );
    } elseif ($prefs['zend_mail_handler'] === 'mailgun') {
        $transport = new MailgunService(
            $prefs['zend_mail_mailgun_domain'],
            $prefs['zend_mail_mailgun_key'],
            $prefs['zend_mail_mailgun_api_endpoint']
        );
    } elseif ($prefs['zend_mail_handler'] === 'mandrill') {
        $transport = new MandrillService($prefs['zend_mail_mandrill_key']);
    } elseif ($prefs['zend_mail_handler'] === 'postage') {
        $transport = new PostageService($prefs['zend_mail_postage_key']);
    } elseif ($prefs['zend_mail_handler'] === 'postmark') {
        $transport = new PostmarkService($prefs['zend_mail_postmark_key']);
    } elseif ($prefs['zend_mail_handler'] === 'sendGrid') {
        $transport = new SendGridService(
            $prefs['zend_mail_send_grid_username'],
            $prefs['zend_mail_send_grid_key']
        );
    } elseif ($prefs['zend_mail_handler'] === 'sparkPost') {
        $transport = new SparkPostService($prefs['zend_mail_spark_post_key']);
    } elseif ($prefs['zend_mail_handler'] === 'smtp') {
        $options = [
            'host' => $prefs['zend_mail_smtp_server']
        ];

        if ($prefs['zend_mail_smtp_auth']) {
            $options['connection_class'] = $prefs['zend_mail_smtp_auth'];
            $options['connection_config'] = [
                'username' => $prefs['zend_mail_smtp_user'],
                'password' => $prefs['zend_mail_smtp_pass']
            ];
        }

        if ($prefs['zend_mail_smtp_port']) {
            $options['port'] = $prefs['zend_mail_smtp_port'];
        }

        if ($prefs['zend_mail_smtp_security']) {
            $options['connection_config']['ssl'] = $prefs['zend_mail_smtp_security'];
        }

        if ($prefs['zend_mail_smtp_helo']) {
            $options['name'] = $prefs['zend_mail_smtp_helo'];
        }

        if ($prefs['openpgp_gpg_pgpmimemail'] == 'y') {
            $transport = new OpenPGP_Zend_Mail_Transport_Smtp();
        } else {
            $transport = new Laminas\Mail\Transport\Smtp();
        }
        $transportOptions = new Laminas\Mail\Transport\SmtpOptions($options);
        $transport->setOptions($transportOptions);
    } elseif ($prefs['zend_mail_handler'] === 'file') {
        $mail_debug_path = TIKI_PATH . '/temp/mail_debug';
        if (! file_exists($mail_debug_path)) {
            // is the parent temp dir group writable?
            $group_write = fileperms(TIKI_PATH . '/temp') & 0x0010;
            mkdir($mail_debug_path);
            chmod($mail_debug_path, $group_write ? 0771 : 0751); // no public read perm
        }
        $transport = new Laminas\Mail\Transport\File();
        $transportOptions = new Laminas\Mail\Transport\FileOptions(
            [
                'path' => $mail_debug_path,
                'callback' => function ($transport) {
                    return 'Mail_' . date('YmdHis') . '_' . mt_rand() . '.eml';
                },
            ]
        );
        $transport->setOptions($transportOptions);
    } elseif ($prefs['zend_mail_handler'] === 'sendmail' && ! empty($prefs['sender_email'])) {
        // from http://framework.zend.com/manual/1.12/en/zend.mail.introduction.html#zend.mail.introduction.sendmail
        $transport = new Laminas\Mail\Transport\Sendmail('-f' . $prefs['sender_email']);
    } else {
        $transport = new Laminas\Mail\Transport\Sendmail();
    }

    $tiki_maillib__zend_mail_default_transport = $transport;

    $done = true;
}

/**
 * @return Laminas\Mail\Message
 */
function tiki_get_basic_mail()
{
    tiki_mail_setup();
    $mail = new Laminas\Mail\Message();
    $mail->setEncoding('UTF-8');
    $mail->getHeaders()->addHeaderLine('X-Tiki', 'yes');
    return $mail;
}

/**
 * @param string|null $fromName Optional name to be used when sending emails
 * @return Laminas\Mail\Message
 */
function tiki_get_admin_mail($fromName = null)
{
    global $prefs;

    $mail = tiki_get_basic_mail();

    if (! empty($prefs['sender_email'])) {
        // [BUG FIX] hollmeer 2012-11-04:
        // Added returnpath for Sendmail; does not send without;
        // catch/ignore error, if already set
        try {
            $mail->setFrom($prefs['sender_email'], $fromName ? $fromName : $prefs['sender_name']);
            $mail->setSender($prefs['sender_email']);
        } catch (Exception $e) {
            // was already set, then do nothing
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

    $mail->addTo($email, $recipientName);

    $mail->setSubject($subject);
    $mail->setBody($textBody);

    tiki_send_email($mail);
}

function tiki_send_email($email)
{
    global $prefs;

    if (! empty($prefs['zend_mail_redirect'])) {
        $email->setTo($prefs['zend_mail_redirect']);
        $email->setCc([]);
        $email->setBcc([]);
    }

    /* @var $tiki_maillib__zend_mail_default_transport TransportInterface|MailServiceInterface */
    global $tiki_maillib__zend_mail_default_transport;

    $tiki_maillib__zend_mail_default_transport->send($email);
}
