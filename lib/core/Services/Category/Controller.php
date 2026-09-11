<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
use Tiki\Lib\core\Services\Category\CategorizationHelper;

class Services_Category_Controller
{
    private $filters = [
        'objects'          => 'string',
        'items'           => 'xss',
        'categIds'              => 'array',
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
        if (TIKI_API) { // api/categorize
            $input['object_action'] = 'categorize';
            $this->normalizeCategoryIds($input);
        }
        return $this->categorize($input);
    }

    public function action_uncategorize($input)
    {
        if (TIKI_API) { // api/uncategorize
            $input['object_action'] = 'uncategorize';
            $this->normalizeCategoryIds($input);
        }
        return $this->categorize($input);
    }

    // This function handles both ui and api context requests
    private function categorize($input)
    {
        $util = new Services_Utilities();
        $CATEGORIZE = $input['object_action'] == 'categorize';
        $UNCATEGORIZE = $input['object_action'] == 'uncategorize';
        $action = $input['object_action'];
        $categories = $input->asArray('categIds');
        $convertedCategories = $this->convertCategories($categories);

        if ($util->notConfirmPost() && ! TIKI_API) { // This should be skipped when request is from API
            // validate action to perform
            return $this->buildCategorizationUiConfirmation(
                $input,
                $action,
                $convertedCategories
            );
        } elseif ($util->checkCsrf()) {
            $util->setVars($input, $this->filters, 'items');
            $originalObjects = $this->convertObjects($util->items);
            if (TIKI_API) {
                $util->setVars($input, $this->filters, 'objects');
                $originalObjects = $this->convertObjects($util->items);
            }
            $messages = [];
            $err_messages = [];
            $oldRequest = TIKI_API && isset($input['categId']); // Legacy support for `categId`

            [$permittedCategories, $unpermittedCategories] = $this->resolveCategoryPermissions($convertedCategories, $action);

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
            foreach ($originalObjects as $object) {
                $type = $object['type'];
                $id = $object['id'];
                if (! $objectlib->isValidObject($type, $id)) {
                    throw new Services_Exception(tr('Invalid %0 ID: %1', $type, $id), 403);
                }
            }
            $categlib = TikiLib::lib('categ');
            $categoryResults = [];
            foreach ($permittedCategories as $cat) {
                $filteredObjects = $originalObjects;
                $categorizedObjects = [];
                $categId = $cat['id'];
                $outputCategoryName = $cat['name'];
                //first determine if objects are already in the category
                foreach ($originalObjects as $key => $object) {
                    $alreadyIn = in_array($categId, $categlib->get_object_categories($object['type'], $object['id']));
                    if (
                        ($CATEGORIZE && $alreadyIn) ||
                        ($UNCATEGORIZE && ! $alreadyIn)
                    ) {
                        $categorizedObjects[] = $object;
                        unset($filteredObjects[$key]);
                    }
                }
                //provide appropriate feedback for objects already in category
                if ($categorizedObjectsCount = count($categorizedObjects)) {
                    $messages[] = CategorizationHelper::unchangedMessage(
                        $outputCategoryName,
                        $categorizedObjectsCount,
                        $action
                    );
                }
                //now add objects to the category
                if (count($filteredObjects)) {
                    $funct = 'doCategorize';
                    if ($UNCATEGORIZE) {
                        $funct = 'doUncategorize';
                    }
                    $return = $this->processObjects($funct, $categId, $filteredObjects);
                    $categoryResults[] = $return;
                    $count = isset($return['objects']) ? count($return['objects']) : 0;
                    if ($count) {
                        $messages[] = CategorizationHelper::successMessage(
                            $outputCategoryName,
                            $count,
                            $action,
                        );
                    } else {
                        $err_messages[] = CategorizationHelper::emptyResultMessage(
                            $outputCategoryName,
                            $action,
                        );
                    }
                } else {
                    //this code is reached when all objects selected were already in the category
                    $categoryResults[] = [
                        'categId'   => $categId,
                        'objects'   => $originalObjects,
                        'count'     => 'unchanged'
                    ];
                }
            }
            if (TIKI_API) {
                return $this->formatApiResponse(
                    $categoryResults,
                    $messages,
                    $err_messages,
                    $oldRequest
                );
            } else {
                if (! empty($messages)) {
                    Feedback::success(implode('<br>', $messages));
                }

                if (! empty($err_messages)) {
                    Feedback::error(implode('<br>', $err_messages));
                }
                return Services_Utilities::refresh();
            }
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
            $url = \SmartyTiki\Modifier\Sefurl::apply($object, $type);
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
            if (! TIKI_API) {
                $cat = explode('-', $category, 2);

                if (count($cat) == 2) {
                    list($name, $id) = $cat;
                    $out[] = ['name' => $name, 'id' => $id];
                }
            } else {
                $out[] = ['name' => "categId-" . $category, 'id' => $category];
            }
        }
        return $out;
    }

    private function formatApiResponse(array $results, array $messages, array $errors, bool $oldRequest)
    {
        $response = [];

        if ($oldRequest) {
            // LEGACY (single categId): Keep it flat for backward compatibility
            $response = $results[0] ?? $results;
        } else {
            // NEW SYSTEM: Always return a 'data' array so it's predictable
            $response['data'] = $results;
        }

        if ($messages) {
            $response['messages'] = $messages;
        }

        if ($errors) {
            $response['err_messages'] = $errors;
        }

        return $response;
    }

    private function buildCategorizationUiConfirmation($input, string $action, array $categories)
    {
        $util = new Services_Utilities();

        $util->setVars($input, $this->filters, 'objects');
        $objects = $this->convertObjects($util->items);

        if (empty($categories)) {
            Services_Utilities::modalException(
                tra('No destination category was selected. Please select at least one.')
            );
        }

        if ($util->itemsCount === 0) {
            Services_Utilities::modalException(
                tra('No object was selected. Please select one or more objects.')
            );
        }

        return [
            'title' => tra('Please confirm'),
            'modal' => '1',
            'confirmAction' => $input->action->word(),
            'customMsg' => $action === 'categorize'
                ? tr('Add the following object(s)')
                : tr('Remove the following object(s)'),
            'confirmButton' => $action === 'categorize' ? tra('Add') : tra('Remove'),
            'items' => $util->items,
            'extra' => ['object_action' => $action],
            'objects' => array_map(
                fn ($obj) => strtoupper($obj['type'] . ': ')
                . \SmartyTiki\FunctionHandler\ObjectLink::render(
                    $obj,
                    TikiLib::lib('smarty')->getEmptyInternalTemplate()
                ),
                $objects
            ),
            'categories' => $categories,
        ];
    }

    private function resolveCategoryPermissions(array $categories, mixed $action): array
    {
        $allowed = [];
        $denied = [];

        foreach ($categories as $cat) {
            $perms = Perms::get('category', $cat['id']);

            $hasPermission = ($action === 'categorize' && $perms->add_objects) || ($action === 'uncategorize' && $perms->remove_objects);

            if ($hasPermission) {
                $allowed[] = $cat;
            } else {
                $denied[] = $cat;
            }
        }

        return [$allowed, $denied];
    }

    /**
     * Legacy support for `categId`. Do not extend.
     *
     * Older clients may send `categId` as a scalar. The public API exposes only
     * `categIds` (array), so legacy input is normalized.
     *
     * Used by api/categorize and api/uncategorize.
     *
     * @param JitFilter $request
     *
     * @return void
     */
    private function normalizeCategoryIds(JitFilter &$request): void
    {
        if (isset($request['categId']) && ! isset($request['categIds'])) {
            $request['categIds'] = [(int) $request['categId']];
        }
    }
}
