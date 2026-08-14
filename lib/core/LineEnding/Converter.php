<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\LineEnding;

/**
 * Class Converter
 * Handles detection and conversion of line endings in files
 */
class Converter
{
    /**
     * Fix line endings in files or report files with incorrect line endings
     *
     * @param array $files Array of file paths to check
     * @param bool $reportOnly If true, only detect issues without fixing them
     * @param string $targetEnding Target line ending (default: \n for Unix)
     * @return array Array of files that had incorrect line endings (fixed or detected)
     */
    public function fix(array $files, bool $reportOnly = false, string $targetEnding = "\n"): array
    {
        $affected = [];

        // Binary file extensions to skip
        $binaryExtensions = [
            'png', 'jpg', 'jpeg', 'gif', 'ico', 'svg', 'bmp', 'webp', 'svgz', 'xcf', // Images
            'woff', 'woff2', 'ttf', 'eot', 'otf', // Fonts
            'pdf', 'zip', 'tar', 'gz', 'bz2', 'rar', '7z', // Archives/Documents
            'exe', 'dll', 'so', 'dylib', // Executables/Libraries
            'mp3', 'mp4', 'wav', 'avi', 'mov', // Media
            'swf', 'fla', // Flash
            'db', 'sqlite', 'sqlite3', // Databases
        ];

        foreach ($files as $file) {
            if (! is_file($file) || ! is_readable($file)) {
                continue;
            }

            // Skip binary files by extension
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, $binaryExtensions)) {
                continue;
            }

            // Check if file needs conversion using streaming detection
            $needsConversion = $this->detectCarriageReturn($file);

            if (! $needsConversion) {
                continue;
            }

            if (! $reportOnly) {
                // Convert line endings using streaming approach
                $this->convertLineEndingsInFile($file, $targetEnding);
            }

            $affected[] = $file;
        }

        return $affected;
    }

    /**
     * Detect if a file contains carriage returns (needs conversion)
     * Uses streaming to avoid loading entire file into memory
     *
     * @param string $filePath Path to the file
     * @return bool True if file contains \r, false otherwise
     */
    protected function detectCarriageReturn(string $filePath): bool
    {
        $file = fopen($filePath, 'rb');
        if ($file === false) {
            return false;
        }

        $chunkSize = 8192; // 8KB chunks
        $hasCarriageReturn = false;

        while (! feof($file)) {
            $chunk = fread($file, $chunkSize);
            if ($chunk === false) {
                break;
            }

            if (strpos($chunk, "\r") !== false) {
                $hasCarriageReturn = true;
                break;
            }
        }

        fclose($file);
        return $hasCarriageReturn;
    }

    /**
     * Convert line endings in file using in-place modification
     * Preserves ALL file metadata (permissions, ownership, timestamps, ACLs, extended attributes)
     * by modifying the file directly without creating a new inode
     *
     * @param string $filePath Path to the file
     * @param string $targetEnding Target line ending (default: \n)
     * @return bool True on success, false on failure
     */
    protected function convertLineEndingsInFile(string $filePath, string $targetEnding = "\n"): bool
    {
        // Open file for reading and writing (r+b = read/write binary, doesn't truncate)
        $file = fopen($filePath, 'r+b');
        if ($file === false) {
            return false;
        }

        try {
            // Get original file size
            $stats = fstat($file);
            if ($stats === false) {
                fclose($file);
                return false;
            }
            $originalSize = $stats['size'];

            if ($originalSize === 0) {
                fclose($file);
                return true; // Empty file, nothing to do
            }

            // Process file in chunks
            $chunkSize = 8192; // 8KB chunks
            $readPos = 0;
            $writePos = 0;
            $leftover = ''; // Handle \r\n split across chunks

            while ($readPos < $originalSize) {
                // Seek to read position
                fseek($file, $readPos, SEEK_SET);

                // Calculate how much to read
                $toRead = min($chunkSize, $originalSize - $readPos);
                $chunk = fread($file, $toRead);

                if ($chunk === false) {
                    break;
                }

                $readPos += strlen($chunk);

                // Prepend any leftover from previous chunk
                if ($leftover !== '') {
                    $chunk = $leftover . $chunk;
                    $leftover = '';
                }

                // If chunk ends with \r and not at EOF, save it for next iteration
                // to handle \r\n that might be split across chunks
                if ($readPos < $originalSize && str_ends_with($chunk, "\r")) {
                    $leftover = "\r";
                    $chunk = substr($chunk, 0, -1);
                }

                // Convert line endings: CRLF and CR to target ending
                $converted = str_replace(["\r\n", "\r"], $targetEnding, $chunk);

                // Seek to write position and write converted chunk
                fseek($file, $writePos, SEEK_SET);
                $written = fwrite($file, $converted);

                if ($written === false) {
                    fclose($file);
                    return false;
                }

                $writePos += $written;
            }

            // Write any remaining leftover
            if ($leftover !== '') {
                $converted = str_replace("\r", $targetEnding, $leftover);
                fseek($file, $writePos, SEEK_SET);
                $written = fwrite($file, $converted);
                if ($written !== false) {
                    $writePos += $written;
                }
            }

            // Truncate file to new size (may be smaller if CRLF was converted to LF)
            ftruncate($file, $writePos);

            fclose($file);
            return true;
        } catch (\Exception) {
            fclose($file);
            return false;
        }
    }
}
