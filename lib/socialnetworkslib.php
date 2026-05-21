<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// this script may only be included - so its better to die if called directly.
use Tiki\Lib\Logs\LogsLib;

if (str_contains($_SERVER['SCRIPT_NAME'], basename(__FILE__))) {
    header('location: index.php');
    exit;
}

/**
* This class bundles several social networks functions (twitter, facebook ...)
* @author cdrwhite
* @since 6.0
*/
class SocialNetworksLib extends LogsLib
{
    /**
    * Latest Facebook API version for accessing graph.facebook.com
    * Documentation says it's best to specify the version, otherwise the oldest version is used
    * Will need to be updated whenever the API version is updated
    *
    * @var string
    */
    private $graphVersion = 'v18.0';

    /**
    * @var array   options for Twitter Zend functions
    */
    // @tiki-external-link-ok: Twitter OAuth base URL, requires authentication
    public $options = [
            'callbackUrl'    => '',
            'siteUrl'        => 'https://api.twitter.com/oauth',
            'consumerKey'    => '',
            'consumerSecret' => '',
    ];

    /**
    * retrieves the URL for the current page
    *
    * @return string   URL for the current page
    */
    public function getURL()
    {
        $url = 'http';
        $port = '';
        if (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $url .= 's';
            if ($_SERVER['SERVER_PORT'] != 443 && str_starts_with($_SERVER['HTTP_HOST'], ':')) {
                $port = ':' . $_SERVER['SERVER_PORT'];
            }
        } else {
            if ($_SERVER['SERVER_PORT'] != 80 && str_starts_with($_SERVER['HTTP_HOST'], ':')) {
                $port = ':' . $_SERVER['SERVER_PORT'];
            }
        }
        $url .= '://' . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'];
        return $url;
    }

    /**
    * Checks if the site is registered with twitter (consumer key and secret are set)
    *
    * @return bool true, if this site is registered with twitter as an application
    */
    public function twitterRegistered()
    {
        global $prefs;
        return ! empty($prefs['socialnetworks_twitter_client_id']) && ! empty($prefs['socialnetworks_twitter_client_secret']);
    }

    /**
    * If this site is registered with twitter, it redirects to twitter to ask for a request token
    */
    public function getTwitterRequestToken()
    {
        global $prefs;

        if (! $this->twitterRegistered()) {
            return false;
        }

        $oauthlib = TikiLib::lib('oauth');
        try {
            return $oauthlib->request_token('twitter');
        } catch (\Exception $e) {
            error_log("Twitter OAuth error: " . $e->getMessage()); // @phpstan-ignore disallowedFunctions.errorLog (Twitter OAuth exception reporting)
            return false;
        }
    }

    /**
    * When the user confirms the request token, twitter redirects back to our site providing us with a request token.
    * This function receives a permanent access token for the given user and stores it in his preferences
    *
    * @param string $user  user Id of the user to store the access token for
    *
    * @return bool         true on success
    */
    public function getTwitterAccessToken($user)
    {
        if (! isset($_GET['code']) || ! isset($_SESSION['OAUTH_STATE_twitter'])) {
            return false;
        }

        $oauthlib = TikiLib::lib('oauth');

        try {
            if ($oauthlib->request_access('twitter')) {
                $token = $oauthlib->retrieve_token('twitter');
                $this->set_user_preference($user, 'twitter_token', serialize($token));
                return true;
            }
        } catch (\Exception $e) {
            error_log("Twitter OAuth error: " . $e->getMessage()); // @phpstan-ignore disallowedFunctions.errorLog (Twitter OAuth exception reporting)
        }
        return false;
    }

    /**
    * Checks if the site is registered with facebook (application id , api key and secret are set)
    *
    * @return bool true, if this site is registered with facebook as an application
    */
    public function facebookRegistered()
    {
        global $prefs;
        return ($prefs['socialnetworks_facebook_application_id'] != '' and $prefs['socialnetworks_facebook_application_secr'] != '');
    }

    /**
    * if this site is registered with facebook, it redirects to facebook to ask for a request token
    */
    public function getFacebookRequestToken()
    {
        global $prefs;
        if (! $this->facebookRegistered()) {
            return false;
        }
        $scopes = [];
        if ($prefs['socialnetworks_facebook_publish_stream'] == 'y') {
            $scopes[] = 'publish_actions';
        }
        if ($prefs['socialnetworks_facebook_manage_events'] == 'y') {
            $scopes[] = 'create_event';
            $scopes[] = 'rsvp_event';
        }
        if ($prefs['socialnetworks_facebook_sms'] == 'y') {
            $scopes[] = 'sms';
        }
        if ($prefs['socialnetworks_facebook_manage_pages'] == 'y') {
            $scopes[] = 'manage_pages';
        }
        if ($prefs['socialnetworks_facebook_email'] === 'y') {
            $scopes[] = 'email';
        }
        $scope = implode(',', $scopes);
        $url = $this->getURL();
        if (! str_starts_with($url, '?')) {
            $url = preg_replace('/\?.*/', '', $url);
        }
        $url = urlencode($url . '?request_facebook');
        $url = 'https://www.facebook.com/' . $this->graphVersion . '/dialog/oauth?client_id='
            . $prefs['socialnetworks_facebook_application_id'] . '&scope=' . $scope . '&redirect_uri=' . $url;
        header("Location: $url");
        die();
    }

    /**
    * Request access token
    *
    * @return bool|string|null
    * @throws Exception
    */
    public function getFacebookAccessToken()
    {
        global $prefs;
        //make request and get response
        $responseBody = $this->facebookGraph(
            '',
            'oauth/access_token',
            [
                'client_id' => $prefs['socialnetworks_facebook_application_id'],
                'client_secret' => $prefs['socialnetworks_facebook_application_secr'],
                // code parameter is included in $this->getURL()
                'redirect_uri' => $this->getURL()
            ],
            false,
            'GET'
        );
        $decodedBody = json_decode($responseBody);

        if (isset($decodedBody->access_token) || str_starts_with($responseBody, 'access_token=')) {
            if (isset($decodedBody->access_token)) {
                $access_token = $decodedBody->access_token;
            } else {
                $access_token = substr($responseBody, 13);
                if ($endoftoken = strpos($access_token, '&')) {
                    // Returned string may have other var like expiry
                    $access_token = substr($access_token, 0, $endoftoken);
                }
            }

            return $access_token;
        } else {
            if (! empty($decodedBody->error)) {
                Feedback::error($decodedBody->error->type . ': ' . $decodedBody->error->message);
            } else {
                Feedback::error(tr('Facebook feed data not retrieved'));
            }
            return null;
        }
    }

    public function getFacebookUserProfile($access_token)
    {
        global $prefs;

        $fields = ['id', 'name', 'first_name', 'last_name'];

        if ($prefs['socialnetworks_facebook_email'] == 'y') {
            $fields[] = 'email';
        }

        $resp = $this->facebookGraph('', 'me', ['fields' => implode(',', $fields),'access_token' => $access_token], false, 'GET');
        return json_decode($resp);
    }

    /**
    * Facebook pre-login
    *
    * @return bool
    * @throws Exception
    */
    public function facebookLoginPre()
    {
        global $prefs, $user;

        if ($prefs['socialnetworks_facebook_application_id'] == '' || $prefs['socialnetworks_facebook_application_secr'] == '') {
            return false;
        }

        $access_token = $this->getFacebookAccessToken();
        $fb_profile = $this->getFacebookUserProfile($access_token);
        if (is_object($fb_profile) && ! empty($fb_profile->id)) {
            $this->facebookLogin($access_token, $fb_profile);
        } elseif (is_object($fb_profile) && is_object($fb_profile->error)) {
            Feedback::error($fb_profile->error->type . ': ' . $fb_profile->error->message);
            return false;
        } else {
            Feedback::error(tr('Facebook profile information not retrieved'));
            return false;
        }

        return true;
    }

    /**
     *
     * This is where real login happens
     */
    public function facebookLogin($access_token, $fb_profile)
    {
        global $prefs, $user;
        $userlib = TikiLib::lib('user');

        if (! $user) {
            if ($prefs['socialnetworks_facebook_login'] != 'y') {
                return false;
            }

            $local_user = $this->getOne("select `user` from `tiki_user_preferences` where `prefName` = 'facebook_id' and `value` = ?", [$fb_profile->id]);
            if ($local_user) {
                $user = $local_user;
            } elseif ($prefs['socialnetworks_facebook_autocreateuser'] == 'y') {
                $local_user = $this->facebookCreateUser($access_token, $fb_profile);
            }

            if ($local_user) {
                $user = $local_user;
            } else {
                Feedback::errorAndDie(tra('You need to link your local account to Facebook before you can login using it'), \Laminas\Http\Response::STATUS_CODE_401);
            }

            global $user_cookie_site;
            $_SESSION[$user_cookie_site] = $user;
            $userlib->update_expired_groups();
            $this->set_user_preference($user, 'facebook_id', $fb_profile->id);
            $this->set_user_preference($user, 'facebook_token', $access_token);
            $userlib->update_lastlogin($user);
            header('Location: tiki-index.php');
            die;
        } else {
            $this->set_user_preference($user, 'facebook_id', $fb_profile->id);
            $this->set_user_preference($user, 'facebook_token', $access_token);
        }
        return true;    //do we need this?
    }


    /**
    * Creates a new user from facebook profile
    *
    * @returns $user it created
    */
    public function facebookCreateUser($access_token, $fb_profile)
    {
        global $prefs, $user;
        $userlib = TikiLib::lib('user');

        $randompass = $userlib->genPass();
        $email = $prefs['socialnetworks_facebook_email'] === 'y' ? $fb_profile->email : '';
        if ($prefs['login_is_email'] == 'y' && $email) {
            $user = $email;
        } elseif ($prefs['login_autogenerate'] == 'y') {
            $user = '';
        } else {
            $user = 'fb_' . $fb_profile->id;
        }
        $user = $userlib->add_user($user, $randompass, $email);

        if (! $user) {
            Feedback::errorAndDie(tra('We were unable to create a new user with your Facebook account. Please contact the administrator.'), \Laminas\Http\Response::STATUS_CODE_500);
        }

        $ret = $userlib->get_usertrackerid("Registered");
        $userTracker = $ret['usersTrackerId'];
        $userField = $ret['usersFieldId'];
        if ($prefs['socialnetworks_facebook_create_user_trackeritem'] == 'y' && $userTracker && $userField) {
            $definition = Tracker_Definition::get($userTracker);
            $utilities = new Services_Tracker_Utilities();
            $fields = ['ins_' . $userField => $user];
            if (! empty($prefs['socialnetworks_facebook_names'])) {
                $names = array_map('trim', explode(',', $prefs['socialnetworks_facebook_names']));
                $fields['ins_' . $names[0]] = $fb_profile->first_name;
                $fields['ins_' . $names[1]] = $fb_profile->last_name;
            }
            $utilities->insertItem(
                $definition,
                [
                    'status' => '',
                    'fields' => $fields,
                    'validate' => false,
                ]
            );
        }

        $this->set_user_preference($user, 'realName', $fb_profile->name);
        if ($prefs['socialnetworks_facebook_firstloginpopup'] == 'y') {
            $this->set_user_preference($user, 'socialnetworks_user_firstlogin', 'y');
        }
        if ($prefs['feature_userPreferences'] == 'y') {
            $fb_avatar = json_decode($this->facebookGraph('', 'me/picture', ['type' => 'square', 'width' => '480', 'redirect' => '0','access_token' => $access_token], false, 'GET'));
            $avatarlib = TikiLib::lib('avatar');
            $avatarlib->set_avatar_from_url($fb_avatar->data->url, $user);
        }

        return $user;
    }

    /**
    * Checks if the site is registered with linkedIn (client id  and secret are set)
    *
    * @return bool true, if this site is registered with linkedIn as an application
    */
    public function linkedInRegistered()
    {
        global $prefs;
        return ($prefs['socialnetworks_linkedin_client_id'] != '' and $prefs['socialnetworks_linkedin_client_secr'] != '');
    }

    public function getLinkedInRequestToken()
    {
        global $prefs;
        if (! $this->linkedInRegistered()) {
            return false;
        }
        $scopes = [];
        $scopes[] = 'r_liteprofile';
        if ($prefs['socialnetworks_linkedin_email'] == 'y') {
            $scopes[] = 'r_emailaddress';
        }
        $scope = implode(' ', $scopes);

        //generate a random state token to pass to linked in to verify on response to protect against CSRF
        $state = md5((string) rand());
        $_SESSION['LINKEDIN_REQ_STATE'] = $state;

        $url = $this->getURL();
        if (! str_starts_with($url, '?')) {
            $url = preg_replace('/\?.*/', '', $url);
        }
        $_SESSION['LINKEDIN_CALLBACK_URL'] = $url;
        $url = 'https://www.linkedin.com/uas/oauth2/authorization?response_type=code&client_id=' . $prefs['socialnetworks_linkedin_client_id'] .
            '&state=' . $state . '&scope=' . $scope . '&redirect_uri=' . $url;
        header("Location: $url");
        die();
    }

    public function getLinkedInAccessToken()
    {
        global $prefs;
        if (! $this->linkedInRegistered()) {
            return false;
        }

        $curl_request = curl_init();
        curl_setopt_array($curl_request, [
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://www.linkedin.com/uas/oauth2/accessToken',
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => http_build_query([
                client_secret => $prefs['socialnetworks_linkedin_client_secr'],
                client_id => $prefs['socialnetworks_linkedin_client_id'],
                client_secret => $prefs['socialnetworks_linkedin_client_secr'],
                grant_type => "authorization_code",
                redirect_uri => $_SESSION['LINKEDIN_CALLBACK_URL'],
                code => $_SESSION['LINKEDIN_AUTH_CODE'],
            ], '', '&'),
        ]);

        $curl_result = curl_exec($curl_request);
        $ret = json_decode($curl_result);

        if (empty($curl_result)) {
            Feedback::errorAndDie(tra('We were unable to connect to your LinkedIn account. Please contact the administrator.'), \Laminas\Http\Response::STATUS_CODE_500);
        }

        $_SESSION['LINKEDIN_ACCESS_TOKEN'] = $ret->access_token;
        $_SESSION['LINKEDIN_ACCESS_TOKEN_EXPIRY'] = time() + $ret->expires_in;

        $this->linkedInLogin();
        return true;
    }

    public function linkedInLogin()
    {
        global $user, $prefs;
        $userlib = TikiLib::lib('user');
        $curl = curl_init();

        $data = [
            "oauth2_access_token" => $_SESSION['LINKEDIN_ACCESS_TOKEN']
        ];

        $data2 = $data + [
                "projection" => "(id,firstName,lastName,profilePicture(displayImage~:playableStreams))"
        ];
        $url = "https://api.linkedin.com/v2/me";
        $url = sprintf("%s?%s", $url, http_build_query($data2, '', '&'));
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        $linkedin_info = json_decode($result);

        if (isset($linkedin_info->serviceErrorCode)) {
            Feedback::errorAndDie(tra('We were unable to log you in using your LinkedIn account. Please contact the administrator.'), \Laminas\Http\Response::STATUS_CODE_500);
        }

        if ($prefs['socialnetworks_linkedin_email'] == 'y') {
            $data3 = $data + [
                    "q" => "members",
                    "projection" => "(elements*(handle~))"
                ];

            $url = "https://api.linkedin.com/v2/emailAddress";
            $url = sprintf("%s?%s", $url, http_build_query($data3, '', '&'));
            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

            $result = curl_exec($curl);

            $linkedin_email = json_decode($result);

            if (isset($linkedin_email->serviceErrorCode)) {
                Feedback::errorAndDie(tra('We were unable to log you in using your LinkedIn account. Please contact the administrator.'), \Laminas\Http\Response::STATUS_CODE_500);
            }
        }

        $linkedin_locale = $linkedin_info->firstName->preferredLocale->language;
        if (! empty($linkedin_info->firstName->preferredLocale->country)) {
            $linkedin_locale .= '_' . $linkedin_info->firstName->preferredLocale->country;
        }

        if (! $user) {
            if ($prefs['socialnetworks_linkedin_login'] != 'y') {
                return false;
            }
            $local_user = $this->getOne("select `user` from `tiki_user_preferences` where `prefName` = 'linkedin_id' and `value` = ?", [$linkedin_info->id]);
            if ($local_user) {
                $user = $local_user;
            } elseif ($prefs['socialnetworks_linkedin_autocreateuser'] == 'y') {
                $randompass = $userlib->genPass();
                $ha = 'handle~';
                $email = $prefs['socialnetworks_linkedin_email'] === 'y' ? $linkedin_email->elements[0]->$ha->emailAddress : '';
                if ($prefs['login_is_email'] == 'y' && $email) {
                    $user = $email;
                } elseif ($prefs['login_autogenerate'] == 'y') {
                        $user = '';
                } else {
                    $user = 'li_' . $linkedin_info->id;
                }
                $user = $userlib->add_user($user, $randompass, $email);

                if (! $user) {
                    Feedback::errorAndDie(tra('We were unable to log you in using your LinkedIn account. Please contact the administrator.'), \Laminas\Http\Response::STATUS_CODE_500);
                }
                //Checks if user tracker is used and if it is, then set the names as per the info
                $ret = $userlib->get_usertrackerid("Registered");
                $userTracker = $ret['usersTrackerId'];
                $userField = $ret['usersFieldId'];
                if ($prefs['socialnetworks_linkedin_create_user_trackeritem'] == 'y' && $userTracker && $userField) {
                    $definition = Tracker_Definition::get($userTracker);
                    $utilities = new Services_Tracker_Utilities();
                    $fields = ['ins_' . $userField => $user];
                    if (! empty($prefs['socialnetworks_linkedin_names'])) {
                        $names = array_map('trim', explode(',', $prefs['socialnetworks_linkedin_names']));
                        $fields['ins_' . $names[0]] = $linkedin_info->firstName->localized->$linkedin_locale;
                        $fields['ins_' . $names[1]] = $linkedin_info->lastName->localized->$linkedin_locale;
                    }
                    $utilities->insertItem(
                        $definition,
                        [
                            'status' => '',
                            'fields' => $fields,
                            'validate' => false,
                        ]
                    );
                }

                $this->set_user_preference($user, 'realName', $linkedin_info->firstName->localized->$linkedin_locale . ' ' . $linkedin_info->lastName->localized->$linkedin_locale);
                if ($prefs['feature_userPreferences'] == 'y') {
                    // Get largest profile image up to 480px in width
                    $di = 'displayImage~';
                    $si = 'com.linkedin.digitalmedia.mediaartifact.StillImage';
                    $displayImages = array_reverse($linkedin_info->profilePicture->$di->elements);
                    $displayImage = '';
                    foreach ($displayImages as $i) {
                        if ($i->data->$si->storageSize->width <= 480) {
                            $displayImage = $i->identifiers[0]->identifier;
                            break;
                        }
                    }
                    if ($displayImage) {
                        $avatarlib = TikiLib::lib('avatar');
                        $avatarlib->set_avatar_from_url($displayImage, $user);
                    }
                }
            } else {
                $_SESSION['loginfrom'] = str_replace('tiki-socialnetworks_linkedin.php', 'tiki-socialnetworks.php', $_SERVER['REQUEST_URI']);
                Feedback::errorAndDie(tra('You need to link your local account to LinkedIn before you can login using it'), \Laminas\Http\Response::STATUS_CODE_500);
            }
            global $user_cookie_site;
            $_SESSION[$user_cookie_site] = $user;
            $userlib->update_expired_groups();
            $this->set_user_preference($user, 'linkedin_id', $linkedin_info->id);
            $this->set_user_preference($user, 'linkedin_token', $_SESSION['LINKEDIN_ACCESS_TOKEN']);
            $userlib->update_lastlogin($user);
            header('Location: tiki-index.php');
            die;
        } else {
            $this->set_user_preference($user, 'linkedin_id', $linkedin_info->id);
            $this->set_user_preference($user, 'linkedin_token', $_SESSION['LINKEDIN_ACCESS_TOKEN']);
        }
        return true;
    }

    /**
    * Sends a tweet via Twitter
    *
    * @param string    $message    Message to send
    * @param string    $user       UserId of the user to send the message for
    * @param bool      $cutMessage Should the message be cut if it is longer than 140 characters,
    *                              if set to false, an error will be returned if the message is longer than 140 characters
    *
    * @return int  -1 if the user did not authorize the site with twitter,
    *                          -2, if the message is longer than 140 characters,
                          a negative number corresponding to the HTTP response codes from Twitter
                          (see X Developer Platform documentation)
    *                              or a positive tweet id of the message
    */
    public function tweet($message, $user, $cutMessage = false)
    {
        global $prefs;
        $token = $this->get_user_preference($user, 'twitter_token', '');
        if (! $token) {
            $this->add_log('tweet', 'user not registered with twitter');
            return -1;
        }
        if ($cutMessage) {
            $message = substr($message, 0, 280);
        } else {
            if (strlen($message) > 280) {
                $this->add_log('tweet', 'message too long');
                return -2;
            }
        }
        $token = unserialize($token);
        $accessToken = is_object($token) && method_exists($token, 'getToken') ? $token->getToken() : null;

        if (! $accessToken) {
            $this->add_log('tweet', 'invalid access token');
            return -3;
        }

        $this->options['callbackUrl'] = $this->getURL();
        $this->options['consumerKey'] = $prefs['socialnetworks_twitter_client_id'];
        $this->options['consumerSecret'] = $prefs['socialnetworks_twitter_client_secret'];
        $httpClient = TikiLib::lib('tiki')->get_http_client('https://api.twitter.com/2/tweets');
        $httpClient->setHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ]);
        $httpClient->setMethod('POST');
        $httpClient->setRawBody(json_encode(['text' => $message]));

        try {
            $response = $httpClient->send();
            if (! $response->isSuccess()) {
                $this->add_log('tweet', 'Twitter response failed: ' . $response->getBody());
                return -4;
            }

            $json = json_decode($response->getBody(), true);
            return $json['data']['id'] ?? 1;
        } catch (Exception $e) {
            $this->add_log('tweet', 'twitter error ' . $e->getMessage());
            return -5;
        }
    }

    /**
    * Deletes a tweet with the given tweet id
    *
    * @param int       $id     Id of the tweet to delete
    * @param string    $user       UserId of the user who sent the tweet
    *
    * @return bool                 true on success
    */
    public function destroyTweet($id, $user)
    {
        global $prefs;
        $token = $this->get_user_preference($user, 'twitter_token', '');
        if (! $token) {
            return false;
        }
        $token = unserialize($token);
            $accessToken = is_object($token) && method_exists($token, 'getToken') ? $token->getToken() : null;

        if (! $accessToken) {
            $this->add_log('destroyTweet', 'invalid or missing access token');
            return false;
        }

        $url = "https://api.twitter.com/2/tweets/{$id}";

        $client = TikiLib::lib('tiki')->get_http_client($url);
        $client->setMethod('DELETE');
        $client->setHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ]);
        try {
            $response = $client->send();
            if (! $response->isSuccess()) {
                $this->add_log('destroyTweet', 'Twitter response error: ' . $response->getBody());
                return false;
            }

            return true;
        } catch (\Exception $e) {
            $this->add_log('destroyTweet', 'exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
    * Talking to Facebook via the graph api at "https://graph.facebook.com/" using fsockopen
    *
    * @param    string $user     userId of the user to send the request for
    * @param    string $action   directory/file part of the graph api URL
    * @param    array  $params   parameters for the api call, each entry is one element submitted in the request
    * @param    bool   $addtoken should the access token be added to the parameters if the calling function did not pass this parameter
    *
    * @param string    $method
    *
    * @return    string                body of the response page (json encoded object)
    * @throws Exception
    */
    public function facebookGraph($user, $action, $params, $addtoken = true, $method = 'POST')
    {
        if (! $this->facebookRegistered()) {
            $this->add_log('facebookGraph', 'application not set up');
            return false;
        }
        if ($addtoken) {
            $token = $this->get_user_preference($user, 'facebook_token', '');
            if ($token == '') {
                $this->add_log('facebookGraph', 'user not registered with facebook');
                return false;
            }

            if (! isset($params['access_token'])) {
                $params['access_token'] = $token;
            }
        }

        // set up http client to make request
        $url = 'https://graph.facebook.com/' . $this->graphVersion . '/' . $action;
        if (! empty($params) && is_array($params) && $method === 'GET') {
            // set url this way instead of using setUri and setParameterGet to avoid failure in some environments
            $url .= '?' . urldecode(http_build_query($params, '', '&'));
        }
        $client = TikiLib::lib('tiki')->get_http_client($url);
        $client->setMethod($method);
        if (! empty($params) && is_array($params) && $method === 'POST') {
            $client->setParameterPost($params);
        }
        // make request
        $response = $client->send();
        return $response->getBody();
    }

    /**
    *
    * publish a message (status or link with more options) on facebook
    *
    * @param string    $user       userId of the user to send for
    * @param string    $message    message/main text to send
    * @param string    $url        optional URL to pass along
    * @param string    $text       optional text to show for the URL
    * @param string    $caption    optional caption of the message accompanying the url
    * @param string    $privacy    currently unused as I did not find the docu on how to use the privacy settings
    *
    * @return  string|bool         false on error, object Id of the message on success
    */
    public function facebookWallPublish($user, $message, $url = '', $text = '', $caption = '', $privacy = '')
    {
        $params = [];
        if ($url != '') {
            $params['link'] = $url;
            if ($text != '') {
                $params['name'] = $text;
            }
            if ($caption != '') {
                $params['caption'] = $caption;
            }
            $params['description'] = $message;
        } else {
            $params['message'] = substr($message, 0, 400);
        }
        $ret = $this->facebookGraph($user, 'me/feed/', $params);
        $result = json_decode($ret);
        if (isset($result->id)) {
            return $result->id;
        } else {
            return false;
        }
    }

    /**
    * like an object on facebook
    *
    * @param string    $user       userId of the user to send for
    * @param string    $facebookId id of the object to like
    *
    * @return  string|bool         false on error, object Id of the message on success
    */
    public function facebookLike($user, $id)
    {
        $params = [];
        $ret = $this->facebookGraph($user, "$id/likes/", $params);
        return json_decode($ret);
    }

    /**
    * Talking to bit.ly api at "http://api.bit.ly/" using Zend
    *
    * @param   string  $user       userId of the user to send the request for
    * @param   string  $action     directory/file part of the api URL
    * @param   array   $params     parameters for the api call, each entry is one element submitted in the request
    *
    * @return  string              body of the response page (json encoded object)
    */
    public function bitlyApi($user, $action, $params)
    {
        global $prefs;

        if ($prefs['socialnetworks_bitly_sitewide'] != 'y') {
            $login = $this->get_user_preference($user, 'bitly_login', '');
        }
        if ($login == '') {
            $login = $prefs['socialnetworks_bitly_login'];

            if ($login == '') {
                return false;
            }

            $key = $prefs['socialnetworks_bitly_key'];
        } else {
            $key = $this->get_user_preference($user, 'bitly_key', '');
        }
        if ($key == '') {
            return false;
        }

        $httpclient = TikiLib::lib('tiki')->get_http_client();
        $httpclient->setUri("http://api.bit.ly/$action");

        $params['login'] = $login;
        $params['apiKey'] = $key;
        $httpclient->setParameterGet($params);

        $response = $httpclient->send();
        if (! $response->isSuccess()) {
            return false;
        }
        return $response->getBody();
    }

    /**
    * Asks bit.ly to shorten an url for us
    *
    * @param $user
    * @param $url
    */
    public function bitlyShorten($user, $url)
    {
        $query = 'SELECT * FROM `tiki_url_shortener` WHERE `longurl_hash`=MD5(?)';

        $result = $this->query($query, [$url]);

        while ($data = $result->fetchRow()) {
            if ($url == $data['longurl']) {
                return $data['shorturl'];
            }
        }

        $params = [
                'version' => '2.0.1',
                'longUrl' => $url,
                'history' => '1',
        ];

        $ret = $this->bitlyApi($user, 'shorten', $params);
        if ($ret == false) {
            return false;
        }

        $ret = json_decode($ret);
        if ($ret->errorCode != 0) {
            return false;
        }

        $shorturl = $ret->{'results'}->{$url}->{'shortUrl'};
        $query = 'INSERT INTO `tiki_url_shortener` SET `user`=?, `longurl`=?, `longurl_hash`=MD5(?), `service`=?, `shorturl`=?';
        $this->query($query, [$user, $url, $url, 'bit.ly', $shorturl]);

        return $shorturl;
    }

    /**
    * Get Timeline off Twitter
    * @param  string   $user       Tiki username to get timeline for
    * @param  string   $timelineType   Timeline to get: public/friends/search - Default: public
    * @param  string   $search     Search string
    * @return string|int           Returns an array of tweets on success, or an internal error code:
    *                              -1 = User not authorized
    *                              -2 = Invalid or missing token
    *                              -3 = Twitter API responded with error
    *                              -4 = HTTP client exception
    *
    * Note: These codes are maintained for internal logic and compatibility,
    * but Twitter/X no longer uses or documents numerical error codes like those found at
    * https://developer.x.com/en/docs/twitter-api (deprecated).
    */
    public function getTwitterTimeline($user, $timelineType = 'public', $search = 'tikiwiki')
    {
        global $prefs;
        $token = $this->get_user_preference($user, 'twitter_token', '');
        if (! $token) {
            $this->add_log('tweet', 'user not registered with twitter');
            return -1;
        }
        $token = unserialize($token);
        $accessToken = is_object($token) && method_exists($token, 'getToken') ? $token->getToken() : null;


        if (! $accessToken) {
            $this->add_log('tweet', 'invalid access token');
            return -2;
        }

        $url = $timelineType === 'search'
            ? 'https://api.twitter.com/2/tweets/search/recent?query=' . urlencode($search)
            : 'https://api.twitter.com/2/users/me/tweets';
        $httpClient = TikiLib::lib('tiki')->get_http_client($url);
        $httpClient->setHeaders(['Authorization' => 'Bearer ' . $accessToken]);


        try {
            $response = $httpClient->send();
            if (! $response->isSuccess()) {
                $this->add_log('tweet', 'Twitter response error: ' . $response->getBody());
                return -3;
            }

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            $this->add_log('tweet', 'exception: ' . $e->getMessage());
            return -4;
        }
    }

    /**
    *
    * get the public Facebook timeline of a user
    *
    * @param string $user     Tiki username to get facebook wall for
    * @param bool   $addtoken should the access token be added to the parameters if the calling function did not pass this parameter
    *
    * @return        string|bool    false on error, JSON encoded Facebook response on success
    * @throws Exception
    */
    public function facebookGetWall($user, $addtoken = true)
    {
        if (! $this->facebookRegistered()) {
            $this->add_log('facebookGraph', 'application not set up');
            return false;
        }
        if ($addtoken) {
            $token = $this->get_user_preference($user, 'facebook_token', '');
            // expires will make the token fail
            $token = preg_replace('/&expires=(\d)*/', '', $token);
            $token = urlencode($token);
            if ($token == '') {
                $this->add_log('facebookGraph', 'user not registered with facebook');
                return -1;
            }
        }

        // set up http client to make request
        $url = 'https://graph.facebook.com/' . $this->graphVersion . '/me/feed';
        if ($addtoken) {
            $url .= '?access_token=' . $token;
        }
        $client = TikiLib::lib('tiki')->get_http_client($url);
        // make request
        $response = $client->send();
        $body = $response->getBody();
        $result = json_decode($body);
        // process result
        if ($result && $result->data) {
            foreach ($result->data as $key => $value) {
                if (isset($result->data[$key]->message)) {
                    $feed[$key]["message"] = $result->data[$key]->message;
                    $feed[$key]["type"] = "message";
                } elseif (isset($result->data[$key]->story)) {
                    $feed[$key]["message"] = $result->data[$key]->story;
                    $feed[$key]["type"] = "story";
                }
                if (isset($result->data[$key]->from->name)) {
                    $feed[$key]["fromName"] = $result->data[$key]->from->name;
                }
                if (isset($result->data[$key]->from->id)) {
                    $feed[$key]["fromId"] = $result->data[$key]->from->id;
                }
                $feed[$key]["created_time"]
                    = $result->data[$key]->created_time;
                $id = $result->data[$key]->id;
                $id = str_replace("_", "/posts/", $id);
                $feed[$key]["link"] = "https://www.facebook.com/" . $id;
            }
            return $feed;
        } else {
            if (! empty($result->error)) {
                Feedback::error($result->error->type . ': ' . $result->error->message);
            } else {
                Feedback::error(tr('Facebook feed data not retrieved'));
            }
            return false;
        }
    }
}

global $socialnetworkslib;

$socialnetworkslib = new SocialNetworksLib();
