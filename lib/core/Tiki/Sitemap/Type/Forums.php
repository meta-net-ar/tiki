<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Sitemap\Type;

use Tiki\Sitemap\AbstractType;
use TikiLib;
use Perms;

/**
 * Generate Sitemap for Forums
 */
class Forums extends AbstractType
{
    /**
     * Generate Sitemap
     */
    public function generate(bool $auto = false, ?array $context = null)
    {
        global $prefs;

        if (! $this->checkFeatureAndPermissions('feature_forums')) {
            return;
        }

        $commentsLib = TikiLib::lib('comments');
        $channels = $commentsLib->list_forums();

        $channels['data'] = Perms::filter(['type' => 'forum'], 'object', $channels['data'], ['object' => 'forumId'], 'forum_read');

        $this->addEntriesToSitemap($channels, '/tiki-view_forum.php?forumId=%s', 'forumId', null, 'forum.xml', '', 'lastPost', '0.6', 'daily');
    }
}
