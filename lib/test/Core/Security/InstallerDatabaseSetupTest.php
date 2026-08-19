<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Test\Core\Security;

use Exception;
use PHPUnit\Framework\TestCase;
use Tiki\Installer\BaseImageResolver;

class InstallerDatabaseSetupTest extends TestCase
{
    private string $tempRoot;
    private string $dbDirectory;

    protected function setUp(): void
    {
        $this->tempRoot = sys_get_temp_dir() . '/tiki_installer_' . uniqid('', true);
        $this->dbDirectory = $this->tempRoot . '/db';

        if (! mkdir($this->dbDirectory, 0777, true) && ! is_dir($this->dbDirectory)) {
            $this->fail('Unable to create temporary installer directory for tests.');
        }
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tempRoot);
    }

    public function testCustomSqlIsIgnoredWithoutInstallIni(): void
    {
        $this->createFile($this->dbDirectory . '/custom_tiki.sql', "SELECT 1;\n");

        $resolver = new BaseImageResolver($this->tempRoot);

        $this->assertNull($resolver->resolve());
    }

    public function testExplicitLocalSourceDefaultsToCustomSql(): void
    {
        $expected = $this->dbDirectory . '/custom_tiki.sql';
        $this->createFile($this->dbDirectory . '/install.ini', "source.type = local\n");
        $this->createFile($expected, "SELECT 1;\n");

        $resolver = new BaseImageResolver($this->tempRoot);

        $this->assertSame(realpath($expected), $resolver->resolve());
    }

    public function testExplicitLocalSourceCanUseDbSubdirectories(): void
    {
        $expected = $this->dbDirectory . '/backups/dump.sql';
        $this->createFile($this->dbDirectory . '/install.ini', "source.type = local\nsource.file = backups/dump.sql\n");
        $this->createFile($expected, "SELECT 1;\n");

        $resolver = new BaseImageResolver($this->tempRoot);

        $this->assertSame(realpath($expected), $resolver->resolve());
    }

    public function testRejectsLocalSourceOutsideDbDirectory(): void
    {
        $this->createFile($this->tempRoot . '/outside.sql', "SELECT 1;\n");
        $this->createFile($this->dbDirectory . '/install.ini', "source.type = local\nsource.file = ../outside.sql\n");

        $resolver = new BaseImageResolver($this->tempRoot);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Local install base images must be stored inside db/.');
        $resolver->resolve();
    }

    public function testRejectsRemoteSourceTypes(): void
    {
        $this->createFile(
            $this->dbDirectory . '/install.ini',
            "source.type = http\nsource.file = https://example.com/dump.sql\n"
        );

        $resolver = new BaseImageResolver($this->tempRoot);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Remote install base images are no longer supported.');
        $resolver->resolve();
    }

    private function createFile(string $path, string $contents): void
    {
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            $this->fail('Unable to create fixture directory: ' . $directory);
        }

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
