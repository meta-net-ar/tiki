<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Sitemap;

use Perms;
use Melbahja\Seo\Sitemap;
use Melbahja\Seo\Sitemap\IndexBuilder;
use Melbahja\Seo\Sitemap\LinksBuilder;

/**
 * Generate XML files following the XML Protocol that can be submitted to search engines
 */
class Generator
{
    /**
     * The prefix to be added when scanning the sub folder for valid type handlers
     */
    public const NAMESPACE_PREFIX = '\\Tiki\\Sitemap\\Type\\';

    /**
     * The base class the type handlers must extend
     */
    public const BASE_CLASS = '\\Tiki\\Sitemap\\AbstractType';

    /**
     * The base of the sitemap file name
     */
    public const BASE_FILE_NAME = 'sitemap';

    protected $basePath;

    public function __construct($basePath = null)
    {
        global $tikipath;
        if (is_null($basePath)) {
            $basePath = $tikipath;
        }

        $this->basePath = $basePath;

        if (! function_exists('filter_our_sefurl') && file_exists($basePath . 'tiki-sefurl.php')) {
            include_once($basePath . 'tiki-sefurl.php');
        }
    }

    /**
     * Function for generate sitemap XML
     * @param string $baseUrl
     */
    public function generate($baseUrl)
    {
        global $user;

        /** @var \Perms $perms */
        $perms = Perms::getInstance();
        $oldGroups = $perms->getGroups();
        $loggedUser = $user;

        $perms->setGroups(['Anonymous']); // ensure that permissions are processed as Anonymous
        $user = null;

        $baseUrl = rtrim($baseUrl, '/');
        $relativePath = self::getRelativePath();

        $sitemap = new Sitemap(
            baseUrl: $baseUrl,
            saveDir: $this->basePath . $relativePath,
            indexName: $this->getSitemapFilename(),
            sitemapBaseUrl: $baseUrl . '/' . $relativePath,
            indent: ' ',
        );

        // Execute all other handlers, for the different type of content
        $directoryFiles = new \GlobIterator(__DIR__ . '/Type/*.php');

        // to prevent uncomplete sitemaps.
        ignore_user_abort(true);
        set_time_limit(0);

        /** @var \SplFileInfo $file */
        foreach ($directoryFiles as $file) {
            if ($file->getFilename() === 'index.php') {
                continue; // file to prevent directory browsing
            }

            $name = $file->getBasename('.php');
            $class = self::NAMESPACE_PREFIX . $name;

            if (! class_exists($class)) {
                continue;
            }

            if ($name === 'Index') {

                /** @var AbstractType $typeHandler */
                $typeHandler = new $class(new IndexBuilder(
                    baseUrl:  $sitemap->getSitemapBaseUrl(),
                    filePath: $sitemap->saveDir . DIRECTORY_SEPARATOR . $sitemap->indexName
                ));
            } else {

                /** @var AbstractType $typeHandler */
                $typeHandler = new $class($sitemap);
            }


            if (is_subclass_of($typeHandler, self::BASE_CLASS)) {
                $typeHandler->generate();
            }
        }

        // Save sitemap files.
        $sitemap->render();

        $user = $loggedUser; // restore the configuration for permissions
        $perms->setGroups($oldGroups);
    }

    /**
     *
     * @param  string $baseUrl
     * @param  string $outputFile real path of the sitemap cache/output file.
     * @return bool
     */
    public function generateSitemap($baseUrl, $outputFile): bool
    {
        global $user, $prefs;

        $context = explode('-', basename($outputFile, '.xml'));
        if (count($context) <= 1 || $context[0] !== self::BASE_FILE_NAME) {
            return false;
        }

        $context = [
            'type'  => $context[1] ?? null,
            'split' => $context[2] ?? null
        ];

        if (empty($context['type'])) { // handle invalid file names.
            return false;
        }

        $smBuilderCalss = IndexBuilder::class;
        $generatorClass = self::NAMESPACE_PREFIX . "Index";

        if ($context['type'] !== 'index') {
            // enforce context split check.
            // until we have spliting in forums and pages.
            // if (
            //     ($prefs['sitemap_split'] === 'none' && $context['split'] !== null)
            //     || ($prefs['sitemap_split'] === 'year' && (str_contains($context['split'], '-') || !$context['split']) )
            //     || ($prefs['sitemap_split'] === 'year_month' && substr_count($context['split'], '_') !== 1)
            // ) {
            //     return false;
            // }

            if ($context['type'] === 'blogposts' || $context['type'] === 'blogs') {
                $context['type'] = 'blog';
            }

            $smBuilderCalss = LinksBuilder::class;
            $generatorClass = self::NAMESPACE_PREFIX . ucfirst($context['type']);
        }

        if (! class_exists($generatorClass)) {
            return false;
        }

        $sitemap = new $smBuilderCalss(
            filePath: $outputFile,
            baseUrl: $context['type'] === 'index' ? rtrim($baseUrl, '/') . '/' . self::getRelativePath() : $baseUrl,
            options: [
                'indent' => ' ',
                'localized' => $prefs['feature_multilingual'] === 'y'
            ]
        );

        /** @var \Perms $perms */
        $perms = Perms::getInstance();
        $oldGroups = $perms->getGroups();
        $loggedUser = $user;
        $perms->setGroups(['Anonymous']);
        $user = null;

        $success = false;
        try {

            /** @var AbstractType $typeHandler */
            $typeHandler = new $generatorClass($sitemap);
            if (is_subclass_of($typeHandler, self::BASE_CLASS)) {
                if (empty($context['split']) === false) {
                    $ymonth = explode('_', $context['split']);
                    $context['year']  = $ymonth[0];
                    $context['month'] = $ymonth[1] ?? null;
                }

                $typeHandler->generate(true, $context);
            }

            $success = $sitemap->render();
        } finally {
            // restore auth state
            $user = $loggedUser;
            $perms->setGroups($oldGroups);
        }

        return $success;
    }



    /**
     * Return the path to the sitemap
     *
     * @param bool $relative if it should return only the relative path (default true)
     * @return string
     */
    public function getSitemapPath($relative = true)
    {
        $path = self::getRelativePath() . $this->getSitemapFilename();

        if (! $relative) {
            $path = $this->basePath . $path;
        }

        return $path;
    }

    /**
     * Return the sitemap file name
     *
     * @return string
     */
    public function getSitemapFilename(?string $name = null)
    {
        return self::BASE_FILE_NAME . '-' . ($name ?? 'index') . '.xml';
    }

    /**
     * Get relative path based on our current domain
     *
     * @return mixed|string
     */
    public static function getRelativePath()
    {
        global $tikidomain;

        $base = 'storage/public/';

        return ! empty($tikidomain) ? $base . $tikidomain . '/' : $base;
    }
}
