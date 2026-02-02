<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\ObjectRenderer;

class ObjectList
{
    private $lastIndex = 0;
    private $customIndexes = [];
    private $renderers = [];
    private $dataIndex = [];

    /**
     * @param $indexKey
     */
    public function addCustomIndex($indexKey)
    {
        $this->customIndexes[ $indexKey ] = [];
    }

    /**
     * @param $type
     * @param $object
     * @param $options
     */
    public function add($type, $object, $options)
    {
        if (! isset($dataIndex[$type])) {
            $this->dataIndex[$type] = [];
        }

        switch ($type) {
            case 'wiki page':
                if (array_key_exists('languages', $options)) {
                    $renderer = new MultilingualWiki($type, $object, $options);
                } else {
                    $renderer = new Wiki($type, $object, $options);
                }

                break;

            default:
                $renderer = new TrackerItem($type, $object, $options);
                break;
        }

        if ($renderer && $renderer->isValid()) {
            $index = ++$this->lastIndex;
            $this->renderers[$index] = $renderer;

            foreach ($this->customIndexes as $key => & $data) {
                if ($prop = $renderer->getIndexValue($key)) {
                    $prop = strtolower($prop);

                    if (isset($data[$prop])) {
                        $data[$prop][] = $index;
                    } else {
                        $data[$prop] = [ $index ];
                    }
                }
            }
        }
    }

    public function finalize()
    {
        foreach ($this->customIndexes as & $data) {
            ksort($data);
        }
    }

    /**
     * @param $smarty
     * @param $key
     * @param $options
     */
    public function render($smarty, $key, $options)
    {
        if (is_null($key)) {
            foreach ($this->renderers as $index => $renderer) {
                $smarty->assign('index', $index);

                $renderer->render($smarty, $options);
            }
        } else {
            foreach ($this->customIndexes[$key] as $indexes) {
                foreach ($indexes as $index) {
                    $renderer = $this->renderers[$index];
                    $smarty->assign('index', $index);

                    $renderer->render($smarty, $options);
                }
            }
        }
    }
}
