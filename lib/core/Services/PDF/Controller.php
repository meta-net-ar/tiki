<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_PDF_Controller
{
    public function setUp()
    {
        Services_Exception_Disabled::check('feature_wiki_print');
    }

    public function action_get_pdf($input)
    {
        // edit permission is needed to convert arbitrary text to PDF
        if (! Perms::get()->edit) {
            throw new Services_Exception_Denied();
        }

        $contents = $input->contents->html();
        $filename = $input->filename->text();

        if (empty($filename)) {
            $filename = 'contents.pdf';
        }

        require_once 'lib/pdflib.php';
        $generator = new PdfGenerator();
        if (! empty($generator->error)) {
            Feedback::error($generator->error);
            return "";
        } else {
            $pdf = $generator->getPdf('tiki-print.php', [], $contents);
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($pdf));
            echo $pdf;
            exit;
        }
    }

    //function added to hold current state of fancy table / sorted table for pdf and print version. So when user generates pdf he gets his sorted data not default data in table.
    public function action_storeTable($input)
    {
        //write content to file
        $tableName = $input->tableName->text();
        $fname = "temp/" . $tableName . '_' . session_id() . ".txt";
        $tableFile = fopen($fname, "w");
        $table = $input->tableHTML->none();
        $table = $this->cleanPivotTableSvgs($table);
        fwrite($tableFile, $table);
        fclose($tableFile);
        //create session array to hold temp tables for printing, table original name and file name
        chmod($fname, 0755);
    }

    public function action_checkPDFFile()
    {

        if (file_exists('temp/public/pdffile_' . session_id() . '.txt')) {
            $pdfLenght = file_get_contents('temp/public/pdffile_' . session_id() . '.txt');
            unlink('temp/public/pdffile_' . session_id() . '.txt');
            return $pdfLenght;
        }

        return false;
    }

    private function cleanPivotTableSvgs($html)
    {
        $doc = new DOMDocument();
        @$doc->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $tag = $doc->getElementsByTagName('svg')->item(0);
        if ($tag) {
            $finder = new DomXPath($doc);
            $classname = 'main-svg';
            $nodes = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' $classname ')]");

            if ($nodes->length) {
                $oldNode = $nodes->item(1);
                $node = $doc->createElement('div');
                // Using negative margin-top as position absolute won't work
                $node->setAttribute('style', 'margin-top:-' . $oldNode->getAttribute('height') . 'px;overflow:hidden;');
                $node->appendChild($oldNode);
                $tag->parentNode->appendChild($node);

                for ($i = 2; $i < count($nodes); $i++) {
                    $nodeToRemove = $nodes->item($i);
                    $nodeToRemove->parentNode->removeChild($nodeToRemove);
                }

                $legends = $finder->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' legendtoggle ')]");
                if ($legends->length) {
                    foreach ($legends as $legend) {
                        $legend->setAttribute('fill', 'transparent');
                    }
                }

                $subject = str_replace(['<html>', '</html>', '<body>', '</body>'], ['', '', '', ''], @$doc->saveHTML());

                return preg_replace('/^<!DOCTYPE.+?>/', '', $subject);
            }
        }

        return $html;
    }
}
