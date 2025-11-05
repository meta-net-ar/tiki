<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

function wikiplugin_qr_info()
{
    return [
        'name'          => tra('QR Code'),
        'documentation' => 'PluginQR',
        'description'   => tra('Generate QR Code'),
        'body'          => tra('Data'),
        'prefs'         => ['wikiplugin_qr'],
        'format'        => 'html',
        'params'        => [
            'size' => [
                'required' => false,
                'name' => tra('Size'),
                'description' => tra('Size of QR Code'),
                'since' => '23.0',
                'filter' => 'digits',
                'default' => 350,
            ],
        ],
    ];
}

function wikiplugin_qr($data, $params)
{

    // default size
    $params['size'] = (int) ($params['size'] ?: 350);

    if (extension_loaded('imagick')) {
        $imageBackEnd = new ImagickImageBackEnd();
        $imageType = 'png';
    } else {
        $imageBackEnd = new SvgImageBackEnd();
        $imageType = 'svg+xml';
    }

    $writer = new Writer(
        new ImageRenderer(
            new RendererStyle($params['size']),
            $imageBackEnd
        )
    );
    $tfaSecretQR = base64_encode($writer->writeString($data));
    return '<img src="data:image/' . $imageType . ';base64,' . $tfaSecretQR . '"/>';
}
