<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
class Tiki_Render_Editable
{
    private $inner;
    private $layout = 'inline';
    private $group = false;
    private $label = null;
    private $fieldFetchUrl;
    private $objectStoreUrl;
    private $field = null;

    public function __construct($html, array $parameters)
    {
        $this->inner = $html;

        if (! empty($parameters['layout']) && in_array($parameters['layout'], ['inline', 'block', 'dialog'])) {
            $this->layout = $parameters['layout'];
        }

        if (! empty($parameters['group'])) {
            $this->group = $parameters['group'];
        }

        if (! empty($parameters['label'])) {
            $this->label = $parameters['label'];
        }

        if (empty($parameters['field'])) {
            $this->field = ['id' => null, 'type' => null];
        } else {
            $this->field = $parameters['field'];
        }

        if ($this->field['type'] != 'form' && empty($parameters['object_store_url'])) {
            throw new Exception(tr('Internal error: mandatory parameter object_store_url is missing'));
        }

        $servicelib = TikiLib::lib('service');
        if (! empty($parameters['field_fetch_url'])) {
            $this->fieldFetchUrl = $parameters['field_fetch_url'];
        }

        if (! empty($parameters['object_store_url'])) {
            $this->objectStoreUrl = $parameters['object_store_url'];
        }
    }

    public function __toString()
    {
        global $prefs;

        if ($prefs['ajax_inline_edit'] != 'y') {
            return $this->inner === null ? '' : $this->inner;
        }

        $smarty = TikiLib::lib('smarty');

        // block = dialog goes to span as well
        $tag = ($this->layout == 'block') ? 'div' : 'span';
        $fieldId = $this->field['id'];
        $fieldType = $this->field['type'];
        $fieldFetch = \SmartyTiki\Modifier\Escape::apply(json_encode($this->fieldFetchUrl));
        $label = \SmartyTiki\Modifier\Escape::apply($this->label);
        if ($objectStore = $this->objectStoreUrl) {
            $objectStore['edit'] = 'inline';
        }
        $objectStore = \SmartyTiki\Modifier\Escape::apply(json_encode($objectStore));
        $group = \SmartyTiki\Modifier\Escape::apply($this->group);

        $value = $this->inner;
        if (is_null($value)) {
            $value = '';
        }

        $class = "editable-inline";
        if ($this->layout == 'dialog') {
            $class = "editable-dialog";
        }

        if (! $this->fieldFetchUrl) {
            $class .= ' loaded';
        }

        if ($fieldType == 'form') {
            $class .= ' inline-form';
            $params = [
                'rows' => 6,
                'autosave' => 'n',
                '_toolbars' => $this->field['wysiwyg'] ? 'y' : 'n',
                '_wysiwyg' => $this->field['wysiwyg'] ? 'y' : 'n',
            ];
            if ($params['_wysiwyg'] === 'y') {
                TikiLib::lib('wysiwyg')->setUpEditor($fieldId, $params);
                $editable = '<input type="hidden" id="allowhtml" name="allowhtml" value="1" /><textarea name="' . $fieldId . '" id="' . $fieldId . '" style="display:none" rows="' . $params['rows'] . '">' . htmlspecialchars($value) . '</textarea>';
            } elseif ($this->layout == 'block') {
                $editable = '<textarea class="form-control" name="' . $fieldId . '" id="' . $fieldId . '" rows="' . $params['rows'] . '">' . htmlspecialchars($value) . '</textarea>';
            } else {
                $editable = '<input type="text" class="form-control" name="' . $fieldId . '" id="' . $fieldId . '" value="' . htmlspecialchars($value) . '" />';
            }
            $editable = '<div style="display: none">' . $editable . '</div>';
        } else {
            $editable = '';
            if (trim(strip_tags($value)) == '') {
                // When the value is empty, make sure it becomes visible/clickable
                $value .= '&nbsp;';
            }
        }

        return "<$tag class=\"$class\" data-field-fetch-url=\"$fieldFetch\" data-object-store-url=\"$objectStore\" data-group=\"$group\" data-label=\"$label\" data-field-id=\"$fieldId\" data-field-type=\"$fieldType\">$value" . \SmartyTiki\FunctionHandler\Icon::render(['name' => 'edit', 'iclass' => 'ml-2'], $smarty->getEmptyInternalTemplate()) . "</$tag>$editable";
    }
}
