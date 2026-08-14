<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Services_LanguageCheck_Controller
{
    public const DEFAULT_LANGUAGE_TOOL_URL = 'http://localhost:8081';

    private $languageToolUrl;
    private $httpClient;

    public function __construct()
    {
        global $prefs;
        $this->languageToolUrl = self::getLanguageToolUrl($prefs);
        $this->httpClient = TikiLib::lib('tiki')->get_http_client();
    }

    /**
     * Get LanguageTool URL from preferences or return default
     *
     * @param array|null $prefs Preferences array (if null, uses global $prefs)
     * @return string LanguageTool server URL
     */
    public static function getLanguageToolUrl(?array $prefs = null): string
    {
        if ($prefs === null) {
            global $prefs;
        }
        return isset($prefs['language_tool_url']) && $prefs['language_tool_url']
            ? $prefs['language_tool_url']
            : self::DEFAULT_LANGUAGE_TOOL_URL;
    }

    /**
     * Check text for grammar and spelling errors using LanguageTool
     *
     * @param JitFilter $input
     * @return array
     */
    public function action_check_text($input)
    {
        global $prefs;

        // Check if language checking is enabled
        if ($prefs['feature_language_check'] !== 'y') {
            throw new Services_Exception(tr('Language checking feature is not enabled.'), 403);
        }

        $text = (string) $input->text->text();
        $language = (string) ($input->language->word() ?: 'auto');
        $username = (string) ($input->username->word() ?: '');

        if (empty($text)) {
            return ['result' => []];
        }

        try {
            $errors = $this->checkTextWithLanguageTool((string) $text, (string) $language, (string) $username);
            return ['result' => $errors];
        } catch (Exception $e) {
            throw new Services_Exception(tr('Error checking text: %0', $e->getMessage()), 500);
        }
    }

    /**
     * Get supported languages from LanguageTool
     *
     * @param JitFilter $input
     * @return array
     */
    public function action_get_languages($input)
    {
        global $prefs;

        if ($prefs['feature_language_check'] !== 'y') {
            throw new Services_Exception(tr('Language checking feature is not enabled.'), 403);
        }

        try {
            $languages = $this->getSupportedLanguages();
            return ['result' => $languages];
        } catch (Exception $e) {
            throw new Services_Exception(tr('Error getting languages: %0', $e->getMessage()), 500);
        }
    }

    /**
     * Check text using LanguageTool API
     *
     * @param string $text
     * @param string $language
     * @param string $username
     * @return array
     */
    private function checkTextWithLanguageTool($text, $language, $username)
    {
        $url = $this->languageToolUrl . '/v2/check';

        $params = [
            'text' => $text,
            'language' => $language,
        ];

        if (! empty($username)) {
            $params['username'] = $username;
        }

        $client = clone $this->httpClient;
        $client->setUri($url);
        $client->setMethod(Laminas\Http\Request::METHOD_POST);
        $client->setParameterPost($params);
        $client->setOptions(['timeout' => 10]);

        $response = $client->send();

        if (! $response->isSuccess()) {
            throw new Exception('LanguageTool API request failed: ' . $response->getStatusCode());
        }

        $result = json_decode($response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid response from LanguageTool API');
        }

        return $result['matches'] ?? [];
    }

    /**
     * Get supported languages from LanguageTool
     *
     * @return array
     */
    private function getSupportedLanguages()
    {
        $url = $this->languageToolUrl . '/v2/languages';

        $client = clone $this->httpClient;
        $client->setUri($url);
        $client->setMethod(Laminas\Http\Request::METHOD_GET);
        $client->setOptions(['timeout' => 30]);

        $response = $client->send();

        if (! $response->isSuccess()) {
            throw new Exception('LanguageTool API request failed: ' . $response->getStatusCode());
        }

        $result = json_decode($response->getBody(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid response from LanguageTool API');
        }

        return $result;
    }
}
