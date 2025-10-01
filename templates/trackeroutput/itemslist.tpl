{* Note that when there in only one item it needs to be unformatted as it is often used inline in pretty trackers *}
<div class="itemslist-field display_f{$field.fieldId|escape}"
        data-trackerid="{$field.trackerId}" data-itemid="{$item.itemId}" data-fieldId="{$field.fieldId}" data-listmode="{$context.list_mode}">
    {if $data.num > 1}
        <ul class="list-unstyled">
            {foreach from=$data.items key=id item=label}
                <li class="d-flex justify-content-between">
                    <span class="d-flex justify-content-start">
                        {if !empty($data.links)}
                            {object_link type=trackeritem id=$id title=$label}
                        {elseif $data.raw}
                            {$label}
                        {else}
                            {$label|escape}
                        {/if}
                    </span>
                    <span class="d-flex justify-content-end">
                        {if $field.options_map.editItem || $field.options_map.deleteItem}
                            {if $field.options_map.editItem && $data.itemPermissions[$id].can_modify}
                                <a class="itemslist-btn px-1" href="{service controller=tracker action=update_item trackerId=$field.options_map.trackerId itemId=$id}">
                                    {icon name="edit" ititle='{tr}Edit item{/tr}'}
                                </a>
                            {/if}
                            {if $field.options_map.deleteItem && $data.itemPermissions[$id].can_remove}
                                <a class="text-danger itemslist-btn px-1" href="{service controller=tracker action=remove_item trackerId=$field.options_map.trackerId itemId=$id}">
                                    {icon name="remove" ititle='{tr}Delete item{/tr}'}
                                </a>
                            {/if}
                        {/if}
                    </span>
                </li>
            {/foreach}
        </ul>
    {elseif $data.num eq 1}{strip}
        {foreach from=$data.items key=id item=label}
            {if !empty($data.links)}
                {object_link type=trackeritem id=$id title=$label}
            {elseif $data.raw}
                {$label}
            {else}
                {$label|escape}
            {/if}
        {/foreach}
        {if $field.options_map.editItem && $data.itemPermissions[$id].can_modify}
            <a class="itemslist-btn px-1" href="{service controller=tracker action=update_item trackerId=$field.options_map.trackerId itemId=$id}">
                {icon name="edit" ititle='{tr}Edit item{/tr}'}
            </a>
        {/if}
        {if $field.options_map.deleteItem && $data.itemPermissions[$id].can_remove}
            <a class="text-danger itemslist-btn px-1" href="{service controller=tracker action=remove_item trackerId=$field.options_map.trackerId itemId=$id}">
                {icon name="remove" ititle='{tr}Delete item{/tr}'}
            </a>
        {/if}
    {/strip}{/if}
    {if !empty($data.addItemText)}
        {$forcedParam[$data.otherFieldPermName]=$data.parentItemId}
        <div class="mt-2">
            <a class="btn btn-secondary btn-sm itemslist-btn" href="{service controller=tracker action=insert_item trackerId=$field.options_map.trackerId forced=$forcedParam}" role="button">
                {icon name='create' _menu_text='y' _menu_icon='y' ititle="{$data.addItemText}" alt="{$data.addItemText}"}
            </a>
        </div>
    {/if}
</div>
