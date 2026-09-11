<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.


namespace Tiki\Lib\core\RSS;

use DOMElement;

class CustomEntryField
{
    /**
     * Flexible method to handle dynamic fields.
     * It checks for the existence of a tag dynamically.
     */
    public static function getCustomField($entry, $tagName): array
    {
        $node = $entry->getElement()->getElementsByTagName($tagName)->item(0);

        if ($node) {
            $contentArray = [];

            $contentArray[] = $node->nodeValue;
            foreach ($node->childNodes as $childNode) {
                if ($childNode instanceof DOMElement) {
                    $contentArray[] = $childNode->nodeValue;
                }
            }

            if ($node->hasAttributes()) {
                foreach ($node->attributes as $attrName => $attrNode) {
                    $contentArray[] = "{$attrName}: {$attrNode->nodeValue}";
                }
            }

            return $contentArray;
        }
        return [];
    }
}
