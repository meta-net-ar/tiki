<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\Unoconv\UnoconvStrategy;
use Tiki\Lib\Unoconv\UnoserverStrategy;

function prefs_alchemy_list()
{
    $mediaAlchemystHelp = 'Media-Alchemyst';

    $prefs = [
        'alchemy_converter_type' => [
            'name' => tra('Document Converter'),
            'description' => tra('Select the converter to use for office documents.'),
            'type' => 'list',
            'options' => [
                UnoconvStrategy::NAME => tra('Unoconv'),
                UnoserverStrategy::NAME => tra('Unoserver')
            ],
            'default' => UnoconvStrategy::NAME,
            'help' => $mediaAlchemystHelp,
        ],
        'alchemy_ffmpeg_path' => [
            'name' => tra('ffmpeg path'),
            'description' => tra('Path to the location of the ffmpeg binary'),
            'type' => 'text',
            'help' => 'https://www.ffmpeg.org/',
            'size' => '256',
            'default' => '/usr/bin/ffmpeg',
        ],
        'alchemy_ffprobe_path' => [
            'name' => tra('ffprobe path'),
            'description' => tra('Path to the location of the ffprobe binary'),
            'type' => 'text',
            'help' => 'https://ffmpeg.org/ffprobe.html',
            'size' => '256',
            'default' => '/usr/bin/ffprobe',
        ],
        'alchemy_imagine_driver' => [
            'name' => tra('Alchemy Image library'),
            'description' => tra('Select either Image Magick or GD Graphics Library.'),
            'type' => 'list',
            'help' => 'https://imagine.readthedocs.io/en/latest/usage/introduction.html#drivers',
            'options' => [
                'imagick' => tra('Imagemagick'),
                'gd' => tra('GD')
            ],
            'default' => 'imagick',
        ],
        'alchemy_unoconv_path' => [
            'name' => tra('unoconv path'),
            'description' => tra('Path to the location of the unoconv binary.'),
            'type' => 'text',
            'size' => '256',
            'default' => '/usr/bin/unoconv',
            'help' => $mediaAlchemystHelp,
        ],
        'alchemy_gs_path' => [
            'name' => tra('ghostscript path'),
            'description' => tra('Path to the location of the ghostscript binary.'),
            'type' => 'text',
            'size' => '256',
            'default' => '/usr/bin/gs',
            'help' => $mediaAlchemystHelp,
        ],
        'alchemy_unoconv_timeout' => [
            'name' => tra('unoconv timeout'),
            'description' => tra('The maximum amount of time for unoconv to execute.'),
            'filter' => 'digits',
            'type' => 'text',
            'default' => 60,
            'units' => tra('seconds'),
            'help' => $mediaAlchemystHelp,
        ],
        'alchemy_unoconv_port' => [
            'name' => tra('unoconv port'),
            'description' => tra('unoconv running port.'),
            'type' => 'text',
            'size' => '5',
            'filter' => 'digits',
            'default' => UnoconvStrategy::DEFAULT_PORT,
            'help' => $mediaAlchemystHelp,
        ],
        'alchemy_unoserver_port' => [
            'name' => tra('unoserver port'),
            'description' => tra('unoserver daemon port.'),
            'type' => 'text',
            'size' => '5',
            'filter' => 'digits',
            'default' => UnoserverStrategy::DEFAULT_PORT,
            'help' => $mediaAlchemystHelp,
        ],
    ];

    if (! class_exists('\Imagick')) {
        $prefs['alchemy_imagine_driver']['options']['imagick'] .= tr(' (Extension not loaded)');
    }

    if (! extension_loaded('gd')) {
        $prefs['alchemy_imagine_driver']['options']['gd'] .= tr(' (Extension not loaded)');
    }

    return $prefs;
}
