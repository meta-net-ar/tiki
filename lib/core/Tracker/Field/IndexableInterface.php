<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
namespace Tracker\Field;

interface IndexableInterface extends \Tracker\Field\FieldInterface
{
    public function getDocumentPart(\Search_Type_Factory_Interface $typeFactory);

    public function getProvidedFields(): array;

    public function getProvidedFieldTypes(): array;

    public function getGlobalFields(): array;

    /**
     * Returns the start of the keys the field will be available unded in the unified interface.
     *
     * @return text
     */
    public function getBaseKey();
}
