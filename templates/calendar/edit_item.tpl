{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form action="{service controller='calendar' action='edit_item'}" method="post" class="edit-event-form">
        <div class="form-contents">
            <div class="preview d-none">
                <button type="button" class="btn-close" style="position:absolute;right:2rem;top:2rem;z-index:1;" aria-label="{tr}Close{/tr}"></button>
                {remarksbox title="{tr}Preview{/tr}" type='secondary' icon='edit' close='n'}{/remarksbox}
            </div>
            <div class="h5 my-3">
                {if $calitemId}
                    {tr}Edit Calendar Item{/tr}
                {else}
                    {tr}New Calendar Item{/tr}
                {/if}
            </div>
            <input type="hidden" name="calitem[user]" value="{$calitem.user|escape}">
            <input type="hidden" name="tzoffset" value="">
            <input type="hidden"  name="return_url" value="tiki-calendar.php">
            {if $calitemId}
                <input type="hidden" name="calitemId" value="{$calitemId|escape}">
            {/if}
            {if not empty($smarty.request.trackerItemId)}
                <input type="hidden" name="calitem[trackerItemId]" value="{$smarty.request.trackerItemId|escape}">
            {/if}
            <input type="hidden" name="modal" value="{$modal|escape}">
            {ticket}
            {if $prefs.calendar_addtogooglecal == 'y'}
                {wikiplugin _name="addtogooglecal" calitemid=$calitemId}{/wikiplugin}
            {/if}
            {if $prefilled}
            <input type="hidden" name="calitem[calendarId]" value="{$calitem.calendarId}">
            {else}
            <div class="mb-3 row">
                <label for="calid" class="col-form-label col-sm-3">{tr}Calendar{/tr}</label>
                <div class="col-sm-9">
                    <input name="calendarchanged" type="hidden">
                    <select name="calitem[calendarId]" id="calid" class="form-control" required
                            onchange="$(this).parents('.edit-event-form').tikiModal(tr('Loading...')); needToConfirm=false; $('input[name=calendarchanged]').val(1); $('input[name=saveitem]').trigger('click');">
                        {foreach $calendars as $aCalendar}
                            {$calstyle = ''}
                            {if not empty($aCalendar.custombgcolor)}
                                {$calstyle='background-color:#'|cat:$aCalendar.custombgcolor|cat:';'}
                            {/if}
                            {if not empty($aCalendar.customfgcolor)}
                                {$calstyle=$calstyle|cat:'color:#'|cat:$aCalendar.customfgcolor}
                            {/if}
                            {if $calstyle}
                                {$calstyle = ' style="'|cat:$calstyle|cat:'"'}
                            {/if}
                            <option value="{$aCalendar.calendarId}"{$calstyle}
                                    {if isset($calitem.calendarId)}
                                        {if $calitem.calendarId eq $aCalendar.calendarId}
                                            selected="selected"
                                        {/if}
                                    {elseif $calendarView}
                                        {if $calendarView eq $aCalendar.calendarId}
                                            selected="selected"
                                        {/if}
                                    {else}
                                        {if $calendarId}
                                            {if $calendarId eq $aCalendar.calendarId}
                                                selected="selected"
                                            {/if}
                                        {/if}
                                    {/if}
                            >
                                {$aCalendar.name|escape}
                            </option>
                        {/foreach}
                    </select>
                </div>
            </div>
            {/if}
            <div class="mb-3 row">
                <label class="col-form-label col-sm-3">{tr}Title{/tr}</label>
                <div class="col-sm-9">
                    <input type="text" name="calitem[name]" value="{$calitem.name|escape}" size="32" class="form-control" required>
                </div>
            </div>
            <div class="mb-3 row">
                <label class="col-form-label col-sm-3">{tr}Created by{/tr}</label>
                <div class="col-sm-9">
                    <div class="summary" style="margin-bottom: 0; padding-top: 7px;">
                        {$calitem.user|escape}
                    </div>

                </div>
            </div>
            {if $prefilled}
            <input type="hidden" name="calitem[end_or_duration]" value="end" id="end_or_duration">
            <input type="hidden" name="calitem[start]" value="{$calitem.start}">
            <input type="hidden" name="calitem[end]" value="{$calitem.end}">
            <input type="hidden" name="exact_start_end" value="1">
            <div class="row mt-md-3 mb-3 date">
                <label class="col-form-label col-sm-3">{tr}Start{/tr}</label>
                <div class="col-sm-7 start">
                    {$calitem.start|tiki_short_datetime} {$displayTimezone}
                </div>
            </div>
            <div class="row mt-md-3 mb-3 date">
                <label class="col-form-label col-sm-3">{tr}End{/tr}</label>
                <div class="col-sm-7 end">
                    {$calitem.end|tiki_short_datetime} {$displayTimezone}
                </div>
            </div>
            {else}
            <div class="mb-3 row">
                <label class="col-form-label col-sm-3">{tr}Recurrence{/tr}</label>
                <div class="col-sm-9">
                    {if $recurrence.id gt 0}
                        <input type="hidden" name="recurrent" value="1">
                        {tr}This event depends on a recurrence rule,{/tr}
                        {tr}starting on{/tr} {$recurrence.startPeriod|tiki_long_date},&nbsp;
                        {if $recurrence.endPeriod gt 0}
                            {tr}ending by{/tr} {$recurrence.endPeriod|tiki_long_date}
                        {else}
                            {tr}ending after{/tr} {$recurrence.nbRecurrences} {tr}events{/tr}
                        {/if}
                        {if $recurranceNumChangedEvents gt 1}
                            {tr _0=$recurranceNumChangedEvents}(%0 events have been manually modified){/tr}
                        {elseif $recurranceNumChangedEvents gt 0}
                            {tr _0=$recurranceNumChangedEvents}(%0 event has been manually modified){/tr}
                        {/if}
                        <br>
                    {else}
                        <div class="form-check">
                            <label class="form-check-label">
                                <input type="checkbox"  class="form-check-input" aria-label="{tr}Select{/tr}" id="id_recurrent" name="recurrent" value="1"{if $calitem.recurrenceId gt 0 or $recurrent eq 1} checked="checked" {/if}>
                                {tr}This event depends on a recurrence rule{/tr}
                            </label>
                        </div>
                    {/if}
                </div>
            </div>
            {* / .mb-3 *}
            {include file='./recurrence.tpl'}
            {* / .row *}
            <div id="timezonePicker" class="row mt-md-3 mb-3 date" style=" {if ( !($calitem.recurrenceId gt 0) and $recurrent neq 1 )} display:none; {/if}">
                <label class="col-form-label col-sm-3">{tr}Recurrence time zone{/tr}</label>
                {if $edit}
                    <div class="col-sm-9">
                        <select name="recurrenceDstTimezone" class="form-control">
                            {foreach from=$timezones key=k item=tz}
                                <option value="{$tz}" {if $recurrence.recurrenceDstTimezone && $recurrence.recurrenceDstTimezone eq $tz} selected="selected" {else}{if $displayTimezone eq $tz} selected="selected" {/if}{/if}>
                                    {$tz}
                                </option>
                            {/foreach}
                        </select>
                        <div class="form-text">
                            {tr}This timezone is used only for recurring events to determine the actual DST settings when creating future events.{/tr}
                        </div>
                    </div>
                {else}
                    <div class="col-sm-9">
                        {if ! empty($recurrence.recurrenceDstTimezone)}<span>{$recurrence.recurrenceDstTimezone}</span>{else}{$displayTimezone}{/if}
                    </div>
                {/if}
            </div>

            <div class="row mt-md-3 mb-3 date">
                <label class="col-form-label col-sm-3">{tr}Start{/tr}</label>
                <div class="col-sm-5 start">
                    {jscalendar id="start" date=$calitem.start fieldname="calitem[start]" showtime='y' isutc=1}
                </div>
                <div class="col-sm-2">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="calitem[allday]" id="allday" value="1" {if $calitem.allday} checked="checked"{/if}>
                        <label class="form-check-label" for="allday">
                            {tr}All day{/tr}
                        </label>
                    </div>
                </div>
            </div> {* / .mb-3 *}
            <div class="row mt-md-3 mb-3 date">
                <label class="col-form-label col-sm-3">{tr}End{/tr}</label>
                <input type="hidden" name="calitem[end_or_duration]" value="end" id="end_or_duration">
                <div class="col-sm-5 end ">
                    {jscalendar id="end" date=$calitem.end fieldname="calitem[end]" showtime='y' isutc=1}
                </div>
                <div class="col-sm-5 duration time" style="display:none;">
                    {html_select_time prefix="duration_" display_seconds=false time=$calitem.duration|default:'01:00' minute_interval=$prefs.calendar_minute_interval class='form-control date noselect2'}
                </div>
                <div class="col-sm-2 time">
                    <a href="#" id="durationBtn" class="btn btn-sm btn-secondary">
                        {tr}Show duration{/tr}
                    </a>
                </div>
                {if $impossibleDates}
                    <br>
                    <span style="color:#900;">
                        {tr}Events cannot end before they start{/tr}
                    </span>
                {/if}
            </div> {* / .mb-3 *}
            {/if}
            <div class="mb-3 row">
                <label class="col-form-label col-sm-3">{tr}Description{/tr}</label>
                <div class="col-sm-9">
                        {strip}
                            {textarea name="calitem[description]" id="editwiki" cols=40 rows=10 _preview=$prefs.ajax_edit_previews}
                            {$calitem.description}
                            {/textarea}
                        {/strip}
                </div>
            </div>
            {if $calendar.customstatus eq 'y'}
                <div class="mb-3 row">
                    <label class="col-form-label col-sm-3">{tr}Status{/tr}</label>
                    <div class="col-sm-9">
                        <div class="statusbox {if $calitem.status eq 0}status0{/if}">
                            <input id="status0" type="radio" name="calitem[status]" value="0"
                                    {if (!empty($calitem) and $calitem.status eq 0) or (empty($calitem) and $calendar.defaulteventstatus eq 0)}
                                        checked="checked"
                                    {/if}
                            >
                            <label for="status0">
                                {tr}Tentative{/tr}
                            </label>
                        </div>
                        <div class="statusbox    {if $calitem.status eq 1}status1{/if}">
                            <input id="status1" type="radio" name="calitem[status]" value="1" {if $calitem.status eq 1} checked="checked" {/if} >
                            <label for="status1">
                                {tr}Confirmed{/tr}
                            </label>
                        </div>
                        <div class="statusbox {if $calitem.status eq 2}status2{/if}">
                            <input id="status2" type="radio" name="calitem[status]" value="2" {if $calitem.status eq 2} checked="checked" {/if}>
                            <label for="status2">
                                {tr}Cancelled{/tr}
                            </label>
                        </div>
                    </div>
                </div>
                {* / .mb-3 *}
            {/if}
            {if $calendar.custompriorities eq 'y'}
                <div class="mb-3 row clearfix">
                    <label class="col-form-label col-sm-3">{tr}Priority{/tr}</label>
                    <div class="col-sm-2">
                        <select name="calitem[priority]" style="background-color:#{$customPriorityColors[$calitem.priority]};" onchange="this.style.bacgroundColor='#'+this.selectedIndex.value;" class="form-control">
                            {foreach $customPriorities as $priority}
                                <option value="{$priority}" style="background-color:#{$customPriorityColors[$priority]};" {if $calitem.priority eq $priority} selected="selected" {/if}>
                                    {$priority}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                {* / .mb-3 *}
            {/if}
            {if $calendar.customcategories eq 'y'}
                <div class="mb-3 row" id="calcat">
                    <label class="col-form-label col-sm-3">
                        {tr}Classification{/tr}
                    </label>
                    <div class="col-sm-9">
                        <select name="calitem[categoryId]" class="form-control">
                            <option value=""></option>
                            {foreach $customCategories as $categ}
                                <option value="{$categ.categoryId}" {if $calitem.categoryId eq $categ.categoryId} selected="selected" {/if}>
                                    {$categ.name|escape}
                                </option>
                            {/foreach}
                        </select>
                        <p class="text-center"><label>{tr}OR CREATE A NEW CLASSIFICATION{/tr}</label></p>
                        <div class="w-100">
                            <div class="input-group my-2">
                                <span class="input-group-text">{tr}New classification:{/tr}</span>
                                <input type="text" class="form-control" name="calitem[newcat]">
                            </div>
                            <div class="input-group my-2 d-flex">
                                <span class="input-group-text">{tr}Background Color{/tr}</span>
                                <input value="{$calendar.custombgcolor}" type="color" class="form-control form-control-color" name="calitem[newcatbgcolor]" id="newcatbgcolor">
                            </div>
                        </div>
                    </div>
                </div> {* / .mb-3 *}
            {/if}
            {if $calendar.customlocations eq 'y'}
                <div class="mb-3 row" id="calloc">
                    <label class="col-form-label col-sm-3">{tr}Location{/tr}</label>
                    <div class="col-sm-9">
                        <select name="calitem[locationId]" class="form-control">
                            <option value="">{tr}No location selected yet, please add one{/tr}</option>
                            {foreach $customLocations as $location}
                                <option value="{$location.locationId}" {if $calitem.locationId eq $location.locationId} selected="selected" {/if}>
                                    {$location.name|escape}
                                </option>
                            {/foreach}
                        </select>
                        <div class="input-group my-2">
                          <span class="input-group-text">{tr}New location:{/tr}</span>
                          <input type="text" class="form-control" name="calitem[newloc]">
                        </div>
                    </div>
                </div> {* / .mb-3.row *}
            {/if}
            {if $calendar.customurl ne 'n'}
                <div class="mb-3 row">
                    <label class="col-form-label col-sm-3">{tr}URL{/tr}</label>
                    <div class="col-sm-9">
                        <input type="text" name="calitem[url]" value="{$calitem.url}" size="32" class="form-control url">
                    </div>
                </div>
                {* / .mb-3.row *}
            {/if}
            {if $calendar.customlanguages eq 'y'}
                <div class="mb-3 row" id="callang">
                    <label class="col-form-label col-sm-3">{tr}Language{/tr}</label>
                    <div class="col-sm-9">
                        <select name="calitem[lang]" class="form-control">
                            <option value=""></option>
                            {foreach $customLanguages as $language}
                                <option value="{$language.value}" {if $calitem.lang eq $language.value} selected="selected" {/if}>
                                    {$language.name}
                                </option>
                            {/foreach}
                        </select>
                    </div>
                </div> {* / .mb-3.row *}
            {/if}
            {if !empty($groupforalert) && $showeachuser eq 'y'}
                <div class="mb-3 row">
                    <label class="col-form-label col-sm-3" for="listtoalert">{tr}Choose users to alert{/tr}</label>
                    <div class="col-sm-9">
                        {section name=idx loop=$listusertoalert}
                            {if $showeachuser eq 'n'}
                                <input type="hidden" name="listtoalert[]" value="{$listusertoalert[idx].user}">
                            {else}
                                <input type="checkbox" class="form-check-input" id="listtoalert" name="listtoalert[]" value="{$listusertoalert[idx].user}">
                                {$listusertoalert[idx].user}
                            {/if}
                        {/section}
                    </div>
                </div>
                {* / .mb-3.row *}
            {/if}
            {if $calendar.customparticipants eq 'y'}
                <div class="mb-3 row" id="calorg">
                    <label class="col-form-label col-sm-3">{tr}Organized by{/tr}</label>
                    <div class="col-sm-9">
                        {user_selector name='calitem[organizers]' select=$calitem.organizers multiple='true' allowNone='y' editable='y' realnames = 'n'}
                    </div>
                </div> {* / .mb-3.row *}
            {/if}
            {if $calendar.customparticipants eq 'y'}
                <div class="mb-3 row" id="calpart">
                    <label class="col-form-label col-sm-3">{tr}Participants{/tr}</label>
                    <div class="col-sm-9">
                        {if isset($calitem.participants)}
                            {user_selector name='participants' select=$calitem.selected_participants multiple='true' allowNone='y' editable='y' realnames='n'}
                            <br>
                            <div class="row">
                                <div class="col-sm-9">
                                    <input type="text" name="add_participant_email" id="add_participant_email" value="" placeholder="or invite by email address..." class="form-control">
                                </div>
                                <div class="col-sm-3">
                                    <input type="button" class="btn btn-primary btn-sm" value="Add" id="invite_emails">
                                </div>
                            </div>
                            <br>
                            <div class="table-responsive">
                                <table class="table normal table-bordered" id="participant_roles">
                                    <tr>
                                        <th>{tr}Invitee{/tr}</th>
                                        <th>{tr}Status{/tr}</th>
                                        <th>{tr}Role{/tr}</th>
                                        <th></th>
                                    </tr>
                                    <tr class="d-none" id="participant-template-row">
                                        <td class="username"></td>
                                        <td>
                                            <select class="form-control noselect2" name="calitem[participant_partstat]">
                                                <option value="NEEDS-ACTION">{tr}NEEDS-ACTION{/tr}</option>
                                                <option value="ACCEPTED">{tr}ACCEPTED{/tr}</option>
                                                <option value="TENTATIVE">{tr}TENTATIVE{/tr}</option>
                                                <option value="DECLINED">{tr}DECLINED{/tr}</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control noselect2" name="calitem[participant_roles]">
                                                <option value="0">{tr}Chair{/tr}</option>
                                                <option value="1">{tr}Required participant{/tr}</option>
                                                <option value="2">{tr}Optional participant{/tr}</option>
                                                <option value="3">{tr}Non-participant{/tr}</option>
                                            </select>
                                        </td>
                                        <td>
                                            {icon name='delete' iclass='text-danger delete-participant'}
                                        </td>
                                    </tr>
                                    {foreach item=ppl from=$calitem.participants}
                                        <tr data-user="{$ppl.username|escape}" class="{$ppl.username|escape}">
                                            <td>{$ppl.username|userlink}</td>
                                            <td>
                                                <select name="calitem[participant_partstat][{$ppl.username}]" class="form-control">
                                                    <option value="NEEDS-ACTION">NEEDS-ACTION</option>
                                                    <option value="ACCEPTED" {if $ppl.partstat eq 'ACCEPTED'}selected{/if}>ACCEPTED</option>
                                                    <option value="TENTATIVE" {if $ppl.partstat eq 'TENTATIVE'}selected{/if}>TENTATIVE</option>
                                                    <option value="DECLINED" {if $ppl.partstat eq 'DECLINED'}selected{/if}>DECLINED</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="calitem[participant_roles][{$ppl.username}]" class="form-control">
                                                    <option value="0">{tr}chair{/tr}</option>
                                                    <option value="1" {if $ppl.role eq '1'}selected{/if}>{tr}required participant{/tr}</option>
                                                    <option value="2" {if $ppl.role eq '2'}selected{/if}>{tr}optional participant{/tr}</option>
                                                    <option value="3" {if $ppl.role eq '3'}selected{/if}>{tr}non-participant{/tr}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <a href="#" class="delete-participant"><span class="icon icon-remove fas fa-times"></span></a>
                                            </td>
                                        </tr>
                                    {/foreach}
                                </table>
                            </div>
                            <div><a href="#" class="btn btn-secondary btn-sm availability-check">{tr}Check availability{/tr}</a></div>
                            <br/>
                            <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="calitem[process_itip]" value="1" checked>
                            Send calendar invitations and event updates via email
                        {/if}
                    </div>
                </div> {* / .mb-3.row *}
            {/if}
            {if $recurrence.id gt 0}
                <div class="row">
                    <div class="col-sm-9 offset-sm-3">
                        <input type="radio" id="id_affectEvt" name="affect" value="event" checked="checked"> <label for="id_affectEvt">
                            {tr}Update this event only{/tr}
                        </label><br>
                        {if $recurranceNumChangedEvents}
                            <input type="radio" id="id_affectMan" name="affect" value="manually">
                            <label for="id_affectMan">
                                {tr}Update every unchanged event in this recurrence series{/tr}
                            </label>
                            <br>
                        {/if}
                        <input type="radio" id="id_affectAll" name="affect" value="all"> <label for="id_affectAll">
                            {tr}Update every event in this recurrence series{/tr}
                        </label>
                    </div>
                </div>
            {/if}
            {if !$user and $prefs.feature_antibot eq 'y'}
                {include file='antibot.tpl'}
            {/if}
            {js_insert_icon type="jscalendar"}
        </div> {* /.form-contents *}
        <div class="submit">
            <input type="hidden" id="act" name="act" value="">
            <input type="submit" class="btn btn-secondary" name="preview" value="{tr}Preview{/tr}" onclick="needToConfirm=false">
            <input type="submit" class="btn btn-primary {if $prefilled}need-participant{/if}" name="saveitem" value="{tr}Save{/tr}" onclick="needToConfirm=false">
            {if $tiki_p_add_events eq 'y' and empty($saveas) and not empty($calitemId)}
                <input type="submit" class="btn btn-secondary" name="saveas" data-alt_controller="calendar" data-alt_action="copy_item"
                       onclick="needToConfirm=false" value="{tr}Copy to a new event{/tr}">
            {/if}
            {if $calitemId && ! $recurrence.id}
                <input type="submit" name="delete" data-alt_controller="calendar" data-alt_action="delete_item"
                       class="btn btn-danger" onclick="needToConfirm=false;" value="{tr}Delete event{/tr}">
            {/if}
            {if $recurrence.id}
                <div class="dropdown">
                    <button class="btn btn-danger dropdown-toggle" type="button" id="deleteMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                        {tr}Delete event(s){/tr}
                    </button>
                    <ul class="dropdown-menu">
                        <li><button type="submit" data-alt_controller="calendar" data-alt_action="delete_item" class="dropdown-item" data-confirm="{tr}Are you sure you want to delete this event?{/tr}">{tr}This event only{/tr}</button></li>
                        <li><button type="submit" data-alt_controller="calendar" data-alt_action="delete_recurrent_items" class="dropdown-item" data-confirm="{tr}Are you sure you want to delete recurring event series for all future events?{/tr}">{tr}Future recurring events{/tr}</button></li>
                        <li><button type="submit" data-alt_controller="calendar" data-alt_action="delete_recurrent_items" data-alt_param="all" data-alt_param_value="1" class="dropdown-item" data-confirm="{tr}Are you sure you want to delete all recurring events in this series?{/tr}">{tr}All recurring events{/tr}</button></li>
                    </ul>
                </div>
            {/if}
            {if $prefs.calendar_export_item == 'y' and not empty($calitemId)}
                {button href='tiki-calendar_export_ical.php? export=y&calendarItem='|cat:$calitemId _text="{tr}Export Event as iCal{/tr}"}
            {/if}
        </div>
    </form>
{/block}
