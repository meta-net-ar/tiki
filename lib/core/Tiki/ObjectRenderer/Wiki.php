<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\ObjectRenderer;

use TikiLib;

class Wiki extends AbstractObjectRenderer
{
    private $info;

    /**
     * @param $objectType
     * @param $objectId
     */
    public function __construct($objectType, $objectId)
    {
        parent::__construct($objectType, $objectId);
        global $tikilib;

        $info = $tikilib->get_page_info($objectId);

        $info['parsed'] = TikiLib::lib('parser')->parse_data(
            $info['data'],
            [
                'is_html' => $info['is_html'],
                'print' => 'y',
            ]
        );

        $this->info = $info;
    }

    /**
     * @param $smarty
     * @param $options
     * @return mixed
     */
    public function doRender($smarty, $options)
    {
        $options['display_template'] = 'print/print-' . $options['display'] . '_wiki.tpl';
        $smarty->assign('info', $this->info);

        return $smarty->fetch($options['display_template']);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function getIndexValue($key)
    {
        switch ($key) {
            case 'title':
                return $this->info['pageName'];
        }
    }
}
