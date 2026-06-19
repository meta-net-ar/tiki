<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
// $Id$

require_once(__DIR__ . '/../../wiki-plugins/wikiplugin_youtube.php');

class WikiPlugin_YoutubeTest extends PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        global $prefs;

        $prefs['http_header_referrer_policy_value'] = 'strict-origin-when-cross-origin';
    }

    public function testWikiPluginCodeWithDefaultReferrerPolicy(): void
    {
        global $prefs;

        $referrerPolicy = $prefs['http_header_referrer_policy_value'];

        $this->assertEquals('^Plugin YouTube error: the movie parameter is empty.^', wikiplugin_youtube('', []));
        $this->assertEquals(
            '~np~<iframe src="//www.youtube.com/embed/bPHuY7QL568?" frameborder="0" width="425" height="350" allowfullscreen="" referrerpolicy="' . $referrerPolicy . '"></iframe>~/np~',
            wikiplugin_youtube('', ['movie' => 'http://www.youtube.com/watch?v=bPHuY7QL568'])
        );
        $this->assertEquals(
            '~np~<iframe src="//www.youtube.com/embed/deby_Yb1-ac?" frameborder="0" width="425" height="350" allowfullscreen="" referrerpolicy="' . $referrerPolicy . '"></iframe>~/np~',
            wikiplugin_youtube('', ['movie' => 'https://www.youtube.com/watch?v=deby_Yb1-ac'])
        );
        $this->assertEquals(
            '~np~<iframe src="//www.youtube.com/embed/deby_Yb1-ac?" frameborder="0" width="425" height="350" allowfullscreen="" referrerpolicy="' . $referrerPolicy . '"></iframe>~/np~',
            wikiplugin_youtube('', ['movie' => 'https://youtu.be/deby_Yb1-ac'])
        );
        $this->assertEquals(
            '~np~<iframe src="//www.youtube-nocookie.com/embed/deby_Yb1-ac?" frameborder="0" width="425" height="350" allowfullscreen="" referrerpolicy="' . $referrerPolicy . '"></iframe>~/np~',
            wikiplugin_youtube('', ['movie' => 'https://youtu.be/deby_Yb1-ac', 'privacyEnhanced' => 'y'])
        );
    }
}
