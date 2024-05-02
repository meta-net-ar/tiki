<?php

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.
/**
 * This is the main interface to index tiki objects.  It turns them in documents for the search index, through the various Search_Type_Interface
 */
interface Search_ContentSource_Interface
{
    /**
     * Provides a list of type-specific object IDs available in the database.
     *
     * @return Traversable
     */
    public function getDocuments();

    /**
     * Provides the basic data for the specified object ID.
     *
     * @return array An array of keys, some standard (ex: title), some specific to the document type (ex: tracker_status).  False if the document isn't found in the source
     */
    public function getDocument($objectId, Search_Type_Factory_Interface $typeFactory): array|false;

    /**
     * Returns an array containing the list of field names that can be provided
     * by the content source.
     *
     * benoitg- 2023-03-04:  This list seems to be quite incomplete in the case of some object types (namely TrackerItemSource).
     */
    public function getProvidedFields(): array;

    /**
     * Returns an array containing the list of field names that can be provided
     * by the content source and their respective search types.
     */
    public function getProvidedFieldTypes(): array;


    /**
     * Returns an array containing the list of field names that must be included
     * in the "contents" key contents if indexable.
     */
    public function getGlobalFields(): array;
}
