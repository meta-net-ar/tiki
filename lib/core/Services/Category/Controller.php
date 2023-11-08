<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Category_Controller
{
    public function setUp()
    {
        global $prefs;

        if ($prefs['feature_categories'] != 'y') {
            throw new Services_Exception_Disabled('feature_categories');
        }
    }

    /**
     * Returns the section for use with certain features like banning
     * @return string
     */
    public function getSection()
    {
        return 'categories';
    }

    public function action_list_categories($input)
    {
        global $prefs;

        $parentId = $input->parentId->int();
        $descends = $input->descends->int();
        $type = $input->type->text();
        if ($type != 'roots' && $type != 'all') {
            $type = $descends ? 'descendants' : 'children';
            if (! $parentId) {
                throw new Services_Exception_MissingValue('parentId');
            }
        }

        $categlib = TikiLib::lib('categ');
        return $categlib->getCategories(['identifier' => $parentId, 'type' => $type]);
    }

    public function action_create($input)
    {
        $parentId = $input->parentId->int();
        if ($parentId) {
            $perms = Perms::get('category', $parentId);
        } else {
            $perms = Perms::get();
        }
        if (! $perms->admin_categories) {
            throw new Services_Exception_Denied();
        }
        $categlib = TikiLib::lib('categ');
        try {
            $newcategId = $categlib->add_category(
                $parentId,
                $input->name->text(),
                $input->description->text(),
                $input->tplGroupContainerId->int(),
                $input->tplGroupPattern->text()
            );
            if ($input->parentPerms->boolean()) {
                TikiLib::lib('user')->copy_object_permissions($parentId, $newcategId, 'category');
                Perms::getInstance()->clear();
            }
            return $categlib->get_category($newcategId);
        } catch (Exception $e) {
            throw new Services_Exception($e->getMessage());
        }
    }

    public function action_update($input)
    {
        $categlib = TikiLib::lib('categ');

        $categId = $input->categId->int();
        $parentId = $input->parentId->int();

        $category = $categlib->get_category($categId);
        if (! $category) {
            throw new Services_Exception_NotFound();
        }

        $perms = Perms::get('category', $categId);
        if (! $perms->admin_categories) {
            throw new Services_Exception_Denied();
        }

        if ($parentId) {
            $perms = Perms::get('category', $parentId);
            if (! $perms->admin_categories) {
                throw new Services_Exception_Denied();
            }
        } else {
            $parentId = $category['parentId'];
        }

        try {
            $categlib->update_category(
                $categId,
                $input->name->text() ?: $category['name'],
                $input->description->text() ?: $category['description'],
                $parentId,
                $input->tplGroupContainerId->int() ?: $category['tplGroupContainerId'],
                $input->tplGroupPattern->text() ?: $category['tplGroupPattern']
            );
            if ($input->parentPerms->boolean()) {
                TikiLib::lib('user')->remove_object_permission('', $categId, 'category', '');
                TikiLib::lib('user')->copy_object_permissions($parentId, $categId, 'category');
            }
            return $categlib->get_category($categId);
        } catch (Exception $e) {
            throw new Services_Exception($e->getMessage());
        }
    }

    public function action_remove($input)
    {
        $categlib = TikiLib::lib('categ');
        $categId = $input->categId->int();

        $category = $categlib->get_category($categId);
        if (! $category) {
            throw new Services_Exception_NotFound();
        }

        $perms = Perms::get('category', $categId);
        if (! $perms->admin_categories) {
            throw new Services_Exception_Denied();
        }

        $result = $categlib->remove_category($categId);
        if (! empty($result) && $result->numRows()) {
            return $category;
        } else {
            throw new Services_Exception(tr('Could not delete requested category.'));
        }
    }

    public function action_categorize($input)
    {
        $categId = $input->categId->int();
        $objects = (array) $input->objects->none();

        $perms = Perms::get('category', $categId);

        if (! $perms->add_objects) {
            throw new Services_Exception(tr('Permission denied'), 403);
        }

        $filteredObjects = $originalObjects = $this->convertObjects($objects);
        $util = new Services_Utilities();
        if (count($originalObjects) && $util->isActionPost()) {
            //first determine if objects are already in the category
            $categlib = TikiLib::lib('categ');
            $inCategory = [];
            foreach ($originalObjects as $key => $object) {
                $objCategories = $categlib->get_object_categories($object['type'], $object['id']);
                if (in_array($categId, $objCategories)) {
                    $inCategory[] = $object;
                    unset($filteredObjects[$key]);
                }
            }
            //provide appropriate feedback for objects already in category
            if ($inCount = count($inCategory)) {
                $msg = $inCount === 1 ? tr('No change made for one object already in the category')
                    : tr('No change made for %0 objects already in the category', $inCount);
                Feedback::note($msg);
            }
            //now add objects to the category
            if (count($filteredObjects)) {
                $return = $this->processObjects('doCategorize', $categId, $filteredObjects);
                $count = isset($return['objects']) ? count($return['objects']) : 0;
                if ($count) {
                    $msg = $count === 1 ? tr('One object added to category')
                        : tr('%0 objects added to category', $count);
                    Feedback::success($msg);
                } else {
                    Feedback::error(tr('No objects added to category'));
                }
                return $return;
            } else {
                //this code is reached when all objects selected were already in the category
                return [
                    'categId'   => $categId,
                    'objects'   => $objects,
                    'count'     => 'unchanged'
                ];
            }
        } else {
            return [
                'categId' => $categId,
                'objects' => $objects,
            ];
        }
    }

    public function action_uncategorize($input)
    {
        $categId = $input->categId->digits();
        $objects = (array) $input->objects->none();

        $perms = Perms::get('category', $categId);

        if (! $perms->remove_objects) {
            throw new Services_Exception(tr('Permission denied'), 403);
        }

        $filteredObjects = $originalObjects = $this->convertObjects($objects);
        $util = new Services_Utilities();
        if (count($originalObjects) && $util->isActionPost()) {
            //first determine if objects are already not in the category
            $categlib = TikiLib::lib('categ');
            $notInCategory = [];
            foreach ($originalObjects as $key => $object) {
                $objCategories = $categlib->get_object_categories($object['type'], $object['id']);
                if (! in_array($categId, $objCategories)) {
                    $notInCategory[] = $object;
                    unset($filteredObjects[$key]);
                }
            }
            //provide appropriate feedback for objects already not in category
            if ($notCount = count($notInCategory)) {
                $msg = $notCount === 1 ? tr('No change made for one object not in the category')
                    : tr('No change made for %0 objects not in the category', $notCount);
                Feedback::note($msg);
            }
            //now uncategorize objects that are in the category
            if (count($filteredObjects)) {
                $return = $this->processObjects('doUncategorize', $categId, $filteredObjects);
                $count = isset($return['objects']) ? count($return['objects']) : 0;
                if ($count) {
                    $msg = $count === 1 ? tr('One object removed from category')
                        : tr('%0 objects removed from category', $count);
                    Feedback::success($msg);
                } else {
                    Feedback::error(tr('No objects removed from category'));
                }
                return $return;
            } else {
                //this code is reached when all objects selected were already not in the category
                return [
                    'categId'   => $categId,
                    'objects'   => $objects,
                    'count'     => 'unchanged'
                ];
            }
        } else {
            return [
                'categId' => $categId,
                'objects' => $objects,
            ];
        }
    }

    public function action_select($input)
    {
        $categlib = TikiLib::lib('categ');
        $objectlib = TikiLib::lib('object');
        $smarty = TikiLib::lib('smarty');

        $type = $input->type->text();
        $object = $input->object->text();

        $perms = Perms::get($type, $object);
        if (! $perms->modify_object_categories) {
            throw new Services_Exception_Denied('Not allowed to modify categories');
        }

        $input->replaceFilter('subset', 'int');
        $subset = $input->asArray('subset', ',');

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $name = $objectlib->get_title($type, $object);
            $url = smarty_modifier_sefurl($object, $type);
            $targetCategories = (array) $input->categories->int();
            $count = $categlib->update_object_categories($targetCategories, $object, $type, '', $name, $url, $subset, false);
        }

        $categories = $categlib->get_object_categories($type, $object);
        return [
            'subset' => implode(',', $subset),
            'categories' => array_combine(
                $subset,
                array_map(
                    function ($categId) use ($categories) {
                        return [
                            'name' => TikiLib::lib('object')->get_title('category', $categId),
                            'selected' => in_array($categId, $categories),
                        ];
                    },
                    $subset
                )
            ),
        ];
    }

    private function processObjects($function, $categId, $objects)
    {
        $tx = TikiDb::get()->begin();

        foreach ($objects as & $object) {
            $type = $object['type'];
            $id = $object['id'];

            $object['catObjectId'] = $this->$function($categId, $type, $id);
        }

        $tx->commit();

        $categlib = TikiLib::lib('categ');
        $category = $categlib->get_category((int) $categId);

        return [
            'categId' => $categId,
            'count' => $category['objects'],
            'objects' => $objects,
        ];
    }

    private function doCategorize($categId, $type, $id)
    {
        $categlib = TikiLib::lib('categ');
        return $categlib->categorize_any($type, $id, $categId);
    }

    private function doUncategorize($categId, $type, $id)
    {
        $categlib = TikiLib::lib('categ');
        if ($oId = $categlib->is_categorized($type, $id)) {
            $result = $categlib->uncategorize($oId, $categId);
            return $oId;
        }
        return 0;
    }

    private function convertObjects($objects)
    {
        $out = [];
        foreach ($objects as $object) {
            $object = explode(':', $object, 2);

            if (count($object) == 2) {
                list($type, $id) = $object;
                $objectPerms = Perms::get($type, $id);

                if ($objectPerms->modify_object_categories) {
                    $out[] = ['type' => $type, 'id' => $id];
                }
            }
        }

        return $out;
    }
}
