<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Sitemap\Type;

use TikiLib;
use Tiki\Sitemap\AbstractType;

/**
 * Generate Sitemap for Index
 */
class Index extends AbstractType
{
    /**
     * Generate Sitemap
     */
    public function generate(bool $auto = false, ?array $context = null)
    {
        global $prefs, $base_url;

        $sitemaps = [];

        if ($this->checkFeatureAndPermissions('feature_articles')) {
            $sitemaps = array_merge($sitemaps, $this->getTypeDateRanges("Articles", $prefs['sitemap_split']));
        }

        if ($this->checkFeatureAndPermissions('feature_blogs')) {
            $sitemaps[] = ['prefix' => 'blogs'];
            $sitemaps = array_merge($sitemaps, $this->getTypeDateRanges("Blog", $prefs['sitemap_split']));
        }

        if ($this->checkFeatureAndPermissions('feature_forums')) {
            $sitemaps[] = ['prefix' => 'forums'];
            // TODO: make use of pagination here when list_forums has min/max date ranges.
            // $sitemaps = array_merge($sitemaps, $this->getTypeDateRanges("Forums", $prefs['sitemap_split']));
        }

        if ($this->checkFeatureAndPermissions('feature_wiki')) {
            $sitemaps[] = ['prefix' => 'pages'];
            // TODO: make use of pagination here when list_pages has min/max date ranges.
            // $sitemaps = array_merge($sitemaps, $this->getTypeDateRanges("Pages", $prefs['sitemap_split']));
        }

        foreach ($sitemaps as $item) {
            $name = $item['prefix'];
            if (isset($item['year'])) {
                $name .= "-{$item['year']}";
                if (isset($item['month'])) {
                    $name .= "_{$item['month']}";
                }
            }

            $url = "sitemap-{$name}.xml";
            if ($prefs['feature_sefurl'] !== 'y') {
                $base = rtrim($base_url, "/");
                $url = "{$base}/tiki-sitemap.php?file={$url}";
            }

            $this->sitemap->url($url);

            if (isset($item['lastMod']) && empty($item['lastMod']) === false) {
                $this->sitemap->lastMod($item['lastMod']);
            }

            $this->sitemap->commit();
        }
    }
}
