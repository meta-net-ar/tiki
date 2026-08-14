<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\ObjectRenderer;

use TikiLib;

class MultilingualWiki extends AbstractObjectRenderer
{
    private $renderers = [];

    /**
     * @param $type
     * @param $object
     * @param array $options
     */
    public function __construct($type, $object, $options = [])
    {
        parent::__construct($type, $object, $options);
        $multilinguallib = TikiLib::lib('multilingual');
        $tikilib = TikiLib::lib('tiki');

        $languages = $options['languages'];
        $this->renderers = array_fill_keys($languages, null);

        if ($trads = $multilinguallib->getTrads($type, $tikilib->get_page_id_from_name($object))) {
            foreach ($trads as $trad) {
                if (in_array($trad['lang'], $languages)) {
                    $this->renderers[ $trad['lang'] ] = new Wiki($type, $tikilib->get_page_name_from_id($trad['objId']), $options);
                }
            }
        } else {
            $this->renderers[ reset($languages) ] = new Wiki($type, $object, $options);
        }
    }

    /**
     * @param $smarty
     * @param $options
     * @return string
     */
    public function doRender($smarty, $options)
    {
        $out = '';

        $languages = array_keys($this->renderers);
        if (isset($options['languages'])) {
            $languages = $options['languages'];
        }

        foreach ($languages as $lang) {
            if ($this->renderers[$lang]) {
                $out .= $this->renderers[$lang]->doRender($smarty, $options);
            }
        }

        return $out;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getIndexValue($key)
    {
        if (str_starts_with($key, 'lang_')) {
            list( $key, $lang ) = explode('_', substr($key, 5), 2);

            if (isset($this->renderers[$lang]) && $this->renderers[$lang]) {
                return $this->renderers[$lang]->getIndexValue($key);
            }

            return;
        }

        return reset($this->renderers)->getIndexValue($key);
    }
}
