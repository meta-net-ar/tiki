<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Services_Category_Controller
{
    private $filters = [
        'object'          => 'string',
        'items'           => 'xss',
        'categories'      => 'array',
        'to'              => 'string',
        'object_action'   => 'string',
    ];
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

    /**
     * Categorize "perform with checked" but with no action selected
     *
     * @param $input
     * @throws Services_Exception
     * @throws Exception
     */
    public function actionNoAction()
    {
        Services_Utilities::modalException(tra('No action was selected. Please select an action before clicking OK.'));
    }

    public function action_list_categories($input)
    {
        global $prefs;

        $parentId = $input->parentId->int();
        $descends = $input->descends->int();
        $type = $input->type->text();

        if ($parentId) {
            $perms = Perms::get('category', $parentId);
        } else {
            $perms = Perms::get();
        }
        if (! $perms->tiki_p_view_category) {
            throw new Services_Exception_Denied();
        }

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
        $name = $input->name->text();
        if ($parentId) {
            $perms = Perms::get('category', $parentId);
        } else {
            $perms = Perms::get();
        }
        if (! $perms->admin_categories) {
            throw new Services_Exception_Denied();
        }
        if (empty($name)) {
            throw new Services_Exception_MissingValue('name');
        }

        $categlib = TikiLib::lib('categ');
        try {
            $newcategId = $categlib->add_category(
                $parentId,
                $name,
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
        $util = new Services_Utilities();
        $CATEGORIZE = $input['object_action'] == 'categorize';
        $UNCATEGORIZE = $input['object_action'] == 'uncategorize';

        if ($util->notConfirmPost()) {
            // validate action to perform
            $msg = '';
            if ($CATEGORIZE) {
                $msg = tr('Add the following object(s)');
            } elseif ($UNCATEGORIZE) {
                $msg = tr('Remove the following object(s)');
            } else {
                Services_Utilities::modalException(tra('No action was selected. Please select an action.'));
            }

            $util->setVars($input, $this->filters, 'object');
            $objects = $this->convertObjects($util->items);
            $categories = $input->asArray('to');
            if (empty($categories)) {
                Services_Utilities::modalException(tra('No destination category was selected. Please select at least one.'));
            }
            if ($util->itemsCount > 0) {
                return [
                    'title' => tra('Please confirm'),
                    'modal' => '1',
                    'confirmAction' => $input->action->word(),
                    'customMsg' => $msg,
                    'confirmButton' => $CATEGORIZE ? tra('Add') : tra('Remove'),
                    'items' => $util->items,
                    'extra' => ['object_action' => $input['object_action']],
                    'objects' => array_map(function ($obj) {
                        return strtoupper($obj['type'] . ': ') . smarty_function_object_link($obj, TikiLib::lib('smarty')->getEmptyInternalTemplate());
                    }, $objects),
                    'categories' => $this->convertCategories($categories),
                ];
            } else {
                Services_Utilities::modalException(tra('No object was selected. Please select one or more objects.'));
            }
        } elseif ($util->checkCsrf()) {
            $util->setVars($input, $this->filters, 'items');
            $filteredObjects = $originalObjects = $this->convertObjects($util->items);
            $messages = [];
            $err_messages = [];
            $categories = $this->convertCategories($input->asArray('to'));
            $permittedCategories = [];
            $unpermittedCategories = [];
            foreach ($categories as $cat) {
                $perms = Perms::get('category', $cat['id']);
                if ((! $perms->add_objects && $CATEGORIZE) || (! $perms->remove_objects && $UNCATEGORIZE)) {
                    $unpermittedCategories[] = $cat;
                } else {
                    $permittedCategories[] = $cat;
                }
            }
            if (empty($permittedCategories)) {
                throw new Services_Exception(tr('Permission denied'), 403);
            }

            if (! empty($unpermittedCategories)) {
                $cat_names = array_map(function ($cat) {
                    return $cat['name'];
                }, $unpermittedCategories);
                $err_messages[] = tr('You are not permitted to perform this action on these categories %0', '<strong>' . implode('<br>', $cat_names) . '</strong>');
            }

            //check if objects exist
            $objectlib = TikiLib::lib('object');
            foreach ($filteredObjects as $object) {
                $type = $object['type'];
                $id = $object['id'];
                if (! $objectlib->isValidObject($type, $id)) {
                    throw new Services_Exception(tr('Invalid %0 ID: %1', $type, $id), 403);
                }
            }
            $categlib = TikiLib::lib('categ');
            foreach ($permittedCategories as $cat) {
                $categorizedObjects = [];
                $categId = $cat['id'];
                $outputCategoryName = '<strong>' . $cat['name'] . '</strong>';
                //first determine if objects are already in the category
                foreach ($originalObjects as $key => $object) {
                    $objCategories = $categlib->get_object_categories($object['type'], $object['id']);
                    if (
                        ($CATEGORIZE && in_array($categId, $objCategories)) ||
                        ($UNCATEGORIZE && ! in_array($categId, $objCategories))
                    ) {
                        $categorizedObjects[] = $object;
                        unset($filteredObjects[$key]);
                    }
                }
                //provide appropriate feedback for objects already in category
                if ($categorizedObjectsCount = count($categorizedObjects)) {
                    $msg = '';
                    if ($CATEGORIZE) {
                        $msg = $categorizedObjectsCount === 1 ? tr('%0 No change made for one object already in this category', $outputCategoryName)
                            : tr('%0: No change made for %1 objects already in the category', $outputCategoryName, $categorizedObjectsCount);
                    } elseif ($UNCATEGORIZE) {
                        $msg = $categorizedObjectsCount === 1 ? tr('%0: No change made for one object not in the category', $outputCategoryName)
                            : tr('%0: No change made for %1 objects not in the category', $outputCategoryName, $categorizedObjectsCount);
                    }
                    $messages[] = $msg;
                }
                //now add objects to the category
                if (count($filteredObjects)) {
                    $funct = 'doCategorize';
                    if ($UNCATEGORIZE) {
                        $funct = 'doUncategorize';
                    }
                    $return = $this->processObjects($funct, $categId, $filteredObjects);
                    $count = isset($return['objects']) ? count($return['objects']) : 0;
                    if ($count) {
                        $msg = '';
                        if ($CATEGORIZE) {
                            $msg = $count === 1 ? tr('%0: One object added to category', $outputCategoryName)
                                : tr('%0: %1 objects added to category', $outputCategoryName, $count);
                        } elseif ($UNCATEGORIZE) {
                            $msg = $count === 1 ? tr('%0: One object removed from category', $outputCategoryName)
                                : tr('%0: %1 objects removed from category', $outputCategoryName, $count);
                        }
                        $messages[] = $msg;
                    } else {
                        $err_messages[] = tr('%0:  No objects added to category', $outputCategoryName);
                    }
                }
            }
            if (! empty($messages)) {
                Feedback::success(implode('<br>', $messages));
            }

            if (! empty($err_messages)) {
                Feedback::error(implode('<br>', $err_messages));
            }
            return Services_Utilities::refresh();
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

    private function convertCategories($categories): array
    {
        $out = [];
        foreach ($categories as $category) {
            $cat = explode('-', $category, 2);

            if (count($cat) == 2) {
                list($name, $id) = $cat;
                $out[] = ['name' => $name, 'id' => $id];
            }
        }

        return $out;
    }
}
