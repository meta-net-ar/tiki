{*Smarty template*}

{* start ************ Task list ***************}
<form action="tiki-user_tasks.php" method="post">
    {ticket}
    <table class="table table-hover formcolor">
    <thead>
        <tr>
            <td colspan="6">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <label>{tr}Tasks per page{/tr}</label>
                        <select name="tasks_maxRecords" class="form-select form-select-sm d-inline-block w-auto">
                        <option value="-1" {if $prefs.tasks_maxRecords eq -1}selected="selected"{/if}>{tr}All{/tr}</option>
                        {foreach from=array(2, 5, 10, 20, 30, 40, 50) item=val}
                        <option value="{$val}" {if $prefs.tasks_maxRecords eq $val}selected="selected"{/if}>{$val}</option>
                        {/foreach}
                        </select>
                    </div>

                    <div class="col-md-9 text-center">
                        <div class="mb-2">
                            <strong>{tr}Show:{/tr}</strong>
                            <label class="mx-1"><input name="show_private" {if $show_private}checked="checked"{/if} type="checkbox"> {tr}Private{/tr}</label>
                            
                            {if $tiki_p_tasks_receive eq 'y'}
                                <label class="mx-1"><input name="show_received" {if $show_received}checked="checked"{/if} type="checkbox"> {tr}Received{/tr}</label>
                            {/if}
                            
                            {if $tiki_p_tasks_send eq 'y'}
                                <label class="mx-1"><input name="show_submitted" {if $show_submitted}checked="checked"{/if} type="checkbox"> {tr}Submitted{/tr}</label>
                            {/if}
                            
                            {if $tiki_p_tasks_receive eq 'y' or $tiki_p_tasks_send eq 'y'}
                                <label class="mx-1"><input name="show_shared" {if $show_shared}checked="checked"{/if} type="checkbox"> {tr}Shared{/tr}</label>
                            {/if}
                            
                            <label class="mx-1"><input name="show_trash" {if $show_trash}checked="checked"{/if} type="checkbox"> {tr}Trash{/tr}</label>
                            <label class="mx-1"><input name="show_completed" {if $show_completed}checked="checked"{/if} type="checkbox"> {tr}Completed{/tr}</label>
                            
                            {if ($admin_mode)}
                                <label class="mx-1 highlight text-primary">
                                    <input name="show_admin" {if $show_admin}checked="checked"{/if} type="checkbox"> {tr}All Shared Tasks{/tr}
                                </label>
                            {/if}
                        </div>
                        <div>
                            <input type="submit" class="btn btn-info btn-sm" name="reload" value="{tr}Reload{/tr}">
                        </div>
                    </div>
                </div>
            </td>
        </tr>

        <tr>
            <th style="width: 40px;">&nbsp;</th>
            <th><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'title_desc'}title_asc{else}title_desc{/if}">{tr}Title{/tr}</a></th>
            <th><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'start_desc'}start_asc{else}start_desc{/if}">{tr}Start{/tr}</a></th>
            <th><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'end_desc'}end_asc{else}end_desc{/if}">{tr}End{/tr}</a></th>
            <th class="text-end"><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'priority_desc'}priority_asc{else}priority_desc{/if}">{tr}Priority{/tr}</a></th>
            <th class="text-end"><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'percentage_desc'}percentage_asc{else}percentage_desc{/if}">{tr}Completed{/tr}</a></th>
        </tr>
    </thead>

    <tbody>
        {section name=task_i loop=$tasklist}
            {assign var=task value=$tasklist[task_i]}
            <tr>
                <td class="prio{$task.priority} align-middle">
                    <input {if $task.disabled}disabled="disabled"{/if} type="checkbox" name="task[{$task.taskId}]">
                    
                    {if $task.deleted}
                        {if $task.creator ne $user}
                            {icon name='remove' class='tips' title=":{tr}Deleted{/tr}"}
                        {else}
                            {icon name='trash' class='tips' title=":{tr}In the trash{/tr}"}
                        {/if}
                    {/if}

                    {if ($task.creator eq $task.user) and ($task.user eq $user)}
                        {*private task*}
                    {elseif ($task.user eq $user)}
                        {*received task*}
                        {icon name='login' title="{tr}Task received{/tr}"}
                        {if $task.accepted_creator eq 'n' or $task.accepted_user eq 'n'}
                            {icon name='remove' class='tips' title=":{tr}Rejected by a user{/tr}"}
                        {elseif $task.accepted_user eq ''}
                            {icon name='history' class='tips' title=":{tr}Waiting for me{/tr}"}
                        {elseif $task.accepted_creator eq 'y'}
                            {icon name='ok' class='tips' title=":{tr}Accepted by task user and creator{/tr}"}
                        {else}
                            {icon name='user' class='tips' title=":{tr}Waiting for other user{/tr}"}
                        {/if}
                    {elseif ($task.creator eq $user)}
                        {*submitted task*}
                        {icon name='logout' class='tips' title=":{tr}Task sent{/tr}"}
                        {if $task.accepted_creator eq 'n' or $task.accepted_user eq 'n'}
                            {icon name='remove' class='tips' title=":{tr}Not Accepted by One User{/tr}"}
                        {elseif $task.accepted_user eq ''}
                            {if $task.accepted_creator eq 'y'}
                                {icon name='user' class='tips' title=":{tr}Waiting for other user{/tr}"}
                            {else}
                                {icon name='history' class='tips' title=":{tr}Waiting for me{/tr}"}
                            {/if}
                        {elseif $task.accepted_creator eq 'y'}
                            {icon name='ok' class='tips' title=":{tr}Accepted by task user and creator{/tr}"}
                        {else}
                            {icon name='history' class='tips' title=":{tr}Waiting for me{/tr}"}
                        {/if}
                    {else}
                        {*shared task*}
                        {icon name='group' class='tips' title=":{tr}Task shared by a group{/tr}"}
                    {/if}
                </td>

                <td class="prio{$task.priority} align-middle">
                    <a class="link {if $task.status eq 'c'}text-decoration-line-through text-muted{/if}" 
                       href="tiki-user_tasks.php?taskId={$task.taskId}&amp;offset={$offset}&amp;sort_mode={$sort_mode}&amp;tiki_view_mode=view&amp;find={$find}">
                       {$task.title|escape}
                    </a>
                </td>

                <td class="prio{$task.priority} align-middle {if $task.status eq 'c'}text-decoration-line-through text-muted{/if}">
                    {$task.start|tiki_short_date} <small>[{$task.start|tiki_short_time}]</small>
                </td>
                <td class="prio{$task.priority} align-middle {if $task.status eq 'c'}text-decoration-line-through text-muted{/if}">
                    {$task.end|tiki_short_date} <small>[{$task.end|tiki_short_time}]</small>
                </td>

                <td class="prio{$task.priority} text-end align-middle {if $task.status eq 'c'}text-decoration-line-through text-muted{/if}">
                    {$task.priority}
                </td>

                <td class="prio{$task.priority} text-end align-middle">
                    <select {if $task.disabled}disabled="disabled"{/if} name="task_perc[{$task.taskId}]" class="form-select form-select-sm d-inline-block w-auto">
                        <option value="w" {if $task.percentage_null}selected="selected"{/if}>{tr}Waiting{/tr}</option>
                        {section name=zz loop=$percs}
                            <option value="{$percs[zz]|escape}" {if $task.percentage eq $percs[zz] and !$task.percentage_null}selected="selected"{/if}>
                                {$percs[zz]}%
                            </option>
                        {/section}
                    </select>
                </td>
            </tr>
        {sectionelse}
            <tr>
                <td colspan="6" class="text-center p-4"><em>{tr}No tasks entered{/tr}</em></td>
            </tr>
        {/section}
    </tbody>

    <tfoot>
        <tr>
            <td colspan="3" class="align-bottom">
                <div class="input-group input-group-sm w-auto">
                    <span class="input-group-text">{icon name='ok'}</span>
                    <select name="action" class="form-select">
                        <option value="">{tr}Select One{/tr}</option>
                        <option value="waiting_marked">{tr}Waiting{/tr}</option>
                        <option value="open_marked">{tr}Open{/tr}</option>
                        <option value="complete_marked">{tr}Completed{/tr}</option>
                        <option value="move_marked_to_trash">{tr}Trash{/tr}</option>
                        <option value="remove_marked_from_trash">{tr}Undo Trash{/tr}</option>
                    </select>
                    <button type="submit" class="btn btn-primary" name="update_tasks">{tr}Go{/tr}</button>
                </div>
            </td>
            <td colspan="3" class="text-end align-bottom">
                <button type="submit" class="btn btn-primary btn-sm" name="update_percentage">{tr}Apply{/tr}</button>
            </td>
        </tr>
    </tfoot>
</table>
</form>

{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}
{* end ************ Task list ***************}
