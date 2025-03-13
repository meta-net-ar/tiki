<div class="object-selector-multi">
{if !empty($object_selector_multi.separator)}
    <input
        data-separator="{$object_selector_multi.separator|escape}"
        data-use_permname="{$object_selector_multi.use_permname|escape}"
        type="text"
        style="display: none"
        id="{$object_selector_multi.simpleid|escape}"
        {if !empty($object_selector_multi.simpleclass)}class="{$object_selector_multi.simpleclass|escape}"{/if}
        {if !empty($object_selector_multi.simplename)}name="{$object_selector_multi.simplename|escape}"{/if}
        value="{$object_selector_multi.current_selection_simple|join:$object_selector_multi.separator|escape}"
    >
{/if}
<textarea
    id="{$object_selector_multi.id|escape}"
    style="display: none"
    {if !empty($object_selector_multi.name)}name="{$object_selector_multi.name|escape}"{/if}
    {if !empty($object_selector_multi.class)}class="{$object_selector_multi.class|escape}"{/if}
    {if !empty($object_selector_multi.title)}data-label="{$object_selector_multi.title|escape}"{/if}
    {if !empty($object_selector_multi.parent)}data-parent="{$object_selector_multi.parent|escape}"{/if}
    {if !empty($object_selector_multi.parentkey)}data-parentkey="{$object_selector_multi.parentkey|escape}"{/if}
    {if !empty($object_selector_multi.format)}data-format="{$object_selector_multi.format|escape}"{/if}
    {if !empty($object_selector_multi.sort)}data-sort="{$object_selector_multi.sort|escape}"{/if}
    data-wildcard="{$object_selector_multi.wildcard|escape}"
    data-filters="{$object_selector_multi.filter|escape}"
    data-threshold="{$object_selector_multi.threshold|default:$prefs.tiki_object_selector_threshold|escape}"
    data-searchfield="{$object_selector_multi.searchfield|escape}"
    data-relationshiptrackerid="{$object_selector_multi.relationshipTrackerId}"
>{$object_selector_multi.current_selection|join:"\n"}</textarea>
    <div class="basic-selector d-none">
        <select class="form-select" style="width: 100%;" multiple>
            {foreach $object_selector_multi.current_selection as $object}
                <option value="{$object|escape}" selected="selected">{$object.title|escape}</option>
            {/foreach}
        </select>
    </div>

    <div class="card d-none">
        <div class="card-header">
            <div class="input-group">
                <span class="input-group-text">
                    {icon name=search}
                </span>
                <input type="text" placeholder="{$object_selector_multi.placeholder|escape}..." value="" class="filter form-control" autocomplete="off">
                <input type="button" class="btn btn-info search" value="{tr}Find{/tr}">
            </div>
        </div>
        <div class="card-body">
            <p class="too-many">{tr}Search and select what you are looking for from the options that appear.{/tr}</p>
            <p class="too-many">
                <b class="text-warning">{tr}Please note:{/tr}</b>
                {tr}Depending on your current Tiki configuration you might not see all the options available. Adjust the 'Object selector threshold' in the 'Pagination' settings to show them all.{/tr}
            </p>
            <div class="results">
                {foreach from=$object_selector_multi.current_selection item=object name=ix}
                    <div class="form-check">
                        <input id="{$object_selector_multi.id|escape}_selected_{$smarty.foreach.ix.index}" class="form-check-input" type="checkbox" value="{$object|escape}" checked>
                        <label class="form-check-label" for="{$object_selector_multi.id|escape}_selected_{$smarty.foreach.ix.index}">
                            {if $object|substring:0:11 eq 'trackeritem'}
                                {tracker_item_status_icon item=$object|substring:12}
                            {/if}
                            {$object->getTitle($object_selector_multi.format)|escape}
                            {if isset($object.metadata) && $object.metadata}
                                <a href="{bootstrap_modal controller=tracker action=update_item trackerId=$object.metadata.trackerId itemId=$object.metadata.itemId skipRefresh=1 size='modal-lg'}" title="edit metadata"|tra class="btn btn-link">{icon name="clipboard-list"}</a>
                            {elseif $object_selector_multi.relationshipTrackerId}
                                <a href="{bootstrap_modal controller=tracker action=insert_item trackerId=$object_selector_multi.relationshipTrackerId skipRefresh=1 refreshMeta=$object_selector_multi.name refreshObject=$object|escape size='modal-lg'}" title="add metadata"|tra class="btn btn-link metadata-insert-item" data-object="{$object|escape}">{icon name="clipboard-list"}</a>
                            {/if}
                        </label>
                    </div>
                {/foreach}
            </div>
            <p class="no-results d-none">
                {tr}No matching results.{/tr}
            </p>
        </div>
    </div>

    {if $object_selector_multi.relationshipTrackerId}
    <div class="metadata-icon-template d-none">
        <a href="{bootstrap_modal controller=tracker action=insert_item trackerId=$object_selector_multi.relationshipTrackerId skipRefresh=1 refreshMeta=$object_selector_multi.name size='modal-lg'}" title="add metadata"|tra class="btn btn-link metadata-insert-item">{icon name="clipboard-list"}</a>
    </div>
    {/if}
</div>

{jq}
$('[id={{$object_selector_multi.id|escape}}]')
    .object_selector_multi();
{/jq}
