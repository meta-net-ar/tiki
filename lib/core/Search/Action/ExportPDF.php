<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

class Search_Action_ExportPDF implements Search_Action_Action
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

                $pdfContent = $this->getPDFContent($pageName, $input);

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
     * FIXME Refactor with \Search_Action_EmailAction::getPDFAttachment
     *
     * @param string    $pageName The name of the wiki page for which to generate the PDF.
     * @param array     $input    Array of input values from the `params` parameter
     *
     * @return string Returns the generated PDF attachment as a string.
     *         If the user does not have view permissions, an empty string is returned.
     *         If an error occurs during PDF generation or the page does not exist, returns empty string.
     * @throws Exception
     */
    private function getPDFContent(string $pageName, array $input): string
    {
        if (! Perms::get('wiki page', $pageName)->view) {
            return '';
        }

        require_once('tiki-setup.php');
        require_once 'lib/pdflib.php';
        $generator = new PdfGenerator();
        if (! empty($generator->error)) {
            Feedback::error($generator->error);
            return '';
        } else {
            $params = [
                'page' => $pageName,
                'display' => 'pdf',
            ];
            $params = array_merge($input, $params);

            // If the page doesn't exist, then display an error
            if (! ($info = TikiLib::lib('tiki')->get_page_info($pageName))) {
                Feedback::error(sprintf(tra('Page %s cannot be found'), $pageName));
                return '';
            }

            $oldGet = $_GET;
            $oldRequest = $_REQUEST;
            $_GET = $_REQUEST = $params;    // cheat for wiki arg vars
            $pdata = TikiLib::lib('parser')->parse_data($info["data"], [
                'page' => $pageName,
                'is_html' => $info["is_html"],
                'print' => 'y',
                'namespace' => $info["namespace"]
            ]);
            //replacing bootstrap classes for print version.
            $pdata = str_replace(['col-sm','col-md','col-lg'], 'col-xs', $pdata);


            $url = 'tiki-print.php?' . http_build_query($params);

            $pdfContent = $generator->getPdf($url, [], $pdata);
            $_GET = $oldGet;
            $_REQUEST = $oldRequest;

            return $pdfContent;
        }
    }
}
