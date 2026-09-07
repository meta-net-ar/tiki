<form action="tiki-user_tasks.php" method="post" class="mt-3">
    {ticket}
    <input type="hidden" name="taskId" value="{$taskId|escape}">
    <input type="hidden" name="task_version" value="{$info.task_version|escape}">
    <input type="hidden" name="last_version" value="{$info.last_version|escape}">
    <input type="hidden" name="date_Day" value="{$date_Day|escape}">
    <input type="hidden" name="date_Month" value="{$date_Month|escape}">
    <input type="hidden" name="date_Year" value="{$date_Year|escape}">
    <input type="hidden" name="date_Hour" value="{$date_Hour|escape}">
    <input type="hidden" name="date_Minute" value="{$date_Minute|escape}">
    <input type="hidden" name="creator" value="{$info.creator|escape}">
    <input type="hidden" name="created" value="{$info.created|escape}">

    <div class="card mb-4 shadow-sm mx-auto" style="max-width: 900px;">
        <div class="card-header bg-light d-flex flex-wrap justify-content-between align-items-center py-3">
            <h5 class="mb-0 fw-bold">
                {if ($info.taskId > 0)}
                    {if (($info.creator eq $user) or ($info.user eq $user) or $admin_mode) and ($info.task_version eq $info.last_version)}
                        {tr}Edit Task{/tr}
                    {else}
                        {tr}View Task{/tr}
                    {/if}
                {else}
                    {tr}Open a new task{/tr}
                {/if}
            </h5>
            
            <div class="d-flex gap-2">
                {if ($tiki_view_mode eq 'view')}
                    <a href="tiki-user_tasks.php?taskId={$taskId}&amp;tiki_view_mode=edit" class="btn btn-sm btn-outline-primary">{tr}Edit Task{/tr}</a>
                {/if}
                {if ($info.creator eq $user or $info.user eq $user or $admin_mode) and ($info.task_version eq $info.last_version)}
                    {if ($info.taskId > 0 and $info.creator ne $info.user)}
                        <a href="tiki-user_tasks.php?taskId={$taskId}&amp;save=on&amp;task_accept=on" class="btn btn-sm btn-outline-success">{icon name='ok'} {tr}Accept{/tr}</a>
                        <a href="tiki-user_tasks.php?taskId={$taskId}&amp;save=on&amp;tas_not_accept=on" class="btn btn-sm btn-outline-danger">{icon name='remove'} {tr}NOT accept{/tr}</a>
                    {/if}
                    {if !empty($info.deleted)}
                        <a href="tiki-user_tasks.php?taskId={$taskId}&amp;save=on&amp;remove_from_trash=on" class="btn btn-sm btn-warning">{icon name='undo'} {tr}Remove from Trash{/tr}</a>
                    {else}
                        {if $info.taskId > 0}
                        <button type="submit" name="move_into_trash" value="on" class="btn btn-sm btn-outline-danger" onclick="this.form.insertAdjacentHTML('beforeend', '<input type=\'hidden\' name=\'save\' value=\'on\'>');">
                           {icon name='trash'} {tr}Move to Trash{/tr}
                        </button>
                        {/if}
                    {/if}
                {/if}
            </div>
        </div>
        
        <div class="card-body px-md-5 py-md-4">
            {if ($saved)}
                <div class="alert alert-success text-center">
                    <h5 class="mb-0">{tr}Task saved{/tr}</h5>
                </div>
            {/if}
            
            {* Info Header *}
            <div class="row mb-4 pb-3 border-bottom text-muted small">
                <div class="col-md-8">
                    {tr}Created by{/tr} <strong class="text-body">{$info.creator|escape}</strong> &nbsp;
                    {$info.created|tiki_short_date}&nbsp;--&nbsp;{$info.created|tiki_short_time} &nbsp;|&nbsp;
                    
                    {if ($info.task_version > 0)}
                        <a href="tiki-user_tasks.php?taskId={$taskId}&amp;show_history={$info.task_version-1}" class="text-decoration-none">{icon name='backward'}</a>
                    {/if}
                    {tr}Version:{/tr} <strong class="text-body">{$info.task_version+1}</strong>
                    {if $info.task_version < $info.last_version}
                        <a href="tiki-user_tasks.php?taskId={$taskId}&amp;show_history={$info.task_version+1}" class="text-decoration-none">{icon name='forward'}</a>
                    {/if} &nbsp;|&nbsp;
                    <a href="tiki-user_tasks.php?taskId={$taskId}&amp;show_history={$info.last_version}" class="text-decoration-none">{tr}Last Version:{/tr} {$info.last_version+1}</a>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    {tr}taskId:{/tr} <strong class="text-body">{$info.taskId|escape}</strong>
                </div>
            </div>

            <div class="mb-4">
                <label for="task_title" class="form-label fw-bold">{tr}Title{/tr}</label>
                <input id="task_title" type="text" class="form-control form-control-lg" name="title" value="{$info.title|escape}" placeholder="{tr}Enter task title{/tr}">
            </div>
            
            <div class="mb-4">
                <label for="edittask" class="form-label fw-bold">{tr}Description{/tr}</label>
                <div class="border rounded p-2 bg-light">
                    {toolbars area_id='edittask'}
                    <textarea id="edittask" class="form-control mt-2 border-0 shadow-sm" rows="12" name="description">{$info.description|escape}</textarea>
                </div>
            </div>

            <div class="row align-items-center mb-4">
                <div class="col-auto">
                    <label class="form-label fw-bold mb-0">{tr}Start{/tr}</label>
                </div>
                <div class="col">
                    <div id="start_date_container" class="d-flex align-items-center flex-wrap gap-2 date-time-selectors">
                        {html_select_date time=$start_date prefix="start_" end_year="+4" field_order=$prefs.display_field_order}
                        <span class="text-muted fw-bold mx-1">|</span>
                        {html_select_time minute_interval=10 time=$start_date prefix="start_" display_seconds=false use_24_hours=$use_24hr_clock}
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-check form-switch m-0">
                        <input name="use_start_date" id="use_start_date" {if $info.start or $taskId eq 0} checked="checked" {/if} type="checkbox" class="form-check-input">
                        <label class="form-check-label small text-muted" for="use_start_date">{tr}Enable{/tr}</label>
                    </div>
                </div>
            </div>

            <div class="row align-items-center mb-4">
                <div class="col-auto">
                    <label class="form-label fw-bold mb-0">{tr}End{/tr}</label>
                </div>
                <div class="col">
                    <div id="end_date_container" class="d-flex align-items-center flex-wrap gap-2 date-time-selectors">
                        {html_select_date time=$end_date prefix="end_" end_year="+4" field_order=$prefs.display_field_order}
                        <span class="text-muted fw-bold mx-1">|</span>
                        {html_select_time minute_interval=10 time=$end_date prefix="end_" display_seconds=false use_24_hours=$use_24hr_clock}
                    </div>
                </div>
                <div class="col-auto">
                    <div class="form-check form-switch m-0">
                        <input name="use_end_date" id="use_end_date" {if !empty($info.end)} checked="checked" {/if} type="checkbox" class="form-check-input">
                        <label class="form-check-label small text-muted" for="use_end_date">{tr}Enable{/tr}</label>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold">{tr}Task user{/tr}</label>
                    {if ($receive_users)}
                        {if ($info.taskId > 0 and !$admin_mode)}
                            <select class="form-select" name="task_user_dummy" disabled="disabled">
                                <option value="{$info.user}">{$info.user}</option>
                            </select>
                            <input type="hidden" name="task_user" value="{$info.user}">
                        {else}
                            <select class="form-select" name="task_user">
                                {section name=user_i loop=$receive_users}
                                    <option value="{$receive_users[user_i].login|escape}" {if ( $receive_users[user_i].login eq $info.user)} selected="selected" {/if}>
                                        {$receive_users[user_i].login|escape}
                                    </option>
                                {/section}
                            </select>
                        {/if}
                    {else}
                        <input type="text" class="form-control" name="task_user" value="{$info.user|escape}">
                    {/if}
                    
                    {if (($info.user ne $info.creator) or ($taskId eq 0))}
                        <div class="form-check mt-2 small text-muted">
                            <input class="form-check-input" type="checkbox" name="rights_by_creator" id="rights_by_creator"
                                {if $info.creator ne $user} disabled="disabled" {/if}
                                {if $info.rights_by_creator eq 'y'} checked="checked" {/if}>
                            <label class="form-check-label" for="rights_by_creator">{tr}Only the creator can delete this task{/tr}</label>
                        </div>
                    {/if}
                </div>
                <div class="col-md-6 mt-3 mt-md-0">
                    <label class="form-label fw-bold">{tr}Shared for Group{/tr}</label>
                    <select class="form-select" name="public_for_group">
                        <option></option>
                        {section name=groups_i loop=$receive_groups}
                            <option value="{$receive_groups[groups_i].groupName|escape}" {if ( $receive_groups[groups_i].groupName eq $info.public_for_group)} selected="selected" {/if}>
                                {$receive_groups[groups_i].groupName|escape}
                            </option>
                        {/section}
                    </select>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-4">
                    <label class="form-label fw-bold">{tr}Status{/tr}</label>
                    <div class="form-control-plaintext py-0">
                        {if $info.status eq 'o'}
                            <span class="badge bg-primary">{tr}Open / In Process{/tr}</span>
                        {else}
                            <span class="badge bg-secondary">{tr}Waiting / Not Started{/tr}</span>
                        {/if}
                        {if $info.completed}
                        <div class="small text-muted mt-2">
                            {icon name="calendar"} {$info.completed|tiki_short_date}&nbsp;--&nbsp;{$info.completed|tiki_short_time}
                        </div>
                        {/if}
                    </div>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <label class="form-label fw-bold">{tr}Priority{/tr}</label>
                    <select class="form-select" name="priority">
                        <option value="1" {if $info.priority eq 1} selected="selected"{/if}>1 - {tr}Lowest{/tr} -</option>
                        <option value="2" {if $info.priority eq 2} selected="selected"{/if}>2 - {tr}Low{/tr} -</option>
                        <option value="3" {if $info.priority eq 3} selected="selected"{/if}>3 - {tr}Normal{/tr} -</option>
                        <option value="4" {if $info.priority eq 4} selected="selected"{/if}>4 - {tr}High{/tr} -</option>
                        <option value="5" {if $info.priority eq 5} selected="selected"{/if}>5 - {tr}Very High{/tr} -</option>
                    </select>
                </div>
                <div class="col-md-4 mt-3 mt-md-0">
                    <label class="form-label fw-bold">{tr}Percentage completed{/tr}</label>
                    <select class="form-select" name="percentage">
                        <option value="w" {if !empty($info.percentage_null)} selected="selected" {/if}>{tr}Waiting{/tr}</option>
                        {section name=zz loop=$percs}
                            <option value="{$percs[zz]|escape}" {if $info.percentage eq $percs[zz] and !$info.percentage_null} selected="selected" {/if}>
                                {$percs[zz]}%
                            </option>
                        {/section}
                    </select>
                </div>
            </div>

            {if ($info.user ne $info.creator and ($info.task_version eq $info.last_version))}
                <div class="mb-4 p-3 bg-light rounded border">
                    <label class="form-label fw-bold">{tr}Info{/tr}</label>
                    <p class="small text-muted mb-2">{tr}This message will be send to users if you are makeing changes of assigned tasks{/tr}</p>
                    <textarea class="form-control mb-2" rows="2" name="task_info_message">{$info.info|escape}</textarea>
                    <div class="form-check">
                        <input checked="checked" type="checkbox" class="form-check-input" id="task_send_changes_message" name="task_send_changes_message">
                        <label class="form-check-label" for="task_send_changes_message">{tr}Send a message with changes{/tr}</label>
                    </div>
                </div>
            {/if}
            
            {if (($info.taskId > 0) and ($info.user ne $info.creator))}
            <div class="mb-4 small p-3 bg-light rounded border">
                <div class="row">
                    <div class="col-md-6 border-end">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{tr}Accepted by User{/tr}</span>
                            <strong class="{if $info.accepted_user eq 'y'}text-success{elseif $info.accepted_user eq 'n'}text-danger{else}text-warning{/if}">
                                {if $info.accepted_user eq 'y'}{tr}Yes{/tr}{elseif $info.accepted_user eq 'n'}{tr}No / Rejected{/tr}{else}{tr}Waiting{/tr}{/if}
                            </strong>
                        </div>
                    </div>
                    <div class="col-md-6 pl-md-3">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">{tr}Accepted by Creator{/tr}</span>
                            <strong class="{if $info.accepted_creator eq 'y'}text-success{elseif $info.accepted_creator eq 'n'}text-danger{else}text-warning{/if}">
                                {if $info.accepted_creator eq 'y'}{tr}Yes{/tr}{elseif $info.accepted_creator eq 'n'}{tr}No / Rejected{/tr}{else}{tr}Waiting{/tr}{/if}
                            </strong>
                        </div>
                    </div>
                </div>
            </div>
            {/if}

            {if $info.task_version > 0 or !empty($info.deleted)}
            <div class="mb-0 small text-muted border-top pt-3">
                <div class="row">
                    {if $info.task_version > 0}
                    <div class="col-md-6">
                        {icon name="edit"} {tr}Modified by{/tr} <strong class="text-body">{$info.lasteditor}</strong><br>
                        <span class="ms-3">{$info.changes|tiki_short_date}&nbsp;--&nbsp;{$info.changes|tiki_short_time}</span>
                    </div>
                    {/if}
                    {if !empty($info.deleted)}
                    <div class="col-md-6 mt-2 mt-md-0 text-danger">
                        {icon name="trash"} {tr}Marked as deleted{/tr}<br>
                        <span class="ms-3">{$info.deleted|tiki_short_date}&nbsp;--&nbsp;{$info.deleted|tiki_short_time}</span>
                    </div>
                    {/if}
                </div>
            </div>
            {/if}
            
        </div>
        
        <div class="card-footer bg-light px-md-5 py-4">
            {if (($info.creator eq $user) or ($info.user eq $user) or $admin_mode) and ($info.task_version eq $info.last_version)}
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                    <div>
                        {if $info.taskId eq 0}
                            <div class="form-check m-0">
                                <input checked="checked" type="checkbox" class="form-check-input" id="send_email_newtask" name="send_email_newtask">
                                <label class="form-check-label text-muted" for="send_email_newtask">{tr}Inform task user by email{/tr}</label>
                            </div>
                        {/if}
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-outline-secondary" name="reload">{tr}Reload{/tr}</button>
                        <button type="submit" class="btn btn-outline-primary" name="preview">{tr}Preview{/tr}</button>
                        <button type="submit" class="btn btn-primary px-4" name="save">{tr}Save{/tr}</button>
                    </div>
                </div>
            {else}
                <div class="text-center text-muted fw-bold">
                    {icon name="lock"} {tr}You can only view this task{/tr}
                </div>
            {/if}
        </div>
    </div>
</form>
