<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

/**
 * Tiki\Sections - Section Configuration & Object Detection
 *
 * Defines all application sections and their metadata for permissions,
 * toolbars, and object type mapping. Provides automatic object detection
 * from request parameters.
 *
 * USAGE:
 * - Access section metadata: $sections = Sections::getSections()
 * - Detect current object: $object = Sections::currentObject($_REQUEST)
 * - Use constants ex:
 *   $section = Sections::SECTION_WIKI;
 *
 * Each section defines required features, request parameters, and object types
 * for proper permission checking and UI behavior.
 */

namespace Tiki;

use TikiLib;

class Sections
{
    public const SECTION_WIKI_PAGE = 'wiki page';
    public const SECTION_BLOGS = 'blogs';
    public const SECTION_FILE_GALLERIES = 'file_galleries';
    public const SECTION_FORUMS = 'forums';
    public const SECTION_FORUM = 'forum';
    public const SECTION_CMS = 'cms';
    public const SECTION_TRACKERS = 'trackers';
    public const SECTION_MY_TIKI = 'mytiki';
    public const SECTION_USER_MESSAGES = 'user_messages';
    public const SECTION_WEBMAIL = 'webmail';
    public const SECTION_CONTACTS = 'contacts';
    public const SECTION_FAQS = 'faqs';
    public const SECTION_QUIZZES = 'quizzes';
    public const SECTION_POLL = 'poll';
    public const SECTION_SURVEYS = 'surveys';
    public const SECTION_FEATURED_LINKS = 'featured_links';
    public const SECTION_DIRECTORY = 'directory';
    public const SECTION_CALENDAR = 'calendar';
    public const SECTION_CATEGORIES = 'categories';
    public const SECTION_HTML_PAGES = 'html_pages';
    public const SECTION_NEWSLETTERS = 'newsletters';
    public const SECTION_VALIDATE_EMAIL = 'validate email';
    public const SECTION_WIKI = 'wiki';
    public const SECTION_ADMIN = 'admin';
    public const SECTION_DOCS = 'docs';
    public const SECTION_FREETAGS = 'freetags';
    public const SECTION_GLOBAL = 'global';
    public const SECTION_LIVESUPPORT = 'livesupport';
    public const SECTION_SEARCH = 'search';
    public const SECTION_SHARE = 'share';
    public const SECTION_SHEET = 'sheet';
    public const SECTION_CHAT = 'chat';
    public const SECTION_ADMIN_LAYOUT = 'admin layout';
    // 'admin layout' section is special as it is used for the admin/management pages that should behave as control panels in the Unified Admin Backend UI (if there is a need to exclude an admin page from that layout, do NOT assign it to this one)

    private static $currentSection;

    /**
     * Callbacks triggered when the current section changes
     * @var array
     */
    private static $sectionChangeCallbacks = [];

    /**
     * Returns all defined sections.
     *
     * @return array
     */
    public static function getAllSections(): array
    {
        return [
            self::SECTION_WIKI_PAGE,
            self::SECTION_BLOGS,
            self::SECTION_FILE_GALLERIES,
            self::SECTION_FORUMS,
            self::SECTION_FORUM,
            self::SECTION_CMS,
            self::SECTION_TRACKERS,
            self::SECTION_MY_TIKI,
            self::SECTION_USER_MESSAGES,
            self::SECTION_WEBMAIL,
            self::SECTION_CONTACTS,
            self::SECTION_FAQS,
            self::SECTION_QUIZZES,
            self::SECTION_POLL,
            self::SECTION_SURVEYS,
            self::SECTION_FEATURED_LINKS,
            self::SECTION_DIRECTORY,
            self::SECTION_CALENDAR,
            self::SECTION_CATEGORIES,
            self::SECTION_HTML_PAGES,
            self::SECTION_NEWSLETTERS,
            self::SECTION_VALIDATE_EMAIL,
            self::SECTION_WIKI,
            self::SECTION_ADMIN,
            self::SECTION_DOCS,
            self::SECTION_FREETAGS,
            self::SECTION_GLOBAL,
            self::SECTION_LIVESUPPORT,
            self::SECTION_SEARCH,
            self::SECTION_SHARE,
            self::SECTION_SHEET,
            self::SECTION_CHAT,
            self::SECTION_ADMIN_LAYOUT,
        ];
    }

    protected static $sections = [
        // tra('Wiki Page') -- tra() comments are there for get_strings.php
        'wiki page' => [
            'feature' => 'feature_wiki',
            'key' => 'page',
            'itemkey' => '',
            'objectType' => 'wiki page',
            'commentsFeature' => 'feature_wiki_comments',
        ],
        // tra('Blog')
        // tra('Blog Post')
        'blogs' => [
            'feature' => 'feature_blogs',
            'key' => 'blogId',
            'itemkey' => 'postId',
            'objectType' => 'blog',
            'itemObjectType' => 'blog post',
            'itemCommentsFeature' => 'feature_blogposts_comments'
        ],
        // tra('File Gallery')
        // tra('File')
        'file_galleries' => [
            'feature' => 'feature_file_galleries',
            'key' => 'galleryId',
            'itemkey' => 'fileId',
            'objectType' => 'file gallery',
            'itemObjectType' => 'file',
            'commentsFeature' => 'feature_file_galleries_comments',
        ],
        // tra('Forum')
        // tra('Forum Post')
        'forums' => [
            'feature' => 'feature_forums',
            'key' => 'forumId',
            'itemkey' => 'comments_parentId',
            'objectType' => 'forum',
            'itemObjectType' => 'forum post',
        ],
        // tra('Article')
        'cms' => [
            'feature' => 'feature_articles',
            'key' => 'articleId',
            'itemkey' => '',
            'objectType' => 'article',
            'commentsFeature' => 'feature_article_comments'
        ],
        // tra('Tracker')
        'trackers' => [
            'feature' => 'feature_trackers',
            'key' => 'trackerId',
            'itemkey' => 'itemId',
            'objectType' => 'tracker',
            'itemObjectType' => 'tracker %d',
        ],
        'mytiki' => [
            'feature' => '',
            'key' => 'user',
            'itemkey' => '',
        ],
        'user_messages' => [
            'feature' => 'feature_messages',
            'key' => 'msgId',
            'itemkey' => '',
        ],
        'webmail' => [
            'feature' => 'feature_webmail',
            'key' => 'msgId',
            'itemkey' => '',
        ],
        'contacts' => [
            'feature' => 'feature_contacts',
            'key' => 'contactId',
            'itemkey' => '',
        ],
        // tra('Faq')
        'faqs' => [
            'feature' => 'feature_faqs',
            'key' => 'faqId',
            'itemkey' => '',
            'objectType' => 'faq',
            'commentsFeature' => 'feature_faq_comments',
        ],
        // tra('Quizz')
        'quizzes' => [
            'feature' => 'feature_quizzes',
            'key' => 'quizId',
            'itemkey' => '',
            'objectType' => 'quiz',
        ],
        // tra('Poll')
        'poll' => [
            'feature' => 'feature_polls',
            'key' => 'pollId',
            'itemkey' => '',
            'objectType' => 'poll',
            'commentsFeature' => 'feature_poll_comments',
        ],
        // tra('Survey')
        'surveys' => [
            'feature' => 'feature_surveys',
            'key' => 'surveyId',
            'itemkey' => '',
            'objectType' => 'survey',
        ],
        'featured_links' => [
            'feature' => 'feature_featuredLinks',
            'key' => 'url',
            'itemkey' => '',
        ],
        // tra('Directory')
        'directory' => [
            'feature' => 'feature_directory',
            'key' => 'directoryId',
            'itemkey' => '',
            'objectType' => 'directory',
        ],
        // tra('Calendar')
        'calendar' => [
            'feature' => 'feature_calendar',
            'key' => 'calendarId',
            'itemkey' => 'viewcalitemId',
            'objectType' => 'calendar',
            'itemObjectType' => 'event',
        ],
        'categories' => [
            'feature' => 'feature_categories',
            'key' => 'categId',
            'itemkey' => '',
        ],
        // tra('Html Page')
        'html_pages' => [
            'feature' => 'feature_html_pages',
            'key' => 'pageId',
            'itemkey' => '',
            'objectType' => 'html page',
        ],
        // tra('Newsletter')
        'newsletters' => [
            'feature' => 'feature_newsletters',
            'key' => 'nlId',
            'objectType' => 'newsletter',
        ],
        'validate email' => [
            'feature' => 'validateEmail',
            'dependencies' => ['feature_banning'],
            'itemkey' => '',
            'objectType' => 'list'
        ],
    ];

    public static function setCurrentSection(string $section): void
    {
        global $prefs;

        if (! in_array($section, self::getAllSections(), true)) {
            throw new \InvalidArgumentException("Invalid section: $section");
        }

        if ($prefs['theme_unified_admin_backend'] !== 'y' && $section === self::SECTION_ADMIN_LAYOUT) {
            // Trigger SECTION_ADMIN instead of SECTION_ADMIN_LAYOUT when UAB is disabled
            $section = self::SECTION_ADMIN;
            self::$currentSection = $section;
            self::triggerSectionChangeCallbacks($section);
        }

        if (self::$currentSection !== $section) {
            // Trigger callbacks when section changes
            self::$currentSection = $section;
            self::triggerSectionChangeCallbacks($section);
        }
    }

    public static function getCurrentSection(): ?string
    {
        return self::$currentSection;
    }

    public static function isCurrentSection(string $section): bool
    {
        return self::$currentSection === $section;
    }

    /**
     * Register a callback to be triggered when the section changes
     *
     * @param callable $callback Function to call when section changes. Receives section name as parameter.
     * @return void
     */
    public static function onSectionChange(callable $callback): void
    {
        self::$sectionChangeCallbacks[] = $callback;
    }

    /**
     * Triggers all registered callbacks when the section changes
     *
     * @param string $section The new section
     * @return void
     */
    private static function triggerSectionChangeCallbacks(string $section): void
    {
        foreach (self::$sectionChangeCallbacks as $callback) {
            call_user_func($callback, $section);
        }
    }

    /**
     * Retrieves the list of sections
     *
     * @return array
     */
    public static function getSections()
    {
        return self::$sections;
    }

    /**
     * Attempts to guess the object being processed based on the request parameters
     *
     * @param array|null $request An array with the request parameters (if not provided will default to $_REQUEST)
     * @return null|array Array with the type and object being requested, null/empty if not successful
     * @throws \Exception
     */
    public static function currentObject($request = null)
    {
        global $cat_type, $cat_objid, $postId, $prefs;
        $section = self::getCurrentSection();

        if (! is_array($request)) {
            $request = $_REQUEST; // use the global request object
        }

        if (self::isCurrentSection(self::SECTION_BLOGS) && ! empty($postId)) { // blog post check the category on the blog - but freetags are on blog post
            return [
                'type' => 'blog post',
                'object' => $postId,
            ];
        }

        if (self::isCurrentSection(self::SECTION_FORUMS) && ! empty($request['comments_parentId'])) {
            return [
                'type' => 'forum post',
                'object' => $request['comments_parentId'],
            ];
        }

        // Pretty tracker pages return the tracker item object instead of the parent wiki page object
        // We expose the parent wiki page type and objectId for the benefit of modules, plugins or smarty functions which may want to access the parent page categories and permissions
        if (self::isCurrentSection(self::SECTION_WIKI_PAGE) && isset($request['itemId'])) {
            $parentObject = $cat_objid;
            if (empty($parentObject) && ! empty($request['page'])) {
                $parentObject = $request['page'];
            }
            return [
                'type' => 'trackeritem',
                'object' => (int)$request['itemId'],
                'parentType' => $cat_type,
                'parentObject' => $parentObject,
            ];
        }

        if ($cat_type && $cat_objid) {
            return [
                'type' => $cat_type,
                'object' => $cat_objid,
            ];
        }

        if (self::isCurrentSection(self::SECTION_TRACKERS) && ! empty($request['itemId'])) {
            return [
                'type' => 'trackeritem',
                'object' => $request['itemId'],
            ];
        }

        $sections = self::getSections();

        if (isset($sections[$section])) {
            $info = $sections[$section];

            if (isset($info['itemkey'], $info['itemObjectType'], $request[$info['itemkey']])) {
                $type = isset($request[$info['key']]) ? $info['key'] : '';
                return [
                    'type' => sprintf($info['itemObjectType'], $type),
                    'object' => $request[$info['itemkey']],
                ];
            } elseif (isset($info['key'], $info['objectType'], $request[$info['key']])) {
                if (is_array($request[$info['key']])) {    // galleryId is an array here when in tiki-upload_file.php
                    $k = $request[$info['key']][0];
                } else {
                    $k = $request[$info['key']];
                    // when using wiki_url_scheme the page request var is the page slug, not the page/object name
                    if ($prefs['wiki_url_scheme'] !== 'urlencode' && $info['objectType'] === 'wiki page') {
                        global $jitRequest;
                        if ($prefs["feature_sefurl_tracker_prefixalias"] == 'y') {
                            $referencedPages = TikiLib::lib('wiki')->get_pages_by_alias($k);
                            if ($referencedPages) {
                                $k = $referencedPages[0];
                            }
                        }
                        $k = TikiLib::lib('wiki')->get_page_by_slug($k);
                    }
                }
                return [
                    'type' => $info['objectType'],
                    'object' => $k,
                ];
            }
        }
    }
}
