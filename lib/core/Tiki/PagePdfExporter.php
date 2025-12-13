<?php

/**
 * (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
 *
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
 * $Id$
 */

namespace Tiki;

use PdfGenerator;

/**
 * Service class providing PDF generation for wiki pages.
 *
 * This class is framework-agnostic: it has no dependency on Symfony Console or
 * plugin architecture, so it can be reused by CLI commands, wiki plugins, search
 * actions, etc.
 */
class PagePdfExporter
{
    /**
     * Generate raw PDF binary for the given wiki page.
     *
     * @param int|string $pageIdentifier Page id or page name
     * @param array $input Additional parameters forwarded to tiki-print.php
     * @return string PDF binary
     * @throws \Exception on error or missing page
     */
    public function getPdfContent($pageIdentifier, array $input = []): string
    {
        global $prefs;

        $tikilib = \TikiLib::lib('tiki');
        $info = $this->validatePageAccess($pageIdentifier);

        // Add license footer when prefs require it
        $hasCopyright = isset($prefs['wiki_feature_copyrights'])
            && $prefs['wiki_feature_copyrights'] === 'y'
            && isset($prefs['wikiLicensePage']);

        if ($hasCopyright) {
            $license_info = $tikilib->get_page_info($prefs['wikiLicensePage']);
            $tikilib->add_hit($prefs['wikiLicensePage']);
            $info['data'] .= "\n<HR>\n" . $license_info['data'];
        }

        return $this->generatePdfFromWikiPage($info);
    }

    /**
     * Generate PDF from wiki page info array
     *
     * @param array $pageInfo Array containing page info (data, is_html, namespace, etc.)
     * @return string PDF binary
     * @throws \Exception on error
     */
    public function generatePdfFromWikiPage(array $pageInfo): string
    {
        $parserlib = \TikiLib::lib('parser');

        // Render wiki content to HTML (print mode)
        $html = $parserlib->parse_data(
            $pageInfo['data'],
            [
                'is_html'   => $pageInfo['is_html'] ?? false,
                'namespace' => $pageInfo['namespace'] ?? '',
                'print'     => 'y',
            ]
        );

        // Replace bootstrap grid classes for print version.
        $html = str_replace(['col-sm', 'col-md', 'col-lg'], 'col-xs', $html);

        // Use PdfGenerator to get binary
        require_once 'lib/pdflib.php';
        $generator = new PdfGenerator();

        if (! empty($generator->error)) {
            throw new \Exception($generator->error);
        }

        $params = [
            'page' => $pageInfo['pageName'] ?? '',
            'display' => 'pdf',
        ];

        return $generator->getPdf('tiki-print.php', $params, $html);
    }

    /**
     * Convenience wrapper: generate PDF and write it to a file.
     * Returns the final path written.
     */
    public function exportToFile($pageIdentifier, ?string $outputPath = null, array $input = []): string
    {
        $tikilib = \TikiLib::lib('tiki');
        global $tikipath;

        // Compute filename when none supplied
        if (empty($outputPath)) {
            // Resolve title for sanitising
            $info = $this->validatePageAccess($pageIdentifier);
            $title = $info['pageName'] ?? $info['pageSlug'] ?? (string)$pageIdentifier;
            $file = preg_replace('/\W+/u', '_', $title);
            $filename = $tikilib->remove_non_word_characters_and_accents($file) . '.pdf';

            // Use temp/public directory for generated PDFs
            $outputPath = $tikipath . TEMP_PUBLIC_PATH . '/' . $filename;
        }

        // Ensure the directory exists
        $dir = dirname($outputPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Ensure we are not overwriting existing file by adding counter suffix
        $outputPath = $this->findAvailableName($outputPath);

        // Produce binary and write
        $pdf = $this->getPdfContent($pageIdentifier, $input);
        file_put_contents($outputPath, $pdf);

        return $outputPath;
    }

    /**
     * Helper to find an available file name (e.g., report.pdf -> report.1.pdf etc.)
     */
    private function findAvailableName(string $base): string
    {
        $counter = 0;
        $altName = $base;
        while (file_exists($altName)) {
            ++$counter;
            $altName = preg_replace('/\.(\d+\.)?(\w{1,4})$/', ".{$counter}.\$2", $base);
        }
        return $altName;
    }

    /**
     * Validate page access and get page info
     *
     * @param int|string $pageIdentifier Page ID or name
     * @return array Page info array
     * @throws \RuntimeException If page not found or access denied
     */
    public function validatePageAccess($pageIdentifier): array
    {
        $tikilib = \TikiLib::lib('tiki');

        if (is_numeric($pageIdentifier)) {
            $info = $tikilib->get_page_info_from_id($pageIdentifier);
        } else {
            $info = $tikilib->get_page_info($pageIdentifier);
        }

        if (empty($info)) {
            throw new \RuntimeException(tr('Page not found'));
        }

        if (! \Perms::get('wiki page', $info['pageName'])->view) {
            throw new \RuntimeException(tr('Permission denied'));
        }

        return $info;
    }
}
