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
        $wikilib = TikiLib::lib('wiki');
        $priority = [];

        if (! $this->checkFeatureAndPermissions('feature_wiki')) {
            return;
        }

        $multilinguallib = TikiLib::lib('multilingual');
        $isMultilingual  = $prefs['feature_multilingual'] === 'y';

        // In case of multilingual websites.
        // find pages that are in primary site lang then attach the translations to it.
        if ($isMultilingual) {
            $pagesFilter = ['lang' => $prefs['site_language']];

             /** @var \TikiLib $tikilib */
            $listPages = $tikilib->list_pages(filter: $pagesFilter);

            foreach ($listPages['data'] as &$page) {
                try {
                    $translations = $multilinguallib->getTranslations('wiki page', $page['page_id']);
                    $page['has_translations'] = count($translations) > 1;
                    $page['translations'] = array_slice($translations, 1);
                } catch (Exception $e) {
                    $page['has_translations'] = false;
                    $page['translations'] = [];
                }
            }
        } else {
            // fallback to default behavior
            $listPages = $tikilib->list_pages();
        }


        $attributes = TikiLib::lib('attribute')->getAllAttributes("tiki.object.sitemap");
        $listPages['data'] = array_filter($listPages['data'], function ($page) use ($attributes) {
            if ($attributes[$page['pageName']] !== 'n') {
                return ($page);
            }
        });
        $priority = array_count_values(array_column($wikilib->getAllBacklinks(), 'toPage'));
        foreach ($priority as $key => $values) {
            $priority[$key] = '0.8';
        }
        $priority[$prefs['wikiHomePage']] = '1.0';
        $this->addEntriesToSitemap($listPages, '/tiki-index.php?page=%s', 'pageSlug', null, 'pages.xml', '', 'lastModif', $priority);
    }

    /**
     * Generate llms txt map.
     */
    public function getLlmsTxtEntries(bool $useMdLinks = false): array
    {
        global $prefs, $tikilib, $base_url;

        if (! $this->checkFeatureAndPermissions('feature_wiki')) {
            return [];
        }

        $listPages  = $tikilib->list_pages(filter: ['lang' => $prefs['site_language']]);
        $attributes = TikiLib::lib('attribute')->getAllAttributes('tiki.object.sitemap');
        $listPages['data'] = array_filter($listPages['data'], function ($page) use ($attributes) {
            return ($attributes[$page['pageName']] ?? '') !== 'n';
        });

        $entries     = [];
        $friendlyUrl = ($prefs['feature_sefurl'] ?? 'n') === 'y';

        foreach ($listPages['data'] as $page) {
            if ($useMdLinks) {
                if ($friendlyUrl) {
                    $url = rtrim($base_url, '/') . '/page-' . urlencode($page['pageSlug'] ?: $page['pageName']) . '.md';
                } else {
                    $url = rtrim($base_url, '/') . '/tiki-llms.php?type=content&content_type=wiki&page=' . urlencode($page['pageName']);
                }
            } else {
                $url = $this->getEntryUrl('/tiki-index.php?page=%s', $page['pageSlug'] ?: $page['pageName'], null);
            }

            $entries[] = [
                'url' => $url,
                'title' => $page['pageName'],
            ];
        }

        return $entries;
    }
}
