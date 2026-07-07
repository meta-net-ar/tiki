<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\OIntegrate\Converter;

use Search_ContentSource_WebserviceSource;
use Search_Type_Factory_Direct;
use Tiki\Lib\OIntegrate\ConverterInterface;

/**
 * Attempt to index the result from the request
 */
class Indexer implements ConverterInterface
{
    private $format;

    public function __construct($format = 'none')
    {
        $this->format = $format;
    }

    /**
     * @param $content
     * @return mixed|string
     */
    public function convert($content)
    {
        if ($this->format === 'html' || $this->format === 'tikiwiki') {
            if (! empty($_REQUEST['nt_name'])) {    // preview from admin/webservice page
                $source = new Search_ContentSource_WebserviceSource();
                $factory = new Search_Type_Factory_Direct();

                if ($_REQUEST['nt_output'] === 'mindex') {
                    $documents = $source->getDocuments();
                    $data = [];
                    $count = 0;
                    foreach ($documents as $document) {
                        if (str_starts_with($document, $_REQUEST['nt_name'])) {
                            $data[$document] = $source->getDocument($document, $factory);
                            $count++;
                            if ($count > 100) { // enough for a preview?
                                break;
                            }
                        }
                    }
                } else {
                    $data = $source->getDocument($_REQUEST['nt_name'], $factory);
                }

                $output = '<h3>' . tr('Parsed Data') . '</h3>';
                $output .= '<pre style="max-height: 40em; overflow: auto; white-space: pre-wrap">';
                $output .= htmlentities(
                    print_r($data, true), // @phpstan-ignore disallowedFunctions.printR (returns data as string for HTML display, never prints)
                    ENT_QUOTES,
                    'UTF-8'
                );
            } else {
                $output = '<h3>' . tr('Data') . '</h3>';
                $output .= '<pre style="max-height: 20em; overflow: auto; white-space: pre-wrap">';
                $output .= htmlentities(
                    json_encode($content['data'], JSON_PRETTY_PRINT),
                    ENT_QUOTES,
                    'UTF-8'
                );
                $output .= '</pre>';

                if ($this->format === 'html') {
                    $output .= '<h3>' . tr('Mapping') . '</h3>';
                    $output .= '<pre style="max-height: 20em; overflow: auto; white-space: pre-wrap">';
                    $output .= htmlentities(
                        json_encode($content['mapping'], JSON_PRETTY_PRINT),
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    $output .= '</pre>';
                } else {    // wiki mode from plugin
                    $output = "~np~{$output}~/np~";
                }
            }

            return $output;
        } else {
            return $content;
        }
    }
}
