<?php

namespace Tiki\Lib\core\Toolbar;

/**
 * Definition of the CKE Toolbar Combos
 */
class ToolbarCombos
{
    /**
     * Get the content of the format combo
     *
     * Valid toolbar types are:
     * - 'html': WYSIWYG-HTML
     * - 'wiki': Visual Wiki
     *
     * @param string $tb_type The CKE toolbar type
     */
    public static function getFormatTags(string $tb_type): string
    {
        switch ($tb_type) {
            case 'wiki':
                return 'p;h1;h2;h3;h4;h5;h6';
            case 'html':
            default:
                return 'p;h1;h2;h3;h4;h5;h6;pre;address;div'; // CKE default
        }
    }
}
