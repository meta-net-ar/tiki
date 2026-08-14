<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class BOMChecker_Scanner
{
    public const BOM_UTF8 = 'BOM-UTF8';
    public const BOM_UTF16 = 'BOM-UTF16';

    // Tiki source folder
    protected $sourceDir = __DIR__ . '/../../../';

    protected $excludeDir = [];
    protected $scanFiles = [];

    protected $scanExtensions = [
        'php',
        'tpl',
        'js',
    ];

    // The number of files scanned.
    protected $scannedFiles = 0;

    // The list of files detected with BOM
    protected $bomFiles = [
        self::BOM_UTF8 => [],
        self::BOM_UTF16 => [],
    ];

    // The list of files detected without BOM
    protected $withoutBomFiles = [];

    /**
     * @param string $scanDir The file directory to scan.
     * @param array $scanExtensions An array with the file extensions to scan for BOM.
     */
    public function __construct($scanDir = null, $scanExtensions = [], $excludeDir = [], $scanFiles = [])
    {
        if (! empty($scanDir) && is_dir($scanDir)) {
            $this->sourceDir = $scanDir;
        }

        $this->sourceDir = realpath($this->sourceDir);

        if (is_array($scanExtensions) && count($scanExtensions)) {
            $this->scanExtensions = $scanExtensions;
        }

        if (! empty($excludeDir)) {
            $this->excludeDir = $excludeDir;
        }

        if (! empty($scanFiles)) {
            $this->scanFiles = $scanFiles;
        }
    }

    /**
     * Scan the folder for BOM files
     * @return array
     *  An array with the path to the BOM detected files.
     */
    public function scan()
    {
        if (! empty($this->scanFiles)) {
            $this->checkListFiles($this->scanFiles);
        } else {
            $this->checkDir($this->sourceDir);
        }

        return $this->getBomFiles();
    }

    /**
     * Check directory path
     *
     * @param string $sourceDir
     * @return void
     */
    protected function checkDir($sourceDir)
    {
        if (! empty($this->excludeDir) && in_array($sourceDir, $this->excludeDir)) {
            return;
        }

        // Skip node_modules at any level (can appear in multiple nested locations)
        if (basename($sourceDir) === 'node_modules') {
            return;
        }

        $sourceDir = $this->fixDirSlash($sourceDir);

        // Copy files and directories.
        $dirIterator = new FilesystemIterator($sourceDir, FilesystemIterator::SKIP_DOTS);

        foreach ($dirIterator as $fileInfo) {
            $path = $fileInfo->getPathname();

            // If it's a directory, check it recursively
            if ($fileInfo->isDir()) {
                $this->checkDir($path);
                continue;
            }

            // Only process files with allowed extensions
            if (! $fileInfo->isFile() || ! in_array($this->getFileExtension($path), $this->scanExtensions)) {
                continue;
            }

            // Check UTF BOM
            if (! $type = $this->checkUtfBom($path)) {
                $this->withoutBomFiles[] = $path;
                continue;
            }

            // Save files with BOM by type
            $this->bomFiles[$type][] = str_replace($this->sourceDir . '/', '', $path);
        }
    }

    /**
     * Check a list of files
     *
     * @param string $listFiles
     * @return void
     */
    protected function checkListFiles($listFiles)
    {
        if (empty($listFiles)) {
            return;
        }

        foreach ($listFiles as $file) {
            if (in_array($this->getFileExtension($file), $this->scanExtensions)) {
                if (! $type = $this->checkUtfBom($file)) {
                    $this->withoutBomFiles[] = $file;
                } else {
                    $this->bomFiles[$type][] = $file;
                }
            }
        }
    }

    /**
     * Check and change slash directory path
     *
     * @param string $dirPath
     * @return string
     */
    protected function fixDirSlash($dirPath)
    {
        $dirPath = str_replace('\\', '/', $dirPath);

        if (! str_ends_with($dirPath, '/')) {
            $dirPath .= '/';
        }

        return $dirPath;
    }

    /**
     * Get file extension
     *
     * @param string $filePath
     * @return string
     */
    protected function getFileExtension($filePath)
    {
        $info = pathinfo($filePath);
        return $info['extension'] ?? '';
    }

    /**
     * Check if UTF-8 / UTF-16 BOM codification file
     *
     * @param string $filePath
     * @return bool|string false if not found, a string with the type of BOM if found
     */
    protected function checkUtfBom($filePath)
    {
        $file = fopen($filePath, 'r');
        // Note: fgets($file, n) reads up to (n-1) bytes, so we use 4 to read 3 bytes
        // UTF-8 BOM is 3 bytes (\xEF\xBB\xBF), UTF-16 BOMs are 2 bytes
        $data = fgets($file, 4);
        fclose($file);

        $this->scannedFiles++;

        if (str_starts_with($data, "\xEF\xBB\xBF")) {
            return self::BOM_UTF8;
        }

        if (
            (str_starts_with($data, "\xFE\xFF")) // UTF-16 big-endian BOM
            || (str_starts_with($data, "\xFF\xFE")) // UTF-16 little-endian BOM
        ) {
            return self::BOM_UTF16;
        }

        return false;
    }

    /**
     * Get the number of files scanned.
     *
     * @return int
     */
    public function getScannedFiles()
    {
        return $this->scannedFiles;
    }

    /**
     * Get the list of files detected with BOM.
     *
     * @return array
     */
    public function getBomFiles()
    {
        $allFiles = [];
        foreach ($this->bomFiles as $files) {
            $allFiles = array_merge($allFiles, $files);
        }

        return $allFiles;
    }

    /**
     * Get the list of files detected with BOM.
     *
     * @return array
     */
    public function getBomFilesByType($type = null)
    {
        if (! $type) {
            return $this->bomFiles;
        }

        return $this->bomFiles[$type] ?? [];
    }

    /**
     * Get the list of files detected without BOM.
     *
     * @return array
     */
    public function getWithoutBomFiles()
    {
        return $this->withoutBomFiles;
    }

    /**
     * Returs true if there is at least one file found with BOM
     *
     * @return bool
     */
    public function bomFilesFound()
    {
        foreach ($this->bomFiles as $result) {
            if (! empty($result)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fix BOM in files or report files with BOM
     *
     * @param array $files Array of file paths to check
     * @param bool $reportOnly If true, only detect BOM without removing it
     * @return array Array of files that had BOM (fixed or detected)
     */
    public function fix(array $files, bool $reportOnly = false): array
    {
        $affected = [];

        foreach ($files as $file) {
            // Skip if not a file or not readable
            if (! is_file($file) || ! is_readable($file)) {
                continue;
            }

            // Reuse existing checkUtfBom() method for detection
            if ($bomType = $this->checkUtfBom($file)) {
                if (! $reportOnly) {
                    // Remove BOM using stream-based approach for efficiency
                    $this->removeBOMFromFile($file, $bomType);
                }
                $affected[] = $file;
            }
        }

        return $affected;
    }

    /**
     * Remove BOM from file using in-place modification
     * This approach preserves ALL file metadata (permissions, ownership, timestamps, ACLs, extended attributes)
     * by modifying the file directly without creating a new inode
     *
     * @param string $filePath Path to the file
     * @param string $bomType Type of BOM (BOM_UTF8 or BOM_UTF16)
     * @return bool True on success, false on failure
     */
    protected function removeBOMFromFile(string $filePath, string $bomType): bool
    {
        // Determine how many bytes to skip based on BOM type
        $bytesToSkip = ($bomType === self::BOM_UTF8) ? 3 : 2;

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

            // Calculate new size after BOM removal
            $newSize = $originalSize - $bytesToSkip;

            // Read and write content in chunks to shift it to the beginning
            // This is memory-efficient even for large files
            $chunkSize = 8192; // 8KB chunks
            $readPos = $bytesToSkip;
            $writePos = 0;
            $bytesRemaining = $newSize;

            while ($bytesRemaining > 0) {
                // Calculate how much to read (don't exceed remaining bytes)
                $toRead = min($chunkSize, $bytesRemaining);

                // Seek to read position and read chunk
                fseek($file, $readPos, SEEK_SET);
                $chunk = fread($file, $toRead);

                if ($chunk === false || $chunk === '') {
                    break;
                }

                // Seek to write position and write the chunk
                fseek($file, $writePos, SEEK_SET);
                $written = fwrite($file, $chunk);

                if ($written === false) {
                    break;
                }

                // Update positions
                $chunkLen = strlen($chunk);
                $readPos += $chunkLen;
                $writePos += $chunkLen;
                $bytesRemaining -= $chunkLen;
            }

            // Truncate file to new size (removing the BOM bytes from the end)
            ftruncate($file, $newSize);

            fclose($file);
            return true;
        } catch (\Exception) {
            fclose($file);
            return false;
        }
    }
}
