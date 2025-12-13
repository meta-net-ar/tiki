<?php

/**
 * @package tikiwiki
 */

// (c) Copyright by authors of the Tiki Wiki CMS Groupware Project
//
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

//this script may only be included - so its better to die if called directly.
if (str_contains($_SERVER["SCRIPT_NAME"], basename(__FILE__))) {
    header("location: index.php");
    exit;
}

$entities = [];
$relationships = [];
$title = tr("Mermaid schema export");

function addEntity(array &$entities, array $availableTrackers, int $trackerId): bool
{
    if (isset($entities[$trackerId])) {
        return true;
    }
    $tracker = $availableTrackers[$trackerId];
    if (! $tracker) {
        Feedback::warning(tr("Tracker %0 does not exist or you do not have permission to export it", $trackerId));
        return false;
    }
    $entity = ['tracker' => $tracker];
    $entities[$trackerId] = $entity;
    return true;
}

if (! empty($trackerIds)) {
    foreach ($trackerIds as $id) {
        $id = intval($id);
        if (! addEntity($entities, $availableTrackers, $id)) {
            continue;
        };
        $tracker = $availableTrackers[$id];

        if (! $skipRelations) {
            $relationalFields = $tracker->getAllRelationalFieldInstances();
            foreach ($relationalFields as $field) {
                $relationInfo = $field->getRelationInfo();
                if ($relationInfo) { //If relation info is null, the relation exists, but isn't valid
                    if (! addEntity($entities, $availableTrackers, $relationInfo->first->instance->getId())) {
                        //Don't add the relationship at all if one side is unavailable
                        break;
                    }
                    if (! addEntity($entities, $availableTrackers, $relationInfo->second->instance->getId())) {
                        //Don't add the relationship at all if one side is unavailable
                        break;
                    }
                    //Usage of the id will remove duplicates
                    $relationships[$relationInfo->id] = $relationInfo;
                }
            }
        }
    }
}

function exportMermaidER(string $title, array $entities, array $relationships, bool $skipAttributes = false, bool $includePermNames = true, bool $textPlain = false): string
{
    $output = <<<END
        ---
        $title
        ---
        erDiagram

        END;

    //Export entities
    foreach ($entities as $entity) {
        $tracker = $entity['tracker'];
        $entity = mermaidEntityName($tracker->getName() . '_' . $tracker->getId());
        $entityAlias = "[\"{$tracker->getId()}: {$tracker->getName()}\"]";
        $output .= "$entity$entityAlias {\n";
        if (! $skipAttributes) {
            //Export attributes
            foreach ($tracker->getAllFieldInstances() as $trackerField) {
                $type = mermaidTypeName($trackerField->getFieldTypeName());

                $name = mermaidAttributeName($trackerField->getId());

                $comment = '"';
                if ($includePermNames) {
                    $comment .= "{$trackerField->getPermName()}: ";
                }
                $comment .= "{$trackerField->getName()}";
                $comment .= '"';

                $output .= "    $type $name $comment\n";
            }
        }
        $output .= "}\n";
    }

    //Export relationships
    foreach ($relationships as $relationshipInfo) {
        $tracker = $relationshipInfo->first->instance;
        $firstEntity = mermaidEntityName($tracker->getName() . '_' . $tracker->getId());
        $tracker = $relationshipInfo->second->instance;
        $secondEntity = mermaidEntityName($tracker->getName() . '_' . $tracker->getId());
        $leftMin = $relationshipInfo->first->cardinalityMin;
        $leftMax = $relationshipInfo->first->cardinalityMax;
        $rightMin = $relationshipInfo->second->cardinalityMin;
        $rightMax = $relationshipInfo->second->cardinalityMax;
        /* From mermaid doc:
        Value (left)    Value (right)   Meaning
        |o  o|  Zero or one
        ||  ||  Exactly one
        }o  o{  Zero or more (no upper limit)
        }|  |{  One or more (no upper limit)
        */
        $relationshipValue = $leftMax > 1 ? '}' : '|';
        $relationshipValue .= $leftMin === 0 ? 'o' : '|';
        $relationshipValue .= '..';//For now in tiki all relationships are non-identifying
        $relationshipValue .= $rightMin === 0 ? 'o' : '|';
        $relationshipValue .= $rightMax > 1 ? '{' : '|';

        $name = '"' . $relationshipInfo->id . ': ' . $relationshipInfo->name . '"';
        $output .= "$firstEntity $relationshipValue $secondEntity : $name\n";
    }
    return $output;
}

function mermaidEntityName(string $originalId): string
{
    //Replace all but allowed characters with underscores.  Not hyphens (mermain has a parsing but where if TO DELETE is converted to TO-DELETE, it will interpret is as the start of the "to" alias of relationships)
    $mermaidEntityName = preg_replace('/[^A-Za-z0-9\-_]/', '_', $originalId);
    //Add underscore if there is a leading nonalphabetic character
    $mermaidEntityName = preg_replace('/^([^A-Za-z]+)/', '_$1', $mermaidEntityName);
    return $mermaidEntityName;
}

function mermaidAttributeName(string $originalId, string $replacementLeadingCharacters = 'f'): string
{
    //Replace all but allowed characters with hyphens
    $mermaidStr = preg_replace('/[^A-Za-z0-9\-_()\[\]*]/', '-', $originalId);
    //Add a replacement character if if there is a leading nonalphabetic character
    $mermaidStr = preg_replace('/^([^A-Za-z*]+)/', $replacementLeadingCharacters . '$1', $mermaidStr);

    return $mermaidStr;
}

/** The type values must begin with an alphabetic character and may contain digits, hyphens, underscores, parentheses and square brackets. */
function mermaidTypeName(string $originalId): string
{
    //Replace all but allowed characters with hyphens
    $mermaidStr = preg_replace('/[^A-Za-z0-9\-_()\[\]]/', '-', $originalId);

    //Add x if there is a leading nonalphabetic character
    $mermaidStr = preg_replace('/^([^A-Za-z]+)/', 'x$1', $mermaidStr);
    return $mermaidStr;
}
/**
 * Names must begin with an alphabetic character or *, and may also contain digits and hyphens, underscores, parentheses and square brackets.
 */

/**
 * Renders a graph in mermaid format in the tiki interface
 *
 * Keep this separated, it will eventually be moved to it's own class to be re-used by an eventual wikiplugin_mermaid plugin - benoitg - 2024-08-14
 *
 * @param string $mermaidData Mermaid text format
 * @return string HTML markup
 */

function handleMermaid(string $mermaidData, string $width = '100%', string $height = '60vh')
{
    global $headerlib;
    $inputIddenOrTextarea = "<input type='hidden' class='code' value='$mermaidData'>";
    $output = "<div class='containerDiagram'>";
    $output .= $inputIddenOrTextarea;
    $output .= "<pre class='mermaid border' style='overflow: auto; width: $width; height: $height''>";
    $output .= '</pre>';
    $output .= "<input type='hidden' name='rawFormat' id='raw'>";
    $output .= "</div>";
    $headerlib->add_js_module("import handleMermaid from '@mermaidPack'; handleMermaid();");
    return $output;
}
