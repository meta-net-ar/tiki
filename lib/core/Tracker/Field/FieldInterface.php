<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

interface FieldInterface
{
    /**
     * Return an array of types (ususally of just one element) that this
     * class implements.  The key is the type string in the database,
     * the content is an associative array of information about that type.
     * For an example of a field implementing more than one type,
     * see Tracker_Field_Dropdown
     *
     * Currently, we don't have constants to represent this.  The closest thing
     * to a mapping is the return of Tracker_Field_Factory::buildTypeMap()
    */
    public static function getManagedTypesInfo(): array;

    /**
     * Optional method for implementations supporting multiple implementations or needing custom construction.
     *
     * public static function build($type, $trackerDefinition, $fieldInfo, $itemData);
     */

    /**
     * return the values of a field (not necessarily the html that will be displayed) for input or output
     * The values come from either the requestData if defined, the database if defined or the default
     * @param array something like $_REQUEST
     * @return
     */
    public function getFieldData(array $requestData = []);

    /**
     * return the html of the input form for a field
     *  either call renderTemplate if using a tpl or use php code
     * @param
     * @return string html
    */
    public function renderInput($context = []);

    /**
     * return the html for the output of a field
     *  with the link, prepend, append....
     *  Use renderInnerOutput
     * @param
     * @return string html
    */
    public function renderOutput($context = []);

    /**
     * Generate the plain text comparison to include in the watch email.
     */
    public function watchCompare($old, $new);

    /**
     * Augmentable fields allow adding a value to the set of pre-existing values.
     */
    public function addValue($value);

    /**
     * Augmentable fields allow removing a value from the set of pre-existing values.
     */
    public function removeValue($value);

    // The following methods are commented out, because trackerlib,php calls method_exists() on them
    // This is confusing in IDEs, and should probably be replaced with a different pattern.

    /**
     * Called by trackerlib with final value saved
     */
    /*
    public function postSaveHook($value)
    {
        return;
    }
    */
    /**
     * Computes the final value to be saved by the field, otherwise the implementation in trakckerlib is used
     * @returns Final value to be saved
     */
    /*
    public function handleSave($value, $old_value) {

    }
    */
    /**
     * handleFinalSave is use to compute final value when it depends on the value of other fields.
     * It will be called after all other fields are saved, and  will get as parameter all other field data (other than ones that also use handleFinalSave).
     * @returns Final value to be saved */
    /*
    public function handleFinalSave($data)    {
        return;
    }
    */
    /**
     * @returns true if the field value is valid, the error message to display otherwise
     */
    //function isValid($ins_fields_data);
}
