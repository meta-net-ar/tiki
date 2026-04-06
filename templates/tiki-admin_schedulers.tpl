{title help="Scheduler" admpage="general" url="tiki-admin_schedulers.php"}{tr}Scheduler{/tr}{/title}
<div class="t_navbar mb-4">
    {if isset($schedulerinfo.id)}
        {button href="?add=1" class="btn btn-primary" _text="{tr}Add a new Scheduler{/tr}"}
    {/if}

</div>

{* Add alert for stalled tasks *}
{if $stalledTasksCount > 0}
    <div class="alert alert-warning mb-4">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <span class="fa fa-exclamation-circle fa-2x"></span>
            </div>
            <div>
                <h5 class="alert-heading mb-1">{tr}Scheduler Notice{/tr}</h5>
                <p class="mb-0">
                    {tr _0=$stalledTasksCount}There are %0 stalled tasks that may need your attention.{/tr}
                    <a href="tiki-admin_schedulers.php?filter=stalled" class="alert-link">{tr}View stalled tasks{/tr}</a>
                </p>
            </div>
        </div>
    </div>
{/if}

{* Show active filter banner if a filter is applied *}
{if $activeFilter}
    <div class="alert alert-info mb-4">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <span class="fa fa-filter me-2"></span>
                {tr}Showing stalled tasks only{/tr}
            </div>
            <div>
                <a href="tiki-admin_schedulers.php" class="btn btn-sm btn-outline-secondary">
                    <span class="fa fa-times me-1"></span>
                    {tr}Clear filter{/tr}
                </a>
            </div>
        </div>
    </div>
{/if}

{tabset name='tabs_admin_schedulers'}

{* ---------------------- tab with list -------------------- *}
{if $schedulers|count > 0}
    {tab name="{tr}Schedulers{/tr}"}
        {* Notification Users Overview Section *}
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between">
                <h5 class="mb-0">
                    <span class="fa fa-bell me-2 text-muted"></span>
                    {tr}Notification Recipients{/tr}
                </h5>
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#notificationUsersCollapse" aria-expanded="false">
                    <span class="fa fa-chevron-down"></span>
                </button>
            </div>
            <div class="collapse" id="notificationUsersCollapse">
                <div class="mt-2">
                    {if isset($notificationUsers) && $notificationUsers|count > 0}
                        <div class="list-unstyled">
                            {foreach from=$notificationUsers item=user}
                                <div class="mb-1">
                                    <span class="fa fa-envelope me-2 text-muted"></span>
                                    <span title="{$user.login|escape}">{$user.email|escape}</span>
                                </div>
                            {/foreach}
                        </div>
                        <small class="text-muted mt-2 d-block">
                            {tr}These users will be notified when any scheduler is stalled or healed.{/tr}
                            <a href="tiki-admin.php?page=general#Scheduler" class="text-decoration-none ms-1">{tr}You can edit this in settings{/tr}</a>
                        </small>
                    {else}
                        <div class="text-center py-2">
                            <small class="text-muted">
                                {tr}No notification users configured.{/tr}
                                <br>
                                <a href="tiki-admin.php?page=general#Scheduler" class="text-decoration-none">{tr}Configure in General settings{/tr}</a>
                            </small>
                        </div>
                    {/if}
                </div>
            </div>
        </div>

        <div id="admin_schedulers-div">
            <form name="schedulerform" id="schedulerform" method="post">
                {ticket}
                <div class="{if $js}table-responsive {/if}ts-wrapperdiv">
                    {* Use css menus as fallback for item dropdown action menu if javascript is not being used *}
                    <table id="admin_schedulers" class="table normal table-striped table-hover" data-count="{$schedulers|count}">
                        <thead>
                        <tr>
                            <th>
                                {select_all checkbox_names='checked[]'}
                            </th>
                            <th>
                                {tr}Name{/tr}
                            </th>
                        <th>
                            {tr}Description{/tr}
                        </th>
                        <th>
                            {tr}Task{/tr}
                        </th>
                        <th>
                            {tr}Run Time{/tr}
                        </th>
                        <th>
                            {tr}Status{/tr}
                        </th>
                        <th>
                            {tr}Run only once{/tr}
                        </th>
                        <th>
                            {tr}Re-Run{/tr}
                        </th>
                        <th>
                            {*Reserved for stalled notices*}
                            Stalled
                        </th>
                        <th>
                            {tr}Last Run Status{/tr}
                        </th>
                        <th id="actions"></th>
                    </tr>
                    </thead>

                    <tbody>
                    {section name=scheduler loop=$schedulers}
                        {$scheduler_name = $schedulers[scheduler].name|escape}
                        <tr>
                            <td class="checkbox-cell">
                                <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="checked[]" value="{$schedulers[scheduler].id|escape}">
                            </td>
                            <td class="scheduler_name">
                                <a class="link tips"
                                    href="tiki-admin_schedulers.php?scheduler={$schedulers[scheduler].id}{if $prefs.feature_tabs ne 'y'}#2{/if}"
                                    title="{$scheduler_name}:{tr}Edit scheduler settings{/tr}"
                                >
                                    {$scheduler_name}
                                </a>
                            </td>
                            <td class="scheduler_description">
                                {$schedulers[scheduler].description|escape}
                            </td>
                            <td class="scheduler_task">
                                {$schedulers[scheduler].task|escape}
                            </td>
                            <td class="scheduler_run_time">
                                {$schedulers[scheduler].run_time|escape}
                            </td>
                            <td class="scheduler_status">
                                {$schedulers[scheduler].status|escape|ucfirst}
                            </td>
                            <td class="scheduler_run_only_once">
                                <input type="checkbox" {if $schedulers[scheduler].run_only_once}checked{/if} disabled>
                            </td>
                            <td class="scheduler_re_run">
                                <input type="checkbox" {if $schedulers[scheduler].re_run}checked{/if} disabled>
                            </td>
                            <td class="scheduler_stalled">
                                {if $schedulers[scheduler].last_run_stalled}
                                    <span class="badge bg-danger">{tr}Yes{/tr}</span>
                                {else}
                                    <span class="badge bg-secondary">{tr}No{/tr}</span>
                                {/if}
                            </td>
                            <td class="scheduler_last_run_status">
                                {if $schedulers[scheduler].last_run_status eq 'running'}
                                    <span class="badge bg-warning">{tr}Running{/tr}</span>
                                {elseif $schedulers[scheduler].last_run_status eq 'failed'}
                                    <span class="badge bg-danger">{tr}Failed{/tr}</span>
                                {elseif $schedulers[scheduler].last_run_status eq 'done'}
                                    <span class="badge bg-success">{tr}Done{/tr}</span>
                                {elseif $schedulers[scheduler].stalled}
                                    <span class="badge bg-danger">{tr}Stalled{/tr}</span>
                                {else}
                                    <span class="text-muted">-</span>
                                {/if}
                            </td>
                            <td class="action">
                                {actions}
                                    {strip}
                                        {if $schedulers[scheduler].stalled}
                                            <action>
                                                <a href="{bootstrap_modal controller=scheduler action=reset schedulerId=$schedulers[scheduler].id}">
                                                    {icon name="undo" _menu_text='y' _menu_icon='y' alt="{tr}Reset{/tr}"}
                                                </a>
                                            </action>
                                        {else}
                                            <action>
                                                <a href="{service controller=scheduler action=run schedulerId=$schedulers[scheduler].id modal=1}" onclick="runNow(event)" disabled>
                                                    {icon name="play" _menu_text='y' _menu_icon='y' alt="{tr}Run now{/tr}"}
                                                </a>
                                            </action>
                                            <action>
                                                <a href="{service controller=scheduler action=run_background schedulerId=$schedulers[scheduler].id}" onclick="runNowBackground(event)" disabled>
                                                    {icon name="play" _menu_text='y' _menu_icon='y' alt="{tr}Run now{/tr} ({tr}Background{/tr})"}
                                                </a>
                                            </action>
                                        {/if}
                                        <action>
                                            <a href="tiki-admin_schedulers.php?scheduler={$schedulers[scheduler].id}">
                                                {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                            </a>
                                        </action>
                                        <action>
                                            <a href="{query _type='relative' scheduler=$schedulers[scheduler].id logs='1'}">
                                                {icon name="log" _menu_text='y' _menu_icon='y' alt="{tr}Logs{/tr}"}
                                            </a>
                                        </action>
                                        <action>
                                            <a href="{bootstrap_modal controller=scheduler action=remove schedulerId=$schedulers[scheduler].id}">
                                                {icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                            </a>
                                        </action>
                                    {/strip}
                                {/actions}
                            </td>
                        </tr>
                    {/section}
                    </tbody>
                </table>
            </div>

            {if $schedulers|count > 0}
                <div class="input-group col-sm-8">
                    <select class="form-select" name="action">
                        <option value="no_action" selected disabled>
                            {tr}Select action to perform with checked{/tr}...
                        </option>
                        <option value="remove_schedulers">
                            {tr}Remove schedulers...{/tr}
                        </option>
                    </select>
                    <button
                        type="submit"
                        form="schedulerform"
                        formaction="{bootstrap_modal controller=scheduler action=remove_schedulers}"
                        class="btn btn-primary"
                        id="bulk-action-submit"
                    >
                        {tr}OK{/tr}
                    </button>
                </div>
            {/if}
        </form>
        </div>
    {/tab}
{/if}

{* ---------------------- tab with form -------------------- *}
<a id="tab2"></a>
{if isset($schedulerinfo.id) && $schedulerinfo.id}
    {$add_edit_scheduler_tablabel = "{tr}Edit scheduler{/tr}"}
    {$schedulename = "<i>{$schedulerinfo.name|escape}</i>"}
{else}
    {$add_edit_scheduler_tablabel = "{tr}Add a new scheduler{/tr}"}
    {$schedulename = ""}
{/if}

{tab name="{$add_edit_scheduler_tablabel} {$schedulename}"}
    <br><br>
    <div class="row">
        <div class="offset-sm-2 col-sm-10">
            {remarksbox type="note" title="{tr}Information{/tr}"}
            {tr}Use CRON format to enter the values in "Run Time":<br>Minute, Hour, Day of Month, Month, Day of Week<br>Eg. every 5 minutes: */5 * * * *{/tr}<br>
            {tr _0=$run_timezone}"Run time" must be in the %0 time zone.{/tr}
            {/remarksbox}
        </div>
    </div>
    {if $schedulerinfo.task == 'ConsoleCommandTask' and empty($prefs.fallbackBaseUrl) }
        <div class="row">
           <div class="offset-sm-2 col-sm-10">
               {remarksbox type="warning" title="{tr}Warning{/tr}"}
               {tr}Some commands may need to determine the URL of the website and will not be able to do so reliably because fallbackBaseUrl is not set in the <a target="_blank" href="tiki-admin.php?page=general">admin</a>.{/tr}
               {/remarksbox}
           </div>
       </div>
       {/if}
    <form class="form" action="tiki-admin_schedulers.php" method="post"
            enctype="multipart/form-data" name="RegForm" autocomplete="off">
        {ticket}
        <div class="tiki-form-group row">
            <label class="col-sm-2 col-form-label" for="scheduler_name">{tr}Name{/tr} *</label>
            <div class="col-sm-10">
                <input maxlength="{$MAX_SCHEDULER_NAME_LENGTH}" type="text" id='scheduler_name' class="form-control" name='scheduler_name'
                    value="{$schedulerinfo.name|escape}">
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-sm-2 col-form-label" for="scheduler_description">{tr}Description{/tr}</label>
            <div class="col-sm-10">
                <input maxlength="{$MAX_SCHEDULER_DESCRIPTION_LENGTH}" type="text" id='scheduler_description' class="form-control" name='scheduler_description'
                    value="{$schedulerinfo.description|escape}">
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-sm-2 col-form-label" for="scheduler_task">{tr}Task{/tr} *</label>
            <div class="col-sm-10">
                <select id="scheduler_task" name="scheduler_task" class="form-control">
                    <option value=''></option>
                    {html_options options=$schedulerTasks selected=$schedulerinfo.task}
                </select>
                <div id="console_doc">
                    <label>{tr}Documentation : {/tr}</label>
                    <a target="blank" href="https://doc.tiki.org/Console">https://doc.tiki.org/Console</a>
                </div>
            </div>
        </div>

        {foreach from=$schedulerTasks key=commandName item=taskName}
            {scheduler_params name=$commandName params=$schedulerinfo.params}
        {/foreach}

        <div class="tiki-form-group row">
            <label class="col-sm-2 col-form-label" for="scheduler_time">{tr}Run Time{/tr} *</label>
            <div class="col-sm-10">
                <input type="text" id='scheduler_time' class="form-control" name='scheduler_time'
                    value="{$schedulerinfo.run_time|escape}">
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-sm-2 col-form-label" for="scheduler_status">{tr}Status{/tr}</label>
            <div class="col-sm-10">
                <select id="scheduler_status" name="scheduler_status" class="form-control">
                    schedulerStatus
                    {html_options options=$schedulerStatus selected=$schedulerinfo.status}
                </select>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-sm-2 form-check-label" for="scheduler_rerun">{tr}Run if missed{/tr}</label>
            <div class="col-sm-10">
                <div class="form-check">
                    <input type="checkbox" id="scheduler_rerun" class="form-check-input" name="scheduler_rerun"
                        {if !empty($schedulerinfo.re_run)}checked{/if}>
                </div>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-sm-2 form-check-label" for="scheduler_run_only_once">{tr}Run only once{/tr}</label>
            <div class="col-sm-10">
                <div class="form-check">
                    <input type="checkbox" id="scheduler_run_only_once" class="form-check-input" name="scheduler_run_only_once"
                        {if !empty($schedulerinfo.run_only_once)}checked{/if}>
                </div>
            </div>
        </div>
        <div class="tiki-form-group row">
            <label class="col-sm-2 form-check-label" for="enable_send_notification_override">{tr}Always send notifications for this job (override global settings){/tr}</label>
            <div class="col-sm-10">
                <div class="form-check">
                    <input type="checkbox" id="enable_send_notification_override" class="form-check-input" name="enable_send_notification_override"
                        {if isset($schedulerinfo.enable_send_notification_override) && $schedulerinfo.enable_send_notification_override}checked{/if}>
                    <div class="form-text">{tr}If checked, notifications will always be sent when this job is stalled or healed.{/tr}</div>
                </div>
            </div>
        </div>

        <div class="tiki-form-group row">
            <div class="col-sm-10 offset-sm-3">
                {if isset($schedulerinfo.id) && $schedulerinfo.id}
                    <input type="hidden" name="scheduler" value="{$schedulerinfo.id|escape}">
                    <input type="hidden" name="editscheduler" value="1">
                    <input type="submit" class="btn btn-primary" name="save" value="{tr}Save{/tr}">
                {else}
                    <input type="submit" class="btn btn-secondary" name="new_scheduler" value="{tr}Add{/tr}">
                {/if}
            </div>
        </div>

    </form>
{/tab}

<a id="tab3"></a>
{if isset($schedulerinfo.id) && $schedulerinfo.id}
    {tab name="{tr}Scheduler logs{/tr}"}
        <h2>{tr}Scheduler{/tr} {$schedulerinfo.name|escape} Logs</h2>
        <h3>{tr}Last {$numOfLogs} Logs{/tr}</h3>
        <div class="table-responsive">
            <table class="table normal table-striped table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Status</th>
                    <th>Output</th>
                </tr>
                </thead>
                <tbody>
                {section name=run loop=$schedulerruns}
                    <tr>
                        <td>{$schedulerruns[run].id}</td>
                        <td>
                            {$schedulerruns[run].start_time|tiki_short_datetime:'':'n'} ({$display_timezone})
                            {if $display_timezone ne 'UTC'}
                                <br>{$schedulerruns[run].start_time|tiki_short_datetime:'':'n':'UTC'} (UTC)
                            {/if}
                        </td>
                        <td>
                            {if $schedulerruns[run].end_time ne null}
                                {$schedulerruns[run].end_time|tiki_short_datetime:'':'n'} ({$display_timezone})
                                {if $display_timezone ne 'UTC'}
                                    <br>{$schedulerruns[run].end_time|tiki_short_datetime:'':'n':'UTC'} (UTC)
                                {/if}
                            {/if}
                        </td>
                        <td>
                            {if $schedulerruns[run].status eq 'running'}
                                <span class="badge bg-warning">{tr}Running{/tr}</span>
                            {/if}
                            {if $schedulerruns[run].status eq 'failed'}
                                <span class="badge bg-danger">{tr}Failed{/tr}</span>

                            {/if}
                            {if $schedulerruns[run].status eq 'done'}
                                <span class="badge bg-success">{tr}Done{/tr}</span>
                            {/if}
                        </td>
                        <td>
                            {if isset($schedulerruns[run].can_stop) && $schedulerruns[run].can_stop}
                                <a class="btn btn-secondary btn-sm" href="{bootstrap_modal controller=scheduler action=reset schedulerId=$schedulerruns[run].scheduler_id startTime=$schedulerruns[run].start_time}">
                                {icon name="undo" _menu_text='y' _menu_icon='y' alt="{tr}Reset{/tr}"}
                                </a>
                            {else}
                                {$schedulerruns[run].output|nl2br}
                            {/if}
                        </td>
                    </tr>
                {/section}
                </tbody>
            </table>
        </div>
        {pagination_links count=$count step=$numrows offset=$offset}tiki-admin_schedulers.php?scheduler={$schedulerinfo.id}&cookietab=3{/pagination_links}
    {/tab}
{/if}

<a id="tab4"></a>
{if $jobs|count > 0}
    {tab name="{tr}Jobs{/tr}"}
        <div id="admin_jobs-div">
            {remarksbox type="note" title="{tr}Information{/tr}"}
            {tr}This page lists all scheduled background jobs along with their processing status. You can re-run a job or see a log of the job executions. Note that Tiki Scheduler must be executed periodically by a cron job in order to execute the background jobs. If you see a job in Active status below for too long interval, then your cron job is most probably not running. {/tr}
            {/remarksbox}
            <form name="jobsform" id="jobsform" method="post">
                {ticket}
                <div class="{if $js}table-responsive {/if}ts-wrapperdiv">
                    {* Use css menus as fallback for item dropdown action menu if javascript is not being used *}
                    <table id="admin_jobs" class="table normal table-striped table-hover" data-count="{$jobs|count}">
                        <thead>
                        <tr>
                            <th>
                                {select_all checkbox_names='checked_jobs[]'}
                            </th>

                        <th>
                            {tr}Name{/tr}
                        </th>
                        <th>
                            {tr}Task{/tr}
                        </th>
                        <th>
                            {tr}Params{/tr}
                        </th>
                        <th>
                            {tr}Run Time{/tr}
                        </th>
                        <th>
                            {tr}Status{/tr}
                        </th>
                        <th>
                            {tr}Run Status{/tr}
                        </th>
                        <th>
                            {tr}Start Time{/tr}
                        </th>
                        <th>
                            {tr}End Time{/tr}
                        </th>
                        <th>
                            {tr}Output{/tr}
                        </th>
                        <th id="actions"></th>
                    </tr>
                    </thead>

                    <tbody>
                    {section name=job loop=$jobs}
                        {$job = $jobs[job]}
                        <tr>
                            <td class="checkbox-cell">
                                <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="checked_jobs[]" value="{$job.id|escape}">
                            </td>
                            <td class="scheduler_name">
                                <a class="link tips"
                                    href="tiki-admin_schedulers.php?scheduler={$job.id}{if $prefs.feature_tabs ne 'y'}#2{/if}"
                                    title="{$job.name|escape}:{tr}Edit scheduler settings{/tr}"
                                >
                                    {$job.name|escape}
                                </a>
                            </td>
                            <td class="scheduler_task">
                                {$job.task|escape}
                            </td>
                            <td class="scheduler_params">
                                {$job.params|escape}
                            </td>
                            <td class="scheduler_run_time">
                                {$job.creation_date|tiki_short_datetime}
                            </td>
                            <td class="scheduler_status">
                                {$job.status|escape|ucfirst}
                            </td>
                            <td class="scheduler_run_status">
                                {if $job.run_status eq 'running'}
                                    <span class="badge bg-warning">{tr}Running{/tr}</span>
                                {/if}
                                {if $job.run_status eq 'failed'}
                                    <span class="badge bg-danger">{tr}Failed{/tr}</span>

                                {/if}
                                {if $job.run_status eq 'done'}
                                    <span class="badge bg-success">{tr}Done{/tr}</span>
                                {/if}
                            </td>
                            <td>{if $job.start_time ne null}{$job.start_time|tiki_short_datetime}{/if}</td>
                            <td>{if $job.end_time ne null}{$job.end_time|tiki_short_datetime}{/if}</td>
                            <td>
                                {$job.output|nl2br}
                            </td>
                            <td class="action">
                                {actions}
                                    {strip}
                                        {if $job.status eq 'inactive'}
                                            <action>
                                                <a href="{service controller=scheduler action=activate schedulerId=$job.id}">
                                                    {icon name="play" _menu_text='y' _menu_icon='y' alt="{tr}Run again{/tr}"}
                                                </a>
                                            </action>
                                        {/if}
                                        <action>
                                            <a href="{query _type='relative' scheduler=$job.id logs='1'}">
                                                {icon name="log" _menu_text='y' _menu_icon='y' alt="{tr}Logs{/tr}"}
                                            </a>
                                        </action>
                                        <action>
                                            <a href="{bootstrap_modal controller=scheduler action=remove schedulerId=$job.id}">
                                                {icon name="remove" _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                            </a>
                                        </action>
                                    {/strip}
                                {/actions}
                            </td>
                        </tr>
                    {/section}
                    </tbody>
                </table>
            </div>

            {if $jobs|count > 0}
                <div class="input-group col-sm-8">
                    <select class="form-select" name="job_action">
                        <option value="no_action" selected disabled>
                            {tr}Select action to perform with checked{/tr}...
                        </option>
                        <option value="remove_jobs">
                            {tr}Remove jobs...{/tr}
                        </option>
                    </select>
                    <button
                        type="submit"
                        form="jobsform"
                        formaction="{bootstrap_modal controller=scheduler action=remove_jobs}"
                        class="btn btn-primary"
                        id="bulk-job-action-submit"
                    >
                        {tr}OK{/tr}
                    </button>
                </div>
            {/if}
        </form>
        </div>
    {/tab}
{/if}

{* ---------------------- Consolidated Logs Tab -------------------- *}
<a id="tab5"></a>
{if !isset($schedulerinfo.id)}
{tab name="{tr}Logs{/tr}"}
    <h2>{tr}Scheduler Run Logs{/tr}</h2>
    <h3>{tr}Last {$numOfLogs} Logs{/tr}</h3>
    <div id="admin_schedulers_logs-div">
        <div class="{if $js}table-responsive {/if}ts-wrapperdiv">
            <table class="table normal table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>{tr}Name{/tr}</th>
                        <th>{tr}Start Time{/tr}</th>
                        <th>{tr}End Time{/tr}</th>
                        <th>{tr}Status{/tr}</th>
                        <th>{tr}Output{/tr}</th>
                    </tr>
                </thead>
                <tbody>
                    {section name=run loop=$consolidatedLogs}
                        <tr>
                            <td>{$consolidatedLogs[run].id}</td>
                            <td>
                                {if $consolidatedLogs[run].scheduler_name}
                                    {$consolidatedLogs[run].scheduler_name|escape}
                                {else}
                                    <span class="text-muted fst-italic">{tr}Scheduler Deleted{/tr}</span>
                                {/if}
                            </td>
                            <td>
                                {$consolidatedLogs[run].start_time|tiki_short_datetime} ({$display_timezone})
                                {if $display_timezone ne 'UTC'}
                                    <br>{$consolidatedLogs[run].start_time|tiki_short_datetime:'':'y':'UTC'} (UTC)
                                {/if}
                            </td>
                            <td>
                                {if $consolidatedLogs[run].end_time ne null}
                                    {$consolidatedLogs[run].end_time|tiki_short_datetime} ({$display_timezone})
                                    {if $display_timezone ne 'UTC'}
                                        <br>{$consolidatedLogs[run].end_time|tiki_short_datetime:'':'y':'UTC'} (UTC)
                                    {/if}
                                {/if}
                            </td>
                            <td>
                                {if $consolidatedLogs[run].status eq 'running'}
                                    <span class="badge bg-warning">{tr}Running{/tr}</span>
                                {/if}
                                {if $consolidatedLogs[run].status eq 'failed'}
                                    <span class="badge bg-danger">{tr}Failed{/tr}</span>
                                {/if}
                                {if $consolidatedLogs[run].status eq 'done'}
                                    <span class="badge bg-success">{tr}Done{/tr}</span>
                                {/if}
                            </td>
                            <td>
                                {$consolidatedLogs[run].scheduler_output|nl2br}
                            </td>
                        </tr>
                    {sectionelse}
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                {tr}No scheduler logs found{/tr}
                            </td>
                        </tr>
                    {/section}
                </tbody>
            </table>
        </div>
    </div>
    {pagination_links count=$consolidatedLogsCount step=$numrows offset=$offset}tiki-admin_schedulers.php?consolidated_logs=1{/pagination_links}
{/tab}
{/if}
{/tabset}

{jq}
    var selectedSchedulerTask = $('select[name="scheduler_task"]').val();
    $('div [data-task-name="'+selectedSchedulerTask+'"]').show();
    $('#console_doc').hide();

    $('select[name="scheduler_task"]').on('change', function() {
        var taskName = this.value;
        $('div [data-task-name]:not([data-task-name="'+taskName+'"])').hide();
        $('div [data-task-name="'+taskName+'"]').show();
        if (taskName == "ConsoleCommandTask") {
            $('#console_doc').show();
        } else {
            $('#console_doc').hide();
        }
    });

    $('form[name="RegForm"]').validate({
        rules: {
            scheduler_time: {
                validate_cron_runtime: true
            }
        }
    });

    $('#notificationUsersCollapse').on('show.bs.collapse', function() {
        $(this).prev().find('.fa-chevron-down').removeClass('fa-chevron-down').addClass('fa-chevron-up');
    });

    $('#notificationUsersCollapse').on('hide.bs.collapse', function() {
        $(this).prev().find('.fa-chevron-up').removeClass('fa-chevron-up').addClass('fa-chevron-down');
    });

    // Handle bulk action submission
    $('#bulk-action-submit').on('click', function(e) {
        var selectedAction = $('select[name="action"]').val();
        var checkedBoxes = $('input[name="checked[]"]:checked');

        if (!selectedAction || selectedAction === 'no_action') {
            e.preventDefault();
            alert('{tr}Please select an action first.{/tr}');
            return false;
        }

        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('{tr}Please select at least one scheduler.{/tr}');
            return false;
        }

        // Validation passed - let form submit
    });

    // Handle bulk job action submission
    $('#bulk-job-action-submit').on('click', function(e) {
        var selectedAction = $('select[name="job_action"]').val();
        var checkedBoxes = $('input[name="checked_jobs[]"]:checked');

        if (!selectedAction || selectedAction === 'no_action') {
            e.preventDefault();
            alert('{tr}Please select an action first.{/tr}');
            return false;
        }

        if (checkedBoxes.length === 0) {
            e.preventDefault();
            alert('{tr}Please select at least one job.{/tr}');
            return false;
        }

        // Validation passed - let form submit
    });
{/jq}
