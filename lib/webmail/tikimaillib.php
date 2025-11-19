<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * set some default params (mainly utf8 as tiki is utf8) + use the mailCharset pref from a user
 */

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use Symfony\Component\Mime\Header\ParameterizedHeader;
use Symfony\Component\Mime\Part\DataPart;

class TikiMail
{
    /**
     * @var Email
     */
    private $mail;
    private $charset;
    public $errors;

    /**
     * @param string|null $user to username
     * @param string $from from email
     * @param string $fromName from Name
     */
    public function __construct($user = null, $from = '', $fromName = '')
    {
        global $user_preferences, $prefs;

        require_once __DIR__ . '/../mail/maillib.php';

        $tikilib = TikiLib::lib('tiki');
        $userlib = TikiLib::lib('user');

        $to = '';
        $this->errors = [];
        if (! empty($user)) {
            if ($userlib->user_exists($user)) {
                $to = $userlib->get_user_email($user);
                $tikilib->get_user_preferences($user, ['mailCharset']);
                if (isset($user_preferences[$user]['mailCharset'])) {
                    $this->charset = $user_preferences[$user]['mailCharset'];
                }
            } else {
                $str = tra('Mail to: User not found');
                trigger_error($str);
                $this->errors = [$str];
                return;
            }
        }

        if (! empty($from)) {
            $this->mail = tiki_get_basic_mail();
            try {
                $this->mail->from(new Address($from, $fromName));
            } catch (Exception) {
                // was already set, then do nothing
            }
        } else {
            $this->mail = tiki_get_admin_mail($fromName);
        }
        if (! empty($to)) {
            $this->mail->to($to);
        }

        if (empty($this->charset)) {
            $this->charset = $prefs['users_prefs_mailCharset'];
        }
    }

    public function setUser($user)
    {
    }

    public function setSender($email, $name = '')
    {
        $this->mail->sender(new Address($email, $name));
    }

    public function setFrom($email, $name = '')
    {
        $this->mail->from(new Address($email, $name));
    }

    public function setReplyTo($email, $name = '')
    {
        $this->mail->replyTo(new Address($email, $name));
    }

    public function setSubject($subject)
    {
        $this->mail->subject($subject);
    }

    public function setHtml($html, $text = null, $images_dir = null)
    {
        global $prefs;
        if ($prefs['mail_apply_css'] != 'n') {
            $html = $this->applyStyle($html);
        }
        $this->mail->html($html, $this->charset);

        if ($text) {
            $this->mail->text($text, $this->charset);
        }
    }

    public function setText($text = '')
    {
        $this->mail->text($text, $this->charset);
    }

    public function setCc($address)
    {
        foreach ((array) $address as $cc) {
            $this->mail->addCc($cc);
        }
    }

    public function setBcc($address)
    {
        foreach ((array) $address as $bcc) {
            $this->mail->addBcc($bcc);
        }
    }

    public function setHeader($name, $value)
    {
        $headers = $this->mail->getHeaders();
        switch ($name) {
            case 'Message-Id':
                $headers->addIdHeader('Message-ID', trim($value));
                break;
            case 'In-Reply-To':
                $headers->addIdHeader('In-Reply-To', trim($value));
                break;
            case 'References':
                $headers->addIdHeader('References: ', trim($value));
                break;
            default:
                $headers->addTextHeader($name, $value);
                break;
        }
    }

    public function addPart($content, $type)
    {
        // handle type lib/core/Tiki/SabreDav/Utilities.php: 'text/calendar; method=PUBLISH; name=event.ics'
        $parts = explode(';', $type);
        $contentType = trim(array_shift($parts));

        $params = [];
        foreach ($parts as $param) {
            $param = trim($param);
            if (str_contains($param, '=')) {
                list($key, $value) = explode('=', $param, 2);
                $params[strtolower(trim($key))] = trim($value, '" ');
            }
        }

        $filename = $params['name'] ?? null;
        if (empty($filename)) {
            $ext = explode('/', $contentType);
            $ext = end($ext);
            if (str_contains($ext, '+')) { // Handle types like application/xml+svg
                $extParts = explode('+', $ext);
                $ext = end($extParts);
            }
            $filename = 'part_' . uniqid() . '.' . $ext;
        }

        $dataPart = new DataPart($content, $filename, $contentType);

        $headers = $dataPart->getPreparedHeaders();
        $headers->remove('Content-Type');
        $contentTypeHeader = new ParameterizedHeader('Content-Type', $contentType, ['method' => $params[' method']]);
        $headers->add($contentTypeHeader);
        $this->mail->addPart($dataPart);
    }

    /**
     * Get the Symfony Message object
     *
     * @return Email
     */
    public function getMessage()
    {
        return $this->mail;
    }

    public function send($recipients, $type = 'mail')
    {
        global $tikilib, $prefs;
        $logslib = TikiLib::lib('logs');
        $logEmailRes = [];
        $logEmailRes['subject'] = trim($this->mail->getSubject());
        foreach ($this->mail->getFrom() as $address) {
            $logEmailRes['from_email'] = $address->getAddress();
            $logEmailRes['from_name'] = $address->getName();
            break;
        }

        $this->mail->getHeaders()->remove('to'); // Ensure 'To' header is managed by addCc/addBcc for sending
        $addedRecipients = [];
        foreach ((array) $recipients as $to) {
            try {
                $this->mail->addTo($to);
                $addedRecipients[] = trim($to);
            } catch (RfcComplianceException $e) {
                $error = $e->getMessage();
                $this->errors[] = $error;
                $error = ' [' . $error . ']';
                $logEmailRes['to'] = $to;
                $logEmailRes['error'] = $error;
                $logslib->add_log('Email', $to . '/' . $this->mail->getSubject() . $error, '', '', '', '', $logEmailRes);
                unset($logEmailRes['error']);
            }
        }

        if (empty($addedRecipients)) {
            return false;
        }

        if ($prefs['email_footer']) {
            $footer = PHP_EOL . PHP_EOL . $prefs['email_footer'];
            $htmlFooter = '<br><br>' . $prefs['email_footer'];

            $currentTextBody = $this->mail->getTextBody();
            $currentHtmlBody = $this->mail->getHtmlBody();

            if ($currentTextBody) {
                $this->mail->text($currentTextBody . $footer, $this->charset);
            }

            if ($currentHtmlBody) {
                if (str_contains($currentHtmlBody, '</body>')) {
                    $this->mail->html(str_replace('</body>', $htmlFooter . '</body>', $currentHtmlBody), $this->charset);
                } else {
                    $this->mail->html($currentHtmlBody . $htmlFooter, $this->charset);
                }
            }
        }

        if ($prefs['mailer_queue'] == 'y') {
            $query = "INSERT INTO `tiki_mail_queue` (message) VALUES (?)";
            $bindvars = [serialize($this->mail)];
            $tikilib->query($query, $bindvars, -1, 0);
            $title = 'mail';
        } else {
            try {
                // This is the key line to investigate if there's a problem
                tiki_send_email($this->mail);
                $title = 'mail';
                $error = '';
            } catch (TransportExceptionInterface | \Throwable $e) {
                $title = 'mail error';
                $error = $e->getMessage();
                $this->errors[] = $error;
                $error = ' [' . $error . ']';
            }

            if ($title == 'mail error' || $prefs['log_mail'] == 'y') {
                foreach ($addedRecipients as $u) {
                    $emailStatus = empty($error) ? 'success' : 'error';
                    $logEmailRes['to'] = $u;
                    $logEmailRes[$emailStatus] = empty($error) ? 'Email has been sent' : $error;
                    $logslib->add_log('Email', $u . '/' . $this->mail->getSubject() . $error, '', '', '', '', $logEmailRes);
                }
            }
        }
        return $title == 'mail';
    }

    public function addAttachment($data, $filename, $mimetype)
    {
        $this->mail->attach($data, $filename, $mimetype);
    }

    private function collectCss()
    {
        static $css;
        if ($css) {
            return $css;
        }

        $cachelib = TikiLib::lib('cache');
        if ($css = $cachelib->getCached('email_css')) {
            return $css;
        }

        $headerlib = TikiLib::lib('header');
        $files = $headerlib->get_css_files();
        $contents = array_map(function ($file) {
            if ($file[0] == '/') {
                return file_get_contents($file);
            } elseif (str_starts_with($file, 'http')) {
                return TikiLib::lib('tiki')->httprequest($file);
            } else {
                if (str_starts_with($file, 'themes/')) {   // only use the tiki base and current theme files
                    return file_get_contents(TIKI_PATH . '/' . $file);
                }
            }
        }, $files);

        $css = implode("\n\n", array_filter($contents));
        $cachelib->cacheItem('email_css', $css);
        return $css;
    }

    private function applyStyle($html)
    {
        // convert <ins> and <del> to spans for better rendering (e.g. don't force colors in Outlook)
        // these spans can be further styled in themes
        $html = str_replace(['<ins', '<del'], '<span', $html);
        $html = str_replace(['</ins>', '</del>'], '</span>', $html);
        $html = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' . $html;
        $css = $this->collectCss();
        $processor = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles();
        $html = $processor->convert($html, $css);
        return $html;
    }
}
