<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Attribute_Controller
{
    public function setUp()
    {
    }

    /**
     * Function to get an attribute
     *
     * @param $input JitFilter
     *  ->attribute string      lowercase letters and two dots
     *  ->type string           object type
     *  ->object mixed          id or name of object
     *
     * @return array value=>string containing the value
     * @throws Exception
     * @throws Services_Exception
     */
    public function action_get($input)
    {
        $attribute = $input->attribute->text();
        $type = $input->type->text();
        $object = $input->object->text();
        $value = '';

        // ensure the target, source, and relation info are passed to the service
        if (! $type || ! $attribute) {
            throw new Services_Exception(tr('Invalid input'), 400);
        }

        if ($object) {      // for objects yet to be created we don't get an object id, so don't set any attributes
            $value = TikiLib::lib('attribute')->get_attribute($type, $object, $attribute);
        }

        //return the attribute value if there were no errors
        return [
            'value' => $value,
        ];
    }

    public function action_set($input)
    {
        $type = $input->type->text();
        $itemId = $input->itemId->text();
        $value = $input->value->text();
        $comment = $input->comment->text();
        $attribute = $input->attribute->text();

        // Check if required infos are passed to the service
        if (! $type || ! $itemId || ! $attribute) {
            throw new Services_Exception(tr('Invalid input'), 400);
        }

        // Check view permissions of requested object
        $perms = Perms::get($type, $itemId);
        if (! $perms->view) {
            throw new Services_Exception(tr('Permission denied'), 403);
        }

        $attributeLib = TikiLib::lib('attribute');
        $attributeLib->set_attribute(
            $type,
            $itemId,
            $attribute,
            $value,
            $comment
        );
    }

    public function action_find_objects_with($input): array
    {
        $attribute = $input->attribute->text();
        $value = $input->value->text();

        // Check if info are passed to the service
        if (! $attribute || ! $value) {
            throw new Services_Exception(tr('Invalid input'), 400);
        }

        // Check global view permissions for requested objects
        $perms = Perms::get();
        if (! $perms->view) {
            throw new Services_Exception(tr('Permission denied'), 403);
        }
        $attributeLib = TikiLib::lib('attribute');
        $ojects = $attributeLib->find_objects_with(
            $attribute,
            $value
        );

        return [
            'objects' => $ojects,
        ];
    }

    public function action_get_attribute($input)
    {
        $type = $input->type->text();
        $itemId = $input->itemId->text();
        $attribute = $input->attribute->text();

        // Check if info are passed to the service
        if (! $type || ! $itemId || ! $attribute) {
            throw new Services_Exception(tr('Invalid input'), 400);
        }

        // Check view permissions of request object
        $perms = Perms::get($type, $itemId);
        if (! $perms->view) {
            throw new Services_Exception(tr('Permission denied'), 403);
        }

        $attributeLib = TikiLib::lib('attribute');
        $result = $attributeLib->get_attribute(
            $type,
            $itemId,
            $attribute
        );

        return $result;
    }
}
