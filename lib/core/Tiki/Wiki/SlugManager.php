<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Wiki;

class SlugManager
{
    private $generators = [];
    private $validationCallback;

    public function __construct()
    {
        $table = \TikiDb::get()->table('tiki_pages');
        $this->validationCallback = function ($slug) use ($table) {
            return $table->fetchCount(['pageSlug' => $slug]) > 0;
        };
    }

    public function setValidationCallback(callable $callback)
    {
        $this->validationCallback = $callback;
    }

    public function addGenerator(SlugManager\Generator $generator)
    {
        $this->generators[$generator->getName()] = $generator;
    }

    public function getOptions()
    {
        return array_map(function ($generator) {
            return $generator->getLabel();
        }, $this->generators);
    }

    /**
     * @param $generator
     * @param $pageName
     * @param bool $asciiOnly
     * @param bool $ignoreCounter If true, generated alias won't contain the counter in case of multiple pages with same slug
     * @return mixed
     * @throws \Exception
     */
    public function generate($generator, $pageName, $asciiOnly = false, $ignoreCounter = false)
    {
        $exists = $this->validationCallback;

        if ($asciiOnly) {
            $pageName = \TikiLib::lib('tiki')->take_away_accent($pageName);
            $pageName = preg_replace('/[^\w-]+/', ' ', $pageName);    // remove other non-word chars and replace with a space
        }

        $impl = $this->generators[$generator];

        $slug = $impl->generate($pageName);
        if ($ignoreCounter) {
            return $slug;
        }

        $counter = 2;
        while ($exists($slug)) {
            $slug = $impl->generate($pageName, $counter++);
        }

        return $slug;
    }

    public function degenerate($generator, $slug)
    {
        $impl = $this->generators[$generator];

        return $impl->degenerate($slug);
    }


    /**
     *  Generate multiple slug variations based on given options.
     *
     * @param string $slug The original slug or page name.
     * @param array  $slug_options List of formats to generate ['dash', 'underscore', 'urlencode'].
     *
     * @return array
     */
    public function generateSlugsVariations(string $slug, array $slug_options = []): array
    {
        if (empty($slug_options)) {
            $slug_options = array_keys($this->generators);
        }
        $slug_variations = [];
        foreach ($slug_options as $option) {
            $impl = $this->generators[$option];
            switch ($option) {
                case 'dash':
                    // Convert spaces or underscores to dashes
                    $slug_variations[$option] = $impl->generate($this->degenerate('underscore', $slug));
                    break;
                case 'underscore':
                    // Convert spaces or dashes to underscores
                    $slug_variations[$option] = $impl->generate($this->degenerate('dash', $slug));
                    break;
                case 'urlencode':
                    // URL-encoded version
                    $slug_variations[$option] = $impl->generate($this->degenerate('urlencode', $slug));
                    break;
            }
        }

        return $slug_variations;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function normalizeToDash(string $value): string
    {
        // Replace spaces, plus signs, and underscores with dashes
        $normalized = preg_replace('/[\s+_]/', '-', $value);
        return trim($normalized, '-');
    }
}
