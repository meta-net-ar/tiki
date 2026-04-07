<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace test\Tiki\Lib\wiki;

use HeaderLib;
use TestableTikiLib;
use TikiLib;
use TikiTestCase;
use UsersLib;
use WikiParser_PluginOutput;

require_once(__DIR__ . '/../../wiki-plugins/wikiplugin_model3dviewer.php');

class Model3DViewerTest extends TikiTestCase
{
    public $overrideLibs;
    public $headerlib;
    public $filegallib;
    public $userlib;
    public $originalPrefs;
    public $originalUser;
    public $originalBaseHost;
    public $originalUrlPath;
    public $originalTikiDomain;

    protected function setUp(): void
    {
        parent::setUp();

        global $prefs, $user, $base_host, $url_path, $tikidomain;

        $this->originalPrefs = $prefs;
        $this->originalUser = $user ?? null;
        $this->originalBaseHost = $base_host ?? null;
        $this->originalUrlPath = $url_path ?? null;
        $this->originalTikiDomain = $tikidomain ?? null;

        $this->headerlib = new HeaderLib();
        $this->filegallib = $this->getMockBuilder(\Tiki\Lib\Filegals\FileGalLib::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->userlib = $this->getMockBuilder(UsersLib::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->overrideLibs = new TestableTikiLib();
        $this->overrideLibs->overrideLibs([
            'header' => $this->headerlib,
            'filegal' => $this->filegallib,
            'user' => $this->userlib,
        ]);

        $user = 'testuser';
        $prefs['wikiplugin_model3dviewer'] = 'y';
    }

    protected function tearDown(): void
    {
        global $prefs, $user, $base_host, $url_path, $tikidomain;

        unset($this->overrideLibs);

        $prefs = $this->originalPrefs;
        $user = $this->originalUser;
        $base_host = $this->originalBaseHost;
        $url_path = $this->originalUrlPath;
        $tikidomain = $this->originalTikiDomain;

        parent::tearDown();
    }

    // --- wikiplugin_model3dviewer_info() ---

    public function testInfoReturnsExpectedStructure(): void
    {
        $info = wikiplugin_model3dviewer_info();

        $this->assertIsArray($info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('params', $info);
        $this->assertArrayHasKey('prefs', $info);
        $this->assertContains('wikiplugin_model3dviewer', $info['prefs']);
    }

    public function testInfoDeclaresAllExpectedParams(): void
    {
        $info = wikiplugin_model3dviewer_info();

        $expectedParams = [
            'type', 'fileId', 'src', 'camera_position', 'camera_type',
            'autorotate', 'backgroundColor', 'height', 'width', 'desc',
            'controls', 'shadow', 'light_type', 'exposure', 'autoplay', 'animation_loop',
        ];

        foreach ($expectedParams as $param) {
            $this->assertArrayHasKey($param, $info['params'], "Missing param: $param");
        }
    }

    // --- resolve_model3dviewer_src() ---

    public function testResolveSrcReturnsExternalUrl(): void
    {
        $params = ['src' => 'https://example.com/model.glb'];
        $result = resolve_model3dviewer_src($params, false, 'model.glb');

        $this->assertSame('https://example.com/model.glb', $result);
    }

    public function testResolveSrcStripsWhitespace(): void
    {
        $params = ['src' => ' https://example.com/ model .glb '];
        $result = resolve_model3dviewer_src($params, false, 'model.glb');

        $this->assertSame('https://example.com/model.glb', $result);
    }

    public function testResolveSrcSanitizesJavascriptUrl(): void
    {
        $params = ['src' => 'javascript:alert(1)'];
        $result = resolve_model3dviewer_src($params, false, 'model.glb');

        $this->assertEmpty($result);
    }

    public function testResolveSrcSanitizesJavascriptUrlCaseInsensitive(): void
    {
        $params = ['src' => 'JavaScript:alert(document.cookie)'];
        $result = resolve_model3dviewer_src($params, false, 'model.glb');

        $this->assertEmpty($result);
    }

    public function testResolveSrcBuildsAbsoluteLinks(): void
    {
        global $base_host, $url_path;
        $base_host = 'https://wiki.example.com';
        $url_path = '/tiki/';

        $params = ['src' => 'files/model.glb'];
        $result = resolve_model3dviewer_src($params, true, 'model.glb');

        $this->assertSame('https://wiki.example.com/tiki/files/model.glb', $result);
    }

    public function testResolveSrcAbsoluteLinksWithLeadingSlash(): void
    {
        global $base_host, $url_path;
        $base_host = 'https://wiki.example.com';
        $url_path = '/tiki/';

        $params = ['src' => '/files/model.glb'];
        $result = resolve_model3dviewer_src($params, true, 'model.glb');

        $this->assertSame('https://wiki.example.com/files/model.glb', $result);
    }

    public function testResolveSrcReturnsFalseWhenNoSource(): void
    {
        $params = [];
        $result = resolve_model3dviewer_src($params, false, 'model.glb');

        $this->assertFalse($result);
    }

    public function testResolveSrcWithFileId(): void
    {
        $params = ['fileId' => '42'];
        $result = resolve_model3dviewer_src($params, false, 'scene.glb');

        $this->assertIsString($result);
        $this->assertStringContainsString('42', $result);
        $this->assertStringContainsString('scene.glb', $result);
    }

    // --- wikiplugin_model3dviewer() ---

    private function makeParams(array $overrides = []): array
    {
        return array_merge([
            'type' => 'src',
            'src' => '',
            'fileId' => '',
            'controls' => 'y',
            'autorotate' => 'n',
            'camera_position' => '',
            'camera_type' => 'perspective',
            'shadow' => 'n',
            'light_type' => '',
            'exposure' => '1.0',
            'autoplay' => 'n',
            'animation_loop' => 'n',
            'backgroundColor' => '',
            'height' => '400px',
            'width' => '100%',
        ], $overrides);
    }

    public function testReturnsErrorWhenSourceIsMissing(): void
    {
        $params = $this->makeParams();

        $result = wikiplugin_model3dviewer('', $params);

        $this->assertInstanceOf(WikiParser_PluginOutput::class, $result);
        $this->assertStringContainsString('Missing', $result->toWiki());
    }

    public function testReturnsPermissionDeniedForProtectedFile(): void
    {
        $this->filegallib->method('get_file')->willReturn([
            'fileId' => '10',
            'fileUrl' => 'tiki-download_file.php?fileId=10',
            'filetype' => 'model/gltf-binary',
            'filename' => 'model.glb',
            'description' => '',
        ]);

        $this->userlib->method('user_has_perm_on_object')->willReturn(false);

        $params = $this->makeParams([
            'type' => 'fileId',
            'fileId' => '10',
        ]);

        $result = wikiplugin_model3dviewer('', $params);

        $this->assertIsString($result);
        $this->assertStringContainsString('permission', $result);
    }

    public function testRendersViewerForExternalSource(): void
    {
        $params = $this->makeParams([
            'src' => 'https://example.com/model.glb',
            'autorotate' => 'y',
            'camera_position' => '0,1,5',
            'shadow' => 'y',
            'light_type' => 'studio',
            'exposure' => '1.5',
            'animation_loop' => 'y',
            'backgroundColor' => '#ff0000',
            'height' => '500px',
            'width' => '80%',
        ]);

        $result = wikiplugin_model3dviewer('', $params);

        $this->assertIsString($result);
        $this->assertStringContainsString('~np~', $result);
        $this->assertStringContainsString('model3dviewer', $result);

        $scripts = $this->headerlib->js_modules[0] ?? [];
        $this->assertNotEmpty($scripts, 'Expected JS module to be added');
        $lastJs = end($scripts);
        $this->assertStringContainsString('example.com/model.glb', $lastJs);
        $this->assertStringContainsString('autoRotate: true', $lastJs);
        $this->assertStringContainsString('loop: true', $lastJs);
        $this->assertStringContainsString('backgroundColor: "#ff0000"', $lastJs);
        $this->assertStringContainsString('shadow: true', $lastJs);
        $this->assertStringContainsString('camera: "0,1,5"', $lastJs);
    }

    public function testRendersViewerForFileGallerySource(): void
    {
        $this->filegallib->method('get_file')->willReturn([
            'fileId' => '5',
            'fileUrl' => 'tiki-download_file.php?fileId=5',
            'filetype' => 'model/gltf-binary',
            'filename' => 'robot.glb',
            'description' => 'A robot model',
        ]);

        $this->userlib->method('user_has_perm_on_object')->willReturn(true);

        $params = $this->makeParams([
            'type' => 'fileId',
            'fileId' => '5',
            'autoplay' => 'y',
        ]);

        $result = wikiplugin_model3dviewer('', $params);

        $this->assertIsString($result);
        $this->assertStringContainsString('~np~', $result);
        $this->assertStringContainsString('model3dviewer', $result);

        $scripts = $this->headerlib->js_modules[0] ?? [];
        $this->assertNotEmpty($scripts);
        $lastJs = end($scripts);
        $this->assertStringContainsString('autoRotate: false', $lastJs);
        $this->assertStringContainsString('autoplay: true', $lastJs);
        $this->assertStringContainsString('loop: false', $lastJs);
    }

    public function testDefaultBackgroundColorFallsBackToPref(): void
    {
        global $prefs;
        $prefs['theme_model3dviewer_default_background'] = '#aabbcc';

        $params = $this->makeParams([
            'src' => 'https://example.com/model.glb',
        ]);

        $result = wikiplugin_model3dviewer('', $params);

        $scripts = $this->headerlib->js_modules[0] ?? [];
        $this->assertNotEmpty($scripts);
        $lastJs = end($scripts);
        $this->assertStringContainsString('backgroundColor: "#aabbcc"', $lastJs);
    }
}
