<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Sitemap\Type;

use TikiLib;
use Exception;
use Tiki\Sitemap\AbstractType;

/**
 * Generate Sitemap for Pages
 */
class Pages extends AbstractType
{
    /**
     * Generate Sitemap
     */
    public function generate(bool $auto = false, ?array $context = null)
    {
        global $tikilib, $prefs;

        if (! $this->checkFeatureAndPermissions('feature_wiki')) {
            return;
        }

        $wikilib         = TikiLib::lib('wiki');
        $multilinguallib = TikiLib::lib('multilingual');
        $isMultilingual  = $prefs['feature_multilingual'] === 'y';

        $attributes = TikiLib::lib('attribute')->getAllAttributes("tiki.object.sitemap");
        $priority   = array_count_values(array_column($wikilib->getAllBacklinks(), 'toPage'));
        foreach ($priority as $key => $values) {
            $priority[$key] = '0.8';
        }
        $priority[$prefs['wikiHomePage']] = '1.0';

        $ranges = $this->getTypeDateRanges('Pages', $prefs['sitemap_split']);
        foreach ($ranges as $range) {

            // On automatic mode skip non requested ranges.
            if ($auto
                && (intval($context['year'] ?? 0) !== intval($range['year'] ?? 0)
                    || intval($context['month'] ?? 0) !== intval($range['month'] ?? 0))) {
                continue;
            }

            [$min, $max] = $this->getRangeDateFilters($range);

            $filter = $isMultilingual ? ['lang' => $prefs['site_language']] : [];
            $listPages = $tikilib->list_pages(
                date_min: $min,
                date_max: $max,
                filter: $filter
            );

            if ($isMultilingual) {

                foreach ($listPages['data'] as &$page) {

                    try {
                        $translations             = $multilinguallib->getTranslations('wiki page', $page['page_id']);
                        $page['has_translations'] = count($translations) > 1;
                        $page['translations']     = array_slice($translations, 1);
                    } catch (Exception $e) {
                        $page['has_translations'] = false;
                        $page['translations']     = [];
                    }
                }
            }

            $listPages['data'] = array_filter($listPages['data'], function ($page) use ($attributes) {
                return ($attributes[$page['pageName']] ?? 'y') !== 'n';
            });

            $fileName = $range['prefix'] . '.xml';
            if (isset($range['year'])) {
                $fileName = $range['prefix'] . '-' . $range['year'] . '.xml';
                if (isset($range['month'])) {
                    $fileName = $range['prefix'] . '-' . $range['year'] . '_' . $range['month'] . '.xml';
                }
            }

            $this->addEntriesToSitemap($listPages, '/tiki-index.php?page=%s', 'pageSlug', null, $fileName, '', 'lastModif', $priority);
        }
    }
}
