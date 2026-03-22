<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\HtmlHead;

use TikiLib;
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

class HtmlHead
{
    protected string $mid;
    protected array $images = [];
    protected ?string $title = null;
    protected ?string $canonicalPath = null;
    protected ?array $breadcrumbTitle = null;


    public function __construct(string $mid)
    {
        $this->mid = $mid;
    }

    public function getPageType(): ?string
    {
        switch ($this->mid) {
            case 'tiki-read_article.tpl':
                return 'article';

            case 'tiki-view_blog_post.tpl':
                return 'blog_post';

            case 'tiki-view_blog.tpl':
                return 'blog';

            case 'tiki-show_page.tpl':
            case 'tiki-index_p.tpl':
            case 'tiki-show_page_raw.tpl':
            case 'tiki-all_languages.tpl':
            case 'tiki-show_content.tpl':
            case 'extends:layouts/internal/layout_view.tpl|tiki-show_page.tpl':
                return 'wiki';

            case 'tiki-view_forum.tpl':
                return 'forum';

            case 'tiki-view_forum_thread.tpl':
            case 'tiki-print_forum_thread.tpl':
                return 'forum_thread';

            case 'tiki-view_tracker_item.tpl':
                return 'tracker_item';

            case 'tiki-browse_categories.tpl':
                return 'category';

            case 'tiki-list_file_gallery.tpl':
                return 'file_gallery';

            default:
                return 'website';
        }

        return null;
    }

    public function getDescription(): string
    {
        global $section, $post_info, $blog_data, $parsed_heading, $parsed_body;
        global $gal_info, $description, $title, $prefs;

        $desc = '';

        if ($section === 'blogs') {
            if (! empty($post_info['parsed_excerpt'])) {
                $desc = strip_tags($post_info['parsed_excerpt']);
            } elseif (! empty($post_info['parsed_data'])) {
                $desc = strip_tags($post_info['parsed_data']);
            } else {
                $postTitle = ! empty($post_info['title']) ? $post_info['title'] : '';
                $blogTitle = (! empty($blog_data) && ! empty($blog_data['title'])) ? $blog_data['title'] : '';
                $desc = $postTitle . ($postTitle && $blogTitle ? ' - ' : '') . $blogTitle;
            }
        } elseif ($section === 'cms') {
            if (! empty($parsed_heading)) {
                $desc = strip_tags($parsed_heading);
            } elseif (! empty($parsed_body)) {
                $desc = strip_tags($parsed_body);
            }
        } elseif ($section === 'file_galleries') {
            if (! empty($gal_info['description'])) {
                $desc = strip_tags($gal_info['description']);
            }
        } elseif (! empty($prefs['metatag_pagedesc']) && $prefs['metatag_pagedesc'] === 'y' && ! empty($description)) {
            $desc = strip_tags($description);
        } elseif (! empty($prefs['metatag_description_translated'])) {
            $desc = $prefs['metatag_description_translated'];
        } elseif (! empty($prefs['metatag_description'])) {
            $desc = $prefs['metatag_description'];
        }

        if (empty($desc)) {
            $browserTitle = $this->getBrowserTitle();
            $sep = ! empty($prefs['site_nav_seper']) ? $prefs['site_nav_seper'] : '';
            $pageTitle = isset($title) ? $title : '';
            $desc = $browserTitle . ($browserTitle && $pageTitle ? " $sep " : '') . $pageTitle;
        }

        if (strlen($desc) > 150) {
            $desc = substr($desc, 0, 147) . '...';
        }

        return trim($desc);
    }

    public function getBrowserTitle(): string
    {
        global $prefs;
        return ! empty($prefs['browsertitle_translated']) ? $prefs['browsertitle_translated'] : ($prefs['browsertitle'] ?? '');
    }

    public function getBreadcrumbsTitle(): ?string
    {
        if ($this->breadcrumbTitle !== null) {
            return $this->breadcrumbTitle ?? null;
        }

        global $trail, $prefs;

        if (
            (($prefs['feature_breadcrumbs'] === 'y') || ($prefs['site_title_breadcrumb'] === 'desc'))
            && isset($trail)
            && is_array($trail)
        ) {
            $crumbTitles = [];
            foreach ($trail as $crumb) {
                if (! empty($crumb['title'])) {
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

    public function getTitle(): string
    {
        if ($this->title !== null) {
            return $this->title;
        }

        global $sswindowtitle, $structure, $structure_path, $aliasname;
        global $tracker_item_main_value, $tagTitle, $title, $arttitle;
        global $thread_info, $forum_info, $categ_info, $userinfo;
        global $tracker_info, $page, $description, $headtitle, $prefs;

        $pageTitle = '';

        if (! empty($sswindowtitle)) {
            if ($sswindowtitle === 'none') {
                $pageTitle = '&nbsp;';
            } else {
                $pageTitle = $sswindowtitle;
            }
        } else {
            $localAlias = $aliasname;
            if (empty($localAlias) && isset($structure) && $structure === 'y' && ! empty($structure_path) && is_array($structure_path)) {
                $lastItem = end($structure_path);
                $localAlias = $lastItem['page_alias'] ?? null;
            }

            $breadcrumbsTitle = $this->getBreadcrumbsTitle();
            if (! empty($breadcrumbsTitle)) {
                $middleTitle = $breadcrumbsTitle;
            } else {
                if (! empty($tracker_item_main_value)) {
                    $middleTitle = strlen($tracker_item_main_value) > 255
                        ? substr($tracker_item_main_value, 0, 252) . '...'
                        : $tracker_item_main_value;
                } elseif (! empty($tagTitle)) {
                    $middleTitle = $tagTitle;
                } elseif (! empty($title) && ! is_array($title)) {
                    $middleTitle = $title;
                } elseif (! empty($localAlias)) {
                    $middleTitle = $localAlias;
                } elseif (! empty($arttitle)) {
                    $middleTitle = $arttitle;
                } elseif (! empty($thread_info['title'])) {
                    $middleTitle = $thread_info['title'];
                } elseif (! empty($forum_info['name'])) {
                    $middleTitle = $forum_info['name'];
                } elseif (! empty($categ_info['name'])) {
                    $middleTitle = $categ_info['name'];
                } elseif (! empty($userinfo['login'])) {
                    $middleTitle = $userinfo['login'];
                } elseif (! empty($tracker_info['pagetitle'])) {
                    $middleTitle = $tracker_info['pagetitle'];
                } elseif (! empty($tracker_info['name'])) {
                    $middleTitle = $tracker_info['name'];
                } elseif (! empty($page) && ! empty($prefs['site_title_breadcrumb']) && $prefs['site_title_breadcrumb'] === 'pagetitle') {
                    $middleTitle = $page;
                } elseif (! empty($description)) {
                    $middleTitle = $description;
                } elseif (! empty($page)) {
                    $middleTitle = $page;
                } elseif (! empty($headtitle)) {
                    $middleTitle = str_replace('&nbsp;', ' ', $headtitle);
                } else {
                    $middleTitle = '';
                }
            }

            $browserTitle = $this->getBrowserTitle();
            $sep = ! empty($prefs['site_nav_seper']) ? $prefs['site_nav_seper'] : '';
            $location = ! empty($prefs['site_title_location']) ? $prefs['site_title_location'] : '';

            if ($location === 'only') {
                $pageTitle = $browserTitle;
            } elseif ($location === 'before') {
                $pageTitle = $browserTitle . ($browserTitle && $middleTitle ? " $sep " : '') . $middleTitle;
            } elseif ($location === 'after') {
                $pageTitle = $middleTitle . ($middleTitle && $browserTitle ? " $sep " : '') . $browserTitle;
            } else {
                $pageTitle = $middleTitle ?: $browserTitle;
            }
        }

        $this->title = $pageTitle;
        return $this->title;
    }


    public function getAuthor(): ?string
    {
        global $author, $post_info;

        $pageAuthor = $author;
        if (empty($pageAuthor) && $this->getPageType() === 'blog_post' && ! empty($post_info['author'])) {
            $pageAuthor = $post_info['author'];
        }

        return ! empty($pageAuthor) ? $pageAuthor : null;
    }

    public function getCanonicalPath(): ?string
    {
        global $prefs, $page, $canonical_ending;

        if ($this->canonicalPath !== null) {
            return $this->canonicalPath;
        }

        $url = null;
        if (($prefs['feature_canonical_url'] ?? '') === 'y' && ! empty($this->mid)) {
            switch ($this->mid) {
                case 'tiki-show_page.tpl':
                case 'tiki-index_p.tpl':
                case 'tiki-show_page_raw.tpl':
                case 'tiki-all_languages.tpl':
                case 'tiki-show_content.tpl':
                case 'extends:layouts/internal/layout_view.tpl|tiki-show_page.tpl':
                    if (! empty($page)) {
                        $url = \filter_out_sefurl($page) . (! empty($canonical_ending) ? $canonical_ending : '');
                    }
                    break;

                case 'tiki-view_tracker_item.tpl':
                    if (! empty($_REQUEST['itemId'])) {
                        $url = \filter_out_sefurl($_REQUEST['itemId'], 'trackeritem', null, null, 'n');
                    }
                    break;

                case 'tiki-view_forum.tpl':
                    if (! empty($_REQUEST['forumId'])) {
                        $url = \filter_out_sefurl($_REQUEST['forumId'], 'forum');
                    }
                    break;

                case 'tiki-view_forum_thread.tpl':
                case 'tiki-print_forum_thread.tpl':
                    if (! empty($_REQUEST['comments_parentId'])) {
                        $url = \filter_out_sefurl($_REQUEST['comments_parentId'], 'forumthread');
                    }
                    break;

                case 'tiki-view_blog.tpl':
                    if (! empty($_REQUEST['blogId'])) {
                        $url = \filter_out_sefurl($_REQUEST['blogId'], 'blog', null, null, 'n');
                    }
                    break;

                case 'tiki-view_blog_post.tpl':
                    if (! empty($_REQUEST['postId'])) {
                        $url = \filter_out_sefurl($_REQUEST['postId'], 'blogpost', null, null, 'n');
                    }
                    break;

                case 'tiki-read_article.tpl':
                    if (! empty($_REQUEST['articleId'])) {
                        $url = \filter_out_sefurl($_REQUEST['articleId'], 'article', null, null, 'n');
                    }
                    break;

                case 'tiki-browse_categories.tpl':
                    if (! empty($_REQUEST['parentId'])) {
                        $url = \filter_out_sefurl($_REQUEST['parentId'], 'category');
                    }
                    break;

                case 'tiki-list_file_gallery.tpl':
                    if (isset($_REQUEST['galleryId'])) {
                        $url = 'file' . $_REQUEST['galleryId'];
                    }
                    break;
            }
        }

        $this->canonicalPath = $url;
        return $this->canonicalPath;
    }

    public function getCanonicalUrl(): string
    {
        global $base_url_canonical;
        $path = $this->getCanonicalPath();
        return $path ? $base_url_canonical . $path : ($base_url_canonical ?? '');
    }


    public function getImages(): array
    {
        if (! empty($this->images)) {
            return $this->images;
        }

        global $header_featured_images, $prefs, $hasImage, $topics, $base_url_canonical;

        $images = [];

        if (! empty($header_featured_images) && is_array($header_featured_images)) {
            $images = $header_featured_images;
        } elseif (
            $prefs['feature_canonical_url'] === 'y'
            && $this->mid === 'tiki-read_article.tpl'
            && ((! empty($hasImage) && $hasImage === 'y') || ! empty($topics['image_name']))
        ) {
            $imgUrl = '';
            $articleId = $_REQUEST['articleId'] ?? null;
            $topicId = $_REQUEST['topicId'] ?? null;

            if (! empty($hasImage) && $hasImage === 'y' && ! empty($articleId)) {
                $imgUrl = $base_url_canonical . 'article_image.php?image_type=article&id=' . $articleId;
            } elseif (! empty($topics['image_name']) && ! empty($topicId)) {
                $imgUrl = $base_url_canonical . 'article_image.php?image_type=topic&id=' . $topicId;
            }

            if (! empty($imgUrl)) {
                $images[] = $imgUrl;
            }
        }

        if (empty($images)) {
            if (! empty($prefs['socialnetworks_facebook_site_image'])) {
                $images[] = $prefs['socialnetworks_facebook_site_image'];
            }
            if (! empty($prefs['socialnetworks_twitter_site_image'])) {
                $images[] = $prefs['socialnetworks_twitter_site_image'];
            }
        }

        $this->images = $images;
        return $this->images;
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

    public function getPublishDate(): ?string
    {
        global $publishDate, $post_info;

        $date = null;

        if ($this->getPageType() === 'blog_post' && ! empty($post_info['created'])) {
            $date = $post_info['created'];
        } elseif (! empty($publishDate)) {
            $date = $publishDate;
        }

        return $date ? date('c', is_numeric($date) ? $date : strtotime($date)) : null;
    }

    public function getModifiedDate(): ?string
    {
        global $edit_data, $post_info;

        $date = null;

        if ($this->getPageType() === 'blog_post' && ! empty($post_info['lastModif'])) {
            $date = $post_info['lastModif'];
        } elseif (! empty($edit_data)) {
            $date = $edit_data;
        }

        return $date ? date('c', is_numeric($date) ? $date : strtotime($date)) : null;
    }

    public function __toString(): string
    {
        global $prefs, $base_url_canonical;

        $meta = new MetaTags();

        $meta->title($this->getTitle())->description($this->getDescription());

        if (! empty($canonicalPath = $this->getCanonicalPath())) {
            $meta->canonical($base_url_canonical . $canonicalPath);
            $meta->url($base_url_canonical . $canonicalPath);
        } elseif (! empty($base_url_canonical)) {
            $meta->url($base_url_canonical);
        }

        if (! empty($base_url_canonical)) {
            $meta->twitter('domain', $base_url_canonical);
        }

        $pageType = $this->getPageType();
        $isArticle = in_array($pageType, ['article', 'blog_post']);
        $meta->og('type', $isArticle ? 'article' : 'website');

        if ($isArticle && ! empty($pubDate = $this->getPublishDate())) {
            $meta->articleMeta($pubDate, $this->getModifiedDate(), $this->getAuthor());
        }

        if (! empty($images = $this->getImages())) {
            foreach ($images as $img) {
                $meta->image($img, 'summary');
            }
        } else {
            $meta->twitter('card', 'summary');
        }

        $siteName = ! empty($prefs['socialnetworks_facebook_site_name']) ? $prefs['socialnetworks_facebook_site_name'] : $this->getBrowserTitle();
        if (! empty($siteName)) {
            $meta->og('site_name', $siteName);
        }

        $twitterSite = ! empty($prefs['socialnetworks_twitter_site']) ? $prefs['socialnetworks_twitter_site'] : $siteName;
        if (! empty($twitterSite)) {
            $meta->twitter('site', $twitterSite);
        }

        if (! empty($prefs['socialnetworks_facebook_application_id'])) {
            $meta->push('meta', ['property' => 'fb:app_id', 'content' => $prefs['socialnetworks_facebook_application_id']]);
        }

        if (! empty($robots = $this->getRobots())) {
            $meta->robots($robots);
            $meta->robots($robots, 'googlebot');
        }

        if (! empty($prefs['site_google_analytics_site_ownership'])) {
            $meta->verification('google', $prefs['site_google_analytics_site_ownership']);
        }

        if (! empty($prefs['metatag_geoposition'])) {
            $meta->meta('geo.position', $prefs['metatag_geoposition']);
            $meta->meta('ICBM', str_replace(';', ',', $prefs['metatag_geoposition']));
        }

        if (! empty($prefs['metatag_georegion'])) {
            $meta->meta('geo.region', $prefs['metatag_georegion']);
        }

        if (! empty($prefs['metatag_geoplacename'])) {
            $meta->meta('geo.placename', $prefs['metatag_geoplacename']);
        }

        if (($prefs['metatag_google_notranslate'] ?? 'n') === 'y') {
            $meta->meta('google', 'notranslate');
        }

        if (($prefs['metatag_nositelinkssearchbox'] ?? 'n') === 'y') {
            $meta->meta('google', 'nositelinkssearchbox');
        }

        $this->addAltLinks($meta);

        if (($prefs['schema_enable'] ?? 'n') === 'y') {
            $this->addSchema($meta);
        }

        return (string) $meta;
    }

    private function addAltLinks($meta): void
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
                $meta->feed("{$url}?ver=" . $ver, 'application/rss+xml', $feedTitle);
            }
        }

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
                    $meta->feed($url, 'application/rss+xml', $feedTitle);
                }
            }
        }

        if (! empty($editable) && $editable && ! empty($page)) {
            $canEdit = (! empty($tiki_p_edit) && $tiki_p_edit === 'y') ||
                       (strtolower($page) === 'sandbox') ||
                       (! empty($tiki_p_admin_wiki) && $tiki_p_admin_wiki === 'y');

            if ($canEdit) {
                $meta->push('link', [
                    'rel' => 'alternate',
                    'type' => 'application/x-wiki',
                    'title' => tra('Edit this page! '),
                    'href' => 'tiki-editpage.php?page=' . urlencode($page)
                ]);
            }
        }
    }


    private function addSchema($meta): void
    {
        global $prefs, $base_url_canonical, $trail;

        $entities = [];
        $pageUrl = $this->getCanonicalUrl();
        $webPageData = [
            '@id' => "{$pageUrl}#webpage",
            'url' => $pageUrl,
            'name' => $this->getTitle(),
            'description' => $this->getDescription()
        ];

        if (! empty($siteName = $this->getBrowserTitle())) {
            $entities[] = new Organization([
                '@id' => "{$base_url_canonical}#organization",
                'name' => $siteName,
                'url' => $base_url_canonical,
                'logo' => $prefs['sitelogo_src']
            ]);
        }

        if (! empty($trail) && is_array($trail) && count($trail) > 0) {
            $listItems = [];
            foreach ($trail as $k => $crumb) {
                if (! empty($crumb['title'])) {
                    $position = $k + 1;
                    $item = [
                        '@type' => 'ListItem',
                        'position' => $position,
                        'name' => $crumb['title']
                    ];

                    if (! empty($crumb['url']) && $position < count($trail)) {
                        $item['item'] = (strpos($crumb['url'], 'http') === 0) ? $crumb['url'] : $base_url_canonical . $crumb['url'];
                    }

                    $listItems[] = $item;
                }
            }

            if (! empty($listItems)) {
                $entities[] = new BreadcrumbList([
                    '@id' => "{$pageUrl}#breadcrumb",
                    'itemListElement' => $listItems
                ]);

                $webPageData['breadcrumb'] = ['@id' => "{$pageUrl}#breadcrumb"];
            }
        }

        $entities[] = new WebPage($webPageData);

        $pageType = $this->getPageType();
        $articleId = $_REQUEST['articleId'] ?? null;
        $postId = $_REQUEST['postId'] ?? null;

        if (($pageType === 'article' && ! empty($articleId)) || ($pageType === 'blog_post' && ! empty($postId))) {
            $postData = [
                'headline' => $this->getTitle(),
                'description' => $this->getDescription(),
                'url' => $pageUrl,
                'mainEntityOfPage' => ['@id' => "{$pageUrl}#webpage"],
                'publisher' => ['@id' => "{$base_url_canonical}#organization"]
            ];

            if (! empty($images = $this->getImages())) {
                $postData['image'] = count($images) === 1 ? $images[0] : $images;
            }

            if (! empty($author = $this->getAuthor())) {
                $postData['author'] = new Thing(type: 'Person', props: ['name' => $author]);
            }

            if (! empty($pubDate = $this->getPublishDate())) {
                $postData['datePublished'] = $pubDate;
            }

            if (! empty($modDate = $this->getModifiedDate())) {
                $postData['dateModified'] = $modDate;
            }

            if ($pageType === 'article') {
                $entities[] = new Article($postData);
            } else {
                $entities[] = new BlogPosting($postData);
            }
        } // TODO: resolve/detect other types.

        if (! empty($entities)) {
            $schema = new Schema(...$entities);
            $meta->schema($schema);
        }
    }
}
