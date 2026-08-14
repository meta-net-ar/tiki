<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\TaskQueue\Tasks;

use Exception;
use PdfGenerator;
use Tiki\TaskQueue\Exception\QueueManagerException;
use TikiLib;

class PdfGenerationTask extends QueuedAbstractTask
{
    /**
     * Executes the task.
     * @return mixed The result of the task execution.
     */
    public function execute(): mixed
    {
        global $prefs, $base_url;

        $bufferedOutput = $this->getBufferedOutput();
        $params = $this->getParams();
        $page = $params['params']['page'];
        $pdata = $params['params']['pdata'];
        $pdfType = $params['params']['pdf_type'];
        $getPdfFnParams = $params['params']['get_pdf']['array_params'];
        $phpFile = $params['params']['get_pdf']['file'];

        try {
            $pdfDirectory = STORAGE_PUBLIC_PATH . "/pdf";
            if (! file_exists($pdfDirectory)) {
                mkdir($pdfDirectory, 0777, true);
            }

            if ($pdfType === 'cms') {
                $generator = new PdfGenerator($prefs['print_pdf_from_url']);
            } elseif ($pdfType === 'article') {
                $generator = new PdfGenerator();
            }

            if (! empty($generator->error)) {
                throw new \Exception($generator->error);
            } else {
                $pdfContent = $generator->getPdf($phpFile, $getPdfFnParams, $pdata);
                $page = preg_replace('/\W+/u', '_', $page);
                $page = TikiLib::lib('tiki')->remove_non_word_characters_and_accents($page);
                $filename = "{$page}.pdf";
                $pdfPath = $pdfDirectory . "/" . $filename;
                file_put_contents($pdfPath, $pdfContent);
                $url = $base_url . $pdfDirectory . '/' . $filename;
                $bufferedOutput->write("<a href='$url' target='_blank'>{$filename}</a>");
            }
            return $bufferedOutput->fetch();
        } catch (Exception $e) {
            throw new QueueManagerException($e->getMessage());
        }
    }
}
