<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Test\Core\LineEnding;

use Tiki\LineEnding\Converter;
use TikiTestCase;

/**
 * Unit tests for LineEnding_Converter::fix() method
 */
class ConverterTest extends TikiTestCase
{
    private $testDir;
    private $converter;

    protected function setUp(): void
    {
        parent::setUp();

        // Create temporary directory for test files
        $this->testDir = sys_get_temp_dir() . '/lineending_test_' . uniqid();
        mkdir($this->testDir, 0777, true);

        $this->converter = new Converter();
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
     * Test fix() converts CRLF to LF by default
     */
    public function testFixConvertsCRLFtoLFByDefault()
    {
        $file = $this->testDir . '/crlf.txt';
        $contentWithCRLF = "line1\r\nline2\r\nline3\r\n";
        $expectedContent = "line1\nline2\nline3\n";
        file_put_contents($file, $contentWithCRLF);

        $affected = $this->converter->fix([$file], false);

        $this->assertCount(1, $affected, 'Should fix 1 file with CRLF');
        $this->assertEquals($file, $affected[0]);

        $fixedContent = file_get_contents($file);
        $this->assertEquals($expectedContent, $fixedContent, 'CRLF should be converted to LF');
        $this->assertStringNotContainsString("\r", $fixedContent, 'No carriage returns should remain');
    }

    /**
     * Test fix() converts CR to LF by default
     */
    public function testFixConvertsCRtoLFByDefault()
    {
        $file = $this->testDir . '/cr.txt';
        $contentWithCR = "line1\rline2\rline3\r";
        $expectedContent = "line1\nline2\nline3\n";
        file_put_contents($file, $contentWithCR);

        $affected = $this->converter->fix([$file], false);

        $this->assertCount(1, $affected);
        $fixedContent = file_get_contents($file);
        $this->assertEquals($expectedContent, $fixedContent, 'CR should be converted to LF');
    }

    /**
     * Test fix() handles mixed line endings
     */
    public function testFixHandlesMixedLineEndings()
    {
        $file = $this->testDir . '/mixed.txt';
        $contentWithMixed = "line1\r\nline2\rline3\nline4\r\n";
        $expectedContent = "line1\nline2\nline3\nline4\n";
        file_put_contents($file, $contentWithMixed);

        $affected = $this->converter->fix([$file], false);

        $this->assertCount(1, $affected);
        $fixedContent = file_get_contents($file);
        $this->assertEquals($expectedContent, $fixedContent, 'Mixed line endings should be normalized to LF');
    }

    /**
     * Test fix() in report-only mode detects but doesn't modify files
     */
    public function testFixInReportOnlyModeDetectsButDoesNotModify()
    {
        $file = $this->testDir . '/crlf_report.txt';
        $contentWithCRLF = "line1\r\nline2\r\n";
        file_put_contents($file, $contentWithCRLF);

        $originalContent = file_get_contents($file);

        // Run fix in report-only mode
        $affected = $this->converter->fix([$file], true);

        // Assert file was detected
        $this->assertCount(1, $affected, 'Should detect 1 file with incorrect line endings');
        $this->assertEquals($file, $affected[0]);

        // Assert file was NOT modified
        $unchangedContent = file_get_contents($file);
        $this->assertEquals($originalContent, $unchangedContent, 'File should not be modified in report-only mode');
        $this->assertStringContainsString("\r\n", $unchangedContent, 'CRLF should still be present');
    }

    /**
     * Test fix() doesn't report files with only LF
     */
    public function testFixDoesNotReportFilesWithOnlyLF()
    {
        $file = $this->testDir . '/lf_only.txt';
        file_put_contents($file, "line1\nline2\nline3\n");

        $affected = $this->converter->fix([$file], false);

        $this->assertCount(0, $affected, 'Should not report files with Unix line endings (LF only)');
    }

    /**
     * Test fix() skips binary files
     */
    public function testFixSkipsBinaryFiles()
    {
        // Create a fake PNG file with CRLF
        $pngFile = $this->testDir . '/image.png';
        file_put_contents($pngFile, "fake\r\nimage\r\ndata");

        $affected = $this->converter->fix([$pngFile], false);

        $this->assertCount(0, $affected, 'Should skip binary files by extension');
    }

    /**
     * Test fix() handles multiple files
     */
    public function testFixHandlesMultipleFiles()
    {
        $file1 = $this->testDir . '/file1.txt';
        $file2 = $this->testDir . '/file2.txt';
        $file3 = $this->testDir . '/file3.txt';

        file_put_contents($file1, "content\r\n");  // Has CRLF
        file_put_contents($file2, "content\n");    // Has LF only
        file_put_contents($file3, "content\r\n");  // Has CRLF

        $affected = $this->converter->fix([$file1, $file2, $file3], false);

        $this->assertCount(2, $affected, 'Should fix 2 files with incorrect line endings');
        $this->assertContains($file1, $affected);
        $this->assertContains($file3, $affected);
        $this->assertNotContains($file2, $affected, 'File with LF only should not be affected');

        // Verify line endings were fixed
        $this->assertEquals("content\n", file_get_contents($file1));
        $this->assertEquals("content\n", file_get_contents($file2));
        $this->assertEquals("content\n", file_get_contents($file3));
    }

    /**
     * Test fix() skips non-existent files
     */
    public function testFixSkipsNonExistentFiles()
    {
        $nonExistent = $this->testDir . '/does_not_exist.txt';

        $affected = $this->converter->fix([$nonExistent], false);

        $this->assertCount(0, $affected, 'Should skip non-existent files');
    }

    /**
     * Test fix() skips empty files
     */
    public function testFixSkipsEmptyFiles()
    {
        $emptyFile = $this->testDir . '/empty.txt';
        file_put_contents($emptyFile, '');

        $affected = $this->converter->fix([$emptyFile], false);

        $this->assertCount(0, $affected, 'Should skip empty files');
    }

    /**
     * Test fix() with empty file array
     */
    public function testFixWithEmptyArray()
    {
        $affected = $this->converter->fix([], false);

        $this->assertCount(0, $affected);
    }

    /**
     * Test fix() with large file (performance check)
     */
    public function testFixHandlesLargeFile()
    {
        $largeFile = $this->testDir . '/large.txt';

        // Create file with 1000 lines with CRLF
        $content = str_repeat("This is a line with content\r\n", 1000);
        file_put_contents($largeFile, $content);

        $affected = $this->converter->fix([$largeFile], false);

        $this->assertCount(1, $affected);

        // Verify all CRLFs were converted
        $fixedContent = file_get_contents($largeFile);
        $this->assertStringNotContainsString("\r", $fixedContent);
    }

    /**
     * Test fix() handles CRLF split across chunk boundary (critical edge case)
     * When \r\n is split at exactly 8192 bytes, leftover handling must work correctly
     */
    public function testFixHandlesCRLFSplitAcrossChunkBoundary()
    {
        $file = $this->testDir . '/chunk_boundary.txt';

        // Create content where \r\n is split at 8KB boundary
        // 8191 bytes + \r (byte 8192) + \n (byte 8193)
        $part1 = str_repeat('x', 8191);
        $crlf = "\r\n";
        $part2 = "after boundary\r\n";
        $content = $part1 . $crlf . $part2;

        file_put_contents($file, $content);

        $affected = $this->converter->fix([$file], false);

        $this->assertCount(1, $affected, 'Should detect file needs conversion');

        // Verify conversion worked correctly
        $fixedContent = file_get_contents($file);
        $expected = $part1 . "\n" . "after boundary\n";
        $this->assertEquals($expected, $fixedContent, 'CRLF split across boundary should be handled correctly');
        $this->assertStringNotContainsString("\r", $fixedContent, 'No carriage returns should remain');
    }

    /**
     * Test fix() handles file ending with \r (leftover handling)
     * Tests that the leftover mechanism works when file ends with carriage return
     */
    public function testFixHandlesFileEndingWithCarriageReturn()
    {
        $file = $this->testDir . '/ending_with_cr.txt';

        // File ending with \r (no \n after it)
        $content = "line1\r\nline2\r";
        file_put_contents($file, $content);

        $affected = $this->converter->fix([$file], false);

        $this->assertCount(1, $affected, 'Should detect file needs conversion');

        // Verify the trailing \r was converted
        $fixedContent = file_get_contents($file);
        $expected = "line1\nline2\n";
        $this->assertEquals($expected, $fixedContent, 'Trailing CR should be converted');
        $this->assertStringNotContainsString("\r", $fixedContent, 'No carriage returns should remain');
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
