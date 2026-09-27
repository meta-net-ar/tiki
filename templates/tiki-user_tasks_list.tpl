{*Smarty template*}

{* start ************ Task list ***************}
<form action="tiki-user_tasks.php" method="post">
    {ticket}
    <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
    <input type="hidden" name="offset" value="{$offset|escape}">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 bg-light p-3 border rounded mb-3">
        <div class="d-flex align-items-center gap-2">
            <input type="text" name="find" class="form-control form-control-sm w-auto" placeholder="{tr}Search tasks...{/tr}" value="{$find|escape}">
            <button type="submit" class="btn btn-primary btn-sm" name="search" title="{tr}Search{/tr}">{icon name="search"}</button>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="taskFiltersDropdown" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    {icon name="filter"} {tr}Filters{/tr}
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-3 shadow" aria-labelledby="taskFiltersDropdown" style="min-width: 220px;">
                    <li>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="show_private" id="show_private" {if $show_private}checked="checked"{/if}>
                            <label class="form-check-label" for="show_private">{tr}Private{/tr}</label>
                        </div>
                    </li>
                    {if $tiki_p_tasks_receive eq 'y'}
                    <li>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="show_received" id="show_received" {if $show_received}checked="checked"{/if}>
                            <label class="form-check-label" for="show_received">{tr}Received{/tr}</label>
                        </div>
                    </li>
                    {/if}
                    {if $tiki_p_tasks_send eq 'y'}
                    <li>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="show_submitted" id="show_submitted" {if $show_submitted}checked="checked"{/if}>
                            <label class="form-check-label" for="show_submitted">{tr}Submitted{/tr}</label>
                        </div>
                    </li>
                    {/if}
                    {if $tiki_p_tasks_receive eq 'y' or $tiki_p_tasks_send eq 'y'}
                    <li>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="show_shared" id="show_shared" {if $show_shared}checked="checked"{/if}>
                            <label class="form-check-label" for="show_shared">{tr}Shared{/tr}</label>
                        </div>
                    </li>
                    {/if}
                    <li>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="show_trash" id="show_trash" {if $show_trash}checked="checked"{/if}>
                            <label class="form-check-label" for="show_trash">{tr}Trash{/tr}</label>
                        </div>
                    </li>
                    <li>
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox" name="show_completed" id="show_completed" {if $show_completed}checked="checked"{/if}>
                            <label class="form-check-label" for="show_completed">{tr}Completed{/tr}</label>
                        </div>
                    </li>
                    {if $task_admin or $admin_mode}
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="show_admin" id="show_admin" {if $show_admin}checked="checked"{/if}>
                            <label class="form-check-label text-primary fw-bold" for="show_admin">{tr}All Shared Tasks{/tr}</label>
                        </div>
                    </li>
                    {/if}
                </ul>
            </div>
            
            <button type="submit" class="btn btn-outline-primary btn-sm" name="reload" title="{tr}Apply filters{/tr}">
                {icon name="refresh"} {tr}Apply{/tr}
            </button>
        </div>
    </div>

    <div class="table-responsive">
    <table class="table table-hover formcolor mb-0">
    <thead>
        <tr>
            <th style="width: 40px;">
                <input type="checkbox" class="form-check-input" id="selectAllTasks">
            </th>
            <th><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'title_desc'}title_asc{else}title_desc{/if}">{tr}Title{/tr}</a></th>
            <th><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'start_desc'}start_asc{else}start_desc{/if}">{tr}Start{/tr}</a></th>
            <th><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'end_desc'}end_asc{else}end_desc{/if}">{tr}End{/tr}</a></th>
            <th class="text-end"><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'priority_desc'}priority_asc{else}priority_desc{/if}">{tr}Priority{/tr}</a></th>
            <th class="text-end"><a href="tiki-user_tasks.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'percentage_desc'}percentage_asc{else}percentage_desc{/if}">{tr}Status{/tr}</a></th>
        </tr>
    </thead>

    <tbody>
        {section name=task_i loop=$tasklist}
            {assign var=task value=$tasklist[task_i]}
            <tr>
                <td class="prio{$task.priority} align-middle">
                    <input {if $task.disabled}disabled="disabled"{/if} type="checkbox" name="task[{$task.taskId}]" class="form-check-input task-checkbox">
                    
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
                    {if $task.priority eq 1}
                        <span class="badge bg-secondary">{tr}Very Low{/tr}</span>
                    {elseif $task.priority eq 2}
                        <span class="badge bg-info">{tr}Low{/tr}</span>
                    {elseif $task.priority eq 3}
                        <span class="badge bg-success">{tr}Medium{/tr}</span>
                    {elseif $task.priority eq 4}
                        <span class="badge bg-warning text-dark">{tr}High{/tr}</span>
                    {elseif $task.priority eq 5}
                        <span class="badge bg-danger">{tr}Very High{/tr}</span>
                    {else}
                        {$task.priority}
                    {/if}
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
        <tr class="table-light">
            <td colspan="3" class="align-middle">
                <div class="input-group input-group-sm w-auto">
                    <select name="action" class="form-select">
                        <option value="">{tr}Bulk actions...{/tr}</option>
                        <option value="waiting_marked">{tr}Waiting{/tr}</option>
                        <option value="open_marked">{tr}Open{/tr}</option>
                        <option value="complete_marked">{tr}Completed{/tr}</option>
                        <option value="move_marked_to_trash">{tr}Trash{/tr}</option>
                        <option value="remove_marked_from_trash">{tr}Undo Trash{/tr}</option>
                    </select>
                    <button type="submit" class="btn btn-primary" name="update_tasks">{tr}Apply{/tr}</button>
                </div>
            </td>
            <td colspan="3" class="text-end align-middle">
                <div class="d-flex align-items-center justify-content-end gap-3">
                    <div class="d-flex align-items-center gap-2">
                        <label class="small text-muted text-nowrap m-0">{tr}Per page{/tr}</label>
                        <select name="tasks_maxRecords" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
                            <option value="-1" {if $prefs.tasks_maxRecords eq -1}selected="selected"{/if}>{tr}All{/tr}</option>
                            {foreach from=array(2, 5, 10, 20, 30, 40, 50) item=val}
                            <option value="{$val}" {if $prefs.tasks_maxRecords eq $val}selected="selected"{/if}>{$val}</option>
                            {/foreach}
                        </select>
                    </div>
                    <button type="submit" class="btn btn-outline-primary btn-sm" name="update_percentage">{tr}Save status changes{/tr}</button>
                </div>
            </td>
        </tr>
    </tfoot>
</table>
</div>
</form>

{jq}
$('#selectAllTasks').on('change', function() {
    $('.task-checkbox').prop('checked', $(this).prop('checked'));
});
{/jq}

{pagination_links count=$count step=$maxRecords offset=$offset}{/pagination_links}
{* end ************ Task list ***************}
