<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

use Tracker\Tabular\Schema;

class JsonSource implements SourceInterface
{
    private Schema $schema;
    private \SplFileObject $file;
    private string $encoding;
    private string $format;

    public function __construct(Schema $schema, string $fileName, ?string $encoding = null)
    {
        $this->schema = $schema->getPlainOutputSchema();
        $this->file = new \SplFileObject($fileName, 'r');
        if ($encoding === null) {
            // try to detect
            $likelyEncodings = [
                        'UTF-8', 'ASCII',
                        'Windows-1252', 'Windows-1251', 'Windows-1254',
                        'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',
                        'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10',
                        'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
            ];
            mb_detect_order($likelyEncodings);
            $size = min($this->file->getSize(), 1000000);   // just check the first mb
            $content = $this->file->fread($size);

            $encoding = mb_detect_encoding($content, $likelyEncodings, true);
        }
        $this->encoding = $encoding;
        $this->format = $schema->getFormat();
    }

    public function getEntries()
    {
        $this->file->fseek(0);

        if ($this->format == 'ndjson') {
            $entries = [];
            while (! $this->file->eof()) {
                $entry = json_decode($this->file->fgets(), true);
                if (! is_null($entry)) {
                    $entries[] = $entry;
                }
            }
        } else {
            $size = $this->file->getSize();
            $contents = $this->file->fread($size);
            $entries = json_decode($contents, true);
            if (is_null($entries)) {
                throw new \Exception(tr('Could not decode uploaded json file.'));
            }
        }

        foreach ($entries as $num => $entry) {
            $data = [];
            foreach ($this->schema->getColumns() as $column) {
                if (isset($entry[$column->getField()])) {
                    $data[spl_object_hash($column)] = $this->decode($entry[$column->getField()]);
                    continue;
                }
                if (isset($entry[$column->getLabel()])) {
                    $data[spl_object_hash($column)] = $this->decode($entry[$column->getLabel()]);
                    continue;
                }
                throw new \Exception(tr('Expected field "%0" not found in record %1.', $column->getField(), $num));
            }
            yield new JsonSourceEntry($data);
        }
    }

    public function getSchema()
    {
        return $this->schema;
    }

    private function decode(string $str): string
    {
        if ($this->encoding) {
            return mb_convert_encoding($str, 'UTF-8', $this->encoding);
        } else {
            return $str;
        }
    }
}
