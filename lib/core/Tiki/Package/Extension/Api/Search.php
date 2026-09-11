<?php

namespace Tiki\Package\Extension\Api;

use Tiki\Package\Extension\Api;

class Search extends Api
{
    protected static $sources = [];

    public static function setSources($package, $sources)
    {
        foreach ($sources as $source) {
            try {
                self::$sources[] = new $source->class();
            } catch (\Exception $e) {
                (new \Tiki\Lib\Logs\Tiki_Monolog())->error(tr('Unexpected error occurred'), [
                    'component' => __CLASS__,
                    'method' => __FUNCTION__,
                    'package' => $package,
                    'source' => $source,
                    'exception' => $e->getMessage(),
                ]);
            }
        }
    }

    public static function getSources()
    {
        return self::$sources;
    }
}
