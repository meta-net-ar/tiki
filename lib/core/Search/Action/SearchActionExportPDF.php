<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Search\Action;

use Exception;
use JitFilter;
use Perms;
use Search_Action_Action;
use Search_Action_Exception;
use TikiLib;
use Feedback;
use Tiki\PagePdfExporter;
use Tiki\FileGalLib;

class SearchActionExportPDF implements Search_Action_Action
{
    protected array $definition = [];

    public function getValues()
    {
        $values = [
            'page' => true,
            'gallery_id' => true,
            'params' => false,
        ];
        if (isset($this->definition['params'])) {
            $params = explode(':', $this->definition['params']);
            $params = array_fill_keys($params, false);
            $values = array_merge($values, $params);
        }
        return $values;
    }

    public function definitionAware(array $definition)
    {
        $this->definition = $definition;
    }
    public function validate(JitFilter $data)
    {
        return true;
    }

    public function execute(JitFilter $data)
    {
        try {
            if (! empty($data->page->text())) {
                $params = $data->params->text();

                $input = [];
                foreach (explode(':', $params) as $param) {
                    $val = $data->$param->raw() ? $data->$param->text() : $data->value->text();
                    $input[$param] = str_replace(['~np~', '~/np~'], '', $val);
                }

                $pageName = $data->page->text();
                $galleryId = $data->gallery_id->int();

                $filename = $input['filename'] ?? $pageName;
                unset($input['filename']);

                $pdfContent = $this->getPDFAttachment($pageName, $input);

                $size = strlen($pdfContent);

                /** @var FileGalLib $fileGalleryLib */
                $fileGalleryLib = TikiLib::lib('filegal');

                $gallery = $fileGalleryLib->get_file_gallery($galleryId);

                return $fileGalleryLib->upload_single_file(
                    $gallery,
                    $filename . '.pdf',
                    $size,
                    'application/pdf',
                    $pdfContent
                );
            }

            return true;
        } catch (Exception $e) {
            throw new Search_Action_Exception(tr('Error exporting PDF: %0', $e->getMessage()));
        }
    }

    public function inputType(): string
    {
        return "text";
    }

    public function requiresInput(JitFilter $data)
    {
        $params = $data->params->text();
        foreach (explode(':', $params) as $param) {
            if ($data->field_variants->raw()) {
                $exists = false;
                foreach ($data->field_variants->raw() as $variant) {
                    $key = $param . $variant;
                    if (! empty($data->$key->text())) {
                        $exists = true;
                        break;
                    }
                }
                if (! $exists) {
                    return true;
                }
            } else {
                if (empty($data->$param->text())) {
                    return true;
                }
            }
        }
        return false;
    }

    public function requiresConfirm()
    {
        return false;
    }

    /**
     * Generates a PDF attachment for the specified wiki page.
     *
     * @param string $pageName The name of the wiki page for which to generate the PDF.
     *
     * @return array|false Returns the generated PDF attachment as an array. If the user does not have
     *                     view permissions, an empty array is returned. If an error occurs during PDF
     *                     generation or the page does not exist, returns false.
     */
    private function getPDFAttachment($pageName, $input)
    {
        if (! Perms::get('wiki page', $pageName)->view) {
            return [];
        }
        // Delegate to shared exporter
        try {
            $exporter = new PagePdfExporter();
            return $exporter->getPdfContent($pageName, $input);
        } catch (\Throwable $e) {
            Feedback::error($e->getMessage());
            return false;
        }
    }
}
