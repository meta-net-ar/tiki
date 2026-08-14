<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Sitemap\Type;

use TikiLib;
use Tiki\Sitemap\AbstractType;

/**
 * Generate Sitemap for Blogs
 */
class Blog extends AbstractType
{
    /**
     * Generate Sitemap
     */
    public function generate(bool $auto = false, ?array $context = null)
    {
        global $prefs;

        if (! $this->checkFeatureAndPermissions('feature_blogs')) {
            return;
        }

        $blogLib = TikiLib::lib('blog');

        $listPages = $blogLib->list_blogs();
        $this->addEntriesToSitemap($listPages, '/tiki-view_blog.php?blogId=%s', 'blogId', 'blog', 'blogs.xml', 'title', 'lastModif', '0.8');

        $ranges = $this->getTypeDateRanges('Blog', $prefs['sitemap_split']);
        foreach ($ranges as $range) {
            // on auto mode only gen what requested for
            if ($auto && intval($context['year'] ?? 0) !== intval($range['year'] ?? 0)) {
                continue;
            }

            [$min, $max] = $this->getRangeDateFilters($range);

            $posts = $blogLib->list_posts(date_min: $min, date_max: $max);

            // filename based on range
            $fileName = $range['prefix'] . '.xml';
            if (isset($range['year'])) {
                $fileName = $range['prefix'] . '-' . $range['year'] . '.xml';
                if (isset($range['month'])) {
                    $fileName = $range['prefix'] . '-' . $range['year'] . '_' . $range['month'] . '.xml';
                }
            }

            $this->addEntriesToSitemap($posts, '/tiki-view_blog_post.php?postId=%s', 'postId', 'blogpost', $fileName);
        }
    }
}
