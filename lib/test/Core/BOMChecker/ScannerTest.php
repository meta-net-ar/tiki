<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\BOMChecker;

use BOMChecker_Scanner;
use TikiTestCase;

/**
 * Unit tests for BOMChecker_Scanner::fix() method
 */
class ScannerTest extends TikiTestCase
{
    private $testDir;
    private $scanner;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary directory for test files
        $this->testDir = sys_get_temp_dir() . '/bom_test_' . uniqid();
        mkdir($this->testDir, 0777, true);

        $this->scanner = new BOMChecker_Scanner();
    }

    protected function tearDown(): void
    {
        // Clean up test files
        if (is_dir($this->testDir)) {
            $this->recursiveDelete($this->testDir);
        }

        parent::tearDown();
    }

    /**
     * Test fix() method removes UTF-8 BOM by default
     */
    public function testFixRemovesUtf8BomByDefault()
    {
        // Create file with UTF-8 BOM
        $file = $this->testDir . '/utf8_bom.php';
        $content = "<?php\necho 'test';";
        $contentWithBom = "\xEF\xBB\xBF" . $content;
        file_put_contents($file, $contentWithBom);

        // Run fix (default mode should fix)
        $affected = $this->scanner->fix([$file], false);

        // Assert BOM was removed
        $this->assertCount(1, $affected, 'Should report 1 file fixed');
        $this->assertEquals($file, $affected[0]);

        $fixedContent = file_get_contents($file);
        $this->assertEquals($content, $fixedContent, 'BOM should be removed');
        $this->assertStringNotContainsString("\xEF\xBB\xBF", $fixedContent);
    }

    /**
     * Test fix() method removes UTF-16 BE BOM by default
     */
    public function testFixRemovesUtf16BEBomByDefault()
    {
        $file = $this->testDir . '/utf16be_bom.txt';
        $content = "test content";
        $contentWithBom = "\xFE\xFF" . $content;
        file_put_contents($file, $contentWithBom);

        $affected = $this->scanner->fix([$file], false);

        $this->assertCount(1, $affected);
        $fixedContent = file_get_contents($file);
        $this->assertEquals($content, $fixedContent);
        $this->assertStringNotContainsString("\xFE\xFF", $fixedContent);
    }

    /**
     * Test fix() method removes UTF-16 LE BOM by default
     */
    public function testFixRemovesUtf16LEBomByDefault()
    {
        $file = $this->testDir . '/utf16le_bom.txt';
        $content = "test content";
        $contentWithBom = "\xFF\xFE" . $content;
        file_put_contents($file, $contentWithBom);

        $affected = $this->scanner->fix([$file], false);

        $this->assertCount(1, $affected);
        $fixedContent = file_get_contents($file);
        $this->assertEquals($content, $fixedContent);
    }

    /**
     * Test fix() in report-only mode detects but doesn't modify files
     */
    public function testFixInReportOnlyModeDetectsButDoesNotModify()
    {
        $file = $this->testDir . '/utf8_bom_report.php';
        $contentWithBom = "\xEF\xBB\xBF<?php\necho 'test';";
        file_put_contents($file, $contentWithBom);

        $originalContent = file_get_contents($file);

        // Run fix in report-only mode
        $affected = $this->scanner->fix([$file], true);

        // Assert file was detected
        $this->assertCount(1, $affected, 'Should detect 1 file with BOM');
        $this->assertEquals($file, $affected[0]);

        // Assert file was NOT modified
        $unchangedContent = file_get_contents($file);
        $this->assertEquals($originalContent, $unchangedContent, 'File should not be modified in report-only mode');
        $this->assertStringContainsString("\xEF\xBB\xBF", $unchangedContent, 'BOM should still be present');
    }

    /**
     * Test fix() doesn't report files without BOM
     */
    public function testFixDoesNotReportFilesWithoutBom()
    {
        $file = $this->testDir . '/no_bom.php';
        file_put_contents($file, "<?php\necho 'test';");

        $affected = $this->scanner->fix([$file], false);

        $this->assertCount(0, $affected, 'Should not report files without BOM');
    }

    /**
     * Test fix() handles multiple files
     */
    public function testFixHandlesMultipleFiles()
    {
        // Create files with and without BOM
        $fileWithBom1 = $this->testDir . '/bom1.php';
        $fileWithBom2 = $this->testDir . '/bom2.php';
        $fileNoBom = $this->testDir . '/no_bom.php';

        file_put_contents($fileWithBom1, "\xEF\xBB\xBF<?php echo '1';");
        file_put_contents($fileWithBom2, "\xEF\xBB\xBF<?php echo '2';");
        file_put_contents($fileNoBom, "<?php echo '3';");

        $affected = $this->scanner->fix([$fileWithBom1, $fileWithBom2, $fileNoBom], false);

        $this->assertCount(2, $affected, 'Should fix 2 files with BOM');
        $this->assertContains($fileWithBom1, $affected);
        $this->assertContains($fileWithBom2, $affected);

        // Verify BOM was removed
        $this->assertStringNotContainsString("\xEF\xBB\xBF", file_get_contents($fileWithBom1));
        $this->assertStringNotContainsString("\xEF\xBB\xBF", file_get_contents($fileWithBom2));
    }

    /**
     * Test fix() skips non-existent files
     */
    public function testFixSkipsNonExistentFiles()
    {
        $nonExistent = $this->testDir . '/does_not_exist.php';

        $affected = $this->scanner->fix([$nonExistent], false);

        $this->assertCount(0, $affected, 'Should skip non-existent files');
    }

    /**
     * Test fix() handles large files efficiently using in-place modification
     */
    public function testFixHandlesLargeFile()
    {
        $file = $this->testDir . '/large_file.txt';

        // Create a large file (1MB) with UTF-8 BOM
        // Generate content larger than the chunk size (8KB) to test streaming
        $contentPart = str_repeat("Line of text with some content\n", 1000); // ~32KB
        $largeContent = str_repeat($contentPart, 32); // ~1MB
        $contentWithBom = "\xEF\xBB\xBF" . $largeContent;

        file_put_contents($file, $contentWithBom);

        // Get original file stats before fix
        $originalPerms = fileperms($file);
        $originalSize = filesize($file);

        $affected = $this->scanner->fix([$file], false);

        // Verify file was processed
        $this->assertCount(1, $affected, 'Should fix 1 large file with BOM');

        // Verify BOM was removed
        $fixedContent = file_get_contents($file);
        $this->assertEquals($largeContent, $fixedContent, 'Large file content should match (without BOM)');
        $this->assertStringNotContainsString("\xEF\xBB\xBF", $fixedContent, 'BOM should be removed');

        // Clear stat cache to get updated file size
        clearstatcache(true, $file);

        // Verify file size decreased by 3 bytes (UTF-8 BOM size)
        $newSize = filesize($file);
        $this->assertEquals($originalSize - 3, $newSize, 'File size should decrease by 3 bytes');

        // Verify permissions are preserved
        $newPerms = fileperms($file);
        $this->assertEquals($originalPerms, $newPerms, 'File permissions should be preserved');
    }

    /**
     * Test fix() with empty file array
     */
    public function testFixWithEmptyArray()
    {
        $affected = $this->scanner->fix([], false);

        $this->assertCount(0, $affected);
    }

    /**
     * Helper method to recursively delete directory
     */
    private function recursiveDelete($dir)
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}
