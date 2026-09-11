<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\Lib\Unoconv;

class UnoconvLib implements ConverterInterface
{
    private ?ConverterInterface $strategy = null;

    public function __construct()
    {
        global $prefs;

        $converterType = $prefs['alchemy_converter_type'] ?: UnoconvStrategy::NAME;

        if ($converterType === UnoserverStrategy::NAME && UnoserverStrategy::isLibraryAvailable()) {
            $this->strategy = new UnoserverStrategy();
        } elseif ($converterType === UnoconvStrategy::NAME && UnoconvStrategy::isLibraryAvailable()) {
            $this->strategy = new UnoconvStrategy();
        }
    }

    public function convertFile($sourcePath, $destinationPath, $format = 'pdf', $pageRange = null)
    {
        if (! $this->strategy) {
            throw new \Exception(tr("No converter available. Please install unoserver or unoconv."));
        }

        $this->strategy->convertFile($sourcePath, $destinationPath, $format, $pageRange);
    }

    public static function isLibraryAvailable()
    {
        global $prefs;

        $converterType = $prefs['alchemy_converter_type'] ?: UnoconvStrategy::NAME;

        // Check availability based on configured converter
        if ($converterType === UnoserverStrategy::NAME) {
            return UnoserverStrategy::isLibraryAvailable();
        }

        return UnoconvStrategy::isLibraryAvailable();
    }
}
