<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// author : aris002@yahoo.co.uk

namespace TikiLib\Socnets\SocnetsDataLib;

require_once('lib/socnets/Util.php');
use TikiLib\Socnets\Util\Util;
require_once('lib/socnets/LLOG.php');
require_once('lib/socnets/PrefsGen.php');
use TikiLib\Socnets\PrefsGen\PrefsGen;
require_once('lib/core/Services/Exception/SocnetsProviderNotConfigured.php');
require_once('lib/core/Services/Exception/SocnetsTokenNotFound.php');
require_once('lib/core/Services/Exception/SocnetsApi.php');

use Hybridauth\HttpClient;
use TikiLib;
use Feedback;
use TikiLib\Core\Services\Exception\SocnetsApiException;
use TikiLib\Core\Services\Exception\SocnetsProviderNotConfiguredException;
use TikiLib\Core\Services\Exception\SocnetsTokenNotFoundException;

/**
 * Class to retrieve data from social networks using HybridAuth adapters
 * This replaces the old socialnetworkslib methods
 */
class SocnetsDataLib extends \TikiLib
{
    protected static string $socPrefix = 'socnets_';
    private string $graphVersion = 'v18.0'; // Updated Facebook Graph API version

    /**
     * Get Facebook wall/feed data for a user OR page using HybridAuth token
     *
     * @param string $user Tiki username
     * @param string $providerName Provider name (e.g., 'Facebook')
     * @param int $page Page number (for pagination)
     * @param int $limit Number of posts per page
     * @param string $pageId Facebook Page ID (optional, if displaying a page instead of user feed)
     * @return array Feed data
     * @throws SocnetsProviderNotConfiguredException
     * @throws SocnetsTokenNotFoundException
     * @throws SocnetsApiException
     */
    public function getFacebookWall(string $user, string $providerName = 'Facebook', int $page = 1, int $limit = 10, string $pageId = ''): array
    {
        global $prefs;

        $namedprefix = self::$socPrefix . $providerName;

        // Check if Facebook is registered/configured
        if (! $this->isProviderRegistered($providerName)) {
            $message = tr('Facebook is not configured. Please contact your administrator.');
            Feedback::error($message);
            throw new SocnetsProviderNotConfiguredException('Facebook');
        }

        // Get user's Facebook token from preferences
        $token = $this->get_user_preference($user, $namedprefix . '_token', '');

        if (empty($token)) {
            $message = tr('You need to connect your Facebook account first.');
            Feedback::error($message);
            throw new SocnetsTokenNotFoundException('Facebook');
        }

        try {
            // Use Facebook Graph API to get feed (user or page)
            if (! empty($pageId)) {
                // Get page feed
                $url = 'https://graph.facebook.com/' . $this->graphVersion . '/' . $pageId . '/feed';
            } else {
                // Get user feed
                $url = 'https://graph.facebook.com/' . $this->graphVersion . '/me/feed';
            }

            $url .= '?access_token=' . urlencode($token);
            $url .= '&fields=id,message,story,from,created_time,picture,full_picture,type,status_type,attachments{media,type,title,description,url},permalink_url';
            $url .= '&limit=' . ($limit * $page); // Get enough posts for pagination
            // Facebook returns in reverse chronological order by default (newest first)

            $client = TikiLib::lib('tiki')->get_http_client($url);
            $response = $client->send();

            if (! $response->isSuccess()) {
                // Show detailed error for debugging
                $errorBody = $response->getBody();
                $errorData = json_decode($errorBody);
                if (! empty($errorData->error)) {
                    $message = 'Facebook API Error: ' . $errorData->error->message . ' (Code: ' . $errorData->error->code . ')';
                } else {
                    $message = tr('Error retrieving Facebook feed. You may need to reconnect your account.');
                }
                Feedback::error($message);
                throw new SocnetsApiException($message);
            }

            $body = $response->getBody();
            $result = json_decode($body);

            // Process result
            if ($result && isset($result->data)) {
                $feed = [];
                $index = 0;
                foreach ($result->data as $key => $value) {
                    // Set message content
                    if (isset($value->message)) {
                        $feed[$index]["message"] = $value->message;
                        $feed[$index]["type"] = $value->type ?? "message";
                    } elseif (isset($value->story)) {
                        $feed[$index]["message"] = $value->story;
                        $feed[$index]["type"] = $value->type ?? "story";
                    } else {
                        // Post without text - leave empty
                        $feed[$index]["message"] = '';
                        $feed[$index]["type"] = $value->type ?? "post";
                    }

                    // Set author info
                    if (isset($value->from->name)) {
                        $feed[$index]["fromName"] = $value->from->name;
                    } else {
                        $feed[$index]["fromName"] = "";
                    }

                    if (isset($value->from->id)) {
                        $feed[$index]["fromId"] = $value->from->id;
                    } else {
                        $feed[$index]["fromId"] = "";
                    }

                    // Set timestamp
                    $feed[$index]["created_time"] = $value->created_time ?? '';

                    // Set post type
                    $feed[$index]["post_type"] = $value->type ?? '';
                    $feed[$index]["status_type"] = $value->status_type ?? '';

                    // Set link to Facebook post (use permalink_url if available)
                    if (isset($value->permalink_url)) {
                        $feed[$index]["link"] = $value->permalink_url;
                    } elseif (isset($value->id)) {
                        $id = $value->id;
                        $id = str_replace("_", "/posts/", $id);
                        $feed[$index]["link"] = "https://www.facebook.com/" . $id;
                    } else {
                        $feed[$index]["link"] = '';
                    }

                    // Set image (prefer full_picture if available, otherwise use attachment media)
                    $feed[$index]["image"] = '';
                    $feed[$index]["has_image"] = false;

                    if (! empty($value->full_picture)) {
                        $feed[$index]["image"] = $value->full_picture;
                        $feed[$index]["has_image"] = true;
                    } elseif (isset($value->attachments->data[0]->media->image)) {
                        $feed[$index]["image"] = $value->attachments->data[0]->media->image->src ?? '';
                        $feed[$index]["has_image"] = ! empty($feed[$index]["image"]);
                    }

                    // Set attachments ONLY for non-photo types (to avoid duplication)
                    $feed[$index]["attachments"] = [];
                    if (isset($value->attachments->data) && $value->type !== 'photo') {
                        foreach ($value->attachments->data as $att) {
                            // Only add non-photo attachments or info attachments
                            if (($att->type ?? '') !== 'photo') {
                                $attachment = [
                                    'type' => $att->type ?? '',
                                    'title' => $att->title ?? '',
                                    'description' => $att->description ?? '',
                                    'url' => $att->url ?? '',
                                ];
                                $feed[$index]["attachments"][] = $attachment;
                            }
                        }
                    }

                    $index++;
                }

                // Apply pagination
                $offset = ($page - 1) * $limit;
                $feed = array_slice($feed, $offset, $limit);

                return $feed;
            } else {
                if (! empty($result->error)) {
                    $message = $result->error->type . ': ' . $result->error->message;
                } else {
                    $message = tr('Facebook feed data not retrieved');
                }
                Feedback::error($message);
                throw new SocnetsApiException($message);
            }
        } catch (\Throwable $e) {
            if ($e instanceof SocnetsApiException) {
                throw $e;
            }

            $message = tr('Error connecting to Facebook: ') . $e->getMessage();
            Feedback::error($message);
            throw new SocnetsApiException($message, 0, $e);
        }
    }

    /**
     * Get Twitter timeline for a user using HybridAuth token
     *
     * @param string $user Tiki username
     * @param string $providerName Provider name (e.g., 'Twitter')
     * @return array Timeline data
     * @throws SocnetsProviderNotConfiguredException
     * @throws SocnetsTokenNotFoundException
     * @throws SocnetsApiException
     */
    public function getTwitterTimeline(string $user, string $providerName = 'Twitter'): array
    {
        global $prefs;

        $namedprefix = self::$socPrefix . $providerName;

        // Check if Twitter is registered/configured
        if (! $this->isProviderRegistered($providerName)) {
            $message = tr('Twitter is not configured. Please contact your administrator.');
            Feedback::error($message);
            throw new SocnetsProviderNotConfiguredException('Twitter');
        }

        // Get user's Twitter token from preferences
        $token = $this->get_user_preference($user, $namedprefix . '_token', '');

        if (empty($token)) {
            $message = tr('You need to connect your Twitter account first.');
            Feedback::error($message);
            throw new SocnetsTokenNotFoundException('Twitter');
        }

        try {
            // Use Twitter API v2 to get user's timeline
            $url = 'https://api.twitter.com/2/users/me/tweets';
            $url .= '?max_results=10&tweet.fields=created_at,author_id';

            $client = TikiLib::lib('tiki')->get_http_client($url);
            $client->setHeaders(['Authorization' => 'Bearer ' . $token]);

            $response = $client->send();

            if (! $response->isSuccess()) {
                $message = tr('Unable to retrieve Twitter timeline.');
                Feedback::error($message);
                throw new SocnetsApiException($message);
            }

            $decodedResponse = json_decode($response->getBody(), true);

            if (is_array($decodedResponse)) {
                return $decodedResponse;
            }

            $message = tr('Unexpected response received from Twitter.');
            Feedback::error($message);
            throw new SocnetsApiException($message);
        } catch (\Throwable $e) {
            $message = tr('Error connecting to Twitter: ') . $e->getMessage();
            Feedback::error($message);
            throw new SocnetsApiException($message, 0, $e);
        }
    }

    /**
     * Check if a provider is registered (has app_id and app_secret configured)
     *
     * @param string $providerName Provider name
     * @return bool
     */
    private function isProviderRegistered(string $providerName): bool
    {
        global $prefs;
        $namedprefix = self::$socPrefix . $providerName;

        return (! empty($prefs[$namedprefix . '_app_id']) &&
                ! empty($prefs[$namedprefix . '_app_secret']));
    }

    /**
     * Get social network data via API call
     * Generic method for making authenticated API calls to social networks
     *
     * @param string $user Tiki username
     * @param string $providerName Provider name (e.g., 'Facebook', 'Twitter')
     * @param string $endpoint API endpoint
     * @param array $params Additional parameters
     * @param string $method HTTP method (GET, POST, etc.)
     * @return string API response body
     * @throws SocnetsTokenNotFoundException
     * @throws SocnetsApiException
     */
    public function getSocialNetworkData(string $user, string $providerName, string $endpoint, array $params = [], string $method = 'GET'): string
    {
        global $prefs;

        $namedprefix = self::$socPrefix . $providerName;

        // Get user's token from preferences
        $token = $this->get_user_preference($user, $namedprefix . '_token', '');

        if (empty($token)) {
            $message = tr('You need to connect your account first.');
            Feedback::error($message);
            throw new SocnetsTokenNotFoundException($providerName);
        }

        try {
            $client = TikiLib::lib('tiki')->get_http_client($endpoint);

            // Add authorization header (most providers use Bearer token)
            $client->setHeaders(['Authorization' => 'Bearer ' . $token]);

            $client->setMethod($method);

            if (! empty($params) && $method === 'GET') {
                $endpoint .= '?' . http_build_query($params);
                $client->setUri($endpoint);
            } elseif (! empty($params) && $method === 'POST') {
                $client->setParameterPost($params);
            }

            $response = $client->send();

            if (! $response->isSuccess()) {
                $message = tr('Unable to retrieve social network data.');
                Feedback::error($message);
                throw new SocnetsApiException($message);
            }

            return $response->getBody();
        } catch (\Throwable $e) {
            $message = tr('Error connecting to social network: ') . $e->getMessage();
            Feedback::error($message);
            throw new SocnetsApiException($message, 0, $e);
        }
    }
}
