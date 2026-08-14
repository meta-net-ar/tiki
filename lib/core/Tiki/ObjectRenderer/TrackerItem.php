<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\ObjectRenderer;

use TikiLib;

class TrackerItem extends AbstractObjectRenderer
{
    private static $trackers = [];
    private $valid = false;
    private $tracker;
    private $info;

    /**
     * @param $type
     * @param $object
     * @param array $options
     */
    public function __construct($type, $object, $options = [])
    {
        parent::__construct($type, $object, $options);

        $trklib = TikiLib::lib('trk');

        $info = $trklib->get_tracker_item($object);
        $trackerId = $info['trackerId'];

        if (! isset(self::$trackers[$trackerId])) {
            if (self::$trackers[$trackerId] = $trklib->get_tracker($trackerId)) {
                $fields = $trklib->list_tracker_fields($trackerId);

                self::$trackers[$trackerId]['fields'] = $fields['data'];
            } else {
                $this->valid = false;
                return;
            }
        }

        $this->tracker = self::$trackers[ $info['trackerId'] ];
        $this->info = $info;
        $this->valid = ($type == $this->tracker['name']);

        foreach ($this->tracker['fields'] as & $field) {
            $field['value'] = $this->info[ $field['fieldId'] ];
        }
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @param $smarty
     * @param $options
     * @return mixed
     */
    public function doRender($smarty, $options)
    {
        $smarty->assign('title', $this->getTitle());
        $smarty->assign('tracker', $this->tracker);
        $smarty->assign('item', $this->info);

        $options['display_template'] = 'print/print-' . $options['display'] . '_trackeritem.tpl';
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
                return $this->getTitle();
        }
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        foreach ($this->tracker['fields'] as $field) {
            if ($field['isMain'] == 'y') {
                return $field['value'];
            }
        }
    }
}
