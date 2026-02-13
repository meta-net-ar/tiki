<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

/**
 * Interface for fields whose state may change in response to updates in other fields’ values.
 */
interface AutoSyncableInterface
{
    /**
     * Defines how the field’s state should be updated in response to inline changes in related fields.
     *
     * @param array $requestData The data submitted in the inline edit request
     */
    public function getAutoSyncInlineEditFieldData(array $requestData = []): array;
}
