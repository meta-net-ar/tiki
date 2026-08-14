<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * AttributeLib
 *
 * Manages the table tiki_object_attributes
 *
 * @uses TikiDb_Bridge
 */
class AttributeLib extends TikiDb_Bridge
{
    private $attributes;
    private $cache;

    /**
     *
     */
    public function __construct()
    {
        $this->attributes = $this->table('tiki_object_attributes');
        $this->cache = [];
    }

    /**
     * Get all attributes for an object
     *
     * @param $type string      One of \ObjectLib::get_supported_types()
     * @param $objectId mixed   Object id (or name for wiki pages)
     * @return array            Array [attribute => value]
     */
    public function get_attributes($type, $objectId)
    {
        if (count($this->cache) > 2048) {
            $this->cache = [];
        }
        if (! isset($this->cache[$type . $objectId])) {
            $this->cache[$type . $objectId] = $this->attributes->fetchMap(
                'attribute',
                'value',
                ['type' => $type,'itemId' => $objectId,]
            );
        }
        return $this->cache[$type . $objectId];
    }

        /**
     * Get all value of one attribute for all objects
     *
     * @param $type string      One of \ObjectLib::get_supported_types()
     * @param $objectId mixed   Object id (or name for wiki pages)
     * @return array            Array [attribute => value]
     */
    public function getAllAttributes($attribute)
    {
        $tab = [];
        $allattributes = [];
        $tab = $this->attributes->fetchAll(
            [
                'value' => 'value',
                'itemId' => 'itemId',
            ],
            ['attribute' => $attribute,]
        );
        foreach ($tab as $att) {
            $allattributes[$att['itemId']] = $att['value'];
        }
        return $allattributes;
    }

    /**
     * Get a single attribute
     *
     * @param $type string          One of \ObjectLib::get_supported_types()
     * @param $objectId mixed       Object id (or name for wiki pages)
     * @param $attribute string     At least two dots and only lowercase letters
     * @param $fieldId int          Field id (optional)
     * @return string|boolean       Contents of the attribute on the object or false if not present
     */
    public function get_attribute($type, $objectId, $attribute, $fieldId = null)
    {
        $search = ['type' => $type, 'itemId' => $objectId, 'attribute' => $attribute];
        if ($fieldId) {
            $search['fieldId'] = $fieldId;
        }
        return $this->attributes->fetchOne(
            'value',
            $search
        );
    }

    /**
     * The attribute must contain at least two dots and only lowercase letters.
     */

    /**
     * NAMESPACE management and attribute naming.
     * Please see http://dev.tiki.org/Object+Attributes+and+Relations for guidelines on
     * attribute naming, and document new tiki.*.* names that you add
     * (also grep "set_attribute" just in case there are undocumented names already used)
     */
    public function set_attribute($type, $objectId, $attribute, $value, $comment = null, $fieldId = null)
    {
        if (false === $name = $this->get_valid($attribute)) {
            return false;
        }

        $search = ['type' => $type, 'itemId' => $objectId, 'attribute' => $name];
        if ($fieldId) {
            $search['fieldId'] = $fieldId;
        }

        if ($value === '') {
            $this->attributes->delete(
                $search
            );
        } else {
            $this->attributes->insertOrUpdate(
                [
                    'value' => $value,
                    'comment' => $comment,
                ],
                $search
            );
        }

        // update the cache
        $this->cache[$type . $objectId] = $this->attributes->fetchMap(
            'attribute',
            'value',
            ['type' => $type,'itemId' => $objectId,]
        );

        return true;
    }

        /**
         * @param $name
         * @return mixed
         */
    private function get_valid($name)
    {
        $filter = TikiFilter::get('attribute_type');
        return $filter->filter($name);
    }

        /**
         * @param $attribute
         * @param $value
         * @return mixed
         */
    public function find_objects_with($attribute, $value, $fieldId = null)
    {
        $attribute = $this->get_valid($attribute);
        $search = ['attribute' => $attribute, 'value' => $value];
        if ($fieldId) {
            $search['fieldId'] = $fieldId;
        }
        return $this->attributes->fetchAll(
            ['type', 'itemId', 'comment', 'fieldId'],
            $search
        );
    }

    /**
     * @param $attribute
     * @param $value
     * @return mixed
     */
    public function delete_objects_with($attribute, $value, $fieldId = null)
    {
        $attribute = $this->get_valid($attribute);

        $search = ['attribute' => $attribute, 'value' => $value];
        if ($fieldId) {
            $search['fieldId'] = $fieldId;
        }
        return $this->attributes->delete(
            $search
        );
    }
}
