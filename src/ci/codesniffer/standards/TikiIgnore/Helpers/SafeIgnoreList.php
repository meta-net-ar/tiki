<?php

namespace Tiki\Standards\TikiIgnore\Helpers;

use PHP_CodeSniffer\Util\IgnoreList;

class SafeIgnoreList
{
    public static function forLine(): IgnoreList
    {
        // Using the internal factory safely.
        return IgnoreList::getInstanceIgnoringNothing();
    }

    /**
     * Mark a given sniff as ignored on the given line.
     */
    public static function markIgnored(IgnoreList $ignoreList, string $sniffCode): void
    {
        $ignoreList->set($sniffCode, true);
    }
}
