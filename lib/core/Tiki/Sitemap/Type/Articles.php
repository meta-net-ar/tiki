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
 * Generate Sitemap for Articles
 */
class Articles extends AbstractType
{
    /**
     * Generate Sitemap
     */
    public function generate(bool $auto = false, ?array $context = null)
    {
        global $prefs;

        if (! $this->checkFeatureAndPermissions('feature_articles')) {
            return;
        }

        $articleLibrary = TikiLib::lib('art');
        $multilinguallib = TikiLib::lib('multilingual');
        $isMultilingual  = $prefs['feature_multilingual'] === 'y';

        $ranges = $this->getTypeDateRanges('Articles', $prefs['sitemap_split']);
        foreach ($ranges as $range) {
            // on auto mode only gen what requested for
            if ($auto && intval($context['year'] ?? 0) !== intval($range['year'] ?? 0)) {
                continue;
            }

            [$min, $max] = $this->getRangeDateFilters($range);
            $listPages = $articleLibrary->list_articles(
                0,
                -1,
                'publishDate_desc',
                '',
                $min,
                $max,
                false,
                '',
                '',
                'y',
                '',
                '',
                '',
                '',
                $isMultilingual ? $prefs['site_language'] : '', // only fetch primary lang articles on multilingual.
                '',
                '',
                false,
                'y'
            );

            $listPages['data'] = array_filter($listPages['data'], function ($article) {
                return ($article['disp_article'] === 'y');
            });

            // In case of multilingual websites.
            if ($isMultilingual) {
                // Attach translations to each article
                foreach ($listPages['data'] as &$article) {
                    try {
                        $translations = $multilinguallib->getTranslations('article', $article['articleId']);

                        // exclude primary from translations list (it's index 0)
                        $article['translations'] = array_slice($translations, 1);
                        $article['has_translations'] = count($translations) > 1;
                    } catch (Exception $e) {
                        $article['translations'] = [];
                        $article['has_translations'] = false;
                    }
                }
            }

            // Build filename based on range
            $fileName = $range['prefix'] . '.xml';
            if (isset($range['year'])) {
                $fileName = $range['prefix'] . '-' . $range['year'] . '.xml';
                if (isset($range['month'])) {
                    $fileName = $range['prefix'] . '-' . $range['year'] . '_' . $range['month'] . '.xml';
                }
            }

            $this->addEntriesToSitemap($listPages, '/tiki-read_article.php?articleId=%s', 'articleId', 'article', $fileName);
        }
    }
}
