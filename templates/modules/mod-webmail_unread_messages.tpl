{tikimodule error=$module_params.error title=$tpl_module_title name="unread_email_messages" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
<div class="table-responsive">
<table class="table table-condensed">
    <tbody>
        {if isset($webmailUnreadMessages['pages']) && $webmailUnreadMessages['pages']|count > 0}
            <tr>
                <th colspan="3" class="fw-bold text-uppercase">{tr}pages{/tr}</th>
            </tr>
            {foreach from=$webmailUnreadMessages['pages'] key=page_name item=row_value}
                <tr>
                    <td class="small fw-bold" colspan="2">{$page_name|capitalize}</td>
                </tr>
                {foreach from=$row_value key=row_name item=row}
                    <tr>
                        <td colspan="3" class="small"><a href="{$page_name == 'Main webmail'? 'tiki-webmail.php': $page_name|sefurl:'wikipage'}">{$row['name']}</a></td>
                        <td><span class="badge text-bg-success">{$row['total']}</span></td>
                    </tr>
                {/foreach}
            {/foreach}
        {/if}
        {if isset($webmailUnreadMessages['tracker_items']) && $webmailUnreadMessages['tracker_items']|count > 0}
            <tr>
                <th colspan="3" class="fw-bold text-uppercase">{tr}tracker items{/tr}</th>
            </tr>
            {foreach from=$webmailUnreadMessages['tracker_items'] item=row}
                <tr>
                    <td colspan="3" class="small"><a href="{$row['object_id']|sefurl:'trackeritem'}">{$row['name']|escape}</a></td>
                    <td><span class="badge text-bg-success">{$row['total']}</span></td>
                </tr>
            {/foreach}
        {/if}
    </tbody>
</table>
</div>
{/tikimodule}
