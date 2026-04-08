<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\HtmlHead;

use TikiLib;
use Tiki\Sections;
use Melbahja\Seo\Schema;
use Melbahja\Seo\MetaTags;
use Melbahja\Seo\Schema\Thing;
use Melbahja\Seo\Schema\Organization;
use Melbahja\Seo\Schema\CreativeWork\Article;
use Melbahja\Seo\Schema\CreativeWork\BlogPosting;
use Melbahja\Seo\Schema\CreativeWork\WebPage;
use Melbahja\Seo\Schema\Intangible\BreadcrumbList;

if (function_exists('\filter_out_sefurl') === false) {
    require_once('tiki-sefurl.php');
}

class HtmlHead extends MetaTags
{
    // Set by init() based on section
    public ?string $title = null;
    public ?string $description = null;
    public ?string $author = null;
    public ?string $canonicalPath = null;
    public ?string $canonicalUrl = null;
    public array $images = [];
    public ?string $publishDate = null;
    public ?string $modifiedDate = null;
    public ?string $pageType = null;
    public ?string $breadcrumbTitle = null;
    public ?string $middleSectionTemplate;

    /**
     * Initialize
     */
    public function init(?string $middleSectionTemplate): void
    {
        global $section, $prefs;

        $this->pageType              = $this->getPageTypeFromSection($section);
        $this->middleSectionTemplate = $middleSectionTemplate;

        switch ($section) {
            case Sections::SECTION_BLOGS:
                $this->initBlogs();
                break;

            case Sections::SECTION_CMS:
                $this->initCms();
                break;

            case Sections::SECTION_FILE_GALLERIES:
                $this->initFileGalleries();
                break;

            case Sections::SECTION_WIKI_PAGE:
            case Sections::SECTION_WIKI:
                $this->initWiki();
                break;

            case Sections::SECTION_FORUM:
            case Sections::SECTION_FORUMS:
                $this->initForums();
                break;

            case Sections::SECTION_TRACKERS:
                $this->initTrackers();
                break;

            case Sections::SECTION_CATEGORIES:
                $this->initCategories();
                break;

            case Sections::SECTION_FAQS:
                $this->initFaqs();
                break;

            case Sections::SECTION_SURVEYS:
                $this->initSurveys();
                break;

            case Sections::SECTION_QUIZZES:
                $this->initQuizzes();
                break;

            case Sections::SECTION_POLL:
                $this->initPolls();
                break;

            case Sections::SECTION_DIRECTORY:
                $this->initDirectory();
                break;

            case Sections::SECTION_CALENDAR:
                $this->initCalendar();
                break;

            case Sections::SECTION_NEWSLETTERS:
                $this->initNewsletters();
                break;

            case Sections::SECTION_SHEET:
                $this->initSheets();
                break;

            case Sections::SECTION_HTML_PAGES:
                $this->initHtmlPages();
                break;

            case Sections::SECTION_MY_TIKI:
                $this->initMyTiki();
                break;

            case Sections::SECTION_FREETAGS:
                $this->initFreetags();
                break;

            case Sections::SECTION_SEARCH:
                $this->initSearch();
                break;

            case Sections::SECTION_DOCS:
                $this->initDocs();
                break;

            case Sections::SECTION_ADMIN:
                $this->initAdmin();
                break;

            case Sections::SECTION_USER_MESSAGES:
            case Sections::SECTION_WEBMAIL:
            case Sections::SECTION_CONTACTS:
            case Sections::SECTION_FEATURED_LINKS:
            case Sections::SECTION_VALIDATE_EMAIL:
            case Sections::SECTION_GLOBAL:
            case Sections::SECTION_LIVESUPPORT:
            case Sections::SECTION_SHARE:
            case Sections::SECTION_CHAT:
            default:
                $this->initDefault();
                break;
        }

        $this->fallbacks();
    }

    /**
     * Blogs section
     */
    protected function initBlogs(): void
    {
        global $post_info, $blog_data, $parsed_data;

        $isPost = ! empty($_REQUEST['postId']);

        if ($isPost && ! empty($post_info['title'])) {
            $this->title = $post_info['title'];
        } elseif (! empty($blog_data['title'])) {
            $this->title = $blog_data['title'];
        }

        if ($isPost) {
            if (! empty($post_info['parsed_excerpt'])) {
                $this->description = $this->cleanText($post_info['parsed_excerpt']);
            } elseif (! empty($post_info['parsed_data'])) {
                $this->description = $this->cleanText($post_info['parsed_data']);
            } elseif (! empty($parsed_data)) {
                $this->description = $this->cleanText($parsed_data);
            } else {
                $postTitle = $post_info['title'] ?? '';
                $blogTitle = $blog_data['title'] ?? '';
                $this->description = $postTitle . ($postTitle && $blogTitle ? ' - ' : '') . $blogTitle;
            }
        } elseif (! empty($blog_data['description'])) {
            $this->description = $this->cleanText($blog_data['description']);
        }

        if ($isPost && ! empty($post_info['author'])) {
            $this->author = $post_info['author'];
        }

        if ($isPost) {
            if (! empty($post_info['created'])) {
                $this->publishDate = $this->formatDate($post_info['created']);
            }
            if (! empty($post_info['lastModif'])) {
                $this->modifiedDate = $this->formatDate($post_info['lastModif']);
            }
        }

        if ($isPost && ! empty($_REQUEST['postId'])) {
            $this->canonicalPath = \filter_out_sefurl($_REQUEST['postId'], 'blogpost', null, null, 'n');
        } elseif (! empty($_REQUEST['blogId'])) {
            $this->canonicalPath = \filter_out_sefurl($_REQUEST['blogId'], 'blog', null, null, 'n');
        }
    }

    /**
     * CMS section
     */
    protected function initCms(): void
    {
        global $article_data, $base_url_canonical;
        global $parsed_heading, $parsed_body;

        if (! empty($article_data['title'])) {
            $this->title = $article_data['title'];
        }

        if (! empty($parsed_heading)) {
            $this->description = $this->cleanText($parsed_heading);
        } elseif (! empty($parsed_body)) {
            $this->description = $this->cleanText($parsed_body);
        } elseif (! empty($article_data['heading'])) {
            $this->description = $this->cleanText($article_data['heading']);
        } elseif (! empty($article_data['body'])) {
            $this->description = $this->cleanText($article_data['body']);
        }

        if (! empty($article_data['authorName'])) {
            $this->author = $article_data['authorName'];
        } elseif (! empty($article_data['author'])) {
            $this->author = $article_data['author'];
        }

        if (! empty($article_data['publishDate'])) {
            $this->publishDate = $this->formatDate($article_data['publishDate']);
        }
        if (! empty($article_data['lastModif'])) {
            $this->modifiedDate = $this->formatDate($article_data['lastModif']);
        }

        if (! empty($_REQUEST['articleId'])) {
            $this->canonicalPath = \filter_out_sefurl($_REQUEST['articleId'], 'article', null, null, 'n');
        }

        $articleId = $_REQUEST['articleId'] ?? null;
        $topicId   = $_REQUEST['topicId'] ?? ($article_data['topicId'] ?? null);
        $hasImage  = ! empty($article_data['image_data']) && strlen($article_data['image_data']) > 0;

        if ($hasImage && ! empty($articleId)) {
            $this->images[] = $base_url_canonical . 'article_image.php?image_type=article&id=' . $articleId;
        } elseif (! empty($topicId)) {
            $artlib = TikiLib::lib('art');
            $topics = $artlib->list_topics();
            if (! empty($topics[$article_data['topicId']]['image_name'])) {
                $this->images[] = $base_url_canonical . 'article_image.php?image_type=topic&id=' . $topicId;
            }
        }
    }

    /**
     * File Galleries
     */
    protected function initFileGalleries(): void
    {
        global $gal_info;

        if (! empty($gal_info['name'])) {
            $this->title = $gal_info['name'];
        }

        if (! empty($gal_info['description'])) {
            $this->description = $this->cleanText($gal_info['description']);
        }

        if (isset($_REQUEST['galleryId'])) {
            $this->canonicalPath = 'file' . $_REQUEST['galleryId'];
        }
    }

    /**
     * Wiki section
     */
    protected function initWiki(): void
    {
        global $page, $description, $prefs;
        global $structure, $structure_path, $aliasname, $tagTitle, $title;

        if (! empty($tagTitle)) {
            $this->title = $tagTitle;
        } elseif (! empty($title) && ! is_array($title)) {
            $this->title = $title;
        } elseif (! empty($aliasname)) {
            $this->title = $aliasname;
        } elseif (! empty($structure) && $structure === 'y' && ! empty($structure_path) && is_array($structure_path)) {
            $lastItem = end($structure_path);
            if (! empty($lastItem['page_alias'])) {
                $this->title = $lastItem['page_alias'];
            } else {
                $this->title = $page;
            }
        } elseif (! empty($page)) {
            $this->title = $page;
        }

        if (! empty($prefs['metatag_pagedesc']) && $prefs['metatag_pagedesc'] === 'y' && ! empty($description)) {
            $this->description = $this->cleanText($description);
        }

        if (! empty($page)) {
            global $canonical_ending;
            $this->canonicalPath = \filter_out_sefurl($page) . (! empty($canonical_ending) ? $canonical_ending : '');
        }
    }

    /**
     * Forums section
     */
    protected function initForums(): void
    {
        global $forum_info, $thread_info;

        $isThread = ! empty($_REQUEST['comments_parentId']);

        if ($isThread && ! empty($thread_info['title'])) {
            $this->title = $thread_info['title'];
        } elseif (! empty($forum_info['name'])) {
            $this->title = $forum_info['name'];
        }

        if ($isThread && ! empty($thread_info['title'])) {
            $this->description = $thread_info['title'];
        } elseif (! empty($forum_info['name'])) {
            $this->description = $forum_info['name'];
        }

        if ($isThread && ! empty($_REQUEST['comments_parentId'])) {
            $this->canonicalPath = \filter_out_sefurl($_REQUEST['comments_parentId'], 'forumthread');
        } elseif (! empty($_REQUEST['forumId'])) {
            $this->canonicalPath = \filter_out_sefurl($_REQUEST['forumId'], 'forum');
        }
    }

    /**
     * Trackers section
     */
    protected function initTrackers(): void
    {
        global $tracker_info, $tracker_item_main_value;

        $isItem = ! empty($_REQUEST['itemId']);

        if ($isItem && ! empty($tracker_item_main_value)) {
            $this->title = strlen($tracker_item_main_value) > 255
                ? substr($tracker_item_main_value, 0, 252) . '...'
                : $tracker_item_main_value;
        } elseif (! empty($tracker_info['pagetitle'])) {
            $this->title = $tracker_info['pagetitle'];
        } elseif (! empty($tracker_info['name'])) {
            $this->title = $tracker_info['name'];
        }

        if ($isItem && ! empty($tracker_item_main_value)) {
            $this->description = $tracker_item_main_value;
        } elseif (! empty($tracker_info['name'])) {
            $this->description = $tracker_info['name'];
        }

        if ($isItem) {
            $this->canonicalPath = \filter_out_sefurl($_REQUEST['itemId'], 'trackeritem', null, null, 'n');
        }
    }

    /**
     * Categories section
     */
    protected function initCategories(): void
    {
        global $categ_info;

        if (! empty($categ_info['name'])) {
            $this->title = $categ_info['name'];
        }

        if (! empty($categ_info['name'])) {
            $this->description = $categ_info['name'];
        }

        if (! empty($_REQUEST['parentId'])) {
            $this->canonicalPath = \filter_out_sefurl($_REQUEST['parentId'], 'category');
        }
    }

    /**
     * FAQs
     */
    protected function initFaqs(): void
    {
        global $faq_info;

        if (! empty($faq_info['title'])) {
            $this->title = $faq_info['title'];
        }
    }

    /**
     * Surveys section
     */
    protected function initSurveys(): void
    {
        global $survey_info;

        if (! empty($survey_info['name'])) {
            $this->title = $survey_info['name'];
        }
    }

    /**
     * Quizzes
     */
    protected function initQuizzes(): void
    {
        global $quiz_info;

        if (! empty($quiz_info['name'])) {
            $this->title = $quiz_info['name'];
        }
    }

    /**
     * Polls
     */
    protected function initPolls(): void
    {
        global $poll_info;

        if (! empty($poll_info['title'])) {
            $this->title = $poll_info['title'];
        }
    }

    /**
     * Directory section
     */
    protected function initDirectory(): void
    {
        global $directory_info;

        if (! empty($directory_info['name'])) {
            $this->title = $directory_info['name'];
        }
    }

    /**
     * Calendar section
     */
    protected function initCalendar(): void
    {
        global $calendar;

        if (! empty($calendar['name'])) {
            $this->title = $calendar['name'];
        }
    }

    /**
     * Newsletters
     */
    protected function initNewsletters(): void
    {
        global $nl_info;

        if (! empty($nl_info['name'])) {
            $this->title = $nl_info['name'];
        }
    }

    /**
     * Sheets
     */
    protected function initSheets(): void
    {
        global $sheet_info;

        if (! empty($sheet_info['title'])) {
            $this->title = $sheet_info['title'];
        }
    }

    /**
     * HTML Pages
     */
    protected function initHtmlPages(): void
    {
        global $htmlpage;

        if (! empty($htmlpage['pageName'])) {
            $this->title = $htmlpage['pageName'];
        }
    }

    /**
     * My Tiki section
     */
    protected function initMyTiki(): void
    {
        global $user, $userinfo, $title, $description, $page, $headtitle;

        if (! empty($title) && ! is_array($title)) {
            $this->title = $title;
        } elseif (! empty($description)) {
            $this->title = $description;
        } elseif (! empty($page)) {
            $this->title = $page;
        } elseif (! empty($userinfo['login'])) {
            $this->title = $userinfo['login'];
        } elseif (! empty($user)) {
            $this->title = $user;
        } elseif (! empty($headtitle)) {
            $this->title = str_replace('&nbsp;', ' ', $headtitle);
        }
    }

    /**
     * Freetags
     */
    protected function initFreetags(): void
    {
        global $tag;

        if (! empty($tag)) {
            $this->title = $tag;
        }
    }

    /**
     * Search
     */
    protected function initSearch(): void
    {
        $this->title = tra('Search');
    }

    /**
     * Docs
     */
    protected function initDocs(): void
    {
        global $doc;

        if (! empty($doc['name'])) {
            $this->title = $doc['name'];
        }
    }

    /**
     * Admin section
     */
    protected function initAdmin(): void
    {
        global $admintitle;

        if (! empty($admintitle)) {
            $this->title = $admintitle;
        } else {
            $this->title = tra('Administration');
        }
    }

    /**
     * Default
     */
    protected function initDefault(): void
    {
        global $title, $description, $page, $headtitle, $userinfo, $user;

        if (! empty($title) && ! is_array($title)) {
            $this->title = $title;
        } elseif (! empty($page)) {
            $this->title = $page;
        } elseif (! empty($userinfo['login'])) {
            $this->title = $userinfo['login'];
        } elseif (! empty($user)) {
            $this->title = $user;
        } elseif (! empty($headtitle)) {
            $this->title = str_replace('&nbsp;', ' ', $headtitle);
        }
    }

    /**
     * Get page type from section
     */
    protected function getPageTypeFromSection(?string $section): ?string
    {
        switch ($section) {
            case Sections::SECTION_CMS:
                return 'article';

            case Sections::SECTION_BLOGS:
                return ! empty($_REQUEST['postId']) ? 'blog_post' : 'blog';

            case Sections::SECTION_WIKI_PAGE:
            case Sections::SECTION_WIKI:
                return 'wiki';

            case Sections::SECTION_FORUM:
            case Sections::SECTION_FORUMS:
                return ! empty($_REQUEST['comments_parentId']) ? 'forum_thread' : 'forum';

            case Sections::SECTION_TRACKERS:
                return ! empty($_REQUEST['itemId']) ? 'tracker_item' : 'tracker';

            case Sections::SECTION_CATEGORIES:
                return 'category';

            case Sections::SECTION_FILE_GALLERIES:
                return 'file_gallery';

            case Sections::SECTION_FAQS:
                return 'faq';

            case Sections::SECTION_SURVEYS:
                return 'survey';

            case Sections::SECTION_QUIZZES:
                return 'quiz';

            case Sections::SECTION_POLL:
                return 'poll';

            case Sections::SECTION_DIRECTORY:
                return 'directory';

            case Sections::SECTION_CALENDAR:
                return 'calendar';

            case Sections::SECTION_NEWSLETTERS:
                return 'newsletter';

            case Sections::SECTION_SHEET:
                return 'sheet';

            case Sections::SECTION_HTML_PAGES:
                return 'html_page';
        }

        return null;
    }

    /**
     * fallback metatags
     */
    protected function fallbacks(): void
    {
        global $base_url_canonical, $prefs, $header_featured_images, $section;

        if (! empty($this->canonicalPath)) {
            $this->canonicalUrl = $base_url_canonical . $this->canonicalPath;
        }

        if (! $this->description && !empty($prefs['metatag_description'])) {
            $this->description = $prefs['metatag_description'];
        }

        if (empty($this->title) && ! empty($section)) {
            $this->title = ucfirst($section);
        }

        if (empty($this->images)) {
            if (! empty($header_featured_images) && is_array($header_featured_images)) {
                $this->images = $header_featured_images;
                return;
            }

            if (! empty($prefs['socialnetworks_facebook_site_image'])) {
                $this->og('image', $prefs['socialnetworks_facebook_site_image']);
            }
            if (! empty($prefs['socialnetworks_twitter_site_image'])) {
                $this->twitter('image', $prefs['socialnetworks_twitter_site_image']);
            }
        }
    }


    public function getRobots(): ?string
    {
        global $prefs, $metatag_robotscustom, $metatag_robots;

        $content = null;
        if (! empty($prefs['metatag_robotscustom']) && $prefs['metatag_robotscustom'] === 'y' && ! empty($metatag_robotscustom)) {
            $content = $metatag_robotscustom;
        } else {
            $hasGlobal = ! empty($prefs['metatag_robots']);
            $hasScript = ! empty($metatag_robots);

            if ($hasGlobal && ! $hasScript) {
                $content = $prefs['metatag_robots'];
            } elseif (! $hasGlobal && $hasScript) {
                $content = $metatag_robots;
            } elseif ($hasGlobal && $hasScript) {
                $scriptLower = strtolower($metatag_robots);
                $prefsLower = strtolower($prefs['metatag_robots']);
                $scriptRestrictive = (strpos($scriptLower, 'noindex') !== false || strpos($scriptLower, 'nofollow') !== false);
                $prefsRestrictive = (strpos($prefsLower, 'noindex') !== false || strpos($prefsLower, 'nofollow') !== false);

                if ($scriptRestrictive) {
                    $content = $metatag_robots;
                } elseif ($prefsRestrictive) {
                    $content = $prefs['metatag_robots'];
                } else {
                    $content = $prefs['metatag_robots'] . ', ' . $metatag_robots;
                }
            }
        }

        return $content;
    }

    /**
     * Truncate and clean text to maxLen characters
     */
    protected function cleanText(string $text, int $maxLen = 150): string
    {
        $text = strip_tags($text);
        if (strlen($text) > $maxLen) {
            $text = substr($text, 0, $maxLen - 3) . '...';
        }
        return $text;
    }

    /**
     * Format date to ISO 8601
     */
    protected function formatDate($date): string
    {
        return date('c', is_numeric($date) ? $date : strtotime($date));
    }

    /**
     * Get browser title from preferences
     */
    public function getBrowserTitle(): string
    {
        global $prefs;
        return ! empty($prefs['browsertitle_translated']) ? $prefs['browsertitle_translated'] : ($prefs['browsertitle'] ?? '');
    }

    /**
     * Get breadcrumbs title
     */
    public function getBreadcrumbsTitle(): ?string
    {
        if ($this->breadcrumbTitle !== null) {
            return $this->breadcrumbTitle ?: null;
        }

        global $crumbs, $prefs;

        if (
            (($prefs['feature_breadcrumbs'] ?? '') === 'y' || ($prefs['site_title_breadcrumb'] ?? '') === 'desc')
            && isset($crumbs)
            && is_array($crumbs)
            && ! empty($crumbs)
        ) {
            $crumbTitles = [];
            foreach ($crumbs as $crumb) {
                if (is_object($crumb) && ! empty($crumb->title)) {
                    $crumbTitles[] = $crumb->title;
                } elseif (is_array($crumb) && ! empty($crumb['title'])) {
                    $crumbTitles[] = $crumb['title'];
                }
            }

            if (! empty($crumbTitles)) {
                if (($prefs['site_title_breadcrumb'] ?? '') === 'desc') {
                    $this->breadcrumbTitle = implode(' > ', array_reverse($crumbTitles));
                } else {
                    $this->breadcrumbTitle = implode(' > ', $crumbTitles);
                }
            }
        }

        return $this->breadcrumbTitle;
    }

    /**
     * Get the final title
     */
    public function getTitle(): string
    {
        global $sswindowtitle, $prefs;

        if (! empty($sswindowtitle)) {
            return $sswindowtitle === 'none' ? '&nbsp;' : $sswindowtitle;
        }

        $browserTitle = $this->getBrowserTitle();
        $middleTitle  = $this->title ?? '';

        $breadcrumbsTitle = $this->getBreadcrumbsTitle();
        if (! empty($breadcrumbsTitle)) {
            $middleTitle = $breadcrumbsTitle;
        }

        $sep      = ! empty($prefs['site_nav_seper']) ? $prefs['site_nav_seper'] : '';
        $location = ! empty($prefs['site_title_location']) ? $prefs['site_title_location'] : '';

        // assemble title
        if ($location === 'only') {
            return $browserTitle;
        } elseif ($location === 'before') {
            return $browserTitle . ($browserTitle && $middleTitle ? " $sep " : '') . $middleTitle;
        } elseif ($location === 'after') {
            return $middleTitle . ($middleTitle && $browserTitle ? " $sep " : '') . $browserTitle;
        } else {
            return $middleTitle ?: $browserTitle;
        }
    }

    /**
     * Auto generate meta tags
     */
    public function autoGenerate(): string
    {
        global $prefs, $base_url_canonical;

        $title = $this->getTitle();

        $this->title($title)
            ->description($this->description ?? $title)
            ->meta('generator', 'Tiki Wiki CMS Groupware - https://tiki.org');

        // canonical URL
        if (! empty($this->canonicalUrl)) {
            $this->canonical($this->canonicalUrl);
            $this->url($this->canonicalUrl);
        } elseif (! empty($base_url_canonical)) {
            $this->url($base_url_canonical);
        }

        // twitter domain
        if (! empty($base_url_canonical)) {
            $this->twitter('domain', $base_url_canonical);
        }

        // open graph type
        $isArticle = in_array($this->pageType, ['article', 'blog_post']);
        $this->og('type', $isArticle ? 'article' : 'website');

        // article meta
        if ($isArticle && ! empty($this->publishDate)) {
            $this->articleMeta($this->publishDate, $this->modifiedDate, $this->author);
        }

        // images
        if (! empty($this->images)) {
            foreach ($this->images as $img) {
                $this->image($img, 'summary');
            }
        } else {
            $this->twitter('card', 'summary');
        }

        // robots.
        if (! empty($robots = $this->getRobots())) {
            $this->robots($robots);
            $this->robots($robots, 'googlebot');
        } elseif ($this->pageType === null) { // my tiki pages.
            $this->robots('noindex, nofollow');
            $this->robots('noindex, nofollow', 'googlebot');
        }

        // fb site name
        $siteName = ! empty($prefs['socialnetworks_facebook_site_name']) ? $prefs['socialnetworks_facebook_site_name'] : $this->getBrowserTitle();
        if (! empty($siteName)) {
            $this->og('site_name', $siteName);
        }

        // twitter site
        $twitterSite = ! empty($prefs['socialnetworks_twitter_site']) ? $prefs['socialnetworks_twitter_site'] : $siteName;
        if (! empty($twitterSite)) {
            $this->twitter('site', $twitterSite);
        }

        // fb app id
        if (! empty($prefs['socialnetworks_facebook_application_id'])) {
            $this->push('meta', ['property' => 'fb:app_id', 'content' => $prefs['socialnetworks_facebook_application_id']]);
        }

        // google site verification
        if (! empty($prefs['site_google_analytics_site_ownership'])) {
            $this->verification('google', $prefs['site_google_analytics_site_ownership']);
        }

        // geo tags
        if (! empty($prefs['metatag_geoposition'])) {
            $this->meta('geo.position', $prefs['metatag_geoposition']);
            $this->meta('ICBM', str_replace(';', ',', $prefs['metatag_geoposition']));
        }

        if (! empty($prefs['metatag_georegion'])) {
            $this->meta('geo.region', $prefs['metatag_georegion']);
        }

        if (! empty($prefs['metatag_geoplacename'])) {
            $this->meta('geo.placename', $prefs['metatag_geoplacename']);
        }

        // google directives
        if (($prefs['metatag_google_notranslate'] ?? 'n') === 'y') {
            $this->meta('google', 'notranslate');
        }

        if (($prefs['metatag_nositelinkssearchbox'] ?? 'n') === 'y') {
            $this->meta('google', 'nositelinkssearchbox');
        }

        // RSS feeds
        $this->generateAltLinks();

        // schema.org
        if (($prefs['schema_enable'] ?? 'n') === 'y') {
            $this->generateSchema();
        }

        return (string) $this;
    }

    /**
     * Generate alternate links (RSS feeds, edit link)
     */
    protected function generateAltLinks(): void
    {
        global $prefs, $tiki_p_view, $tiki_p_read_blog, $tiki_p_read_article;
        global $tiki_p_view_file_gallery, $tiki_p_forum_read, $tiki_p_view_directory, $tiki_p_view_calendar;
        global $rsslist_trackers;
        global $editable, $page, $tiki_p_edit, $tiki_p_admin_wiki;

        $feeds = [
            ['wiki', $tiki_p_view, 'feed_wiki', 'feed_wiki_title', 'tiki-wiki_rss.php', 'RSS Wiki'],
            ['blogs', $tiki_p_read_blog, 'feed_blogs', 'feed_blogs_title', 'tiki-blogs_rss.php', 'RSS Blogs'],
            ['articles', $tiki_p_read_article, 'feed_articles', 'feed_articles_title', 'tiki-articles_rss.php', 'RSS Articles'],
            ['file_galleries', $tiki_p_view_file_gallery, 'feed_file_galleries', 'feed_file_galleries_title', 'tiki-file_galleries_rss.php', 'RSS File Galleries'],
            ['forums', $tiki_p_forum_read, 'feed_forums', 'feed_forums_title', 'tiki-forums_rss.php', 'RSS Forums'],
            ['directory', $tiki_p_view_directory, 'feed_directories', 'feed_directories_title', 'tiki-directories_rss.php', 'RSS Directories'],
            ['calendar', $tiki_p_view_calendar, 'feed_calendar', 'feed_calendar_title', 'tiki-calendars_rss.php', 'RSS Calendars'],
        ];

        $ver = urlencode($prefs['feed_default_version'] ?? '2');

        foreach ($feeds as $feed) {
            list($feature, $perm, $pref, $titlePref, $url, $defaultTitle) = $feed;

            if (
                ! empty($prefs["feature_{$feature}"]) && $prefs["feature_{$feature}"] === 'y' &&
                ! empty($prefs[$pref]) && $prefs[$pref] === 'y' &&
                (empty($perm) || $perm === 'y')
            ) {
                $feedTitle = ! empty($prefs[$titlePref]) ? $prefs[$titlePref] : tra($defaultTitle);
                $this->feed("{$url}?ver=" . $ver, 'application/rss+xml', $feedTitle);
            }
        }

        // tracker feeds
        if (
            ! empty($prefs['feature_trackers']) && $prefs['feature_trackers'] === 'y'
            && ! empty($prefs['feed_tracker']) && $prefs['feed_tracker'] === 'y'
            && ! empty($rsslist_trackers)
        ) {
            foreach ($rsslist_trackers as $tracker) {
                if (! empty($tracker['trackerId'])) {
                    $baseTitle = ! empty($prefs['feed_tracker_title']) ? $prefs['feed_tracker_title'] : tra('RSS Tracker');
                    $feedTitle = $baseTitle . ' - ' . ($tracker['name'] ?? '');
                    $url = "tiki-tracker_rss.php?ver=" . $ver . "&trackerId=" . $tracker['trackerId'];
                    $this->feed($url, 'application/rss+xml', $feedTitle);
                }
            }
        }

        // edit link
        if (! empty($editable) && $editable && ! empty($page)) {
            $canEdit = (! empty($tiki_p_edit) && $tiki_p_edit === 'y') ||
                       (strtolower($page) === 'sandbox') ||
                       (! empty($tiki_p_admin_wiki) && $tiki_p_admin_wiki === 'y');

            if ($canEdit) {
                $this->push('link', [
                    'rel' => 'alternate',
                    'type' => 'application/x-wiki',
                    'title' => tra('Edit this page!'),
                    'href' => 'tiki-editpage.php?page=' . urlencode($page)
                ]);
            }
        }
    }

    /**
     * Generate structured data
     */
    protected function generateSchema(): void
    {
        global $prefs, $base_url_canonical, $crumbs;

        $entities = [];
        $pageUrl = $this->canonicalUrl ?? $base_url_canonical;

        $webPageData = [
            '@id' => "{$pageUrl}#webpage",
            'url' => $pageUrl,
            'name' => $this->title,
            'description' => $this->description ?? $this->title,
        ];

        // Organization
        if (! empty($siteName = $this->getBrowserTitle())) {
            $entities[] = new Organization([
                '@id' => "{$base_url_canonical}#organization",
                'name' => $siteName,
                'url' => $base_url_canonical,
                'logo' => $prefs['sitelogo_src'] ?? null,
            ]);
        }

        // Breadcrumbs
        if (! empty($crumbs) && is_array($crumbs) && count($crumbs) > 0) {
            $listItems = [];
            foreach ($crumbs as $k => $crumb) {
                $title = null;
                $url = null;

                if (is_object($crumb)) {
                    $title = $crumb->title ?? null;
                    $url = $crumb->url ?? null;
                } elseif (is_array($crumb)) {
                    $title = $crumb['title'] ?? null;
                    $url = $crumb['url'] ?? null;
                }

                if (! empty($title)) {
                    $position = $k + 1;
                    $item = [
                        '@type' => 'ListItem',
                        'position' => $position,
                        'name' => $title,
                    ];

                    if (! empty($url) && $position < count($crumbs)) {
                        $item['item'] = (strpos($url, 'http') === 0) ? $url : $base_url_canonical . $url;
                    }

                    $listItems[] = $item;
                }
            }

            if (! empty($listItems)) {
                $entities[] = new BreadcrumbList([
                    '@id' => "{$pageUrl}#breadcrumb",
                    'itemListElement' => $listItems,
                ]);
                $webPageData['breadcrumb'] = ['@id' => "{$pageUrl}#breadcrumb"];
            }
        }

        $entities[] = new WebPage($webPageData);

        // Article or BlogPosting
        if (in_array($this->pageType, ['article', 'blog_post'])) {
            $postData = [
                'headline' => $this->getTitle(),
                'description' => $this->description ?? $this->getTitle(),
                'url' => $pageUrl,
                'mainEntityOfPage' => ['@id' => "{$pageUrl}#webpage"],
                'publisher' => ['@id' => "{$base_url_canonical}#organization"],
            ];

            if (! empty($this->images)) {
                $postData['image'] = count($this->images) === 1 ? $this->images[0] : $this->images;
            }

            if (! empty($this->author)) {
                $postData['author'] = new Thing(
                    type: 'Person',
                    props: ['name' => $this->author]
                );
            }

            if (! empty($this->publishDate)) {
                $postData['datePublished'] = $this->publishDate;
            }

            if (! empty($this->modifiedDate)) {
                $postData['dateModified'] = $this->modifiedDate;
            }

            if ($this->pageType === 'article') {
                $entities[] = new Article($postData);
            } else {
                $entities[] = new BlogPosting($postData);
            }
        } // TODO: detect other types.

        if (! empty($entities)) {
            $this->schema(new Schema(...$entities));
        }
    }
}
