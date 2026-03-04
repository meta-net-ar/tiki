<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

if (! function_exists('wikiplugin_slide_helper_collect_files')) {
    function wikiplugin_slide_helper_collect_files($fgalId, $fileIds)
    {
        $files = ['data' => []];
        $filegallib = TikiLib::lib('filegal');

        if ($fgalId) {
            $files = $filegallib->get_files(0, -1, '', '', $fgalId);
            if (empty($files['data'])) {
                $files['data'] = [];
            }
        }

        if ($fileIds) {
            foreach (explode(',', (string) $fileIds) as $fileId) {
                $file = $filegallib->get_file($fileId);
                if (! is_null($file)) {
                    $files['data'][] = $file;
                }
            }
        }

        return $files['data'];
    }
}

if (! function_exists('wikiplugin_slide_helper_parse_blocks')) {
    function wikiplugin_slide_helper_parse_blocks($data)
    {
        $blocks = [];
        $slides = explode('|', ($data ?: ''));

        foreach ($slides as $slide) {
            if (! trim($slide)) {
                continue;
            }

            $slideArr = array_filter(explode(';', str_replace(["\n", "\r"], '', $slide)));
            $parsedSlide = [];
            if (count($slideArr) > 0) {
                foreach ($slideArr as $slideValue) {
                    $slideData = explode(':', $slideValue, 2);
                    if (count($slideData) === 2) {
                        $parsedSlide[trim($slideData[0])] = trim($slideData[1]);
                    }
                }
            }

            if (empty($parsedSlide['text']) && ! empty($slideArr[0])) {
                $parsedSlide['text'] = $slideArr[0];
            }

            $blocks[] = $parsedSlide;
        }

        return $blocks;
    }
}
