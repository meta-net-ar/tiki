{if $mode eq 'table'}
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0">
            {foreach from=$fields item=field}
                <tr class="field_{$field.fieldId}">
                    <th class="text-nowrap" style="width: 1%;">{tr}{$field.name|escape}{/tr}</th>
                    <td>{trackeroutput field=$field item=$item process=y showlinks=n}</td>
                </tr>
            {/foreach}
        </table>
    </div>
{else}
    {foreach from=$fields item=field}
        <h6 class="field_{$field.fieldId}">{tr}{$field.name|escape}{/tr}</h6>
        <div class="field_{$field.fieldId}">{trackeroutput field=$field item=$item process=y showlinks=n}</div>
    {/foreach}
{/if}
{if $can_modify}
    <a class="click-modal tips" href="{service controller=tracker action=update_item trackerId=$item.trackerId itemId=$item.itemId}" title=":{tr}Edit{/tr}">{icon name="edit"} <span>{tr}Edit{/tr}</span></a>
{/if}
{if $can_remove}
    <a class="click-modal tips" href="{service controller=tracker action=remove_item trackerId=$item.trackerId itemId=$item.itemId}" title=":{tr}Delete{/tr}">{icon name="delete"} <span>{tr}Delete{/tr}</span></a>
{/if}
