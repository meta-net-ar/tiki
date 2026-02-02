<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tiki\ObjectRenderer;

abstract class AbstractObjectRenderer
{
    protected $objectType;
    protected $objectId;

    /**
     * @param $objectType
     * @param $objectId
     */
    public function __construct($objectType, $objectId)
    {
        $this->objectType = $objectType;
        $this->objectId = $objectId;
    }

    /**
     * @param $smarty
     * @param $options
     */
    public function render($smarty, $options)
    {
        $options['decorator_template'] = 'print/print-decorator_' . $options['decorator'] . '.tpl';
        $smarty->assign('body', $this->doRender($smarty, $options));
        $smarty->display($options['decorator_template']);
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return true;
    }

    /**
     * @param $smarty
     * @param $template
     * @return mixed
     */
    abstract public function doRender($smarty, $template);

    /**
     * @param $key
     * @return mixed
     */
    abstract public function getIndexValue($key);
}
