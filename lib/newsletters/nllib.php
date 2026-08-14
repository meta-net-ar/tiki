<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

include_once('lib/webmail/tikimaillib.php');

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Email;

class NlLib extends TikiLib
{
    private const TABLE_NEWSLETTER_SUBSCRIPTIONS = 'tiki_newsletter_subscriptions';
    private const TABLE_NEWSLETTERS = 'tiki_newsletters';
    private const TABLE_SENT_NEWSLETTERS_FILES = 'tiki_sent_newsletters_files';
    private const TABLE_SENT_NEWSLETTERS = 'tiki_sent_newsletters';

    private function insertNewsletter(
        string $name,
        string $description,
        string $allowUserSub = 'y',
        string $allowAnySub = 'n',
        string $unsubMsg = '',
        string $validateAddr = 'n',
        string $allowTxt = 'n',
        string $frequency = '',
        string $author = '',
        string $allowArticleClip = 'y',
        string $autoArticleClip = 'n',
        ?string $articleClipRange = null,
        string $articleClipTypes = '',
        string $emptyClipBlocksSend = 'n'
    ): int|false {
        $query = 'insert into `' . self::TABLE_NEWSLETTERS . '` (
                    `name`, `description`, `created`, `lastSent`, `editions`, `users`,
                    `allowUserSub`, `allowTxt`, `allowAnySub`, `unsubMsg`, `validateAddr`,
                    `frequency`, `author`, `allowArticleClip`, `autoArticleClip`,
                    `articleClipRange`, `articleClipTypes`, `emptyClipBlocksSend`
                ) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $result = $this->query($query, [
            $name,
            $description,
            $this->now,
            0,
            0,
            0,
            $allowUserSub,
            $allowTxt,
            $allowAnySub,
            $unsubMsg,
            $validateAddr,
            $frequency,
            $author,
            $allowArticleClip,
            $autoArticleClip,
            $articleClipRange,
            $articleClipTypes,
            $emptyClipBlocksSend,
        ]);

        if ($result) {
            return $this->lastInsertId();
        }

        return false;
    }

    private function updateNewsletter(
        int $nlId,
        string $name,
        string $description,
        string $allowUserSub,
        string $allowAnySub,
        string $unsubMsg,
        string $validateAddr,
        string $allowTxt,
        string $frequency,
        string $allowArticleClip,
        string $autoArticleClip,
        ?string $articleClipRange,
        string $articleClipTypes,
        string $emptyClipBlocksSend
    ): int|false {
        $query = 'update `' . self::TABLE_NEWSLETTERS . '` set
                `name`=?,
                `description`=?,
                `allowUserSub`=?,
                `allowTxt`=?,
                `allowAnySub`=?,
                `unsubMsg`=?,
                `validateAddr`=?,
                `frequency`=?,
                `allowArticleClip`=?,
                `autoArticleClip`=?,
                `articleClipRange`=?,
                `articleClipTypes`=?,
                `emptyClipBlocksSend`=?
                where `nlId`=?';
        $result = $this->query($query, [
            $name,
            $description,
            $allowUserSub,
            $allowTxt,
            $allowAnySub,
            $unsubMsg,
            $validateAddr,
            $frequency,
            $allowArticleClip,
            $autoArticleClip,
            $articleClipRange,
            $articleClipTypes,
            $emptyClipBlocksSend,
            (int) $nlId,
        ]);

        if (! $result && ! $result->numRows()) {
            return false;
        }

        return $nlId;
    }

    public function replace_newsletter(
        ?int $nlId,
        string $name,
        string $description,
        string $allowUserSub,
        string $allowAnySub,
        string $unsubMsg,
        string $validateAddr,
        string $allowTxt,
        string $frequency,
        string $author,
        string $allowArticleClip = 'y',
        string $autoArticleClip = 'n',
        ?string $articleClipRange = null,
        string $articleClipTypes = '',
        string $emptyClipBlocksSend = 'n'
    ): int|false {
        if ($nlId) {
            return $this->updateNewsletter($nlId, $name, $description, $allowUserSub, $allowAnySub, $unsubMsg, $validateAddr, $allowTxt, $frequency, $allowArticleClip, $autoArticleClip, $articleClipRange, $articleClipTypes, $emptyClipBlocksSend);
        } else {
            if (! $this->isNewsletterUnique($name, $author)) {
                return -1;
            } else {
                return $this->insertNewsletter($name, $description, $allowUserSub, $allowAnySub, $unsubMsg, $validateAddr, $allowTxt, $frequency, $author, $allowArticleClip, $autoArticleClip, $articleClipRange, $articleClipTypes, $emptyClipBlocksSend);
            }
        }
    }

    private function isNewsletterUnique(string $name, string $author): bool
    {
        $query = 'select `nlId` from `' . self::TABLE_NEWSLETTERS . '` where `name` = ? and `author` = ?';
        $item = $this->getOne($query, [$name, $author]);

        return ! $item;
    }

    public function replace_edition($nlId, $subject, $data, $users, $editionId = 0, $draft = false, $datatxt = '', $files = [], $wysiwyg = null, $is_html = null)
    {
        if ($draft == false) {
            if ($editionId > 0 && $this->getOne("select `sent` from `" . self::TABLE_SENT_NEWSLETTERS . "` where `editionId`=?", [ (int) $editionId ]) == -1) {
                // save and send a draft
                $query = "update `" . self::TABLE_SENT_NEWSLETTERS . "` set `subject`=?, `data`=?, `sent`=?, `users`=? , `datatxt`=?, `wysiwyg`=?, `is_html`=? ";
                $query .= "where editionId=? and nlId=?";
                $result = $this->query($query, [$subject, $data, $this->now, $users, $datatxt, $wysiwyg, $is_html, (int) $editionId, (int) $nlId]);
                $query = "update `" . self::TABLE_NEWSLETTERS . "` set `editions`= `editions`+ 1 where `nlId`=? ";
                $result = $this->query($query, [(int) $nlId]);
                $query = "delete from `" . self::TABLE_SENT_NEWSLETTERS_FILES . "` where `editionId`=?";
                $result = $this->query($query, [(int) $editionId]);
            } else {
                 // save and send an edition
                $query = "insert into `" . self::TABLE_SENT_NEWSLETTERS . "`(`nlId`,`subject`,`data`,`sent`,`users` ,`datatxt`, `wysiwyg`, `is_html`) values(?,?,?,?,?,?,?,?)";
                $result = $this->query($query, [(int) $nlId, $subject, $data, $this->now, $users, $datatxt, $wysiwyg, $is_html]);
                $query = "update `" . self::TABLE_NEWSLETTERS . "` set `editions`= `editions`+ 1 where `nlId`=?";
                $result = $this->query($query, [(int) $nlId]);
                $editionId = $this->lastInsertId();
            }
        } else {
            if ($editionId > 0 && $this->getOne('select `sent` from `' . self::TABLE_SENT_NEWSLETTERS . '` where `editionId`=?', [(int) $editionId ]) == -1) {
                // save an existing draft
                $query = "update `" . self::TABLE_SENT_NEWSLETTERS . "` set `subject`=?, `data`=?, `datatxt`=?, `wysiwyg`=?, `is_html`=? ";
                $query .= "where editionId=? and nlId=?";
                $result = $this->query($query, [$subject, $data, $datatxt, $wysiwyg, $is_html, (int) $editionId, (int) $nlId]);
                $query = "delete from `" . self::TABLE_SENT_NEWSLETTERS_FILES . "` where `editionId`=?";
                $result = $this->query($query, [(int) $editionId]);
            } else {
                // save a new draft
                $query = "insert into `" . self::TABLE_SENT_NEWSLETTERS . "`(`nlId`,`subject`,`data`,`sent`,`users`,`datatxt`, `wysiwyg`, `is_html`) values(?,?,?,?,?,?,?,?)";
                $result = $this->query($query, [(int) $nlId, $subject, $data, -1, 0, $datatxt, $wysiwyg, $is_html]);
                $editionId = $this->lastInsertId();
            }
        }
        foreach ($files as $file) {
            $query = "insert into `" . self::TABLE_SENT_NEWSLETTERS_FILES . "` (`editionId`,`name`,`type`,`size`,`filename`) values (?,?,?,?,?)";
            $result = $this->query($query, [(int) $editionId, $file['name'], $file['type'], (int) $file['size'], $file['filename']]);
        }
        return $editionId;
    }

    /* get only the email subscribers */
    public function get_subscribers($nlId, $isEmail = 'y')
    {
        $query = "select `email` from `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` where `valid`=? and `nlId`=? and isUser !=?";
        $result = $this->query($query, ['y', (int) $nlId, $isEmail]);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[] = $res["email"];
        }
        return $ret;
    }

    public function get_all_subscribers($nlId, $genUnsub)
    {
        global $prefs, $user;
        $userlib = TikiLib::lib('user');
        $return = [];
        $all_users = [];
        $group_users = [];
        $included_users = [];
        $page_included_emails = [];

        // Get list of the root groups (groups explicitly subscribed to this newsletter)
        //
        $groups = [];
        $query = "select `groupName`,`include_groups` from `tiki_newsletter_groups` where `nlId`=?";
        $result = $this->fetchAll($query, [(int) $nlId]);
        foreach ($result as $res) {
            $groups[] = $res['groupName'];

            if ($res['include_groups'] == 'y') {
                $groups = array_merge($groups, $userlib->get_including_groups($res["groupName"], 'y'));
            }
        }

        // If some groups are subscribed to this newsletter, get the list of users from those groups to be able to add them as subscribers
        // + Generate a random code (to allow users to unsubscribe) for users who don't already have one
        //
        if (count($groups) > 0) {
            $mid = " and (" . implode(" or ", array_fill(0, count($groups), "`groupName`=?")) . ")";
            $query = "select distinct uu.`login`, uu.`email` from `users_users` uu, `users_usergroups` ug where uu.`userId`=ug.`userId` " . $mid;
            $result = $this->query($query, $groups);
            while ($res = $result->fetchRow()) {
                if (empty($res['email'])) {
                    if ($prefs['login_is_email'] == 'y' && $user != 'admin') {
                        $res['email'] = $res['login'];
                    } else {
                        continue;
                    }
                }
                $res['email'] = strtolower($res['email']);
                $all_users[$res['email']] = [
                    'nlId' => (int) $nlId,
                    'email' => $res['email'],
                    'code' => $this->genRandomString($res['login']),
                    'valid' => 'y',
                    'subscribed' => $this->now,
                    'isUser' => 'g',
                    'db_email' => $res['login'],
                    'included' => 'n',
                ];
                $group_users[] = $res['login'];
            }
        }
        unset($groups);

        // Add subscribers that comes from included newsletters (only if their email is not already in the current list)
        //   Those users need to be saved in database for the current newsletter, in order to allow them to unsubscribe to this newsletter only
        //   (This implies to generate a new unsubscription code for the current newsletter)
        //
        $incnl = $this->list_newsletter_included($nlId);
        foreach ($incnl as $incid => $incname) {
            $incall = $this->get_all_subscribers($incid, $genUnsub);
            foreach ($incall as $res) {
                if (empty($all_users[$res['email']])) {
                    $res['code'] = $this->genRandomString($res['db_email']);
                    $res['included'] = 'y';
                    $all_users[$res['email']] = $res;
                    $included_users[] = $res['db_email'];
                }
            }
        }

        // Retrieve current subscribers of the list (into $all_users array)
        // Do not keep subscribers that are:
        //   - not valid (valid = n)
        //   - or that comes from a tiki group (isUser = g)
        //     except those who explicitely unsubscribed themselves (valid = x), in order to keep this information and not add this user again later
        //     except those who are still in a subscribed group ($group_users)
        //   - or an included newsletter (included = y)
        //     except those who explicitely unsubscribed themselves (valid = x), in order to keep this information and not add this user again later
        //     except those who are still in an included newsletter ($included_users)
        //
        //   Note: users from included newsletters or groups (see above) are replaced by current subscribers to keep their code of unsubscription
        //
        $query = "select * from `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` where `nlId`=?";
        $result = $this->query($query, [(int) $nlId]);
        while ($res = $result->fetchRow()) {
            // if the user registered an email address, put it in lowercase to have consistent
            // comparison with other sources of email addresses. Username are case sensitive.
            if (( $res['isUser'] == 'n' )) {
                   $res['email'] = strtolower($res['email']);
            };
            if (
                ( $res['included'] != 'y' || $res['valid'] == 'x' ) && ((
                    $res['valid'] != 'n' && ( $res['isUser'] != 'g' || $res['valid'] == 'x' ) )
                    || ( $res['isUser'] == 'g' && in_array($res['email'], $group_users) )
                )
                || ( $res['included'] == 'y' && in_array($res['email'], $included_users) )
            ) {
                $res['db_email'] = $res['email'];

                // Update e-mails of tiki users (directly included or included via a group)
                // When the e-mail already exists for another subscriber, keep the other subscriber
                //   (e.g. to keep information of users that subscribed themselves)
                //
                if ($res['isUser'] == 'y' || $res['isUser'] == 'g') {
                    $res['email'] = strtolower($userlib->get_user_email($res['db_email']));
                }

                // Add new subscribers to $all_users, or replace the information that was already there from group users
                //   In case of valid users from included newsletters, update everything except the unsubscribe code
                if ($res['included'] == 'y' && $res['valid'] == 'y') {
                    $all_users[$res['email']]['code'] = $res['code'];
                } else {
                    $all_users[$res['email']] = $res;
                }
            }
        }

        $page_emails = $this->list_newsletter_pages($nlId);
        if ($page_emails['count'] > 0) {
            foreach ($page_emails['data'] as $page) {
                $emails = $this->get_emails_from_page($page['wikiPageName']);
                if (! is_array($emails)) {
                    continue;
                }
                foreach ($emails as $email) {
                    if (! empty($email)) {
                        $res = [
                            'valid' => $page['validateAddrs'] == 'y' ? 'n' : 'y',
                            'subscribed' => $this->now,
                            'isUser' => 'n',
                            'db_email' => $email,
                            'email' => $email,
                            'included' => 'n',
                        ];

                        if ($page['addToList'] == 'y') {
                            $res['code'] = $this->genRandomString($email);
                            $all_users[$email] = $res;
                        }
                        $page_included_emails[$email] = $res;
                    }
                }
            }
        }

        // Update database if requested
        //
        if ($genUnsub) {
            $this->query('DELETE FROM `' . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . '` WHERE `nlId`=?', [(int) $nlId]);
            $query = "INSERT INTO `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` (`nlId`,`email`,`code`,`valid`,`subscribed`,`isUser`,`included`) VALUES (?,?,?,?,?,?,?)";
            foreach ($all_users as $res) {
                $this->query(
                    $query,
                    [
                        (int) $nlId,
                        $res['db_email'],
                        $res['code'],
                        $res['valid'],
                        $res['subscribed'],
                        $res['isUser'],
                        $res['included'],
                    ]
                );
            }
        }

        // Only send the newsletter to valid and confirmed emails (valid=y)
        foreach ($all_users as $r) {
            if ($r['valid'] == 'y') {
                $return[] = $r;
            }
        }

        $return = array_merge($return, $page_included_emails);

        return $return;
    }

    /**
     * Removes newsletters subscriptions
     *
     * @param integer $nlId
     * @param string  $email
     * @param boolean $isUser
     *
     * @return Tiki\TikiDb\PdoResult
     * @access public
     */
    public function remove_newsletter_subscription($nlId, $email, $isUser)
    {
        $query = "delete from `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` where `nlId`=? and `email`=? and `isUser`=?";
        return $this->query($query, [(int) $nlId, $email, $isUser], -1, -1, false);
    }

    /**
     * Removes newsletters subscriptions with only the code as parameter
     *
     * @param string $code
     *
     * @return Tiki\TikiDb\PdoResult
     * @access public
     */
    public function remove_newsletter_subscription_code($code)
    {
        $query = 'delete from `' . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . '` where `code`=?';
        return $this->query($query, [$code], -1, -1, false);
    }

    /**
     * @param $nlId
     * @param $group
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function remove_newsletter_group($nlId, $group)
    {
        $query = "delete from `tiki_newsletter_groups` where `nlId`=? and `groupName`=?";
        return $this->query($query, [(int) $nlId,$group], -1, -1, false);
    }

    /**
     * @param $nlId
     * @param $includedId
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function remove_newsletter_included($nlId, $includedId)
    {
        $query = "delete from `tiki_newsletter_included` where `nlId`=? and `includedId`=?";
        return $this->query($query, [(int) $nlId,$includedId], -1, -1, false);
    }


    private function sendNewsletterRelatedEmail(
        string $recipientEmail,
        string $subject,
        string $textTemplate,
        string $htmlTemplate,
        array $smartyAssigns,
        string $logSlug,
        ?string $lang = null
    ): bool {
        global $prefs;
        $smarty = TikiLib::lib('smarty');

        foreach ($smartyAssigns as $key => $value) {
            $smarty->assign($key, $value);
        }
        include_once 'lib/mail/maillib.php';
        $email = tiki_get_admin_mail();
        $email->subject($subject);
        $email->addTo($recipientEmail);

        // Fetch plain text content
        $textMailData = ($lang !== null) ? $smarty->fetchLang($lang, $textTemplate) : $smarty->fetch($textTemplate);

        // Fetch HTML content, with fallback to plain text if HTML template is missing/fails
        $htmlMailData = '';
        $noDuplicateTextPart = false;
        try {
            $htmlMailData = ($lang !== null) ? $smarty->fetchLang($lang, $htmlTemplate) : $smarty->fetch($htmlTemplate);
        } catch (Exception $e) {
            // HTML template missing; fall back to text content for HTML body
            $noDuplicateTextPart = false;
        }

        // Apply bug fix for body tags and nl2br (as in original Laminas logic)
        if ($htmlMailData !== '') {
            // Ensure body tags in HTML part
            if (! str_contains($htmlMailData, '</body>')) {
                $htmlMailData = "<body>" . nl2br($htmlMailData) . "</body>";
            }
        } else {
            // No HTML template, so just use text-template content for HTML body
            if (! str_contains($textMailData, '</body>')) {
                $htmlMailData = "<body>" . nl2br($textMailData) . "</body>";
            } else {
                $htmlMailData = $textMailData;
            }
        }

        // Set both HTML and plain text bodies on the Symfony Email object
        $email->html($htmlMailData);
        if (! $noDuplicateTextPart) {
            $email->text($textMailData);
        }

        // Queueing vs. Direct Send Logic
        if ($prefs['mailer_queue'] == 'y') {
            try {
                $query = "INSERT INTO `tiki_mail_queue` (message) VALUES (?)";
                TikiLib::lib('tiki')->query($query, [serialize($email)], -1, 0);
                $this->logEmailStatus($email, '', $subject, $logSlug . ' (Queued)');
                return true;
            } catch (\Throwable $e) { // Catch any serialization or DB query errors
                $this->logEmailStatus($email, $e->getMessage(), $subject, $logSlug . ' (Queue Failed)');
                return false;
            }
        } else {
            try {
                tiki_send_email($email);
                $this->logEmailStatus($email, '', $subject, $logSlug . ' (Sent)');
                return true;
            } catch (TransportExceptionInterface $e) { // Catch specific Symfony Mailer transport errors
                $this->logEmailStatus($email, $e->getMessage(), $subject, $logSlug . ' (Send Failed)');
                return false;
            } catch (\Throwable $e) { // Catch any other unexpected errors during sending
                $this->logEmailStatus($email, 'Unexpected error: ' . $e->getMessage(), $subject, $logSlug . ' (Send Failed)');
                return false;
            }
        }
    }

    /**
     * @param        $nlId
     * @param        $add
     * @param string $isUser
     * @param string $validateAddr
     * @param string $addEmail
     *
     * @return bool
     * @throws Exception
     */
    public function newsletter_subscribe($nlId, $add, $isUser = 'n', $validateAddr = '', $addEmail = '')
    {
        global $user;
        $userlib = TikiLib::lib('user');
        if (empty($add)) {
            return false;
        }
        if ($isUser == "y" && $addEmail == "y") {
            $add = $userlib->get_user_email($add);
            $isUser = "n";
        }
        $query = "select * from `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` where `nlId`=? and `email`=? and `isUser`=?";
        $result = $this->query($query, [(int) $nlId, $add, $isUser]);
        if ($res = $result->fetchRow()) {
            if ($res['valid'] == 'y') {
                return false; /* already subscribed and valid - keep the same valid status */
            }
        }
        $code = $this->genRandomString($add);
        $info = $this->get_newsletter($nlId);
        if ($info["validateAddr"] == 'y' && $validateAddr != 'n') {
            if ($isUser == "y") {
                $emailTo = $userlib->get_user_email($add);
            } else {
                $emailTo = $add;
            }
            /* if already has validated don't ask again */
            // Generate a code and store it and send an email  with the
            // URL to confirm the subscription put valid as 'n'

            if (empty($res)) {
                $query = "insert into `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "`(`nlId`,`email`,`code`,`valid`,`subscribed`,`isUser`,`included`) values(?,?,?,?,?,?,?)";
                $bindvars = [(int) $nlId,$add,$code,'n',$this->now,$isUser,'n'];
            } else {
                // if already sub'ed but not validated then update code and timestamp (a.k.a. `subscribed`) and resend mail
                $query = "UPDATE `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` SET `code`=?,`subscribed`=? WHERE `nlId`=? AND `email`=? AND `isUser`=? AND `valid`='n' AND `included`='n'";
                $bindvars = [$code,$this->now,(int) $nlId,$add,$isUser];
            }
            $result = $this->query($query, $bindvars);
            // Now email the address with the confirmation instructions;
            if (! isset($_SERVER["SERVER_NAME"])) {
                $_SERVER["SERVER_NAME"] = $_SERVER["HTTP_HOST"];
            }
            $subject = tra('Newsletter subscription information at') . ' ' . $_SERVER["SERVER_NAME"];
            $smartyAssigns = [
                'info' => $info,
                'mail_date' => $this->now,
                'mail_user' => $user,
                'code' => $code,
                'server_name' => $_SERVER["SERVER_NAME"],
            ];

            return $this->sendNewsletterRelatedEmail(
                $emailTo,
                $subject,
                'mail/confirm_newsletter_subscription.tpl',
                'mail/confirm_newsletter_subscription_html.tpl',
                $smartyAssigns,
                'Subscribe Newsletter'
            );
        } else {
            if (! empty($res) && $res["valid"] == 'n') {
                $query = "update `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` set `valid` = 'y' where `nlId` = ? and `email` = ? and `isUser` = ?";
                $result = $this->query($query, [(int) $nlId, $add, $isUser]);
                return $result && $result->numRows();
            }
            $query = "insert into `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "`(`nlId`,`email`,`code`,`valid`,`subscribed`,`isUser`,`included`) values(?,?,?,?,?,?,?)";
            $result = $this->query($query, [(int) $nlId, $add, $code, 'y', $this->now, $isUser, 'n']);
            return $result && $result->numRows();
        }
    }

    private function validateEmailWithDetails($email)
    {
        global $prefs;

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => tr('Invalid email format: %0', $email)
            ];
        }

        $parts = explode('@', $email, 2);
        $domain = $parts[1] ?? '';

        if (($prefs['newsletter_validate_email_dns'] ?? 'n') !== 'y') {
            return ['valid' => true, 'error' => null];
        }

        $mxHosts = [];
        $hasMx = @getmxrr($domain, $mxHosts);

        if (! $hasMx) {
            $hasMx = @checkdnsrr($domain, 'MX');
        }

        if (! $hasMx) {
            return [
                'valid' => false,
                'error' => tr('No mail server found for domain: %0 (the domain %1 has no MX record configured for email delivery)', $email, $domain)
            ];
        }

        $hasValidMx = false;
        if (! empty($mxHosts)) {
            foreach ($mxHosts as $mxHost) {
                $mxHost = trim($mxHost);
                if (! empty($mxHost) && $mxHost !== '.') {
                    $hasValidMx = true;
                    break;
                }
            }
        } else {
            // Resolver found MX but did not return hosts; keep this as valid.
            $hasValidMx = true;
        }

        if (! $hasValidMx) {
            return [
                'valid' => false,
                'error' => tr('No mail server found for domain: %0 (the domain %1 exists but has no valid MX record configured for email delivery)', $email, $domain)
            ];
        }

        return ['valid' => true, 'error' => null];
    }

    private function logEmailStatus(Email $mail, $error, $subject, $slug)
    {
        global $prefs;
        $logslib = TikiLib::lib('logs');
        $logEmailRes = [];

        $logEmailRes['subject'] = strip_tags($subject);

        foreach ($mail->getFrom() as $address) {
            $logEmailRes['from_email'] = $address->getAddress();
            $logEmailRes['from_name'] = $address->getName();
            break;
        }

        if ($error || $prefs['log_mail'] == 'y') {
            $recipients = $mail->getTo();
            foreach ($recipients as $destination) {
                $emailStatus = empty($error) ? 'success' : 'error';
                $logEmailRes['to'] = $destination->getAddress();
                $logEmailRes[$emailStatus] = empty($error) ? 'Email has been sent' : $error;
                $isCan = empty($error) ? 'send' : 'can not send';
                $logslib->add_log('Email', sprintf('%s - %s to %s, subject - %s', $slug, $isCan, $logEmailRes['to'], $logEmailRes['subject']), '', '', '', '', $logEmailRes);
                unset($logEmailRes[$emailStatus]);
            }
        }
    }

    public function confirm_subscription($code)
    {
        global $prefs;
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');
        $foo = parse_url($_SERVER["REQUEST_URI"]);
        $url_subscribe = $tikilib->httpPrefix(true) . $foo["path"];
        $query = "select * from `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` where `code`=?";
        $result = $this->query($query, [$code]);

        if (! $result->numRows()) {
            return false;
        }

        $res = $result->fetchRow();
        $info = $this->get_newsletter($res["nlId"]);
        $query = "update `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` set `valid`=? where `code`=?";
        $result = $this->query($query, ['y', $code]);
        // Now send a welcome email
        if ($res["isUser"] == "y") {
            $user = $res["email"];
            $emailTo = $userlib->get_user_email($user);
        } else {
            $emailTo = $res["email"];
            $user = $userlib->get_user_by_email($emailTo); //global $user is not necessarily defined as the user is not necessary logged in
        }
        if (! isset($_SERVER["SERVER_NAME"])) {
            $_SERVER["SERVER_NAME"] = $_SERVER["HTTP_HOST"];
        }

        $lg = ! $user ? $prefs['site_language'] : $this->get_user_preference($user, "language", $prefs['site_language']);
        $subjectData = $smarty->fetchLang($lg, 'mail/newsletter_welcome_subject.tpl');
        $subject = sprintf($subjectData, $info["name"], $_SERVER["SERVER_NAME"]);
        $smartyAssigns = [
            'info' => $info,
            'mail_date' => $this->now,
            'mail_user' => $user,
            'code' => $res["code"],
            'url_subscribe' => $url_subscribe,
            'server_name' => $_SERVER["SERVER_NAME"],
        ];

        $sendResult = $this->sendNewsletterRelatedEmail(
            $emailTo,
            $subject,
            'mail/newsletter_welcome.tpl',
            'mail/newsletter_welcome_html.tpl',
            $smartyAssigns,
            'Confirm Subscription',
            $lg
        );

        return $sendResult ? $this->get_newsletter($res["nlId"]) : false;
    }

    public function unsubscribe($code, $mailit = false)
    {
        global $prefs;
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');
        $foo = parse_url($_SERVER["REQUEST_URI"]);
        $url_subscribe = $tikilib->httpPrefix(true) . $foo["path"];
        $query = "select * from `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` where `code`=?";
        $result = $this->query($query, [$code]);

        if (! $result->numRows()) {
            return false;
        }

        $res = $result->fetchRow();
        $info = $this->get_newsletter($res["nlId"]);
        if ($res["isUser"] == 'g' || $res["included"] == 'y') {
            $query = "update `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` set `valid`='x' where `code`=?";
        } else {
            $query = "delete from `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` where `code`=?";
        }
        $result = $this->query($query, [$code], -1, -1, false);
        // Now send a bye bye email
        if ($res["isUser"] == "y") {
            $user = $res["email"];
            $emailTo = $userlib->get_user_email($user);
        } else {
            $emailTo = $res["email"];
            $user = $userlib->get_user_by_email($emailTo);
        }
        if (! isset($_SERVER["SERVER_NAME"])) {
            $_SERVER["SERVER_NAME"] = $_SERVER["HTTP_HOST"];
        }

        $lg = ! $user ? $prefs['site_language'] : $this->get_user_preference($user, "language", $prefs['site_language']);
        $subjectData = $smarty->fetchLang($lg, 'mail/newsletter_byebye_subject.tpl');
        $subject = sprintf($subjectData, $info["name"], $_SERVER["SERVER_NAME"]);

        $smartyAssigns = [
            'info' => $info,
            'code' => $res["code"],
            'mail_date' => $this->now,
            'mail_user' => $user,
            'url_subscribe' => $url_subscribe,
            'server_name' => $_SERVER["SERVER_NAME"],
        ];

        if ($mailit) {
            $this->sendNewsletterRelatedEmail(
                $emailTo,
                $subject,
                'mail/newsletter_byebye.tpl',
                'mail/newsletter_byebye_html.tpl',
                $smartyAssigns,
                'Unsubscribe',
                $lg
            );
        }
        return $this->get_newsletter($res["nlId"]);
    }

    /**
     * @param        $nlId
     * @param string $validateAddr
     * @param string $addEmail
     *
     * @return bool
     * @throws Exception
     */
    public function add_all_users($nlId, $validateAddr = '', $addEmail = '')
    {
        $query = "select `email`, `login`from `users_users`";
        $result = $this->query($query, []);
        $success = true;
        while ($res = $result->fetchRow()) {
            if ($addEmail == "y") {
                $add = $res["email"];
                $isUser = "n";
            } else {
                $add = $res["login"];
                $isUser = "y";
            }
            if (! empty($add)) {
                $eachResult = $this->newsletter_subscribe($nlId, $add, $isUser, $validateAddr, $addEmail);
                if (! $eachResult) {
                    $success = false;
                }
            } else {
                $success = false;
            }
        }
        return $success;
    }

    /**
     * @param        $nlId
     * @param        $group
     * @param string $include_groups
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function add_group($nlId, $group, $include_groups = 'n')
    {
        $query = "delete from `tiki_newsletter_groups` where `nlId`=? and `groupName`=?";
        $this->query($query, [(int) $nlId, $group], -1, -1, false);
        $code = $this->genRandomString($group);
        $query = "insert into `tiki_newsletter_groups`(`nlId`,`groupName`,`code`,`include_groups`) values(?,?,?,?)";
        return $this->query($query, [(int) $nlId, $group, $code, $include_groups]);
    }

    /**
     * @param $nlId
     * @param $includedId
     *
     * @return bool
     * @throws Exception
     */
    public function add_included($nlId, $includedId)
    {
        // do not include $includedId subscribers if $includedId newsletter includes $nlId subscribers
        // to avoid fatal recursive errors in get_all_subscribers() method
        $includedIdIncludes = $this->list_newsletter_included($includedId);
        if (array_key_exists($nlId, $includedIdIncludes)) {
            Feedback::warning(tr('Cannot add subscribers from a newsletter that includes this newsletter\'s subscribers'));
            return false;
        } else {
            $query = "delete from `tiki_newsletter_included` where `nlId`=? and `includedId`=?";
            $this->query($query, [(int) $nlId, (int) $includedId], -1, -1, false);
            $query = "insert into `tiki_newsletter_included` (`nlId`,`includedId`) values(?,?)";
            $result = $this->query($query, [(int) $nlId, (int) $includedId]);
            return $result && $result->numRows() > 0;
        }
    }

    /**
     * @param        $nlId
     * @param        $group
     * @param string $validateAddr
     * @param string $addEmail
     *
     * @return bool
     * @throws Exception
     */
    public function add_group_users($nlId, $group, $validateAddr = '', $addEmail = '')
    {
        $groups = array_merge([$group], $this->get_groups_all($group));
        $mid = implode(" or ", array_fill(0, count($groups), "`groupName`=?"));
        $query = "select `login`,`email`  from `users_users` uu, `users_usergroups` ug where uu.`userId`=ug.`userId` and ($mid)";
        $result = $this->query($query, $groups);
        $ret = [];
        while ($res = $result->fetchRow()) {
            if ($addEmail == "y") {
                $ret[] = $res['email'];
            } else {
                $ret[] = $res['login'];
            }
        }
        $ret = array_unique($ret);
        $isUser = $addEmail == "y" ? "n" : "y";
        $success = true;
        foreach ($ret as $o) {
            $eachResult = $this->newsletter_subscribe($nlId, $o, $isUser, $validateAddr, $addEmail);
            if (! $eachResult) {
                $success = false;
            }
        }
        return $success;
    }

    public function get_newsletter($nlId)
    {
        $query = "select * from `" . self::TABLE_NEWSLETTERS . "` where `nlId`=?";
        $result = $this->query($query, [(int) $nlId]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();
        return $res;
    }

    public function get_edition($editionId)
    {
        $query = "select * from `" . self::TABLE_SENT_NEWSLETTERS . "` where `editionId`=?";
        $result = $this->query($query, [(int) $editionId]);
        if (! $result->numRows()) {
            return false;
        }
        $res = $result->fetchRow();
        $res['files'] = $this->get_edition_files($editionId);
        return $res;
    }

    public function get_edition_files($editionId)
    {
        global $prefs;
        $res = [];
        $query = "select * from `" . self::TABLE_SENT_NEWSLETTERS_FILES . "` where `editionId`=?";
        $result = $this->query($query, [(int) $editionId]);
        $res = [];
        while ($f = $result->fetchRow()) {
            $f['error'] = 0;
            $res[] = $f;
        }
        return $res;
    }

    public function update_users($nlId)
    {
        $users = $this->getOne("select count(*) from `" . self::TABLE_NEWSLETTER_SUBSCRIPTIONS . "` where `nlId`=? and `valid`!=?", [(int) $nlId, 'x']);
        $query = "update `tiki_newsletters` set `users`=? where `nlId`=?";
        $result = $this->query($query, [$users, (int) $nlId]);
    }

    /* perms = a or between perms */
    public function list_newsletters($offset, $maxRecords, $sort_mode, $find, $update = '', $perms = '', $full = 'y')
    {
        global $user, $tikilib;
        $bindvars = [];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where (tn.`name` like ? or tn.`description` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        } else {
            $mid = '';
        }

        $query = "select tn.nlId, tn.`name`, tn.`description`, tn.`users`, tn.`editions`, tn.`author`, max(tsn.`sent`) as lastSent, tn.`allowTxt`, tn.`allowArticleClip`
        from `tiki_newsletters` tn
        left join `tiki_sent_newsletters` tsn on (tn.`nlId` = tsn.`nlId`) $mid
        group by tn.`nlId`, tn.`name`, tn.`description`, tn.`users`, tn.`editions`, tn.`author`
        order by " . $this->convertSortmode("$sort_mode");
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $query_count = "select count(*) from  `tiki_newsletters` as tn $mid";
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $objperms = Perms::get('newsletter', $res['nlId']);
            $res['tiki_p_admin_newsletters'] = $objperms->admin_newsletters ? 'y' : 'n';
            $res['tiki_p_send_newsletters'] = $objperms->send_newsletters ? 'y' : 'n';
            $res['tiki_p_subscribe_newsletters'] = $objperms->subscribe_newsletters ? 'y' : 'n';

            if (! empty($perms)) {
                $hasPerm = false;
                if (is_array($perms)) {
                    foreach ($perms as $perm) {
                        if ($res[$perm] == 'y') {
                            $hasPerm = true;
                            break;
                        }
                    }
                } else {
                    $hasPerm = $res[$perms];
                }
                if (! $hasPerm) {
                    continue;
                }
            }
            if ($full != 'n') {
                $ok = count($this->get_all_subscribers($res['nlId'], ""));
                $notok = $this->getOne("select count(*) from `tiki_newsletter_subscriptions` where `valid`=? and `nlId`=?", ['n', (int) $res['nlId']]);
                $res["users"] = $ok + $notok;
                $res["confirmed"] = $ok;
                $res['drafts'] = $this->getOne("select count(*) from `tiki_sent_newsletters` where `nlId`=? and `sent`=-1", [(int) $res['nlId']]);
            }
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    public function list_avail_newsletters()
    {
        $res = [];
        $query = "select `nlId`, `name` from `" . self::TABLE_NEWSLETTERS . "` where `allowUserSub`='y'";
        $bindvars = [];
        $result = $this->query($query, $bindvars);
        while ($rez = $result->fetchRow()) {
            $res[] = $rez;
        }
        return $res;
    }

    public function list_editions($nlId, $offset, $maxRecords, $sort_mode, $find, $drafts = false, $perm = '')
    {
        global $tikilib, $user;
        $bindvars = [];
        $mid = "";

        if ($nlId) {
            $mid .= " and tn.`nlId`=" . (int)$nlId;
            $tiki_p_admin_newsletters = $tikilib->user_has_perm_on_object($user, $nlId, 'newsletter', 'tiki_p_admin_newsletters') ? 'y' : 'n';
            $tiki_p_send_newsletters = $tikilib->user_has_perm_on_object($user, $nlId, 'newsletter', 'tiki_p_send_newsletters') ? 'y' : 'n';
            $tiki_p_subscribe_newsletters = $tikilib->user_has_perm_on_object($user, $nlId, 'newsletter', 'tiki_p_subscribe_newsletters') ? 'y' : 'n';
        }

        if ($find) {
            $findesc = '%' . $find . '%';
            $mid .= " and (`subject` like ? or `data` like ?)";
            $bindvars[] = $findesc;
            $bindvars[] = $findesc;
        }

        $mid .= ($drafts ? ' and tsn.`sent`=-1' : ' and tsn.`sent`<>-1');

        $query = "select tsn.`editionId`,tn.`nlId`,`subject`,`data`,tsn.`users`,`sent`,`name`,tsn.`wysiwyg` from `tiki_newsletters` tn, `tiki_sent_newsletters` tsn ";
        $query .= " where tn.`nlId`=tsn.`nlId` $mid order by " . $this->convertSortMode("$sort_mode");
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $ret = [];
        $query_count = "select count(*) from `" . self::TABLE_NEWSLETTERS . "` tn, `" . self::TABLE_SENT_NEWSLETTERS . "` tsn where tn.`nlId`=tsn.`nlId` $mid";
        $count = $this->getOne($query_count, $bindvars);

        while ($res = $result->fetchRow()) {
            if ($nlId) {
                if ($tiki_p_admin_newsletters != 'y' && $perm && $$perm == 'n') {
                    continue;
                }
                $res['tiki_p_admin_newsletters'] = $tiki_p_admin_newsletters;
                $res['tiki_p_send_newsletters'] = $tiki_p_send_newsletters;
                $res['tiki_p_subscribe_newsletters'] = $tiki_p_subscribe_newsletters;
            } else {
                $res['tiki_p_admin_newsletters'] = $tikilib->user_has_perm_on_object($user, $res['nlId'], 'newsletter', 'tiki_p_admin_newsletters') ? 'y' : 'n';
                $res['tiki_p_send_newsletters'] = $tikilib->user_has_perm_on_object($user, $res['nlId'], 'newsletter', 'tiki_p_send_newsletters') ? 'y' : 'n';
                $res['tiki_p_subscribe_newsletters'] = $tikilib->user_has_perm_on_object($user, $res['nlId'], 'newsletter', 'tiki_p_subscribe_newsletters') ? 'y' : 'n';
                if ($perm && $res[$perm] == 'n') {
                    continue;
                }
            }
            $ret[] = $res;
        }

        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    public function list_newsletter_subscriptions($nlId, $offset, $maxRecords, $sort_mode, $find)
    {
        $bindvars = [(int) $nlId];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where `nlId`=? and (`valid` != 'y' or (`isUser` != 'g' and `included` != 'y')) and `email` like ?";
            $bindvars[] = $findesc;
        } else {
            // show all except valid by group or include newsletters
            $mid = " where `nlId`=?  and (`valid` != 'y' or (`isUser` != 'g' and `included` != 'y')) ";
        }

        $query = "select * from `tiki_newsletter_subscriptions` $mid order by " . $this->convertSortMode("$sort_mode") . ", email asc";
        $query_count = "select count(*) from tiki_newsletter_subscriptions $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    public function list_newsletter_groups($nlId, $offset = -1, $maxRecords = -1, $sort_mode = 'groupName_asc', $find = '')
    {
        $bindvars = [(int) $nlId];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where `nlId`=? and `groupName` like ?";
            $bindvars[] = $findesc;
        } else {
            $mid = " where `nlId`=? ";
        }

        $query = "select * from `tiki_newsletter_groups` $mid order by " . $this->convertSortMode("$sort_mode");
        $query_count = "select count(*) from `tiki_newsletter_groups` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        $userlib = TikiLib::lib('user');
        while ($res = $result->fetchRow()) {
            $res['additional_groups'] = [];
            if ($res['include_groups'] == 'y') {
                $res['additional_groups'] = $userlib->get_including_groups($res["groupName"], 'y');
            }
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    public function list_newsletter_included($nlId)
    {
        $query = "select a.`includedId`,b.`name` from `tiki_newsletter_included` a left join `tiki_newsletters` b on a.`includedId`=b.`nlId` where a.`nlId`=? ";
        $result = $this->query($query, [(int) $nlId]);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[$res['includedId']] = $res['name'];
        }
        return $ret;
    }

    public function list_newsletter_all_included($nlId, $check = [])
    {
        $query = "select a.`includedId`,b.`name` from `tiki_newsletter_included` a left join `tiki_newsletters` b on a.`includedId`=b.`nlId` where a.`nlId`=? ";
        $result = $this->query($query, [(int) $nlId]);
        $ret = [];
        while ($res = $result->fetchRow()) {
            if (! in_array($res['includedId'], $check)) {
                $check[] = $res['includedId'];
                $ret[$res['includedId']] = $res['name'];
                $back = $this->list_newsletter_all_included($res['includedId'], $check);
                $ret = $back + $check;
            }
        }
        return array_unique($ret);
    }

    public function get_unsub_msg($nlId, $email, $lang, $code = '', $user = '')
    {
        global $prefs;
        $userlib = TikiLib::lib('user');
        $tikilib = TikiLib::lib('tiki');
        $smarty = TikiLib::lib('smarty');
        $pth = $tikilib->httpPrefix(true) . substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], 'tiki-'));
        $foo = parse_url($_SERVER["REQUEST_URI"]);
         $smarty->assign('url', $pth);
        $foo = str_replace('send_newsletters', 'newsletters', $foo);
        $url_subscribe = $tikilib->httpPrefix(true) . $foo["path"];
        if ($code == '') {
            $isUser = $user ? "y" : "n";
            $code = $this->getOne("select `code` from `tiki_newsletter_subscriptions` where `nlId`=? and `email`=? and `isUser`=?", [(int) $nlId, $email, $isUser]);
        }
        $url_unsub = $url_subscribe . '?unsubscribe=' . $code;
        $smarty->assign('url_unsub', $url_unsub);
        if ($user == '') {
            $user = $userlib->get_user_by_email($email);
        }
        if ($lang == '') {
            $lang = ! $user ? $prefs['site_language'] : $this->get_user_preference($user, "language", $prefs['site_language']);
        }

        $smarty->assign('thisuser', $user);
        $msg = $smarty->fetchLang($lang, 'mail/newsletter_unsubscribe.tpl');
        return $msg;
    }

    /**
     * @param $nlId
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function remove_newsletter($nlId)
    {
        $query = "delete from `tiki_newsletters` where `nlId`=?";
        $result = $this->query($query, [(int) $nlId], -1, -1, false);
        $query = "delete from `tiki_newsletter_subscriptions` where `nlId`=?";
        $this->query($query, [(int) $nlId], -1, -1, false);
        $query = "delete from `tiki_newsletter_groups` where `nlId`=?";
        $this->query($query, [(int) $nlId], -1, -1, false);
        $this->remove_object('newsletter', $nlId);
        return $result;
    }

    public function remove_edition($nlId, $editionId)
    {
        $query = "delete from `tiki_sent_newsletters` where `editionId`=?";
        $result = $this->query($query, [(int) $editionId], -1, -1, false);
        $query = "update `tiki_newsletters` set `editions`= `editions`- 1 where `nlId`=?";
        $result = $this->query($query, [(int) $nlId]);
    }

    /**
     * @param $nlId
     * @param $email
     * @param $isUser
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function valid_subscription($nlId, $email, $isUser)
    {
        $query = "update `tiki_newsletter_subscriptions` set `valid`= ? where `nlId`=? and `email`=? and `isUser`=?";
        return $this->query($query, ['y', (int) $nlId, $email, $isUser]);
    }

    public function list_tpls()
    {
        global $tikidomain;

        // Determine the correct path to scan
        $path = "templates/$tikidomain/newsletters/";
        if (! is_dir($path)) {
            $path = "templates/newsletters/";
        }

        if (! is_dir($path)) {
            return [];
        }

        $tpls = [];
        $iterator = new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS);

        foreach ($iterator as $fileInfo) {
            $filename = $fileInfo->getFilename();
            if (str_ends_with($filename, '.tpl')) {
                $tpls[] = $filename;
            }
        }

        return $tpls;
    }

    public function memo_subscribers_edition($editionId, $users)
    {
        $query = 'insert into `tiki_sent_newsletters_errors` (`editionId`, `email`, `login`) values(?,?,?)';
        foreach ($users as $user) {
            $result = $this->query($query, [(int) $editionId, $user['email'], $user['login']]);
        }
    }

    public function delete_edition_subscriber($editionId, $user)
    {
        $query = 'delete from `tiki_sent_newsletters_errors` where `editionId`=? and `email`=?';
        $this->query($query, [(int) $editionId, $user['email']]);
    }

    /**
     * @param $editionId
     * @param $user
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function mark_edition_subscriber($editionId, $user)
    {
        $query = 'update `tiki_sent_newsletters_errors` set `error`= ? where `editionId`=? and `email`=?';
        return $this->query($query, ['y', (int) $editionId, $user['email']]);
    }

    public function get_edition_errors($editionId)
    {
        $query = 'select * from `tiki_sent_newsletters_errors` where `editionId`=?';
        $result = $this->query($query, [(int) $editionId]);
        $ret = [];
        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }
        return $ret;
    }

    public function get_edition_nb_errors($editionId)
    {
        $query = 'select count(*) from `tiki_sent_newsletters_errors` where `editionId`=?';
        return $this->getOne($query, [(int) $editionId]);
    }

    public function remove_edition_errors($editionId)
    {
        $query = 'delete from `tiki_sent_newsletters_errors` where `editionId`=?';
        $this->query($query, [(int) $editionId]);
    }

    public function clip_articles($nlId)
    {
        $smarty = TikiLib::lib('smarty');
        $artlib = TikiLib::lib('art');
        $query = 'select `articleClipTypes`, `articleClipRange` from `tiki_newsletters` where nlId = ?';
        $result = $this->fetchAll($query, [$nlId]);
        $articleClipTypes = unserialize($result[0]['articleClipTypes']);
        $date_min = $this->now - $result[0]['articleClipRange'];
        $date_max = $this->now;
        $articles = [];
        $articleClip = '';
        # Order array by publishDate
        $cmpFunc = function ($a, $b) {
            if ($a['publishDate'] == $b['publishDate']) {
                return 0;
            }
            return ($a['publishDate'] > $b['publishDate']) ? -1 : 1;
        };
        foreach ($articleClipTypes as $articleType) {
            $t_articles = $artlib->list_articles(0, -1, 'publishDate_desc', '', $date_min, $date_max, false, $articleType);
            foreach ($t_articles["data"] as $t) {
                $articles[$t["articleId"]] = $t;
            }
        }
        usort($articles, $cmpFunc);
        foreach ($articles as $art) {
            $smarty->assign("nlArticleClipId", $art["articleId"]);
            $smarty->assign("nlArticleClipTitle", $art["title"]);
            $smarty->assign("nlArticleClipSubtitle", $art["subtitle"]);
            $smarty->assign("nlArticleClipParsedheading", TikiLib::lib('parser')->parse_data($art["heading"], ['is_html' => $artlib->is_html($art, true)]));
            $smarty->assign("nlArticleClipPublishDate", $art["publishDate"]);
            $smarty->assign("nlArticleClipAuthorName", $art["authorName"]);
            $articleClip .= $smarty->fetch("mail/newsletter_articleclip.tpl");
        }
        return "<div class=\"articleclip\">\n" . $articleClip . "\n<!-- " . tr("End of last article") . " -->\n</div>";
    }

    // functions for getting email addresses from wiki pages

    public function get_emails_from_page($wikiPageName)
    {
        global $prefs;

        $wikilib = TikiLib::lib('wiki');
        $emails = false;

        $canBeRefreshed = false;
        $o1 = $prefs['feature_wiki_protect_email'];
        $o2 = $prefs['feature_autolinks'];
        $prefs['feature_wiki_protect_email'] = 'n';
        $prefs['feature_autolinks'] = 'n';
        $pageContent = $wikilib->get_parse($wikiPageName, $canBeRefreshed);
        $prefs['feature_wiki_protect_email'] = $o1;
        $prefs['feature_autolinks'] = $o2;

        if (! empty($pageContent)) {
            $pageContent = strip_tags($pageContent, '<p><tr><br>');
            $pageContent = preg_replace(['/<p.*?>/i','/<tr.*?>/i'], "", $pageContent);  // deal with stripped html from smarty
            $pageContent = str_replace(['</p>','</tr>','<br />'], "\n", $pageContent);  // add linefeeds
            $pageContent = preg_replace('/[\\n\\r]/', "\n", $pageContent);  // in case there are MS lineends
            $pageContent = preg_replace('/\\n\\n/', "\n", $pageContent);    // remove blank lines
            $ary = explode("\n", $pageContent);
            $emails = [];
            foreach ($ary as $a) {
                preg_match('/[a-z0-9\-_.]+?@[\w\-\.]+/i', $a, $m);
                if (count($m) > 0) {
                    if (validate_email($m[0])) {
                        $emails[] = strtolower($m[0]);
                    }
                }
            }
        }

        return $emails;
    }

    /**
     * @param        $nlId
     * @param        $wikiPageName
     * @param string $validate
     * @param string $addToList
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function add_page($nlId, $wikiPageName, $validate = 'n', $addToList = 'n')
    {
        $query = "delete from `tiki_newsletter_pages` where `nlId`=? and `wikiPageName`=?";
        $this->query($query, [ (int) $nlId, $wikiPageName], -1, -1, false);
        $query = "insert into `tiki_newsletter_pages` (`nlId`,`wikiPageName`,`validateAddrs`,`addToList`) values(?,?,?,?)";
        return $this->query($query, [ (int) $nlId, $wikiPageName, $validate, $addToList]);
    }

    /**
     * @param $nlId
     * @param $wikiPageName
     *
     * @return Tiki\TikiDb\PdoResult
     */
    public function remove_newsletter_page($nlId, $wikiPageName)
    {
        $query = "delete from `tiki_newsletter_pages` where `nlId`=? and `wikiPageName`=?";
        return $this->query($query, [ (int) $nlId, $wikiPageName], -1, -1, false);
    }

    public function list_newsletter_pages($nlId, $offset = -1, $maxRecords = -1, $sort_mode = 'wikiPageName_asc', $find = '')
    {
        $bindvars = [(int) $nlId];
        if ($find) {
            $findesc = '%' . $find . '%';
            $mid = " where `nlId`=? and `wikiPageName` like ?";
            $bindvars[] = $findesc;
        } else {
            $mid = " where `nlId`=? ";
        }

        $query = "select * from `tiki_newsletter_pages` $mid order by " . $this->convertSortMode("$sort_mode");
        $query_count = "select count(*) from `tiki_newsletter_pages` $mid";
        $result = $this->query($query, $bindvars, $maxRecords, $offset);
        $count = $this->getOne($query_count, $bindvars);
        $ret = [];

        while ($res = $result->fetchRow()) {
            $ret[] = $res;
        }
        $retval = [];
        $retval["data"] = $ret;
        $retval["count"] = $count;
        return $retval;
    }

    /**
     * Get all emails from tracker
     *
     * @param int $trackerId
     * @return mixed
     */
    public function get_emails_from_tracker($trackerId)
    {
        $emails = false;
        $trklib = TikiLib::lib('trk');
        $listItems = $trklib->list_tracker_items($trackerId, 0, -1, '', null);

        if (empty($listItems['data'])) {
            return false;
        }

        foreach ($listItems['data'] as $field) {
            if (empty($field['field_values'])) {
                continue;
            }

            foreach ($field['field_values'] as $fieldValue) {
                if (empty($fieldValue['value']) || false == preg_match('/[a-z0-9\-_.]+?@[\w\-\.]+/i', $fieldValue['value'], $m)) {
                    continue;
                }

                if (count($m) > 0 && validate_email($m[0])) {
                    $emails[] = strtolower($m[0]);
                }
            }
        }

        return $emails;
    }

    private function get_edition_mail($editionId, $target, $is_html = null, $replyTo = null, $sendFrom = null, $unsubscribeLink = null)
    {
        global $prefs, $base_url;
        static $mailcache = [];
        $cachekey = $editionId;

        if (! isset($mailcache[$cachekey])) {
            $tikilib = TikiLib::lib('tiki');
            $headerlib = TikiLib::lib('header');

            $info = $this->get_edition($editionId);
            $nl_info = $this->get_newsletter($info['nlId']);


            // build the html
            $beginHtml = '<body class="tiki_newsletters"><div id="tiki-center" class="clearfix content"><div class="wikitext">';
            $endHtml = '</div></div></body>';
            if ($is_html === null) {
                $is_html = $info['wysiwyg'] === 'y' && $prefs['wysiwyg_htmltowiki'] !== 'y'; // parse as html if wysiwyg and not htmltowiki
            } else {
                $is_html = ! empty($is_html);
            }
            if (stristr($info['data'], '<body') === false) {
                $preparsed = str_replace([
                    '{{recipient.user}}',
                    '{{recipient.email}}',
                    '{{recipient.realName}}'
                ], [
                    $target['user'],
                    $target['email'],
                    $this->get_user_preference($target['user'], 'realName'),
                ], $info['data']);
                if ($preparsed != $info['data']) {
                    $cachekey .= $target['email'];
                }
                $html = "<html>$beginHtml" . $this->parseBody($preparsed, $is_html) . "$endHtml</html>";
            } else {
                $html = str_ireplace('<body>', $beginHtml, $info['data']);
                $html = str_ireplace('</body>', $endHtml, $html);
            }

            if ($nl_info['allowArticleClip'] == 'y' && $nl_info['autoArticleClip'] == 'y') {
                $articleClip = $this->clip_articles($nl_info['nlId']);
                $txtArticleClip = $this->generateTxtVersion($articleClip);
                $info['datatxt'] = str_replace('~~~articleclip~~~', $txtArticleClip, $info['datatxt']);
                $html = str_replace('~~~articleclip~~~', $articleClip, $html);
                if ($articleClip == '<div class="articleclip"></div>' && $nl_info['emptyClipBlocksSend'] == 'y') {
                    return '';
                }
            }

            if (stristr($html, '<base') === false) {
                if (stristr($html, '<head') === false) {
                    $news_css = $this->getNewsletterCss();
                    $news_head = "<html><head><base href=\"$base_url\" /><style>{$news_css}</style></head>";
                    $html = str_ireplace('<html>', $news_head, $html);
                } else {
                    $html = str_ireplace('<head>', "<head><base href=\"$base_url\" />", $html);
                }
            }

            $info['files'] = $this->get_edition_files($editionId);
            include_once 'lib/mail/maillib.php';
            $zmail = tiki_get_admin_mail();

            if (! empty($replyTo)) {
                $zmail->replyTo($replyTo);
            }

            if (! empty($sendFrom)) {
                $zmail->from($sendFrom);
                $zmail->sender($sendFrom);
            }

            foreach ($info['files'] as $f) {
                $fpath = $f['path'] ?? $prefs['tmpDir'] . '/newsletterfile-' . $f['filename'];
                $zmail->addPart(new \Symfony\Component\Mime\Part\DataPart(
                    fopen($fpath, 'r'),
                    $f['name'],
                    $f['type']
                ));
            }

            $zmail->subject($info['subject']);

            $mailcache[$cachekey] = [
                'zmail' => $zmail,
                'text' => $info['datatxt'],
                'html' => $html,
                'unsubMsg' => $nl_info['unsubMsg'],
                'nlId' => $nl_info['nlId'],
            ];
        }

        $cache = $mailcache[$cachekey];

        $html = $cache['html'];
        $unsubmsg = '';
        if ($cache["unsubMsg"] == 'y' && ! empty($target["code"])) {
            $unsubmsg = $this->get_unsub_msg($cache["nlId"], $target['email'], $target['language'], $target["code"], $target['user']);
            if (stristr($html, '</body>') === false) {
                $html .= $unsubmsg;
            } else {
                $html = str_replace("</body>", nl2br($unsubmsg) . "</body>", $html);
            }
        }

        $zmail = $cache['zmail'];
        $zmail->html($html);
        $zmail->text($cache['text'] . strip_tags($unsubmsg));

        $zmail->getHeaders()->remove('to');
        $zmail->getHeaders()->remove('cc');
        $zmail->getHeaders()->remove('bcc');

        if ($unsubscribeLink) {
            $zmail->getHeaders()->addTextHeader('List-Unsubscribe', '<' . $unsubscribeLink . '>');
        }

        $zmail->addTo($target['email']);

        return $zmail;
    }

    private function getNewsletterCss()
    {
        global $prefs;
        $headerlib = TikiLib::lib('header');
        $themelib = TikiLib::lib('theme');

        $newsCss = '';
        $newsCssFile = $themelib->get_theme_path($prefs['theme'], '', 'newsletter.css');
        $newsCssFileOption = $themelib->get_theme_path($prefs['theme'], $prefs['theme_option'], 'newsletter.css');

        if (! empty($newsCssFile)) {
            $newsCss .= $headerlib->minify_css($newsCssFile);
        }
        if (! empty($newsCssFileOption) && $newsCssFileOption !== $newsCssFile) {
            $newsCss .= $headerlib->minify_css($newsCssFileOption);
        }
        if (empty($newsCss)) {
            $newsCss = $headerlib->minify_css('themes/base_files/css/newsletter.css');
        }
        return $newsCss;
    }

    // info: subject, data, datatxt, dataparsed, wysiwyg, sendingUniqId, files, errorEditionId, editionId
    // browser: true if on the browser
    // $csrfCheck: indicated whether modified csrf check passed
    public function send($nl_info, $info, $browser, &$sent, &$errors, &$logFileName, $csrfCheck)
    {
        global $prefs, $section;
        $tikilib = TikiLib::lib('tiki');
        $userlib = TikiLib::lib('user');
        $smarty = TikiLib::lib('smarty');
        $users = $this->get_all_subscribers($nl_info['nlId'], $nl_info['unsubMsg'] == 'y');
        $url_unsub = parse_url($_SERVER["REQUEST_URI"]);
        $url_unsubscribe = $tikilib->httpPrefix(true) . $url_unsub["path"];
        if (empty($info['editionId'])) {
            $info['editionId'] = $this->replace_edition(
                $nl_info['nlId'],
                $info['subject'],
                $info['data'],
                0,
                0,
                true,
                $info['datatxt'],
                $info['files'],
                $info['wysiwyg'],
                $info['is_html']
            );
        } else {
            $this->replace_edition(
                $nl_info['nlId'],
                $info['subject'],
                $info['data'],
                0,
                $info['editionId'],
                true,
                $info['datatxt'],
                $info['files'],
                $info['wysiwyg'],
                $info['is_html']
            );
        }

        if (isset($info['begin'])) {
            $this->memo_subscribers_edition($info['editionId'], $users);
        }

        $remaining = $this->table('tiki_sent_newsletters_errors')->fetchColumn(
            'email',
            [
                'editionId' => $info['editionId'],
                'error' => '',
            ]
        );

        $sent = [];
        $errors = [];
        $toSend = [];
        foreach ($users as $uInfo) {
            $userEmail = $uInfo['login'];
            $email = $uInfo['email'];
            if ($userEmail == '') {
                $userEmail = $userlib->get_user_by_email($email);
            }
            $language = ! $userEmail ? $prefs['site_language'] : $tikilib->get_user_preference(
                $userEmail,
                "language",
                $prefs['site_language']
            );

            // Use detailed email validation instead of simple regex
            $validation = $this->validateEmailWithDetails($email);
            if ($validation['valid']) {
                if (in_array($email, $remaining)) {
                    $uInfo['user'] = $userEmail;
                    $uInfo['email'] = $email;
                    $uInfo['language'] = $language;

                    $toSend[$email] = $uInfo;
                } else {
                    $remainingErrors = $this->table('tiki_sent_newsletters_errors')->fetchColumn(
                        'email',
                        [
                            'editionId' => $info['editionId'],
                            'email' => $uInfo['email'],
                            'error' => 'y',
                        ]
                    );
                    if (count($remainingErrors) === 0) {
                        $sent[] = $email;
                    } else {
                        $errors[] = ["user" => $userEmail, "email" => $email, "msg" => tr("potential CSRF")];
                    }
                }
            } else {
                $errors[] = ["user" => $userEmail, "email" => $email, "msg" => $validation['error']];
            }
        }

        $users = array_values($toSend);

        $logFileName = $prefs['tmpDir'] . '/public/newsletter-log-' . $info['editionId'] . '.txt';
        if (! ($logFileHandle = fopen($logFileName, 'a'))) {
            $logFileName = '';
        }

        $smarty->assign('sectionClass', empty($section) ? '' : "tiki_$section ");
        if ($browser) {
            echo $smarty->fetch('send_newsletter_header.tpl');
        }

        if ($browser) {
            @ini_set('zlib.output_compression', 0);
        }

        $throttleLimit = (int) $prefs['newsletter_batch_size'];

        foreach ($users as $us) {
            $tikilib->clear_cache_user_preferences();
            $email = $us['email'];
            $code = $us['code'];
            $url_unsub = $url_unsubscribe . '?unsubscribe=' . $code;
            if ($browser) {
                if (@ob_get_level() == 0) {
                    @ob_start();
                }
                print str_repeat(' ', 4096) . "\n";
            }

            if ($csrfCheck) {
                $zmail = null;
                $errorMsg = null; // Initialize error message variable
                try {
                    $validation = $this->validateEmailWithDetails($email);
                    if (! $validation['valid']) {
                        $errorMsg = $validation['error'];
                        if ($browser) {
                            print '<div class="confirmation">' . ' Total emails sent: ' . count($sent)
                                . tr(' after error validating') . ' <b>' . $email . '</b>: <span class="text-danger">'
                                . tr('Error') . ' - ' . $errorMsg . '</span></div>' . "\n";
                        }
                        $errors[] = ["user" => $us['user'], "email" => $email, "msg" => $errorMsg];
                        $this->mark_edition_subscriber($info['editionId'], $us);
                        $logStatus = 'Error';
                        if ($logFileHandle) {
                            @fwrite($logFileHandle, "$email : $logStatus - $errorMsg\n");
                        }
                        continue;
                    }

                    $zmail = $this->get_edition_mail(
                        $info['editionId'],
                        $us,
                        $info['is_html'],
                        $info['replyto'],
                        $info['sendfrom'],
                        $url_unsub
                    );
                    if (! $zmail) {
                        continue;
                    }
                    if ($prefs['mailer_queue'] == 'y') {
                        $query = "INSERT INTO `tiki_mail_queue` (message) VALUES (?)";
                        $bindvars = [serialize($zmail)];
                        TikiLib::lib('tiki')->query($query, $bindvars, -1, 0);
                    } else {
                        tiki_send_email($zmail);
                    }
                    $sent[] = $email;
                    if ($browser) {
                        print '<div class="confirmation">' . ' Total emails sent: ' . count($sent)
                            . tr(' after sending to') . ' <b>' . $email . '</b>: <span class="text-success">' . tr('OK')
                            . '</span></div>' . "\n";
                    }
                    $this->delete_edition_subscriber($info['editionId'], $us);
                    $logStatus = 'OK';
                    $this->logEmailStatus($zmail, '', trim($zmail->getSubject()), 'From Send Method nllib');
                } catch (TransportExceptionInterface | \Throwable $e) {
                    $errorMsg = $e->getMessage();

                    if (str_contains($errorMsg, 'Connection timed out') || str_contains($errorMsg, 'timeout')) {
                        $errorMsg = tr('Connection timeout: Unable to connect to mail server');
                    } elseif (str_contains($errorMsg, 'Connection refused') || str_contains($errorMsg, 'refused')) {
                        $errorMsg = tr('Connection refused: Mail server refused the connection');
                    } elseif (str_contains($errorMsg, 'Host not found') || str_contains($errorMsg, 'Name or service not known')) {
                        $errorMsg = tr('Host not found: Mail server hostname cannot be resolved');
                    } elseif (str_contains($errorMsg, 'Authentication failed') || str_contains($errorMsg, 'authentication')) {
                        $errorMsg = tr('Authentication failed: Invalid mail server credentials');
                    } elseif (
                        preg_match('/\b550\b/', $errorMsg) || preg_match('/\b5\.1\.1\b/', $errorMsg) ||
                        str_contains($errorMsg, 'User unknown') || str_contains($errorMsg, 'User not found') ||
                        str_contains($errorMsg, 'mailbox unavailable') || str_contains($errorMsg, 'recipient rejected')
                    ) {
                        $errorMsg = tr('Recipient not found: Email address does not exist on the mail server (SMTP 550/5.1.1)');
                    } elseif (
                        preg_match('/\b551\b/', $errorMsg) || preg_match('/\b5\.1\.0\b/', $errorMsg) ||
                        str_contains($errorMsg, 'User not local')
                    ) {
                        $errorMsg = tr('Recipient not local: Email address is not local to this mail server (SMTP 551/5.1.0)');
                    } elseif (
                        preg_match('/\b552\b/', $errorMsg) || preg_match('/\b5\.2\.2\b/', $errorMsg) ||
                        str_contains($errorMsg, 'mailbox full') || str_contains($errorMsg, 'quota exceeded')
                    ) {
                        $errorMsg = tr('Mailbox full: Recipient mailbox is full and cannot accept messages (SMTP 552/5.2.2)');
                    } elseif (
                        preg_match('/\b553\b/', $errorMsg) || preg_match('/\b5\.1\.8\b/', $errorMsg) ||
                        str_contains($errorMsg, 'sender rejected') || str_contains($errorMsg, 'relay denied')
                    ) {
                        $errorMsg = tr('Invalid sender: Sender email address is not allowed or relay denied (SMTP 553/5.1.8)');
                    } elseif (
                        preg_match('/\b554\b/', $errorMsg) || str_contains($errorMsg, 'transaction failed') ||
                        str_contains($errorMsg, 'message rejected')
                    ) {
                        $errorMsg = tr('Message rejected: Mail server rejected the message (SMTP 554)');
                    } elseif (
                        preg_match('/\b451\b/', $errorMsg) || preg_match('/\b4\.\d\.\d\b/', $errorMsg) ||
                        str_contains($errorMsg, 'temporary failure') || str_contains($errorMsg, 'try again')
                    ) {
                        $errorMsg = tr('Temporary failure: Mail server temporarily unavailable, message may be retried later (SMTP 451)');
                    } elseif (preg_match('/\b452\b/', $errorMsg) || str_contains($errorMsg, 'insufficient system storage')) {
                        $errorMsg = tr('Insufficient storage: Mail server storage full, message may be retried later (SMTP 452)');
                    } elseif (preg_match('/\b5\.\d\.\d\b/', $errorMsg)) {
                        $errorMsg = tr('Permanent delivery failure: Mail server permanently rejected the message (SMTP 5xx)');
                    } elseif (preg_match('/\b4\.\d\.\d\b/', $errorMsg)) {
                        $errorMsg = tr('Temporary delivery failure: Mail server temporarily rejected the message, may be retried (SMTP 4xx)');
                    }

                    if ($browser) {
                        print '<div class="confirmation">' . ' Total emails sent: ' . count($sent)
                            . tr(' after error in sending to') . ' <b>' . $email . '</b>: <span class="text-danger">'
                            . tr('Error') . ' - ' . htmlspecialchars($errorMsg) . '</span></div>' . "\n";
                    }
                    $errors[] = ["user" => $us['user'], "email" => $email, "msg" => $errorMsg];
                    $this->mark_edition_subscriber($info['editionId'], $us);
                    $logStatus = 'Error';
                    $this->logEmailStatus($zmail ?: tiki_get_basic_mail(), $errorMsg, $zmail ? trim($zmail->getSubject()) : '', 'From Send Method nllib');
                }
            } else {
                $errorMsg = tr('Potential cross site forgery request detected');
                if ($browser) {
                    print '<div class="confirmation">' . ' Total emails sent: ' . count($sent)
                        . tr(' after failure to send to') . ' <b>' . $email . '</b>: <span class="text-danger">'
                        . tr('Error - potential cross site request forgery detected') . '</span></div>' . "\n";
                }
                $errors[] = [
                    "user" => $us['user'],
                    "email" => $email,
                    "msg" => $errorMsg,
                ];
                $this->mark_edition_subscriber($info['editionId'], $us);
                $logStatus = 'Error';
            }

            if ($logFileHandle) {
                $logMsg = "$email : $logStatus";
                if ($logStatus === 'Error' && isset($errorMsg)) {
                    $logMsg .= " - $errorMsg";
                }
                @fwrite($logFileHandle, "$logMsg\n");
            }

            if ($browser) {
                // Flush output to force the browser to display email addresses as soon as emails are sent
                // This should avoid CGI and/or proxy and/or browser timeouts when sending to a lot of emails
                @ob_flush();
                @flush();
                @ob_end_flush();
            }

            if ($prefs['newsletter_throttle'] === 'y' && 0 == --$throttleLimit) {
                if (isset($_SESSION['tickets']['newsletter']['iterations'])) {
                    if ($_SESSION['tickets']['newsletter']['iterations'] > 1) {
                        --$_SESSION['tickets']['newsletter']['iterations'];
                    } else {
                        unset($_SESSION['tickets']['newsletter']);
                    }
                }

                $rate = (int) $prefs['newsletter_pause_length'];
                $replytoData = '';
                if (! empty($info['replyto'])) {
                    $replytoData = ' data-replyto="' . $info['replyto'] . '"';
                }
                $sendfromData = '';
                if (! empty($info['sendfrom'])) {
                    $sendfromData = ' data-sendfrom="' . $info['sendfrom'] . '"';
                }

                TikiLib::lib('access')->setTicket();
                $ticketData = ' data-ticket="' . $smarty->getTemplateVars('ticket') . '"';

                print '<div class="throttle" data-edition="' . $info['editionId'] . '"' . $replytoData . $sendfromData . $ticketData
                    . ' data-rate="' . $rate . '">' . tr('Limiting the email send rate. Resuming in %0 seconds.', $rate)
                    . '</div>';
                exit;
            }
        }
        $info['editionId'] = $this->replace_edition(
            $nl_info['nlId'],
            $info['subject'],
            $info['data'],
            count($sent),
            $info['editionId'],
            false,
            $info['datatxt'],
            $info['files'],
            $info['wysiwyg'],
            $info['is_html']
        );
        foreach ($info['files'] as $k => $f) {
            if ($f['savestate'] == 'tikitemp') {
                $newpath = $prefs['tmpDir'] . '/newsletterfile-' . $f['filename'];
                rename($f['path'], $newpath);
                unlink($f['path'] . '.infos');
                $info['files'][$k]['savestate'] = 'tiki';
                $info['files'][$k]['path'] = $newpath;
            }
        }
        if ($logFileHandle) {
            @fclose($logFileHandle);
        }
    }

    // code originally in tiki-send_newsletters.php but made into a lib function so it could
    // be reused for the resume option when newsletter throttling is used
    public function closesendframe($sent, $errors, $logFileName)
    {
        $smarty = TikiLib::lib('smarty');
        $nb_sent = count($sent);
        $nb_errors = count($errors);

        $msg = '<h4>' . sprintf(tra('Newsletter successfully sent to %s users.'), $nb_sent) . '</h4>';
        if ($nb_errors > 0) {
            $msg .= "\n" . '<span class="text-danger">' . '(' . sprintf(tra('Number of errors: %s'), $nb_errors) . ')'
                . '</span><br /><br />';

            // Display detailed list of errors
            $msg .= '<div class="alert alert-danger"><strong>' . tra('Errors details:') . '</strong><ul style="margin-top: 10px; margin-bottom: 0;">';
            foreach ($errors as $error) {
                $email_display = htmlspecialchars($error['email'] ?? '');
                $error_msg = htmlspecialchars($error['msg'] ?? tra('Unknown error'));
                $msg .= '<li><strong>' . $email_display . ':</strong> ' . $error_msg . '</li>';
            }
            $msg .= '</ul></div>';
        }

        // If logfile exists and if it is reachable from the web browser, add a download link
        if (! empty($logFileName) && $logFileName[0] != '/' && $logFileName[0] != '.') {
            $smarty->assign('downloadLink', $logFileName);
        }

        echo str_replace("'", "\\'", $msg);
        echo $smarty->fetch('send_newsletter_footer.tpl');

        $smarty->assign('sent', $nb_sent);
        $smarty->assign('emited', 'y');
        if (count($errors) > 0) {
            $smarty->assign_by_ref('errors', $errors);
        }
        unset($_SESSION["sendingUniqIds"][ $_REQUEST["sendingUniqId"] ]);

        return;
    }

    public function generateTxtVersion($txt, $parsed = null)
    {
        global $tikilib;

        if (empty($parsed)) {
            $txt = TikiLib::lib('parser')->parse_data($txt, ['absolute_links' => true, 'suppress_icons' => true]);
        } else {
            $txt = $parsed;
        }
        $txt = str_replace('&nbsp;', ' ', $txt);
        $txt = strip_tags($txt);
        $txt = str_replace("\t", '', $txt);
        $txt = str_replace("\n\n", "\n", $txt);     // convert from wysiwyg seems to double up linefeeds

        $txt = html_entity_decode($txt);
        return $txt;
    }

    /**
     * @param      $data
     * @param bool $is_html
     *
     * @return string
     * @throws Exception
     */
    public function parseBody($data, int $is_html = 0): string
    {
        global $prefs;
        $allowImageLazyLoad = $prefs['allowImageLazyLoad'];
        $prefs['allowImageLazyLoad'] = 'n';

        $parsed = TikiLib::lib('parser')->parse_data(
            $data,
            [
                'absolute_links' => true,
                'suppress_icons' => true,
                'is_html'        => $is_html,
            ]
        );

        $prefs['allowImageLazyLoad'] = $allowImageLazyLoad;
        return $parsed;
    }
}

$nllib = new NlLib();
