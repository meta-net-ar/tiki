{* $Id$ *}
{title help="Mail queues"}{tr}Mail queues{/tr}{/title}

{if empty($prefs.sender_email)}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
        {tr}You need to set <a class="alert-link" href="tiki-admin.php?page=general">Sender Email</a> before creating email queue{/tr}.
    {/remarksbox}
{/if}

<br>
<h2>{tr}Summary of the current mail queue status{/tr}</h2>
<p>{tr}Total messages in queue: {/tr}<strong>{$total_cant}</strong></p>
<p>{tr}Total messages stalled: {/tr}<strong>{$cant}</strong></p>
<p>{tr}Current value for max retries in case of error: {/tr}<strong>{$max_retries}</strong></p>
<h2>{tr}Mail queues{/tr}</h2>

<form method="get" action="tiki-admin_email_queue.php">
    {ticket}
    <div class="table-responsive email-queue-table">
        <table class="table table-striped table-hover">
            <tr>
                <th>
                    {if $mailQueues}
                        {select_all label='Select All' checkbox_names='checked[]'}
                    {/if}
                </th>
                <th>{tr}Date{/tr}</th>
                <th>{tr}Destination{/tr}</th>
                <th>{tr}Subject{/tr}</th>
                <th>{tr}Message{/tr}</th>
                <th></th>
            </tr>
            {section name=queue loop=$mailQueues}
                <tr>
                    <td class="checkbox-cell">
                        <div class="form-check">
                            <input type="checkbox" name="checked[]" value="{$mailQueues[queue].messageId|escape}">
                        </div>
                    </td>
                    <td class="text">{$mailQueues[queue].date|escape}</td>
                    <td class="text">{$mailQueues[queue].destination|escape}</td>
                    <td class="email">{$mailQueues[queue].subject|escape}</td>
                    <td class="text">{$mailQueues[queue].body|truncate:500|escape}</td>
                    <td class="actions">
                        {actions}
                            {strip}
                                <action>
                                    <a href="{$smarty.server.SCRIPT_NAME}?{query redeliver={$mailQueues[queue].messageId}}" onclick="confirmPopup('{tr}Redeliver mail queue?{/tr}', '{ticket mode=get}')">
                                        {icon name='undo' _menu_text='y' _menu_icon='y' alt="{tr}Redeliver{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a href="{$smarty.server.SCRIPT_NAME}?{query remove={$mailQueues[queue].messageId}}" onclick="confirmPopup('{tr}Delete mail queue?{/tr}', '{ticket mode=get}')">
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                    </a>
                                </action>
                            {/strip}
                        {/actions}
                    </td>
                </tr>
            {sectionelse}
                {norecords _colspan=6}
            {/section}
        </table>
    </div>
    {if $mailQueues}
        <div class="input-group col-sm-8">
            <select class="form-select" name="action">
                <option value="" selected="selected">
                    {tr}Select action to perform with checked{/tr}...
                </option>
                <option value="redeliver" class="confirm-popup" data-confirm-text="{tr}Redeliver selected queues?{/tr}">
                    {tr}Redeliver{/tr}
                </option>
                <option value="delete" class="confirm-popup" data-confirm-text="{tr}Delete selected queues?{/tr}">
                    {tr}Delete{/tr}
                </option>
            </select>
            <button type="submit" class="btn btn-primary" onclick="confirmPopup()">
                {tr}OK{/tr}
            </button>
        </div>
    {/if}
</form>

{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
