{extends 'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form action="{service controller='calendar' action='edit_item'}" method="post" class="edit-event-form">
        <div class="form-contents">
            <div class="preview d-none">
                {remarksbox title="{tr}Preview{/tr}" type='secondary' icon='edit'}{/remarksbox}
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
            <div class="mb-3 row">
                <label for="calid" class="col-form-label col-sm-3">{tr}Calendar{/tr}</label>
                <div class="col-sm-9">
                    <input name="calendarchanged" type="hidden">
                    <select name="calitem[calendarId]" id="calid" class="form-control" required
                            onchange="$(this).parents('.edit-event-form').tikiModal(tr('Loading...')); needToConfirm=false; $('input[name=calendarchanged]').val(1); $('input[name=saveitem]').click();">
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
                                <input type="checkbox" class="form-check-input" id="id_recurrent" name="recurrent" value="1"{if $calitem.recurrenceId gt 0 or $recurrent eq 1} checked="checked" {/if}>
                                {tr}This event depends on a recurrence rule{/tr}
                            </label>
                        </div>
                    {/if}
                </div>
            </div>
            {* / .mb-3 *}
            <div class="row">
                <div class="col-sm-9 offset-sm-3">
                    <div id="recurrenceRules" style=" {if ( !($calitem.recurrenceId gt 0) and $recurrent neq 1 )} display:none; {/if}">
                        {if $calitem.recurrenceId gt 0}
                            <input type="hidden" name="recurrenceId" value="{$recurrence.id}">
                        {/if}
                         {if $recurrence.id gt 0}
                            {if $recurrence.daily}
                                <input type="hidden" name="recurrenceType" value="daily">
                                {tr}On a daily basis{/tr}
                                <br>
                            {/if}
                        {else}
                            <input type="radio" id="id_recurrenceTypeD" name="recurrenceType" value="daily" {if $recurrence.daily or $recurrence.id eq 0} checked="checked" {/if} >
                            <label for="id_recurrenceTypeD">
                                {tr}On a daily basis{/tr}
                            </label>
                        {/if}
                        {if $recurrence.id eq 0 or $recurrence.daily}
                            <div class="mb-3 px-5">
                                <div class="input-group">
                                    <span class="input-group-text">{tr}Every{/tr}</span>
                                    <select name="days" class="form-control">
                                        {for $i=1 to 31}
                                            <option value="{$i}"{if $recurrence.days == $i} selected="selected" {/if}>
                                                {$i}
                                            </option>
                                        {/for}
                                    </select>
                                    <span class="input-group-text">{tr}day(s){/tr}</span>
                                </div>
                                <hr/>
                            </div>
                        {/if}
                        {if $recurrence.id gt 0}
                            {if $recurrence.weekly}
                                <input type="hidden" name="recurrenceType" value="weekly">
                                {tr}On a weekly basis{/tr}
                                <br>
                            {/if}
                        {else}
                            <input type="radio" id="id_recurrenceTypeW" name="recurrenceType" value="weekly" {if $recurrence.weekly} checked="checked" {/if} >
                            <label for="id_recurrenceTypeW">
                                {tr}On a weekly basis{/tr}
                            </label>
                        {/if}
                        {if $recurrence.id eq 0 or $recurrence.weekly}
                            <div class="mb-3 px-5">
                                <div class="input-group">
                                    <span class="input-group-text">{tr}Every{/tr}</span>
                                    <select name="weeks" class="form-control">
                                        {for $i=1 to 52}
                                            <option value="{$i}"{if $recurrence.weeks == $i} selected="selected" {/if}>
                                                {$i}
                                            </option>
                                        {/for}
                                    </select>
                                    <span class="input-group-text">{tr}week(s){/tr}</span>
                                </div>
                                <hr/>
                            </div>
                            <div class="mb-3 px-5">
                                <div class="input-group">
                                    <span class="input-group-text">{tr}Each{/tr}</span>
                                    <select name="weekdays[]" class="form-control" multiple>
                                        {foreach $daynames as $abbr => $dayname}
                                            <option value="{$abbr}"{if in_array($abbr, $recurrence.weekdays)} selected="selected" {/if}>
                                                {$dayname}
                                            </option>
                                        {/foreach}
                                    </select>
                                    <span class="input-group-text">{tr}of the week{/tr}</span>
                                </div>
                                <hr/>
                            </div>
                        {/if}
                        {if $recurrence.id gt 0}
                            {if $recurrence.monthly}
                                <input type="hidden" name="recurrenceType" value="monthly">
                                {tr}On a monthly basis{/tr}
                                <br>
                            {/if}
                        {else}
                            <input type="radio" id="id_recurrenceTypeM" name="recurrenceType" value="monthly" {if $recurrence.monthly} checked="checked" {/if} >
                            <label for="id_recurrenceTypeM">
                                {tr}On a monthly basis{/tr}
                            </label>
                        {/if}
                        {if $recurrence.id eq 0 or $recurrence.monthly}
                            <div class="mb-3 px-5">
                                <div class="input-group">
                                    <span class="input-group-text">{tr}Every{/tr}</span>
                                    <select name="months" class="form-control">
                                        {for $i=1 to 36}
                                            <option value="{$i}"{if $recurrence.months == $i} selected="selected" {/if}>
                                                {$i}
                                            </option>
                                        {/for}
                                    </select>
                                    <span class="input-group-text">{tr}month(s){/tr}</span>
                                </div>
                                <hr/>
                            </div>
                            <div class="mb-3 px-5">
                                {if $recurrence.id neq 0}<input type="hidden" name="recurrenceTypeMonthy" value="{$recurrence.monthlyType}">{/if}
                                {if $recurrence.id eq 0 or $recurrence.monthlyType eq 'date'}
                                    <div class="input-group">
                                        {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" checked="checked" name="recurrenceTypeMonthy" value="date"></span>{/if}
                                        <span class="input-group-text">{tr}Each{/tr}</span>
                                        <select name="dayOfMonth[]" class="form-control" multiple>
                                            {for $k = 1 to 31}
                                                <option value="{$k}" {if in_array($k, $recurrence.dayOfMonth)} selected="selected" {/if} >
                                                    {if $k lt 10}0{/if}{$k}
                                                </option>
                                            {/for}
                                        </select>
                                        <span class="input-group-text">{tr}of the month{/tr}</span>
                                    </div>
                                {/if}
                                {if $recurrence.id eq 0}
                                    <div class="text-center py-2"><span>{tr}OR{/tr}</span></div>
                                {/if}
                                {if $recurrence.id eq 0 or $recurrence.monthlyType eq 'weekday'}
                                    <div class="input-group">
                                        {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" name="recurrenceTypeMonthy" value="weekday"></span>{/if}
                                        <span class="input-group-text">{tr}Every{/tr}</span>
                                        <select name="monthlyWeekNumber" class="form-control" {if $recurrence.id neq 0}disabled{/if}>
                                            <option value="1" {if $recurrence.monthlyWeekdayValue[0] eq '1'} selected="selected" {/if}>
                                                {tr}First{/tr}
                                            </option>
                                            <option value="2" {if $recurrence.monthlyWeekdayValue[0] eq '2'} selected="selected" {/if}>
                                                {tr}Second{/tr}
                                            </option>
                                            <option value="3" {if $recurrence.monthlyWeekdayValue[0] eq '3'} selected="selected" {/if}>
                                                {tr}Third{/tr}
                                            </option>
                                            <option value="4" {if $recurrence.monthlyWeekdayValue[0] eq '4'} selected="selected" {/if}>
                                                {tr}Fourth{/tr}
                                            </option>
                                            <option value="5" {if $recurrence.monthlyWeekdayValue[0] eq '5'} selected="selected" {/if}>
                                                {tr}Fifth{/tr}
                                            </option>
                                            <option value="-1" {if strpos($recurrence.monthlyWeekdayValue, '-1') eq true} selected="selected" {/if}>
                                                {tr}Last{/tr}
                                            </option>
                                        </select>
                                        <select name="monthlyWeekday" class="form-control" {if $recurrence.id neq 0}disabled{/if}>
                                            <option value="SU" {if strpos($recurrence.monthlyWeekdayValue, 'SU') eq true} selected="selected" {/if}>
                                                {tr}Sunday{/tr}
                                            </option>
                                            <option value="MO" {if strpos($recurrence.monthlyWeekdayValue, 'MO') eq true} selected="selected" {/if}>
                                                {tr}Monday{/tr}
                                            </option>
                                            <option value="TU" {if strpos($recurrence.monthlyWeekdayValue, 'TU') eq true} selected="selected" {/if}>
                                                {tr}Tuesday{/tr}
                                            </option>
                                            <option value="WE" {if strpos($recurrence.monthlyWeekdayValue, 'WE') eq true} selected="selected" {/if}>
                                                {tr}Wednesday{/tr}
                                            </option>
                                            <option value="TH" {if strpos($recurrence.monthlyWeekdayValue, 'TH') eq true} selected="selected" {/if}>
                                                {tr}Thursday{/tr}
                                            </option>
                                            <option value="FR" {if strpos($recurrence.monthlyWeekdayValue, 'FR') eq true} selected="selected" {/if}>
                                                {tr}Friday{/tr}
                                            </option>
                                            <option value="SA" {if strpos($recurrence.monthlyWeekdayValue, 'SA') eq true} selected="selected" {/if}>
                                                {tr}Saturday{/tr}
                                            </option>
                                        </select>
                                        <span class="input-group-text">{tr}of the month{/tr}</span>
                                    </div>
                                {/if}
                                <hr/>
                            </div>
                        {/if}
                        {if $recurrence.id gt 0}
                            {if $recurrence.yearly}
                                <input type="hidden" name="recurrenceType" value="yearly">
                                {tr}On a yearly basis{/tr}
                                <br>
                            {/if}
                        {else}
                            {* new recurrences default to yearly for now *}
                            <input type="radio" id="id_recurrenceTypeY" name="recurrenceType" value="yearly">
                            <label for="id_recurrenceTypeY">
                                {tr}On a yearly basis{/tr}
                            </label>
                            <br>
                        {/if}
                        {if $recurrence.id eq 0 or $recurrence.yearly}
                            <div class="mb-3 px-5">
                                <div class="input-group">
                                    <span class="input-group-text">{tr}Every{/tr}</span>
                                    <select name="years" class="form-control">
                                        {for $i=1 to 20}
                                            <option value="{$i}"{if $recurrence.years == $i} selected="selected" {/if}>
                                                {$i}
                                            </option>
                                        {/for}
                                    </select>
                                    <span class="input-group-text">{tr}year(s){/tr}</span>
                                </div>
                                <hr/>
                            </div>
                            <div class="mb-3 px-5">
                                {if $recurrence.id neq 0}<input type="hidden" name="recurrenceTypeYearly" value="{$recurrence.yearlyType}">{/if}
                                {if $recurrence.id eq 0 or $recurrence.yearlyType eq 'date'}
                                    <div class="input-group">
                                        {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" checked="checked" name="recurrenceTypeYearly" value="date"></span>{/if}
                                        <span class="input-group-text">{tr}Each{/tr}</span>
                                        <select name="yearlyDay" class="form-control" onChange="checkDateOfYear(this.options[this.selectedIndex].value,document.forms['f'].elements['yearlyMonth'].options[document.forms['f'].elements['yearlyMonth'].selectedIndex].value);">
                                            {section name=k start=1 loop=31}
                                                <option value="{$smarty.section.k.index}" {if $recurrence.yearlyDay eq $smarty.section.k.index} selected="selected" {/if} >
                                                    {if $smarty.section.k.index lt 10}
                                                        0
                                                    {/if}
                                                    {$smarty.section.k.index}
                                                </option>
                                            {/section}
                                        </select>
                                        <span class="input-group-text">{tr}of{/tr}</span>
                                        <select name="yearlyMonth" class="form-control" onChange="checkDateOfYear(document.forms['f'].elements['yearlyDay'].options[document.forms['f'].elements['yearlyDay'].selectedIndex].value,this.options[this.selectedIndex].value);">
                                            <option value="1" {if $recurrence.yearlyMonth eq '1'} selected="selected" {/if}>
                                                {tr}January{/tr}
                                            </option>
                                            <option value="2" {if $recurrence.yearlyMonth eq '2'} selected="selected" {/if}>
                                                {tr}February{/tr}
                                            </option>
                                            <option value="3" {if $recurrence.yearlyMonth eq '3'} selected="selected" {/if}>
                                                {tr}March{/tr}
                                            </option>
                                            <option value="4" {if $recurrence.yearlyMonth eq '4'} selected="selected" {/if}>
                                                {tr}April{/tr}
                                            </option>
                                            <option value="5" {if $recurrence.yearlyMonth eq '5'} selected="selected" {/if}>
                                                {tr}May{/tr}
                                            </option>
                                            <option value="6" {if $recurrence.yearlyMonth eq '6'} selected="selected" {/if}>
                                                {tr}June{/tr}
                                            </option>
                                            <option value="7" {if $recurrence.yearlyMonth eq '7'} selected="selected" {/if}>
                                                {tr}July{/tr}
                                            </option>
                                            <option value="8" {if $recurrence.yearlyMonth eq '8'} selected="selected" {/if}>
                                                {tr}August{/tr}
                                            </option>
                                            <option value="9" {if $recurrence.yearlyMonth eq '9'} selected="selected" {/if}>
                                                {tr}September{/tr}
                                            </option>
                                            <option value="10" {if $recurrence.yearlyMonth eq '10'} selected="selected" {/if}>
                                                {tr}October{/tr}</option>
                                            <option value="11" {if $recurrence.yearlyMonth eq '11'} selected="selected" {/if}>
                                                {tr}November{/tr}
                                            </option>
                                            <option value="12" {if $recurrence.yearlyMonth eq '12'} selected="selected" {/if}>
                                                {tr}December{/tr}
                                            </option>
                                        </select>
                                    </div>
                                {/if}
                                {if $recurrence.id eq 0}
                                    <div class="text-center py-2"><span>{tr}OR{/tr}</span></div>
                                {/if}
                                {if $recurrence.id eq 0 or $recurrence.yearlyType eq 'weekday'}
                                    <div class="input-group">
                                        {if $recurrence.id eq 0}<span class="input-group-text"><input type="radio" name="recurrenceTypeYearly" value="weekday"></span>{/if}
                                        <span class="input-group-text">{tr}Every{/tr}</span>
                                        <select name="yearlyWeekNumber" class="form-control" {if $recurrence.id neq 0}disabled{/if}>
                                            <option value="1" {if $recurrence.yearlyWeekdayValue[0] eq '1'} selected="selected" {/if}>
                                                {tr}First{/tr}
                                            </option>
                                            <option value="2" {if $recurrence.yearlyWeekdayValue[0] eq '2'} selected="selected" {/if}>
                                                {tr}Second{/tr}
                                            </option>
                                            <option value="3" {if $recurrence.yearlyWeekdayValue[0] eq '3'} selected="selected" {/if}>
                                                {tr}Third{/tr}
                                            </option>
                                            <option value="4" {if $recurrence.yearlyWeekdayValue[0] eq '4'} selected="selected" {/if}>
                                                {tr}Fourth{/tr}
                                            </option>
                                            <option value="5" {if $recurrence.yearlyWeekdayValue[0] eq '5'} selected="selected" {/if}>
                                                {tr}Fifth{/tr}
                                            </option>
                                            <option value="-1" {if strpos($recurrence.yearlyWeekdayValue, '-1') eq true} selected="selected" {/if}>
                                                {tr}Last{/tr}
                                            </option>
                                        </select>
                                        <select name="yearlyWeekday" class="form-control" {if $recurrence.id neq 0}disabled{/if}>
                                            <option value="SU" {if strpos($recurrence.yearlyWeekdayValue, 'SU') eq true} selected="selected" {/if}>
                                                {tr}Sunday{/tr}
                                            </option>
                                            <option value="MO" {if strpos($recurrence.yearlyWeekdayValue, 'MO') eq true} selected="selected" {/if}>
                                                {tr}Monday{/tr}
                                            </option>
                                            <option value="TU" {if strpos($recurrence.yearlyWeekdayValue, 'TU') eq true} selected="selected" {/if}>
                                                {tr}Tuesday{/tr}
                                            </option>
                                            <option value="WE" {if strpos($recurrence.yearlyWeekdayValue, 'WE') eq true} selected="selected" {/if}>
                                                {tr}Wednesday{/tr}
                                            </option>
                                            <option value="TH" {if strpos($recurrence.yearlyWeekdayValue, 'TH') eq true} selected="selected" {/if}>
                                                {tr}Thursday{/tr}
                                            </option>
                                            <option value="FR" {if strpos($recurrence.yearlyWeekdayValue, 'FR') eq true} selected="selected" {/if}>
                                                {tr}Friday{/tr}
                                            </option>
                                            <option value="SA" {if strpos($recurrence.yearlyWeekdayValue, 'SA') eq true} selected="selected" {/if}>
                                                {tr}Saturday{/tr}
                                            </option>
                                        </select>
                                        <span class="input-group-text">{tr}of{/tr}</span>
                                        <select name="yearlyWeekMonth" class="form-control">
                                            <option value="1" {if $recurrence.yearlyWeekMonth eq '1'} selected="selected" {/if}>
                                                {tr}January{/tr}
                                            </option>
                                            <option value="2" {if $recurrence.yearlyWeekMonth eq '2'} selected="selected" {/if}>
                                                {tr}February{/tr}
                                            </option>
                                            <option value="3" {if $recurrence.yearlyWeekMonth eq '3'} selected="selected" {/if}>
                                                {tr}March{/tr}
                                            </option>
                                            <option value="4" {if $recurrence.yearlyWeekMonth eq '4'} selected="selected" {/if}>
                                                {tr}April{/tr}
                                            </option>
                                            <option value="5" {if $recurrence.yearlyWeekMonth eq '5'} selected="selected" {/if}>
                                                {tr}May{/tr}
                                            </option>
                                            <option value="6" {if $recurrence.yearlyWeekMonth eq '6'} selected="selected" {/if}>
                                                {tr}June{/tr}
                                            </option>
                                            <option value="7" {if $recurrence.yearlyWeekMonth eq '7'} selected="selected" {/if}>
                                                {tr}July{/tr}
                                            </option>
                                            <option value="8" {if $recurrence.yearlyWeekMonth eq '8'} selected="selected" {/if}>
                                                {tr}August{/tr}
                                            </option>
                                            <option value="9" {if $recurrence.yearlyWeekMonth eq '9'} selected="selected" {/if}>
                                                {tr}September{/tr}
                                            </option>
                                            <option value="10" {if $recurrence.yearlyWeekMonth eq '10'} selected="selected" {/if}>
                                                {tr}October{/tr}</option>
                                            <option value="11" {if $recurrence.yearlyWeekMonth eq '11'} selected="selected" {/if}>
                                                {tr}November{/tr}
                                            </option>
                                            <option value="12" {if $recurrence.yearlyWeekMonth eq '12'} selected="selected" {/if}>
                                                {tr}December{/tr}
                                            </option>
                                        </select>
                                    </div>
                                {/if}
                            </div>
                            <div id="errorDateOfYear" class="text-danger offset-sm-1"></div>
                            <hr>
                        {/if}
                        {if $recurrence.id gt 0}
                            <input type="hidden" name="startPeriod" value="{$recurrence.startPeriod}">
                            <input type="hidden" name="nbRecurrences" value="{$recurrence.nbRecurrences}">
                            <input type="hidden" name="endPeriod" value="{$recurrence.endPeriod}">
                            {tr}Starting on{/tr} {$recurrence.startPeriod|tiki_long_date},&nbsp;
                            {if $recurrence.endPeriod gt 0}
                                {tr}ending by{/tr} {$recurrence.endPeriod|tiki_long_date}
                            {else}
                                {tr}ending after{/tr} {$recurrence.nbRecurrences} {tr}events{/tr}
                            {/if}.
                        {else}
                            {tr}Start date{/tr}
                            <div class="offset-sm-1 col-sm-6 input-group">
                                {if empty($recurrence.startPeriod)}{$startPeriod = $calitem.start}{else}{$startPeriod = $recurrence.startPeriod}{/if}
                                {jscalendar id="startPeriod" date=$startPeriod fieldname="startPeriod" align="Bc" showtime='n'}
                            </div>
                            <hr/>
                            <input type="radio" id="id_endTypeNb" name="endType" value="nb" {if $recurrence.nbRecurrences or $calitem.calitemId eq 0 or empty($recurrence.id)} checked="checked" {/if}>
                            <label for="id_endTypeNb"> &nbsp;{tr}End after{/tr}
                            </label>
                            <div class="offset-sm-1 col-sm-6">
                                <div class="input-group">
                                    <input type="number" min="1" name="nbRecurrences" class="form-control" value="{if $recurrence.nbRecurrences gt 0}{$recurrence.nbRecurrences}{else}1{/if}">
                                    <span class="input-group-text">
                                        {if $recurrence.nbRecurrences gt 1}{tr}occurrences{/tr}{else}{tr}occurrence{/tr}{/if}
                                    </span>
                                </div>
                            </div>
                            <br>
                            <input type="radio" id="id_endTypeDt" name="endType" value="dt" {if $recurrence.endPeriod gt 0} checked="checked" {/if}>
                            <label for="id_endTypeDt"> &nbsp;{tr}End before{/tr}
                            </label>
                            <div class="offset-sm-1 col-sm-6 input-group">
                                {jscalendar id="endPeriod" date=$recurrence.endPeriod fieldname="endPeriod" align="Bc" showtime='n'}
                            </div>
                        {/if}
                    </div>
                </div>
            </div>
            {* / .row *}
            <div id="timezonePicker" class="row mt-md-3 mb-3 date" style=" {if ( !($calitem.recurrenceId gt 0) and $recurrent neq 1 )} display:none; {/if}">
                <label class="col-form-label col-sm-3">{tr}Time zone{/tr}</label>
                {if $edit}
                    <div class="col-sm-5">
                        <select name="recurrenceDstTimezone" class="form-control" onChange="changeItemTimezone(this.options[this.selectedIndex].value);">
                            {foreach from=$timezones key=k item=tz}
                                <option value="{$tz}" {if $recurrence.recurrenceDstTimezone && $recurrence.recurrenceDstTimezone eq $tz} selected="selected" {else}{if $displayTimezone eq $tz} selected="selected" {/if}{/if}>
                                    {$tz}
                                </option>
                            {/foreach}
                        </select>
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
                        <label class="form-check-label">
                            <input type="checkbox" class="form-check-input" name="calitem[allday]" id="allday" value="true" {if $calitem.allday} checked="checked"{/if}>
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
            <div class="mb-3 row">
                <label class="col-form-label col-sm-3">{tr}Description{/tr}</label>
                <div class="col-sm-9">
                        {strip}
                            {textarea name="calitem[description]" id="editwiki" cols=40 rows=10}
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
                        <div class="input-group my-2">
                          <span class="input-group-text">{tr}New classification:{/tr}</span>
                          <input type="text" class="form-control" name="calitem[newcat]">
                        </div>
                    </div>
                </div> {* / .mb-3 *}
            {/if}
            {if $calendar.customlocations eq 'y'}
                <div class="mb-3 row" id="calloc">
                    <label class="col-form-label col-sm-3">{tr}Location{/tr}</label>
                    <div class="col-sm-9">
                        <select name="calitem[locationId]" class="form-control">
                            <option value=""></option>
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
                    <label class="col-form-label col-sm-3">{tr}Choose users to alert{/tr}</label>
                    <div class="col-sm-9">
                        {section name=idx loop=$listusertoalert}
                            {if $showeachuser eq 'n'}
                                <input type="hidden" name="listtoalert[]" value="{$listusertoalert[idx].user}">
                            {else}
                                <input type="checkbox" class="form-check-input" name="listtoalert[]" value="{$listusertoalert[idx].user}">
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
                                    <input type="text" name="add_participant_email" id="add_participant_email" value="" placeholder="or invite email address..." class="form-control">
                                </div>
                                <div class="col-sm-3">
                                    <input type="button" class="btn btn-primary btn-sm" value="Add" id="invite_emails">
                                </div>
                            </div>
                            <br>
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
                            <input type="checkbox" name="calitem[process_itip]" value="1" checked>
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
            <input type="submit" class="btn btn-primary" name="saveitem" value="{tr}Save{/tr}" onclick="needToConfirm=false">
            {if $tiki_p_add_events eq 'y' and empty($saveas) and not empty($calitemId)}
                <input type="submit" class="btn btn-secondary" name="saveas" data-alt_controller="calendar" data-alt_action="copy_item"
                       onclick="needToConfirm=false" value="{tr}Copy to a new event{/tr}">
            {/if}
            {if $calitemId}
                <input type="submit" name="delete" data-alt_controller="calendar" data-alt_action="delete_item"
                       class="btn btn-danger" onclick="needToConfirm=false;" value="{tr}Delete event{/tr}">
            {/if}
            {if $recurrence.id}
                <input type="submit" name="delete-recurrent" data-alt_controller="calendar" data-alt_action="delete_recurrent_items"
                       class="btn btn-danger" onclick="needToConfirm=false;" value="{tr}Delete recurrent events{/tr}">
            {/if}
            {if $prefs.calendar_export_item == 'y' and not empty($calitemId)}
                {button href='tiki-calendar_export_ical.php? export=y&calendarItem='|cat:$calitemId _text="{tr}Export Event as iCal{/tr}"}
            {/if}
        </div>
    </form>
{/block}
