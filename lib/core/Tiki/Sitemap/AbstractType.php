<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Sitemap;

use Perms;
use TikiLib;
use Melbahja\Seo\Sitemap;
use Melbahja\Seo\Interfaces\SitemapBuilderInterface;

/**
 * Abstract class for Sitemap Entries generation
 *
 * Has all the helper methods, child classes need to override "generate"
 */
abstract class AbstractType
{
    /**
     * @var \Melbahja\Seo\Interfaces\SitemapBuilderInterface|\Melbahja\Seo\Sitemap
     */
    protected $sitemap;

    /**
     * AbstractType constructor.
     * @param \Melbahja\Seo\Interfaces\SitemapBuilderInterface|\Melbahja\Seo\Sitemap $sitemap
     */
    public function __construct(SitemapBuilderInterface|Sitemap $sitemap)
    {
        $this->sitemap = $sitemap;
    }

    /**
     * Generate Sitemap: Function to be override in child classes where the entries for the sitemap are generated
     *
     * @param  bool $auto  Excpets true when sitemap_method pref is 'auto', for generating sitemaps on the fly
     * @param  array|null   $context excepts context for the requested sitemap on auto mode.
     */
    abstract public function generate(bool $auto = false, ?array $context = null);

    /**
     * Check if the feature is available and if a given permission is granted
     *
     * @param string $feature
     * @param string $permission The global permission to be check
     * @return bool
     */
    protected function checkFeatureAndPermissions($feature, $permission = '')
    {
        global $prefs;

        if (empty($feature)) {
            return false;
        }

        if ($prefs[$feature] != 'y') {
            return false;
        }

        if (empty($permission)) {
            return true;
        }

        $perms = Perms::get();
        if ($perms->{$permission} != 'y') {
            return false;
        }

        return true;
    }

    /**
     * Add a list of entries to the Site Map
     *
     * @param array $entries the entries will be taken from the key "data"
     * @param string $urlTemplate Url template, where will be replaced the value of $idField
     * @param string|int $idField Key for to field to be used to replace in the template
     * @param string $entryType Type of entry (for SEF)
     * @param string $titleField Field with the title of entry (for SEF)
     * @param string $updateField Field with the last update date for the entry
     * @param string $priority Priority to assign in sitemap (default 0.6)
     * @param string $changeFreq How frequent is the content to change (default weekly)
     */
    protected function addEntriesToSitemap(
        $entries,
        $urlTemplate,
        $idField,
        $entryType,
        $sitemapName,
        $titleField = 'title',
        $updateField = 'created',
        $priority = '0.6',
        $changeFreq = 'weekly'
    ) {

        global $prefs;

        if (! isset($entries['data'])) {
            return;
        }

        //
        // If the sitemap_method preference is set to 'auto', we don’t generate all sitemap files.
        // Instead we expect \Melbahja\Seo\Interfaces\SitemapBuilderInterface, which can be a sitemap index builder or a links builder.
        // This allows on the fly lazy sitemap rendering, generating only the requested sitemap file.
        //
        if (($this->sitemap instanceof Sitemap) === false) {
            foreach ($entries['data'] as $entry) {
                $url = $this->getEntryUrl($urlTemplate, $entry[$idField], $entryType, $entry[$titleField] ?? '');
                $pri = $this->getEntryPriority($entry, $idField, $priority);

                $this->sitemap->loc($url)->priority($pri)->changeFreq($changeFreq)->lastMod(date('Y/m/d H:i:s', $entry[$updateField]) ?? time());

                if ($entry['has_translations'] ?? false) {
                    foreach ($entry['translations'] as $t) {
                        $this->sitemap->alternate($this->getEntryUrl($urlTemplate, $t['objName'], $entryType), $t['lang']);
                    }
                }
            }

            return;
        }


        //
        // Manual or console generate call
        //
        $this->sitemap->links(['name' => 'sitemap-' . $sitemapName, 'localized' => $prefs['feature_multilingual'] === 'y'], function ($map) use (
            $entries,
            $urlTemplate,
            $idField,
            $entryType,
            $titleField,
            $updateField,
            $priority,
            $changeFreq
        ) {

            foreach ($entries['data'] as $entry) {
                $url = $this->getEntryUrl($urlTemplate, $entry[$idField], $entryType, $entry[$titleField] ?? '');
                $pri = $this->getEntryPriority($entry, $idField, $priority);

                $map->loc($url)->priority($pri)->changeFreq($changeFreq)->lastMod(date('Y/m/d H:i:s', $entry[$updateField]) ?? time());

                if ($entry['has_translations'] ?? false) {
                    foreach ($entry['translations'] as $t) {
                        $map->alternate($this->getEntryUrl($urlTemplate, $t['objName'], $entryType), $t['lang']);
                    }
                }
            }
        });
    }

    /**
     * Build URL for sitemap entry
     */
    protected function getEntryUrl($urlTemplate, $idValue, $entryType, $title = '')
    {
        $url = sprintf($urlTemplate, urlencode($idValue));
        if (function_exists('filter_out_sefurl')) {
            $url = filter_out_sefurl($url, $entryType, $title);
        }
        return $url;
    }

    /**
     * Get priority value for entry
     */
    protected function getEntryPriority($entry, $idField, $priority)
    {
        return is_array($priority) ? ($priority[$entry[$idField]] ?? '0.6') : $priority;
    }

    /**
     * Get distinct sitemap ranges for a content type from database
     */
    protected function getTypeDateRanges(string $type, string $split): array
    {
        $tikilib = TikiLib::lib('tiki');
        $ranges = [];

        switch ($type) {
            case 'Articles':
                if ($split === 'year') {
                    $result = $tikilib->fetchAll("SELECT DISTINCT
                        YEAR(FROM_UNIXTIME(publishDate)) as year,
                        publishDate as lastMod
                        FROM tiki_articles
                        WHERE publishDate IS NOT NULL AND publishDate > 0
                        ORDER BY year DESC");

                    foreach ($result as $row) {
                        if ($row['year']) {
                            $ranges[] = [
                                'prefix'  => 'articles',
                                'year'    => $row['year'],
                                'lastMod' => $row['lastMod']
                            ];
                        }
                    }
                } elseif ($split === 'year_month') {
                    $result = $tikilib->fetchAll("SELECT DISTINCT
                        YEAR(FROM_UNIXTIME(publishDate)) as year,
                        MONTH(FROM_UNIXTIME(publishDate)) as month,
                        publishDate as lastMod
                        FROM tiki_articles
                        WHERE publishDate IS NOT NULL AND publishDate > 0
                        ORDER BY year DESC, month DESC");

                    foreach ($result as $row) {
                        if ($row['year'] && $row['month']) {
                            $ranges[] = [
                                'prefix'  => 'articles',
                                'year'    => $row['year'],
                                'month'   => str_pad($row['month'], 2, '0', STR_PAD_LEFT),
                                'lastMod' => $row['lastMod']
                            ];
                        }
                    }
                } else {
                    $ranges[] = ['prefix' => 'articles'];
                }

                break;

            case 'Blog':
                // Combine blogs and posts dates
                if ($split === 'year') {
                    $result = $tikilib->fetchAll("SELECT DISTINCT
                        YEAR(FROM_UNIXTIME(created)) as year,
                        created as lastMod
                        FROM tiki_blog_posts
                        WHERE created IS NOT NULL
                        ORDER BY year DESC");

                    foreach ($result as $row) {
                        if ($row['year']) {
                            $ranges[] = [
                                'prefix'  => 'blogposts',
                                'year'    => $row['year'],
                                'lastMod' => $row['lastMod']
                            ];
                        }
                    }
                } elseif ($split === 'year_month') {
                    $result = $tikilib->fetchAll("SELECT DISTINCT
                        YEAR(FROM_UNIXTIME(created)) as year,
                        MONTH(FROM_UNIXTIME(created)) as month,
                        created as lastMod
                        FROM tiki_blog_posts
                        WHERE created IS NOT NULL
                        ORDER BY year DESC, month DESC");

                    foreach ($result as $row) {
                        if ($row['year'] && $row['month']) {
                            $ranges[] = [
                                'prefix'  => 'blogposts',
                                'year'    => $row['year'],
                                'month'   => str_pad($row['month'], 2, '0', STR_PAD_LEFT),
                                'lastMod' => $row['lastMod']
                            ];
                        }
                    }
                } else {
                    $ranges[] = ['prefix' => 'blogposts'];
                }

                break;

            case 'Pages':
                if ($split === 'year') {
                    $result = $tikilib->fetchAll("SELECT DISTINCT
                        YEAR(FROM_UNIXTIME(created)) as year,
                        created as lastMod
                        FROM tiki_pages
                        WHERE created IS NOT NULL AND created > 0
                        ORDER BY year DESC");

                    foreach ($result as $row) {
                        if ($row['year']) {
                            $ranges[] = [
                                'prefix'  => 'pages',
                                'year'    => $row['year'],
                                'lastMod' => $row['lastMod']
                            ];
                        }
                    }
                } elseif ($split === 'year_month') { // year_month
                    $result = $tikilib->fetchAll("SELECT DISTINCT
                        YEAR(FROM_UNIXTIME(created)) as year,
                        MONTH(FROM_UNIXTIME(created)) as month,
                        created as lastMod
                        FROM tiki_pages
                        WHERE created IS NOT NULL AND created > 0
                        ORDER BY year DESC, month DESC");

                    foreach ($result as $row) {
                        if ($row['year'] && $row['month']) {
                            $ranges[] = [
                                'prefix'  => 'pages',
                                'year'    => $row['year'],
                                'month'   => str_pad($row['month'], 2, '0', STR_PAD_LEFT),
                                'lastMod' => $row['lastMod']
                            ];
                        }
                    }
                } else {
                    $ranges[] = ['prefix' => 'pages'];
                }

                break;

            case 'Forums':
                if ($split === 'year') {
                    $result = $tikilib->fetchAll("SELECT DISTINCT
                        YEAR(FROM_UNIXTIME(created)) as year,
                        created as lastMod
                        FROM tiki_forums
                        WHERE created IS NOT NULL AND created > 0
                        ORDER BY year DESC");

                    foreach ($result as $row) {
                        if ($row['year']) {
                            $ranges[] = [
                                'prefix'  => 'forums',
                                'year'    => $row['year'],
                                'lastMod' => $row['lastMod']
                            ];
                        }
                    }
                } elseif ($split === 'year_month') { // year_month
                    $result = $tikilib->fetchAll("SELECT DISTINCT
                        YEAR(FROM_UNIXTIME(created)) as year,
                        MONTH(FROM_UNIXTIME(created)) as month,
                        created as lastMod
                        FROM tiki_forums
                        WHERE created IS NOT NULL AND created > 0
                        ORDER BY year DESC, month DESC");

                    foreach ($result as $row) {
                        if ($row['year'] && $row['month']) {
                            $ranges[] = [
                                'prefix'  => 'forums',
                                'year'    => $row['year'],
                                'month'   => str_pad($row['month'], 2, '0', STR_PAD_LEFT),
                                'lastMod' => $row['lastMod']
                            ];
                        }
                    }
                } else {
                    $ranges[] = ['prefix' => 'forums'];
                }
                break;
        }

        return $ranges;
    }

    /**
     * resolve date range for filtering content based on sitemap split
     *
     * @param array $range Range data from getTypeDateRanges
     * @return array [min, max] for use in list_articles date filters
     */
    protected function getRangeDateFilters(array $range): array
    {
        // no split case
        if (empty($range['year'])) {
            return [0, 0];
        }

        $year = (int) $range['year'];
        $month = isset($range['month']) ? (int) $range['month'] : null;

        // year-month split
        if ($month !== null) {
            $minDate = mktime(0, 0, 0, $month, 1, $year);
            $maxDate = mktime(23, 59, 59, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year), $year);
            return [$minDate, $maxDate];
        }

        // year split
        $minDate = mktime(0, 0, 0, 1, 1, $year);
        $maxDate = mktime(23, 59, 59, 12, 31, $year);
        return [$minDate, $maxDate];
    }
}
