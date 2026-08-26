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
        // Each language version gets its own top-level sitemap entry, listing every version
        // (including itself) as an hreflang alternate, per Google's recommended sitemap pattern
        // — rather than only the primary-language page appearing in <loc>, with the others
        // reachable solely as alternates nested inside it.
        if ($isMultilingual) {
            $pagesFilter = ['lang' => $prefs['site_language']];

             /** @var \TikiLib $tikilib */
            $listPages = $tikilib->list_pages(filter: $pagesFilter);

            $flatEntries = [];
            foreach ($listPages['data'] as $page) {
                try {
                    // getTranslations() without $objName/$objLang returns an empty placeholder
                    // at index 0 for "self" — the real alternates start at index 1.
                    $others = array_slice($multilinguallib->getTranslations('wiki page', $page['page_id']), 1);
                } catch (Exception $e) {
                    $others = [];
                }

                $self = ['objId' => $page['page_id'], 'objName' => $page['pageName'], 'lang' => $page['lang']];
                $versions = array_merge([$self], $others);
                $isHomeGroup = in_array($prefs['wikiHomePage'], array_column($versions, 'objName'), true);

                foreach ($versions as $version) {
                    $entry = $version['objName'] === $page['pageName'] ? $page : $tikilib->get_page_info($version['objName']);
                    if (! $entry) {
                        continue;
                    }
                    $entry['has_translations'] = count($versions) > 1;
                    $entry['translations'] = array_values(array_filter(
                        $versions,
                        fn($v) => $v['objName'] !== $version['objName']
                    ));
                    $entry['is_home'] = $isHomeGroup;
                    $flatEntries[] = $entry;
                }
            }
            $listPages['data'] = $flatEntries;
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
        $this->addEntriesToSitemap($listPages, '/tiki-index.php?page=%s', 'pageSlug', null, 'pages.xml', '', 'lastModif', $priority, 'weekly', 'lang');
    }
}
