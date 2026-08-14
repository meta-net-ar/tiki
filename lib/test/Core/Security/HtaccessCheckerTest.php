<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Test\Core\Security;

use PHPUnit\Framework\TestCase;
use Tiki\Security\HtaccessChecker;

class HtaccessCheckerTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/tiki_htaccess_' . uniqid('', true);
        if (! mkdir($this->tempDir) && ! is_dir($this->tempDir)) {
            $this->fail('Unable to create temporary directory for tests.');
        }
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempDir);
    }

    public function testIdenticalFilesReportOk(): void
    {
        $this->createReference();
        copy($this->tempDir . '/_htaccess', $this->tempDir . '/.htaccess');

        $checker = new HtaccessChecker();
        $result = $checker->run($this->tempDir, $this->tempDir . '/_htaccess', ['server_software' => 'Apache/2.4']);

        $this->assertSame(HtaccessChecker::STATUS_OK, $result['status']);
        $this->assertSame('exact_match', $result['code']);
    }

    public function testSymlinkToReferenceIsOk(): void
    {
        if (! function_exists('symlink')) {
            $this->markTestSkipped('symlink() not available on this platform.');
        }

        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('Windows symlink support requires elevated privileges.');
        }

        $this->createReference();
        $target = $this->tempDir . '/.htaccess';
        symlink($this->tempDir . '/_htaccess', $target);

        $checker = new HtaccessChecker();
        $result = $checker->run($this->tempDir, $this->tempDir . '/_htaccess', ['server_software' => 'Apache/2.4']);

        $this->assertSame(HtaccessChecker::STATUS_OK, $result['status']);
        $this->assertSame('symlink_to_reference', $result['code']);
    }

    public function testMismatchProducesDiff(): void
    {
        $this->createReference();
        $this->createFile($this->tempDir . '/.htaccess', "# custom\nRewriteEngine Off\n");

        $checker = new HtaccessChecker();
        $result = $checker->run($this->tempDir, $this->tempDir . '/_htaccess', ['server_software' => 'Apache/2.4']);

        $this->assertSame(HtaccessChecker::STATUS_MISMATCH, $result['status']);
        $this->assertSame('hash_mismatch', $result['code']);
        $this->assertNotEmpty($result['diff']);
    }

    public function testMissingHtaccessReportedAsNotApplicable(): void
    {
        $this->createReference();
        $checker = new HtaccessChecker();
        $result = $checker->run($this->tempDir, $this->tempDir . '/_htaccess', ['server_software' => 'Apache/2.4']);

        $this->assertSame(HtaccessChecker::STATUS_NOT_APPLICABLE, $result['status']);
        $this->assertSame('missing_htaccess', $result['code']);
    }

    public function testUnreadableHtaccessReturnsUnreadable(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            $this->markTestSkipped('File permission semantics differ on Windows.');
        }

        $this->createReference();
        $path = $this->tempDir . '/.htaccess';
        $this->createFile($path, "# restricted\n");
        chmod($path, 0222);

        // Skip test if running as root where chmod doesn't restrict reading
        if (is_readable($path)) {
            chmod($path, 0644);
            $this->markTestSkipped('Cannot test unreadable file when running as root or with insufficient permission restrictions.');
        }

        $checker = new HtaccessChecker();
        $result = $checker->run($this->tempDir, $this->tempDir . '/_htaccess', ['server_software' => 'Apache/2.4']);

        $this->assertSame(HtaccessChecker::STATUS_UNREADABLE, $result['status']);
        $this->assertSame('htaccess_unreadable', $result['code']);

        chmod($path, 0644);
    }

    public function testNonApacheServerSkipsCheck(): void
    {
        $this->createReference();
        copy($this->tempDir . '/_htaccess', $this->tempDir . '/.htaccess');

        $checker = new HtaccessChecker();
        $result = $checker->run($this->tempDir, $this->tempDir . '/_htaccess', ['server_software' => 'nginx/1.24']);

        $this->assertSame(HtaccessChecker::STATUS_NOT_APPLICABLE, $result['status']);
        $this->assertSame('server_not_apache', $result['code']);
    }

    public function testMaintenanceMatch(): void
    {
        $this->createReference();
        $maintenanceContent = "# maintenance\nRewriteEngine On\nRewriteRule ^ maintenance.html [L]\n";
        $this->createFile($this->tempDir . '/.htaccess', $maintenanceContent);

        $checker = new HtaccessChecker();
        $result = $checker->run(
            $this->tempDir,
            $this->tempDir . '/_htaccess',
            [
                'server_software' => 'Apache/2.4',
                'maintenance_contents' => ['fixture' => $maintenanceContent],
            ]
        );

        $this->assertSame(HtaccessChecker::STATUS_MAINTENANCE, $result['status']);
        $this->assertSame('maintenance_variant', $result['code']);
        $this->assertSame('fixture', $result['maintenance_source']['source']);
    }

    private function createReference(): void
    {
        $this->createFile($this->tempDir . '/_htaccess', "# reference\nRewriteEngine On\n");
    }

    private function createFile(string $path, string $contents): void
    {
        if (file_put_contents($path, $contents) === false) {
            $this->fail('Unable to write fixture file: ' . $path);
        }
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @chmod($item->getPathname(), 0644);
                @unlink($item->getPathname());
            }
        }

        @rmdir($dir);
    }
}
