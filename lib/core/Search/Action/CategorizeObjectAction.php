<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Action_CategorizeObjectAction implements Search_Action_Action
{
    public function getValues(): array
    {
        return [
            'object_type' => true,
            'object_id' => true,
            'add' => false,
            'remove' => false,
            'operation' => false,
            'category' => false,
        ];
    }

    public function validate(JitFilter $data): bool
    {
        $categlib = TikiLib::lib('categ');

        $add = $data->add->text();
        $remove = $data->remove->text();
        $operation = $data->operation->word();
        $category = $data->category->int();

        if (! empty($add) && ! $categlib->get_category($add)) {
            throw new Search_Action_Exception(tr('Cannot apply categorize_object: category does not exist: %0', $add));
        }

        if (empty($add) && empty($remove) && empty($operation)) {
            throw new Search_Action_Exception(tr('Cannot apply categorize_object without a category to add or remove.'));
        }

        if (! empty($operation) && ! in_array($operation, ['add', 'remove'])) {
            throw new Search_Action_Exception(tr('Cannot apply categorize_object: operation should be one of add or remove.'));
        }

        if (empty($add) && empty($remove) && ! empty($operation) && empty($category)) {
            throw new Search_Action_Exception(tr('Cannot apply categorize_object: category value not specified.'));
        }
        if (! empty($category)) {
            foreach ($category as $catId) {
                if (! empty($operation) && ! empty($catId) && ! $categlib->get_category($catId)) {
                    throw new Search_Action_Exception(tr('Cannot apply categorize_object: category does not exist: %0', $category));
                }
            }
        }
        return true;
    }

    public function execute(JitFilter $data): bool
    {
        $categlib = TikiLib::lib('categ');
        $add = $data->add->text();
        $remove = $data->remove->text();
        $operation = $data->operation->word();
        $category = $data->category->int();

        $objectType = $data->object_type->text();
        $objectId = $data->object_id->text();

        if ($add) {
            $permName = 'add_object';
            $category = $add;
        } elseif ($remove) {
            $permName = 'remove_object';
            $category = $remove;
        } elseif ($operation == 'add') {
            $permName = 'add_object';
        } elseif ($operation == 'remove') {
            $permName = 'remove_object';
        } else {
            throw new Search_Action_Exception(tr('Failed exeucting categorize_object: nothing to add or remove.'));
        }

        if (! is_array($category)) {
            $category = [$category];
        }

        if (! is_array($objectId)) {
            $objectId = [$objectId];
        }

        foreach ($objectId as $id) {
            if (Perms::get()->$permName) {
                // Add action
                if ($add || $operation == 'add') {
                    $objectId = $categlib->is_categorized($objectType, $id);
                    // Categorize list object
                    foreach ($category as $catId) {
                        if ($objectId) {
                            $catObjectId = $objectId;
                        } else {
                            $catObjectId = $categlib->add_categorized_object($objectType, $id);
                        }
                        $categlib->categorize($catObjectId, $catId);
                    }
                    return true;
                } else {
                    // Check if object is already categorized
                    if ($oId = $categlib->is_categorized($objectType, $id)) {
                        // Uncategorize list object
                        foreach ($category as $catId) {
                            $categlib->uncategorize($oId, $catId);
                        }
                        return true;
                    } else {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    public function inputType(): string
    {
        return "category_tree";
    }

    public function requiresInput(JitFilter $data): bool
    {
        return ! empty($data->operation->text());
    }
}
