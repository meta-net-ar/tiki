<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Api\Comments;

use Tiki\Lib\Test\Api\ApiSchemaLoader;
use Tiki\Lib\Test\Api\ApiTestCase;
use TikiLib;
use Exception;

/**
 * Base class for Comments API integration tests
 * Provides common setup, teardown, and helper methods
 *
 * @group api-integration-test
 */
abstract class ApiBaseCommentsTest extends ApiTestCase
{
    /**
     * Array to track test comment thread IDs for cleanup
     * @var array
     */
    protected static $testComments = [];

    /**
     * Array to track test objects for cleanup
     * @var array
     */
    protected static $testObjects = [];

    /**
     * Default comments created for each object type
     * @var array
     */
    protected static $defaultComments = [];

    /**
     * Guard for lazy one-time test-data creation.
     */
    private static bool $testDataCreated = false;

    /**
     * Preferences to enable required features
     * @var array
     */
    private static $preferences = [
        'feature_wiki_comments' => 'y',
        'wiki_comments_allow_per_page' => 'y',
        'feature_file_galleries_comments' => 'y',
        'feature_polls' => 'y',
        'feature_poll_comments' => 'y',
        'feature_faqs' => 'y',
        'feature_faq_comments' => 'y',
        'feature_blogs' => 'y',
        'feature_blogposts_comments' => 'y',
        'feature_articles' => 'y',
        'feature_article_comments' => 'y',
        'feature_forum' => 'y',
        'comments_notitle' => 'n',
        'feature_comments_locking' => 'y',
        'feature_comments_moderation' => 'y',
        'comments_archive' => 'y',
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::setTestPreferences(self::$preferences);

        // Sync to the in-process $prefs global so ORM calls in lazy setUp() do not
        // attempt to reach search services that are absent in the CI environment.
        global $prefs;
        foreach (self::$preferences as $name => $value) {
            $prefs[$name] = $value;
        }
        self::$testDataCreated = false;
    }

    protected function setUp(): void
    {
        parent::setUp();
        if (! self::$testDataCreated) {
            static::createDefaultTestObjects();
            self::$testDataCreated = true;
        }
    }

    /**
     * Teardown after class - cleanup all test data
     */
    public static function tearDownAfterClass(): void
    {
        static::cleanupTestData();
        parent::tearDownAfterClass();
    }

    /**
     * Create default test objects for all comment types
     */
    protected static function createDefaultTestObjects()
    {
        // Wiki page
        $wikiPage = 'API_Test_WikiPage_Comments';
        static::createWikiPage($wikiPage);
        $wikiPageThreadId = static::createComment('wiki page', $wikiPage, 'Default wiki comment', 'Test comment on wiki page');
        static::createComment('wiki page', $wikiPage, 'Reply to default comment', 'This is a reply', $wikiPageThreadId); // add reply to the default comment to test threading in the response
        static::$defaultComments['wiki page'] = [
            'objectId' => $wikiPage,
            'threadId' => $wikiPageThreadId
        ];

        // File gallery
        $galleryId = static::createFileGallery('API_Test_Gallery_Comments');
        if ($galleryId) {
            $galleryThreadId = static::createComment('file gallery', $galleryId, 'Default gallery comment', 'Test comment on file gallery');
            static::createComment('file gallery', $galleryId, 'Reply to default gallery comment', 'This is a reply', $galleryThreadId); // add reply to the default comment to test threading in the response
            static::$defaultComments['file gallery'] = [
                'objectId' => $galleryId,
                'threadId' => $galleryThreadId
            ];
        }

        // Poll
        $pollId = static::createPoll('API_Test_Poll_Comments');
        if ($pollId) {
            $pollThreadId = static::createComment('poll', $pollId, 'Default poll comment', 'Test comment on poll');
            static::createComment('poll', $pollId, 'Reply to default poll comment', 'This is a reply', $pollThreadId); // add reply to the default comment to test threading in the response
            static::$defaultComments['poll'] = [
                'objectId' => $pollId,
                'threadId' => $pollThreadId
            ];
        }

        // FAQ
        $faqId = static::createFaq('API_Test_FAQ_Comments');
        if ($faqId) {
            $faqThreadId = static::createComment('faq', $faqId, 'Default FAQ comment', 'Test comment on FAQ');
            static::createComment('faq', $faqId, 'Reply to default FAQ comment', 'This is a reply', $faqThreadId); // add reply to the default comment to test threading in the response
            static::$defaultComments['faq'] = [
                'objectId' => $faqId,
                'threadId' => $faqThreadId
            ];
        }

        // Blog post
        $blogPostId = static::createBlogPost('API_Test_BlogPost_Comments');
        if ($blogPostId) {
            $blogPostThreadId = static::createComment('blog post', $blogPostId, 'Default blog comment', 'Test comment on blog post');
            static::createComment('blog post', $blogPostId, 'Reply to default blog comment', 'This is a reply', $blogPostThreadId); // add reply to the default comment to test threading in the response
            static::$defaultComments['blog post'] = [
                'objectId' => $blogPostId,
                'threadId' => $blogPostThreadId
            ];
        }

        // Tracker item
        $trackerItemId = static::createTrackerItem('API_Test_TrackerItem_Comments');
        if ($trackerItemId) {
            $trackerItemThreadId = static::createComment('trackeritem', $trackerItemId, 'Default tracker comment', 'Test comment on tracker item');
            static::createComment('trackeritem', $trackerItemId, 'Reply to default tracker comment', 'This is a reply', $trackerItemThreadId); // add reply to the default comment to test threading in the response
            static::$defaultComments['trackeritem'] = [
                'objectId' => $trackerItemId,
                'threadId' => $trackerItemThreadId
            ];
        }

        // Article
        $articleId = static::createArticle('API_Test_Article_Comments');
        if ($articleId) {
            $articleThreadId = static::createComment('article', $articleId, 'Default article comment', 'Test comment on article');
            static::createComment('article', $articleId, 'Reply to default article comment', 'This is a reply', $articleThreadId); // add reply to the default comment to test threading in the response
            static::$defaultComments['article'] = [
                'objectId' => $articleId,
                'threadId' => $articleThreadId
            ];
        }
    }

    /**
     * Create a wiki page for testing
     * @param string $pageName
     * @return string The page name
     */
    protected static function createWikiPage($pageName)
    {
        $tikilib = TikiLib::lib('tiki');
        $tikilib->create_page($pageName, 0, 'Test content for API comments testing', time(), 'API Test', 'admin', '0.0.0.0', '', '', false, ['lock_it' => 'n', 'comments_enabled' => 'y']);
        static::$testObjects['wiki_pages'][] = $pageName;
        return $pageName;
    }

    /**
     * Create a file gallery for testing
     * @param string $name
     * @return int|null The gallery ID or null on failure
     */
    protected static function createFileGallery($name)
    {
        $filegallib = TikiLib::lib('filegal');
        $galleryId = $filegallib->replace_file_gallery([
            'name' => $name,
            'description' => 'Test gallery for API comments',
            'visible' => 'y',
            'type' => 'default'
        ]);
        if ($galleryId) {
            static::$testObjects['file_galleries'][] = $galleryId;
        }
        return $galleryId;
    }

    /**
     * Create a poll for testing
     * @param string $title
     * @return int|null The poll ID or null on failure
     */
    protected static function createPoll($title)
    {
        $polllib = TikiLib::lib('poll');
        $pollId = $polllib->create_poll(
            0,
            $title
        );
        if ($pollId) {
            static::$testObjects['polls'][] = $pollId;
            $polllib->replace_poll_option($pollId, 0, 'Option 1', 0);
            $polllib->replace_poll_option($pollId, 0, 'Option 2', 1);
            $polllib->replace_poll_option($pollId, 0, 'Option 3', 2);
        }
        return $pollId;
    }

    /**
     * Create a FAQ for testing
     * @param string $title
     * @return int|null The FAQ ID or null on failure
     */
    protected static function createFaq($title)
    {
        $faqlib = TikiLib::lib('faq');
        $faqId = $faqlib->replace_faq(0, $title, 'Test FAQ for API comments', 'y');
        if ($faqId) {
            static::$testObjects['faqs'][] = $faqId;
        }
        return $faqId;
    }

    /**
     * Create a blog post for testing
     * @param string $title
     * @return int|null The blog post ID or null on failure
     */
    protected static function createBlogPost($title)
    {
        $bloglib = TikiLib::lib('blog');

        // First create a blog
        $blogId = $bloglib->replace_blog('API_Test_Blog', 'Test blog for comments', 'admin', 'y', 5, 0, '', 'y', 'y', 'y', 'n', 'y', 'y', 'n', 'y', 'n', 'n', 'n', '', 'n', 5, 'n');
        if (! $blogId) {
            return null;
        }
        static::$testObjects['blogs'][] = $blogId;

        // Then create a post
        $postId = $bloglib->blog_post(
            $blogId,
            'Test blog post content for API comments',
            '',
            'admin',
            $title
        );
        if ($postId) {
            static::$testObjects['blog_posts'][] = $postId;
        }
        return $postId;
    }

    /**
     * Create a tracker item for testing
     * @param string $name
     * @return int|null The tracker item ID or null on failure
     */
    protected static function createTrackerItem($name)
    {
        $trklib = TikiLib::lib('trk');

        // First create a tracker
        $trackerId = $trklib->replace_tracker(0, 'API_Test_Tracker', 'Test tracker for comments', [], 'n');
        if (! $trackerId) {
            return null;
        }
        static::$testObjects['trackers'][] = $trackerId;

        // Create a text field
        $fieldId = $trklib->replace_tracker_field(
            $trackerId,
            0,
            $name,
            't',
            'y',
            'y',
            'y',
            'y',
            'n',
            'y',
            10,
            '',
            '',
            ''
        );

        // Create an item
        $definition = \Tracker_Definition::get($trackerId);
        $fields = $definition->getFields();
        $fields[0]['value'] = $name;
        $itemId = $trklib->replace_item($trackerId, 0, ['data' => $fields]);
        if ($itemId) {
            static::$testObjects['tracker_items'][] = $itemId;
        }
        return $itemId;
    }

    /**
     * Create an article for testing
     * @param string $title
     * @return int|null The article ID or null on failure
     */
    protected static function createArticle($title)
    {
        $artlib = TikiLib::lib('art');
        $articleId = $artlib->replace_article(
            $title,
            'admin',
            0,
            'n',
            '',
            '',
            '',
            '',
            'Test article content for API comments',
            'Test article content for API comments',
            time(),
            'admin',
            0,
            0,
            0,
            'Article'
        );
        if ($articleId) {
            static::$testObjects['articles'][] = $articleId;
        }
        return $articleId;
    }

    /**
     * Create a comment on an object
     * @param string $type
     * @param mixed $objectId
     * @param string $title
     * @param string $data
     * @param int|null $parentId
     * @return int|null The thread ID or null on failure
     */
    protected static function createComment($type, $objectId, $title, $data, $parentId = null)
    {
        $commentslib = TikiLib::lib('comments');
        $message_id = '';

        $threadId = $commentslib->post_new_comment(
            "$type:$objectId",
            $parentId ?: 0,
            'admin',
            $title,
            $data,
            $message_id
        );

        if ($threadId) {
            static::$testComments[] = $threadId;
        }

        return $threadId;
    }

    /**
     * Cleanup all test data
     */
    protected static function cleanupTestData()
    {
        $commentslib = TikiLib::lib('comments');
        $tikilib = TikiLib::lib('tiki');

        // Remove all test comments
        foreach (static::$testComments as $threadId) {
            try {
                $commentslib->remove_comment($threadId);
            } catch (Exception $e) {
                // Ignore errors during cleanup
            }
        }

        // Cleanup test objects
        if (isset(static::$testObjects['wiki_pages'])) {
            foreach (static::$testObjects['wiki_pages'] as $pageName) {
                try {
                    $tikilib->remove_all_versions($pageName);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        if (isset(static::$testObjects['file_galleries'])) {
            $filegallib = TikiLib::lib('filegal');
            foreach (static::$testObjects['file_galleries'] as $galleryId) {
                try {
                    $filegallib->remove_file_gallery($galleryId);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        if (isset(static::$testObjects['polls'])) {
            $polllib = TikiLib::lib('poll');
            foreach (static::$testObjects['polls'] as $pollId) {
                try {
                    $polllib->remove_poll($pollId);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        if (isset(static::$testObjects['faqs'])) {
            $faqlib = TikiLib::lib('faq');
            foreach (static::$testObjects['faqs'] as $faqId) {
                try {
                    $faqlib->remove_faq($faqId);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        if (isset(static::$testObjects['blog_posts'])) {
            $bloglib = TikiLib::lib('blog');
            foreach (static::$testObjects['blog_posts'] as $postId) {
                try {
                    $bloglib->remove_post($postId);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        if (isset(static::$testObjects['blogs'])) {
            $bloglib = TikiLib::lib('blog');
            foreach (static::$testObjects['blogs'] as $blogId) {
                try {
                    $bloglib->remove_blog($blogId);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        if (isset(static::$testObjects['tracker_items'])) {
            $trklib = TikiLib::lib('trk');
            foreach (static::$testObjects['tracker_items'] as $itemId) {
                try {
                    $trklib->remove_tracker_item($itemId);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        if (isset(static::$testObjects['trackers'])) {
            $trklib = TikiLib::lib('trk');
            foreach (static::$testObjects['trackers'] as $trackerId) {
                try {
                    $trklib->remove_tracker($trackerId);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        if (isset(static::$testObjects['articles'])) {
            $artlib = TikiLib::lib('art');
            foreach (static::$testObjects['articles'] as $articleId) {
                try {
                    $artlib->remove_article($articleId);
                } catch (Exception $e) {
                    // Ignore errors
                }
            }
        }

        static::deleteTestPreferences(self::$preferences);
    }

    protected function getSimpleCommentResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('SimpleCommentResponse.yaml');
    }

    protected function getCommentResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('Comment.yaml');
    }

    protected function getCommentListResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('CommentListResponse.yaml');
    }

    protected function getUpdateCommentResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('UpdateCommentResponse.yaml');
    }

    protected function getDeleteCommentResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('DeleteCommentResponse.yaml');
    }

    protected function getLockCommentResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('LockCommentResponse.yaml');
    }

    protected function getModerateCommentResponseSchema(): array
    {
        return ApiSchemaLoader::fromSchemaFile('ModerateCommentResponse.yaml');
    }

    protected function assertValidCommentResponse($comment)
    {
        $this->assertMatchesSchema($comment, $this->getSimpleCommentResponseSchema());
    }

    protected function assertValidUpdateCommentResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getUpdateCommentResponseSchema());
    }

    protected function assertValidCommentListResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getCommentListResponseSchema());
    }

    protected function assertValidDeleteCommentResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getDeleteCommentResponseSchema());
    }

    protected function assertValidLockCommentResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getLockCommentResponseSchema());
    }

    protected function assertValidModerateCommentResponse($response)
    {
        $this->assertMatchesSchema($response, $this->getModerateCommentResponseSchema());
    }
}
