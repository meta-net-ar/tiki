<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Tabular\Source;

use Tracker\Tabular\Schema;

/**
 * Source that parses tab-separated data copied directly from Excel.
 *
 * The parser keeps all pasted columns as-is.
 * The first row contains the column headers and subsequent rows contain data.
 *
 * Example input:
 *   First Name	Last Name	Age
 *   Dulce	Abril	32
 *   Mara	Hashimoto	25
 */
class ExcelPasteSource implements SourceInterface
{
    private Schema $schema;
    private string $content;

    public function __construct(Schema $schema, string $content)
    {
        $this->schema = $schema->getPlainOutputSchema();
        $this->content = $content;
    }

    public function getEntries()
    {
        $lines = preg_split('/\r?\n/', rtrim($this->content));

        if (empty($lines)) {
            return;
        }

        $headerLine = array_shift($lines);
        $headers = $this->splitTabLine($headerLine);

        $mapping = $this->validateAgainstHeaders($headers);

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $fields = $this->splitTabLine($line);

            $data = [];
            foreach ($this->schema->getColumns() as $i => $column) {
                if (! isset($mapping[$i])) {
                    continue;
                }
                $rowIndex = $mapping[$i];
                $data[spl_object_hash($column)] = $fields[$rowIndex] ?? '';
            }

            yield new CsvSourceEntry($data);
        }
    }

    public function getSchema(): Schema
    {
        return $this->schema;
    }

    /**
     * Split a tab-delimited line, preserving empty trailing fields.
     */
    private function splitTabLine(string $line): array
    {
        $fields = str_getcsv($line, "\t", escape: \TikiLib::TIKI_GLOBAL_CSV_ESCAPE_CHAR);
        if (str_ends_with($line, "\t")) {
            $fields[] = '';
        }
        return $fields;
    }

    /**
     * Map schema columns to header indices.
     *
     * @throws \Exception When an expected header is not found.
     */
    private function validateAgainstHeaders(array $headers): array
    {
        $mapping = [];

        foreach ($this->schema->getColumns() as $columnIndex => $column) {
            if ($column->isExportOnly()) {
                continue;
            }

            // Match by label (typical for Excel exports with simple_headers on)
            foreach ($headers as $headerIndex => $header) {
                if ($column->getLabel() === $header) {
                    $mapping[$columnIndex] = $headerIndex;
                    continue 2;
                }
            }

            // Fallback: match by encoded header pattern [field:mode]
            foreach ($headers as $headerIndex => $header) {
                if (preg_match(\Tracker\Tabular\Schema\Column::HEADER_PATTERN, $header, $parts)) {
                    [, , $field, $mode] = $parts;
                    if ($column->is($field, $mode)) {
                        $mapping[$columnIndex] = $headerIndex;
                        continue 2;
                    }
                }
            }

            throw new \Exception(tr('Expected header "%0" not found.', $column->getLabel()));
        }

        return $mapping;
    }
}
