<?php

function buildEmbedContainerAndIframe($iframeSrc, $params = [], $oEmbedData = [])
{
    if (empty($iframeSrc) || ! filter_var($iframeSrc, FILTER_VALIDATE_URL)) {
        Feedback::error(tra('Invalid iframe source URL provided.'));
        return '';
    }

    $containerStyles = [
        'position' => 'relative',
        'overflow' => 'hidden',
        'max-width' => '100%',
        'max-height' => '90vh',
        'margin' => '0 auto',
    ];

    $useResponsive = true;
    $aspectRatio = null;

    if (
        isset($oEmbedData['width'], $oEmbedData['height']) &&
        is_numeric($oEmbedData['width']) && $oEmbedData['width'] > 0 &&
        is_numeric($oEmbedData['height']) && $oEmbedData['height'] > 0
    ) {
        $aspectRatio = $oEmbedData['width'] / $oEmbedData['height'];
    }

    if (! empty($params['width']) && is_numeric($params['width']) && $params['width'] > 0) {
        $containerStyles['width'] = $params['width'] . 'px';
        $containerStyles['margin'] = '0 auto';
        $useResponsive = false;
        if (! empty($params['height']) && is_numeric($params['height']) && $params['height'] > 0) {
            $containerStyles['height'] = $params['height'] . 'px';
        } elseif ($aspectRatio) {
            $containerStyles['aspect-ratio'] = $aspectRatio;
        }
    } elseif (! empty($params['height']) && is_numeric($params['height']) && $params['height'] > 0) {
        $containerStyles['height'] = $params['height'] . 'px';
        $useResponsive = false;
        if ($aspectRatio) {
            $containerStyles['width'] = (int)($params['height'] * $aspectRatio) . 'px';
            $containerStyles['margin'] = '0 auto';
        }
    }

    if ($useResponsive) {
        if ($aspectRatio) {
            $containerStyles['aspect-ratio'] = $aspectRatio;
        } else {
            $containerStyles['aspect-ratio'] = '16/9';
        }
    }

    if (! empty($params['borderRadius']) && $params['borderRadius'] === 'y') {
        $containerStyles['border-radius'] = '12px';
    }
    if (! empty($params['bg'])) {
        $containerStyles['background-color'] = $params['bg'];
    }
    if (! empty($params['border'])) {
        $containerStyles['border'] = '1px solid ' . $params['border'];
    }

    $iframeStyles = $useResponsive
        ? ['position' => 'absolute', 'top' => '0', 'left' => '0', 'width' => '100%', 'height' => '100%']
        : ['width' => '100%', 'height' => '100%'];

    $containerStyle = implode(';', array_map(fn($k, $v) => "$k:$v", array_keys($containerStyles), $containerStyles));
    $iframeStyle = implode(';', array_map(fn($k, $v) => "$k:$v", array_keys($iframeStyles), $iframeStyles));

    if (! empty($params['start']) && is_numeric($params['start']) && $params['start'] > 0) {
        $iframeSrc .= (strpos($iframeSrc, '?') === false ? '?' : '&') . 'start=' . $params['start'];
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);

    $div = $dom->createElement('div');
    $div->setAttribute('style', $containerStyle);

    $iframe = $dom->createElement('iframe');
    $iframe->setAttribute('src', $iframeSrc);
    $iframe->setAttribute('frameborder', '0');
    $iframe->setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
    $iframe->setAttribute('title', tra('Embedded media content'));
    $iframe->setAttribute('style', $iframeStyle);

    if (! empty($params['allowFullScreen']) && $params['allowFullScreen'] === 'y') {
        $iframe->setAttribute('allowfullscreen', '');
    }

    $div->appendChild($iframe);
    $dom->appendChild($div);
    libxml_clear_errors();

    return $dom->saveHTML($div);
}
