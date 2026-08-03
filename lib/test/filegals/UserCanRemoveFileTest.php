<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Test\Filegals;

use TikiLib;
use TikiTestCase;

/**
 * Locks in the shared file-deletion model in FileGalLib::userCanRemoveFile(), which
 * every delete entry point routes through, so it can't silently drift between views.
 *
 * With $galInfo and $perms both passed, the method touches no DB; the only external
 * input is the global $user, which each case sets.
 */
class UserCanRemoveFileTest extends TikiTestCase
{
    /** @var string|null saved global $user, restored after each test */
    private $savedUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->savedUser = $GLOBALS['user'] ?? null;
    }

    protected function tearDown(): void
    {
        $GLOBALS['user'] = $this->savedUser;
        parent::tearDown();
    }

    /**
     * @param array $overrides perms to force to 'y' (everything else defaults to 'n')
     */
    private function perms(array $granted = []): array
    {
        $perms = [
            'tiki_p_admin_file_galleries' => 'n',
            'tiki_p_remove_files'         => 'n',
            'tiki_p_edit_gallery_file'    => 'n',
        ];
        foreach ($granted as $name) {
            $perms[$name] = 'y';
        }
        return $perms;
    }

    private function file(string $owner, int $galleryId = 5, int $fileId = 42): array
    {
        return ['fileId' => $fileId, 'galleryId' => $galleryId, 'user' => $owner];
    }

    private function gallery(string $owner = ''): array
    {
        return ['user' => $owner];
    }

    private function can($fileInfo, $galInfo, $perms): bool
    {
        return TikiLib::lib('filegal')->userCanRemoveFile($fileInfo, $galInfo, $perms);
    }

    public function testAdminCanRemoveAnyFile(): void
    {
        $GLOBALS['user'] = 'bob';
        // Not the owner, no remove_files, but admin overrides everything.
        $this->assertTrue($this->can(
            $this->file('alice'),
            $this->gallery('alice'),
            $this->perms(['tiki_p_admin_file_galleries'])
        ));
    }

    public function testRemoveFilesPermissionCanRemoveOthersFile(): void
    {
        $GLOBALS['user'] = 'bob';
        $this->assertTrue($this->can(
            $this->file('alice'),
            $this->gallery('alice'),
            $this->perms(['tiki_p_remove_files'])
        ));
    }

    public function testFileOwnerCanRemoveWithoutRemovePermission(): void
    {
        $GLOBALS['user'] = 'bob';
        // Owns the file, no remove_files, not admin, still allowed.
        $this->assertTrue($this->can(
            $this->file('bob'),
            $this->gallery('alice'),
            $this->perms()
        ));
    }

    public function testGalleryOwnerCanRemoveWithoutRemovePermission(): void
    {
        $GLOBALS['user'] = 'bob';
        $this->assertTrue($this->can(
            $this->file('alice'),
            $this->gallery('bob'),
            $this->perms()
        ));
    }

    public function testNonOwnerWithoutPermissionsCannotRemove(): void
    {
        $GLOBALS['user'] = 'bob';
        $this->assertFalse($this->can(
            $this->file('alice'),
            $this->gallery('alice'),
            $this->perms()
        ));
    }

    public function testEditGalleryFileAloneDoesNotAllowRemoval(): void
    {
        $GLOBALS['user'] = 'bob';
        // edit_gallery_file governs renaming/editing, not deletion.
        $this->assertFalse($this->can(
            $this->file('alice'),
            $this->gallery('alice'),
            $this->perms(['tiki_p_edit_gallery_file'])
        ));
    }

    public function testAnonymousIsNotTreatedAsOwner(): void
    {
        // Empty user must never match an empty file/gallery owner as "ownership".
        $GLOBALS['user'] = '';
        $this->assertFalse($this->can(
            $this->file(''),
            $this->gallery(''),
            $this->perms()
        ));
    }

    public function testAnonymousWithRemovePermissionCanRemove(): void
    {
        $GLOBALS['user'] = '';
        $this->assertTrue($this->can(
            $this->file(''),
            $this->gallery(''),
            $this->perms(['tiki_p_remove_files'])
        ));
    }

    public function testMissingFileIdReturnsFalse(): void
    {
        $GLOBALS['user'] = 'bob';
        // No fileId, refuse even for an admin, rather than acting on nothing.
        $this->assertFalse($this->can(
            ['galleryId' => 5, 'user' => 'bob'],
            $this->gallery('bob'),
            $this->perms(['tiki_p_admin_file_galleries'])
        ));
    }

    public function testNonArrayFileInfoReturnsFalse(): void
    {
        $GLOBALS['user'] = 'bob';
        // Guards against get_file()/get_file_info() returning false for a bad id.
        $this->assertFalse($this->can(
            false,
            $this->gallery('bob'),
            $this->perms(['tiki_p_admin_file_galleries'])
        ));
    }
}
