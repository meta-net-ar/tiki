<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tracker_Item
{
    public const PERM_NAME_MAX_ALLOWED_SIZE = 50;

    /**
     * includes itemId, trackerId and fields using the fieldId as key
     * @var array - plain from database.
     */
    private $info;


    /**
     * object with tracker definition. includes itemId, items (nr of items for that tracker).
     * other important attributes: trackerInfo array, factory null, fields array,  perms Perms_Accessor
     */
    private Tracker_Definition $definition;

    private $owners;
    private $ownerGroup;
    private $writerGroup;
    private $perms;

    private $isNew = false;

    /**
     * Return an instance from an item id.
     * @param $itemId int
     * @throws Exception
     */
    public static function fromId($itemId): ?Tracker_Item
    {
        $info = TikiLib::lib('trk')->get_tracker_item($itemId);

        if ($info) {
            return self::fromInfo($info);
        } else {
            return null;
        }
    }

    public static function fromInfo(array $info): Tracker_Item
    {
        $obj = new self();
        if (empty($info['trackerId']) && ! empty($info['itemId'])) {
            $info['trackerId'] = TikiLib::lib('trk')->get_tracker_for_item($info['itemId']);
        }
        $obj->info = $info;
        if (isset($info['trackerId'])) {
            $obj->definition = Tracker_Definition::get($info['trackerId']);
        } else {
            throw new InvalidArgumentException("Cannot initialize tracker item without a trackerId and its definition.");
        }
        $obj->initialize();

        return $obj;
    }

    public static function newItem($trackerId)
    {
        $obj = new self();
        $obj->info = [];
        $obj->definition = Tracker_Definition::get($trackerId);
        $obj->asNew();
        $obj->initialize();

        return $obj;
    }

    private function __construct()
    {
    }

    public function asNew()
    {
        $this->isNew = true;
        $this->info['itemId'] = null;
    }

    public function canView()
    {
        if ($this->isNew()) {
            return true;
        }

        if ($this->canFromSpecialPermissions('Modify') || $this->canFromSpecialPermissions('View')) {
            return true;
        }

        if ($this->canSeeOwn()) {
            return true;
        }

        $permName = $this->getViewPermission();

        return $this->perms->$permName;
    }

    public function canViewHistory()
    {
        $permName = 'tracker_view_history';
        ;

        return $this->perms->$permName;
    }

    public function canModify()
    {
        if ($this->isNew()) {
            return $this->perms->create_tracker_items;
        }

        if ($this->canFromSpecialPermissions('Modify')) {
            return true;
        }

        $status = $this->info['status'] ?? '';

        if ($status == 'c') {
            return $this->perms->modify_tracker_items_closed;
        } elseif ($status == 'p') {
            return $this->perms->modify_tracker_items_pending;
        } else {
            return $this->perms->modify_tracker_items;
        }
    }

    public function canRemove()
    {
        if ($this->isNew()) {
            return false;
        }

        if ($this->canFromSpecialPermissions('Remove')) {
            return true;
        }

        $status = $this->info['status'];

        if ($status == 'c') {
            return $this->perms->remove_tracker_items_closed;
        } elseif ($status == 'p') {
            return $this->perms->remove_tracker_items_pending;
        } else {
            return $this->perms->remove_tracker_items;
        }
    }

    public function canViewComments()
    {
        if ($this->perms->tracker_view_comments || $this->perms->comment_tracker_items) {
            return true;
        }
        if ($this->canSeeOwn()) {
            return true;
        }
        return false;
    }

    public function canPostComments()
    {
        if ($this->perms->comment_tracker_items) {
            return true;
        }
        if ($this->canSeeOwn()) {
            return true;
        }
        if ($this->canFromSpecialPermissions('Modify')) {
            return true;
        }
        return false;
    }

    public function getSpecialPermissionUsers($itemId, $operation)
    {
        $users = [];

        if ($operation == 'View' && $this->definition->getConfiguration('userCanSeeOwn') == 'y') {
            $users = array_unique(array_merge($users, $this->owners));
        }

        if ($operation == 'View' && $this->definition->getConfiguration('groupCanSeeOwn') == 'y' && $this->ownerGroup && in_array($this->ownerGroup, $this->perms->getGroups())) {
            $users = array_unique(array_merge($users, TikiLib::lib('user')->get_group_users($this->ownerGroup)));
        }

        if ($this->definition->getConfiguration('writerCan' . $operation, 'n') == 'y') {
            $users = array_unique(array_merge($users, $this->owners));
        }

        if ($this->definition->getConfiguration('writerGroupCan' . $operation, 'n') == 'y' && $this->writerGroup && in_array($this->writerGroup, $this->perms->getGroups())) {
            $users = array_unique(array_merge($users, TikiLib::lib('user')->get_group_users($this->writerGroup)));
        }

        return $users;
    }

    public function getOwners()
    {
        return $this->owners;
    }

    private function canFromSpecialPermissions($operation)
    {
        global $user;
        if (! $this->definition) {
            return false;
        }

        if ($operation == 'View' && $this->canSeeOwn()) {
            return true;
        }

        if ($this->definition->getConfiguration('writerCan' . $operation, 'n') == 'y' && $user && $this->owners && in_array($user, $this->owners)) {
            return true;
        }

        if ($this->definition->getConfiguration('writerGroupCan' . $operation, 'n') == 'y' && $this->ownerGroup && in_array($this->ownerGroup, $this->perms->getGroups())) {
            return true;
        }

        return false;
    }

    private function canSeeOwn()
    {
        global $user;

        if (! $this->definition) {
            return false;
        }

        if ($this->definition->getConfiguration('userCanSeeOwn') == 'y' && ! empty($user) && $this->owners && in_array($user, $this->owners)) {
            return true;
        }

        if ($this->definition->getConfiguration('groupCanSeeOwn') == 'y' && ! empty($user) && $this->ownerGroup) {
            $usergroups = TikiLib::lib('tiki')->get_user_groups($user);
            if (in_array($this->ownerGroup, $usergroups)) {
                return true;
            }
        }

        return false;
    }

    private function initialize()
    {
        $this->owners = $this->getItemOwners();
        $this->ownerGroup = $this->getItemGroupOwner();
        $this->writerGroup = $this->getItemGroupWriter();
        $this->perms = $this->getItemPermissions();
    }

    private function getItemPermissions()
    {
        if ($this->isNew()) {
            if ($this->definition) {
                $trackerId = $this->definition->getConfiguration('trackerId');
                return Perms::get('tracker', $trackerId);
            } else {
                $accessor = new Perms_Accessor();
                $accessor->setResolver(new Perms_Resolver_Default(false));
                return $accessor;
            }
        } else {
            return Perms::get('trackeritem', $this->info['itemId'] ?? '', $this->info['trackerId'] ?? '');
        }
    }

    private function getItemOwners()
    {
        if (! is_object($this->definition)) {
            return []; // TODO: This is a temporary fix, we should be able to getItemOwners always
        }

        if ($this->isNew()) {
            global $user;
            return [$user];
        }


        if (isset($this->info['itemUsers'])) {
            // Used by TRACKERLIST - not all data is loaded, but this is loaded separately
            return $this->info['itemUsers'];
        }

        $owners = array_map(function ($field) {

            $owners = $this->getValue($field);
            return TikiLib::lib('trk')->parse_user_field($owners);
        }, $this->definition->getItemOwnerFields());

        if ($owners) {
            return call_user_func_array('array_merge', $owners);
        } else {
            return [];
        }
    }

    private function getItemGroupOwner()
    {
        if (! is_object($this->definition)) {
            return; // TODO: This is a temporary fix, we should be able to getItemOwner always
        }

        $groupFields = $this->definition->getItemGroupOwnerFields();

        if ($groupFields) {
            return $this->getValue($groupFields[0]);
        }
    }

    private function getItemGroupWriter()
    {
        if (! is_object($this->definition)) {
            return; // TODO: This is a temporary fix, we should be able to getItemOwner always
        }

        $groupField = $this->definition->getWriterGroupField();
        if ($groupField) {
            return $this->getValue($groupField);
        }
    }

    public function getAllowedUserGroupsForField($field)
    {
        $isHidden = $field['isHidden'];
        $visibleBy = $field['visibleBy'];

        $allowed = [
            'allowed_users' => [],
            'allowed_groups' => [],
        ];

        if ($isHidden == 'c') {
            // Creator or creator group check when field can be modified by creator only
            if ($this->definition->getConfiguration('writerCanModify', 'n') == 'y' && $this->owners) {
                $allowed['allowed_users'] = $this->owners;
            }
            if ($this->definition->getConfiguration('writerGroupCanModify', 'n') == 'y' && $this->ownerGroup) {
                $allowed['allowed_groups'] = [$this->ownerGroup];
            }
        } elseif ($isHidden == 'y') {
            // Visible by administrator only
        } else {
            // Permission based on visibleBy apply
            $allowed['allowed_groups'] = $visibleBy;
        }
        return $allowed;
    }

    public function canViewField($fieldId)
    {
        $fieldId = $this->prepareFieldId($fieldId);

        // Nothing stops the tracker administrator from doing anything
        if ($this->perms->admin_trackers) {
            return true;
        }

        // Viewing the item is required to view the field (safety)
        if (! $this->canView()) {
            return false;
        }

        $field = $this->definition->getField($fieldId);

        if (! $field) {
            return false;
        }
        // filter fields to show only Tag field that users has permissio to access
        if ($field['type'] == 'F' && empty($this->perms['view_freetags'])) {
            return false;
        }

        $isHidden = $field['isHidden'];
        $visibleBy = $field['visibleBy'];

        if ($isHidden == 'c' && $this->canFromSpecialPermissions('Modify')) {
            // Creator or creator group check when field can be modified by creator only
            return true;
        } elseif ($isHidden == 'y') {
            // Visible by administrator only
            return false;
        } else {
            // Permission based on visibleBy apply
            return $this->isMemberOfGroups($visibleBy);
        }
    }

    public function canModifyField($fieldId)
    {
        $fieldId = $this->prepareFieldId($fieldId);

        // Nothing stops the tracker administrator from doing anything
        if ($this->perms->admin_trackers) {
            return true;
        }

        // Modify the item is required to modify the field (safety)
        if (! $this->canModify()) {
            return false;
        }

        // Cannot modify a field you are not supposed to see
        // Modify without view means insert-only
        if (! $this->isNew() && ! $this->canViewField($fieldId)) {
            return false;
        }

        $field = $this->definition->getField($fieldId);

        if (! $field) {
            return false;
        }

        $isHidden = $field['isHidden'];
        $editableBy = $field['editableBy'];

        if ($isHidden == 'i' || $isHidden == 'a') {
            // Immutable or editable by admin only after creation
            return $this->isNew();
        } elseif ($isHidden == 'c') {
            // Creator or creator group check when field can be modified by creator only
            return $this->canFromSpecialPermissions('Modify');
        } elseif ($isHidden == 'p') {
            // Editable by administrator only
            return false;
        } else {
            // Permission based on editableBy apply
            return $this->isMemberOfGroups($editableBy);
        }
    }

    private function isMemberOfGroups($groups)
    {
        // Nothing specified means everyone
        if (empty($groups)) {
            return true;
        }

        $commonGroups = array_intersect($groups, $this->perms->getGroups());
        return count($commonGroups) != 0;
    }


    /**
     * Return raw value of a field. Raw means, value as saved in database.
     * @param integer $fieldId
     * @return string - note: all values are saved as a string.
     */
    private function getValue($fieldId)
    {
        if (isset($this->info[$fieldId])) {
            return $this->info[$fieldId];
        }
    }

    public function getId()
    {
        return $this->info['itemId'];
    }

    private function isNew()
    {
        return $this->isNew;
    }

    public function prepareInput($input)
    {
        $input = $input->none();
        $fields = $this->definition->getFields();
        $output = [];

        foreach ($fields as $field) {
            $output[] = $this->prepareFieldInput($field, $input);
        }

        return array_filter($output);
    }

    public function deletedFiles($input)
    {
        $deleteFiles = [];
        foreach ($input as $k => $v) {
            if (substr($k, 0, 4) == 'del_') {
                $key = substr($k, 4);
                if ($this->canModifyField($key)) {
                    $deleteFiles[$key] = $v;
                }
            }
        }

        return $deleteFiles;
    }

    public function prepareOutput()
    {
        $fields = $this->definition->getFields();
        $output = [];

        foreach ($fields as $field) {
            $output[] = $this->prepareFieldOutput($field);
        }

        return array_filter($output);
    }

    public function prepareFieldInput($field, $input)
    {
        $fid = $field['fieldId'];

        if ($this->canModifyField($fid)) {
            $field['ins_id'] = "ins_$fid";

            $factory = $this->definition->getFieldFactory();
            $handler = $factory->getHandler($field, $this->info);

            if (! isset($input[$field['ins_id']]) && isset($input['fields'][$field['permName']])) {
                // getFieldData expects the value to be in $input['ins_xx']
                $input[$field['ins_id']] = $input['fields'][$field['permName']];
            }
            return array_merge($field, $handler->getFieldData($input));
        }
    }

    public function prepareFieldOutput($field)
    {
        $fid = $field['fieldId'];

        if ($this->canViewField($fid)) {
            $field['ins_id'] = "ins_$fid";

            $factory = $this->definition->getFieldFactory();
            $handler = $factory->getHandler($field, $this->info);
            return array_merge($field, $handler->getFieldData([]));
        }
    }

    private function prepareFieldId($fieldId)
    {
        if (TikiLib::startsWith($fieldId, 'ins_') == true) {
            $fieldId = str_replace('ins_', '', $fieldId);
        }

        if (! is_numeric($fieldId)) {
            if ($field = $this->definition->getFieldFromPermName($fieldId)) {
                $fieldId = $field['fieldId'];
            }
        }

        return $fieldId;
    }

    /**
     * Getter method for the permissions of this
     * item.
     *
     * @param string $permName
     * @return bool|null
     */
    public function getPerm($permName)
    {
        return isset($this->perms->$permName) ? $this->perms->$permName : null;
    }

    public function getPerms()
    {
        return $this->perms;
    }

    public function getOwnerGroup()
    {
        return $this->ownerGroup;
    }

    public function getViewPermission()
    {
        $status = isset($this->info['status']) ? $this->info['status'] : 'o';

        if ($status == 'c') {
            return 'view_trackers_closed';
        } elseif ($status == 'p') {
            return 'view_trackers_pending';
        } else {
            return 'view_trackers';
        }
    }

    /**
     * Gets a tracker item's data
     *
     * @param JitFilter|null $input optional input object
     * @param bool|false $forExport gets the field output in list_mode=csv not necessarily the stored value
     * @return array [permName => value]
     */
    public function getData($input = null, $forExport = false)
    {
        $out = [];
        if ($input) {
            $fields = $this->prepareInput($input);

            foreach ($fields as $field) {
                $permName = $field['permName'];
                $out[$permName] = $field['value'];

                if (isset($input->fields) && isset($input->fields[$permName])) {
                    $out[$permName] = $input->fields->$permName->none();
                }
            }
        } else {
            $factory = $this->definition->getFieldFactory();
            $info = $this->info;

            foreach ($this->definition->getFields() as $field) {
                $handler = $factory->getHandler($field, $info);
                $data = $handler->getFieldData();

                $permName = $field['permName'];
                $out[$permName] = isset($data['value']) ? $data['value'] : null;
            }
        }

        $newStatus = 'o';
        if ($this->isNew() && isset($this->definition)) {
            // set status based on tracker setting if status not explicitly requested
            $newStatus = $this->definition->getConfiguration('newItemStatus', 'o');
        }

        return [
            'itemId' => $this->isNew() ? null : $this->info['itemId'],
            'status' => $this->isNew() ? $newStatus : $this->info['status'],
            'creation_date' => $this->info['created'],
            'trackerId' => $this->isNew() ? null : $this->info['trackerId'],
            'fields' => $out,
        ];
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function getInfo()
    {
        return $this->info;
    }

    public function getDisplayedStatus()
    {
        if (
            ($this->definition->getConfiguration('showStatus', 'n') == 'y' && $this->definition->getConfiguration('showStatusAdminOnly', 'n') == 'n')
            || ($this->definition->getConfiguration('showStatusAdminOnly', 'n') == 'y' && $this->perms->admin_trackers)
        ) {
            $status = $this->isNew()
                ? $this->definition->getConfiguration('newItemStatus', 'o')
                : $this->info['status'];

            switch ($status) {
                case 'o':
                    return 'open';
                case 'p':
                    return 'pending';
                case 'c':
                    return 'closed';
            }
        }
    }

    /**
     * Helper method to prepare field values for item fields that do not store their
     * info in database - e.g. ItemsList or need additional data than the database
     * raw values - e.g. Duration field.
     * @param array data to be modified
     */
    public function prepareFieldValues(&$data)
    {
        $fieldData = ['itemId' => $this->info['itemId']];
        foreach ($data as $permName => $value) {
            $field = $this->definition->getField($permName);
            if ($field) {
                $fieldData[$field['fieldId']] = $value;
            }
        }
        foreach ($data as $permName => $value) {
            $field = $this->definition->getField($permName);
            if ($field && ($field['type'] == 'l' || $field['type'] == 'REL')) {
                $handler = $this->definition->getFieldFactory()->getHandler($field, $fieldData);
                $data[$permName] = $handler->getItemValues();
            }
            if ($field && $field['type'] == 'DUR') {
                $handler = $this->definition->getFieldFactory()->getHandler($field, $fieldData);
                $data[$permName] = $handler->getValueInSeconds();
            }
        }
    }
}
