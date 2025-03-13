<div class="object-selector">
<input
    type="text"
    id="{$object_selector.simpleid|escape}"
    style="display: none"
    {if !empty($object_selector.simpleclass)}class="{$object_selector.simpleclass|escape}"{/if}
    {if !empty($object_selector.simplename)}name="{$object_selector.simplename|escape}"{/if}
    {if !empty($object_selector.simplevalue)}value="{$object_selector.current_selection.id|escape}"{/if}
>
<input
    type="text"
    id="{$object_selector.id|escape}"
    style="display: none"
    {if !empty($object_selector.name)}name="{$object_selector.name|escape}"{/if}
    {if !empty($object_selector.class)}class="{$object_selector.class|escape}"{/if}
    {if !empty($object_selector.current_selection)}
        value="{$object_selector.current_selection|escape}"
        data-label="{$object_selector.current_selection.title|escape}"
    {/if}
    {if !empty($object_selector.parent)}data-parent="{$object_selector.parent|escape}"{/if}
    {if !empty($object_selector.parentkey)}data-parentkey="{$object_selector.parentkey|escape}"{/if}
    {if !empty($object_selector.format)}data-format="{$object_selector.format|escape}"{/if}
    {if !empty($object_selector.sort)}data-sort="{$object_selector.sort|escape}"{/if}
    data-wildcard="{$object_selector.wildcard|escape}"
    data-filters="{$object_selector.filter|escape}"
    data-threshold="{$object_selector.threshold|default:$prefs.tiki_object_selector_threshold|escape}"
    data-searchfield="{$object_selector.searchfield|escape}"
    data-relationshiptrackerid="{$object_selector.relationshipTrackerId}"
>
    <div class="basic-selector d-none mb-3{if !empty($object_selector.current_selection.metadata) or $object_selector.relationshipTrackerId} include-icon{/if}">
        <select class="form-select">
            <option value="" class="protected">&mdash;</option>
            {if !empty($object_selector.current_selection)}
                <option value="{$object_selector.current_selection|escape}" selected="selected">{$object_selector.current_selection.title|escape}</option>
            {/if}
        </select>
        {if !empty($object_selector.current_selection.metadata)}
            <a href="{bootstrap_modal controller=tracker action=update_item trackerId=$object_selector.current_selection.metadata.trackerId itemId=$object_selector.current_selection.metadata.itemId skipRefresh=1 size='modal-lg'}" title="edit metadata"|tra class="btn btn-link">{icon name="clipboard-list"}</a>
        {elseif $object_selector.relationshipTrackerId}
            <a href="{bootstrap_modal controller=tracker action=insert_item trackerId=$object_selector.relationshipTrackerId skipRefresh=1 refreshMeta=$object_selector.name refreshObject=$object_selector.name size='modal-lg'}" title="add metadata"|tra class="btn btn-link metadata-insert-item" data-object="{$object_selector.name|escape}">{icon name="clipboard-list"}</a>
        {/if}
    </div>

    <div class="card d-none">
        <div class="card-header">
            <div class="input-group">
                <div class="input-group-text">
                    {icon name="search"}
                </div>
                <input type="text" placeholder="{$object_selector.placeholder|escape}..." value="" class="filter form-control" autocomplete="off">
                <input type="button" class="btn btn-info search" value="{tr}Find{/tr}">
            </div>
        </div>
        <div class="card-body">
            <div class="results">
                <p class="too-many">{tr}Search and select what you are looking for from the options that appear.{/tr}</p>
                <div class="form-check">
                    <input name="{$object_selector.id|escape}_sel" class="form-check-input protected" type="radio" value="" {if ! $object_selector.current_selection} checked="checked" {/if} value="" id="{$object_selector.id|escape}_sel_empty">
                    <label class="form-check-label" for="{$object_selector.id|escape}_sel_empty">&mdash;</label>
                </div>
                {if !empty($object_selector.current_selection)}
                    <div class="form-check">
                        <input type="radio" checked="checked" value="{$object_selector.current_selection|escape}" name="{$object_selector.id|escape}_sel" id="{$object_selector.id|escape}_sel_selected">
                        <label class="form-check-label" for="{$object_selector.id|escape}_sel_selected">
                            {$object_selector.current_selection.title|escape}
                            {if !empty($object_selector.current_selection.metadata)}
                                <a href="{bootstrap_modal controller=tracker action=update_item trackerId=$object_selector.current_selection.metadata.trackerId itemId=$object_selector.current_selection.metadata.itemId skipRefresh=1 size='modal-lg'}" title="edit metadata"|tra class="btn btn-link">{icon name="clipboard-list"}</a>
                            {elseif $object_selector.relationshipTrackerId}
                                <a href="{bootstrap_modal controller=tracker action=insert_item trackerId=$object_selector.relationshipTrackerId skipRefresh=1 refreshMeta=$object_selector.name refreshObject=$object_selector.current_selection|escape size='modal-lg'}" title="add metadata"|tra class="btn btn-link metadata-insert-item" data-object="{$object_selector.current_selection|escape}">{icon name="clipboard-list"}</a>
                            {/if}
                        </label>
                    </div>
                {/if}
            </div>
            <p class="no-results d-none">
                {tr}No matching results.{/tr}
            </p>
        </div>
    </div>

    {if $object_selector.relationshipTrackerId}
    <div class="metadata-icon-template d-none">
        <a href="{bootstrap_modal controller=tracker action=insert_item trackerId=$object_selector.relationshipTrackerId skipRefresh=1 refreshMeta=$object_selector.name size='modal-lg'}" title="add metadata"|tra class="btn btn-link metadata-insert-item">{icon name="clipboard-list"}</a>
    </div>
    {/if}
</div>

{jq}
$('[id={{$object_selector.id|escape}}]')
    .object_selector();
{/jq}
