<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

namespace Tiki\Lib\Image;

use Exception;

class ImageFileHandler
{
    /**
     * Converts a HEIC file to JPEG.
     *
     * @param string $imagepath Path to the HEIC image file.
     * @return string The content of the converted image.
     * @throws Exception If the conversion fails.
     */
    public function convertHeicToJpg(string $imagepath): string
    {
        if (! class_exists('Maestroerror\HeicToJpg')) {
            throw new Exception('HEIC/HEIF conversion class not found');
        }

        try {
            return \Maestroerror\HeicToJpg::convert($imagepath)->get();
        } catch (Exception $e) {
            throw new Exception('Error converting HEIC/HEIF image: ' . $e->getMessage());
        }
    }

    /**
     * Processes the given file info and converts HEIC/HEIF files to JPEG.
     *
     * @param object $fileInfo Contains file metadata (like filename).
     * @param object $wrapper Wrapper for file operations.
     * @return string|null The content of the converted image, or null if no conversion was needed.
     * @throws Exception If the conversion fails or the file type is unsupported.
     */
    public function processFile($fileInfo, $wrapper): string
    {
        $mimelib = \TikiLib::lib('mime');
        $filetype = $mimelib->from_content($fileInfo['filename'], $wrapper->getContents());

        if ($filetype === 'image/heic' || $filetype === 'image/heif') {
            $imagepath = $wrapper->getReadableFile();
            return $this->convertHeicToJpg($imagepath);
        } else {
            return $wrapper->getContents();
        }
    }

    // TODO: Add support for other image formats like AVIF and JPEG XL in another MR.
}
