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

        $ranges = $this->getTypeDateRanges('Forums', $prefs['sitemap_split']);
        foreach ($ranges as $range) {

            // On automatic mode skip non requested ranges.
            if ($auto
                && (intval($context['year'] ?? 0) !== intval($range['year'] ?? 0)
                    || intval($context['month'] ?? 0) !== intval($range['month'] ?? 0))) {
                continue;
            }

            [$min, $max] = $this->getRangeDateFilters($range);

            $channels = $commentsLib->list_forums(
                date_min: $min,
                date_max: $max
            );

            $channels['data'] = Perms::filter(['type' => 'forum'], 'object', $channels['data'], ['object' => 'forumId'], 'forum_read');

            if (empty($channels['data'])) {
                continue;
            }

            $fileName = $range['prefix'] . '.xml';
            if (isset($range['year'])) {
                $fileName = $range['prefix'] . '-' . $range['year'] . '.xml';
                if (isset($range['month'])) {
                    $fileName = $range['prefix'] . '-' . $range['year'] . '_' . $range['month'] . '.xml';
                }
            }

            $this->addEntriesToSitemap($channels, '/tiki-view_forum.php?forumId=%s', 'forumId', null, $fileName, '', 'lastPost', '0.6', 'daily');
        }
    }
}
