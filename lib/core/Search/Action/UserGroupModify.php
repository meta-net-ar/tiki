<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Search_Action_UserGroupModify implements Search_Action_Action
{
    public function getValues()
    {
        return [
            'object_type' => true,
            'object_id' => true,
            'user' => true,
            'add' => false,
            'remove' => false,
            'operation' => false,
            'value' => false,
        ];
    }

    public function validate(JitFilter $data)
    {
        $user = $data->user->text();
        $add = $data->add->text();
        $remove = $data->remove->text();
        $operation = $data->operation->word();
        $value = $data->value->text();

        if (empty($user)) {
            throw new Search_Action_Exception(tr('Cannot apply user_group_modify to an object missing value for user.'));
        }

        if (! is_array($user)) {
            $user = [$user];
        }

        foreach ($user as $u) {
            if (! TikiLib::lib('user')->user_exists($u)) {
                throw new Search_Action_Exception(tr('Cannot apply user_group_modify to an object missing a Tiki user: %0.', $u));
            }
        }

        if (empty($add) && empty($remove) && empty($operation)) {
            throw new Search_Action_Exception(tr('Cannot apply user_group_modify without a group to add or remove.'));
        }

        if (! empty($add) && ! TikiLib::lib('user')->group_exists($add)) {
            throw new Search_Action_Exception(tr('Cannot apply user_group_modify: group does not exist: %0', $add));
        }

        if (! empty($remove) && ! TikiLib::lib('user')->group_exists($remove)) {
            throw new Search_Action_Exception(tr('Cannot apply user_group_modify: group does not exist: %0', $remove));
        }

        if (! empty($operation) && ! in_array($operation, ['add', 'remove'])) {
            throw new Search_Action_Exception(tr('Cannot apply user_group_modify: operation should be one of add or remove.'));
        }

        if (empty($add) && empty($remove) && ! empty($operation) && empty($value)) {
            throw new Search_Action_Exception(tr('Cannot apply user_group_modify: group value not specified.'));
        }

        if (! empty($operation) && ! empty($value) && ! TikiLib::lib('user')->group_exists($value)) {
            throw new Search_Action_Exception(tr('Cannot apply user_group_modify: group does not exist: %0', $value));
        }

        return true;
    }

    public function execute(JitFilter $data)
    {
        $lib = TikiLib::lib('user');
        $user = $data->user->text();
        $add = $data->add->text();
        $remove = $data->remove->text();
        $operation = $data->operation->word();
        $value = $data->value->text();

        if ($add) {
            $permName = 'group_add_member';
            $group = $add;
        } elseif ($remove) {
            $permName = 'group_remove_member';
            $group = $remove;
        } elseif ($operation == 'add') {
            $permName = 'group_add_member';
            $group = $value;
        } elseif ($operation == 'remove') {
            $permName = 'group_remove_member';
            $group = $value;
        } else {
            throw new Search_Action_Exception(tr('Failed exeucting user_group_modify: nothing to add or remove.'));
        }

        if (! is_array($user)) {
            $user = [$user];
        }

        foreach ($user as $u) {
            $userGroups = $lib->get_user_groups_inclusion($u);
            if (Perms::get()->$permName || (array_key_exists($group, $userGroups) && Perms::get()->group_join)) {
                if ($add || $operation == 'add') {
                    $lib->assign_user_to_group($u, $group);
                } else {
                    $lib->remove_user_from_group($u, $group);
                }
            }
        }

        return true;
    }

    public function inputType(): string
    {
        return "text";
    }

    public function requiresInput(JitFilter $data)
    {
        return ! empty($data->operation->text());
    }
}
