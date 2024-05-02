{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <h5 class="mb-3">{$calitem.parsedName}</h5>
    <div class="summary mb-4">
        {$thiscustombgcolor = $calendar.custombgcolor}
        {$thiscustomfgcolor = $calendar.customfgcolor}
        <span class="px-3 py-2 rounded" style="background:#{$thiscustombgcolor};color:#{$thiscustomfgcolor};">{tr}Calendar{/tr} : <b>{$calendar.name|escape}</b></span>
    </div>
    {if $recurrent}
        {if $recurrence.nbRecurrences eq 1}
            {tr}Event occurs once on{/tr}&nbsp;{$recurrence.startPeriod|tiki_long_date}
        {elseif $recurrence.nbRecurrences gt 1 or $recurrence.endPeriod gt 0}
            {tr}Event is repeated{/tr}&nbsp;
            {if $recurrence.nbRecurrences gt 1}
                {$recurrence.nbRecurrences} {tr}times{/tr}<br>
            {/if}
            {if $recurrence.daily}
                {tr}Every{/tr} {$recurrence.days} {tr}day(s){/tr}
            {elseif $recurrence.weekly}
                {tr}Every{/tr} {$recurrence.weeks} {tr}week(s){/tr}<br>
                {tr}Each{/tr}
                {foreach $recurrence.weekdays as $day}{strip}
                    {if $day@iteration eq $day@total and not $day@first}
                        &nbsp;{tr}and{/tr}&nbsp;
                    {elseif not $day@last and not $day@first}
                        ,&nbsp;
                    {/if}
                    {$daynames[$day]}
                {/strip}{/foreach}
                {tr}of the week{/tr}
            {elseif $recurrence.monthly}
                {tr}Every{/tr} {$recurrence.months} {tr}month(s){/tr}<br>
                {if $recurrence.monthlyType eq 'date'}
                    {tr}Each{/tr} {$recurrence.dayOfMonth|join:', '} {tr}of the month{/tr}
                {elseif $recurrence.monthlyType eq 'firstlastweekday'}
                    {tr}Every{/tr}
                    {if $recurrence.monthlyFirstlastWeekdayValue[0] eq '1'}
                        {tr}First Weekday{/tr}
                    {else}
                        {tr}Last Weekday{/tr}
                    {/if}
                    {tr}of the month{/tr}
                {else}
                    {tr}Every{/tr}
                    {if $recurrence.monthlyWeekdayValue[0] eq '1'}
                        {tr}First{/tr}
                    {elseif $recurrence.monthlyWeekdayValue[0] eq '2'}
                        {tr}Second{/tr}
                    {elseif $recurrence.monthlyWeekdayValue[0] eq '3'}
                        {tr}Third{/tr}
                    {elseif $recurrence.monthlyWeekdayValue[0] eq '4'}
                        {tr}Fourth{/tr}
                    {elseif $recurrence.monthlyWeekdayValue[0] eq '5'}
                        {tr}Fifth{/tr}
                    {elseif $recurrence.monthlyWeekdayValue[0] eq '-1'}
                        {tr}Last{/tr}
                    {/if}
                    {if strpos($recurrence.monthlyWeekdayValue, 'SU') eq true}
                        {tr}Sunday{/tr}
                    {elseif strpos($recurrence.monthlyWeekdayValue, 'MO') eq true}
                        {tr}Monday{/tr}
                    {elseif strpos($recurrence.monthlyWeekdayValue, 'TU') eq true}
                        {tr}Tuesday{/tr}
                    {elseif strpos($recurrence.monthlyWeekdayValue, 'WE') eq true}
                        {tr}Wednesday{/tr}
                    {elseif strpos($recurrence.monthlyWeekdayValue, 'TH') eq true}
                        {tr}Thursday{/tr}
                    {elseif strpos($recurrence.monthlyWeekdayValue, 'FR') eq true}
                        {tr}Friday{/tr}
                    {elseif strpos($recurrence.monthlyWeekdayValue, 'SA') eq true}
                        {tr}Saturday{/tr}
                    {/if}
                    {tr}of the month{/tr}
                {/if}
            {elseif $recurrence.yearly}
                {tr}Every{/tr} {$recurrence.years} {tr}year(s){/tr}<br>
                {if $recurrence.yearlyType eq 'date'}
                    {tr}Each{/tr} {$recurrence.yearlyDay} {tr}of{/tr}
                    {if $recurrence.yearlyMonth eq '1'}
                        {tr}January{/tr}
                    {elseif $recurrence.yearlyMonth eq '2'}
                        {tr}February{/tr}
                    {elseif $recurrence.yearlyMonth eq '3'}
                        {tr}March{/tr}
                    {elseif $recurrence.yearlyMonth eq '4'}
                        {tr}April{/tr}
                    {elseif $recurrence.yearlyMonth eq '5'}
                        {tr}May{/tr}
                    {elseif $recurrence.yearlyMonth eq '6'}
                        {tr}June{/tr}
                    {elseif $recurrence.yearlyMonth eq '7'}
                        {tr}July{/tr}
                    {elseif $recurrence.yearlyMonth eq '8'}
                        {tr}August{/tr}
                    {elseif $recurrence.yearlyMonth eq '9'}
                        {tr}September{/tr}
                    {elseif $recurrence.yearlyMonth eq '10'}
                        {tr}October{/tr}
                    {elseif $recurrence.yearlyMonth eq '11'}
                        {tr}November{/tr}
                    {elseif $recurrence.yearlyMonth eq '12'}
                        {tr}December{/tr}
                    {/if}
                {elseif $recurrence.yearlyType eq 'firstlastweekday'}
                    {tr}Every{/tr}
                    {if $recurrence.yearlyFirstlastWeekdayValue eq '1'}
                        {tr}First Weekday{/tr}
                    {else}
                        {tr}Last Weekday{/tr}
                    {/if}
                        {tr}of{/tr}
                    {if $recurrence.yearlyWeekMonth eq '1'}
                        {tr}January{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '2'}
                        {tr}February{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '3'}
                        {tr}March{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '4'}
                        {tr}April{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '5'}
                        {tr}May{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '6'}
                        {tr}June{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '7'}
                        {tr}July{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '8'}
                        {tr}August{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '9'}
                        {tr}September{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '10'}
                        {tr}October{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '11'}
                        {tr}November{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '12'}
                        {tr}December{/tr}
                    {/if}
                {else}
                    {tr}Every{/tr}
                    {if $recurrence.yearlyWeekdayValue[0] eq '1'}
                        {tr}First{/tr}
                    {elseif $recurrence.yearlyWeekdayValue[0] eq '2'}
                        {tr}Second{/tr}
                    {elseif $recurrence.yearlyWeekdayValue[0] eq '3'}
                        {tr}Third{/tr}
                    {elseif $recurrence.yearlyWeekdayValue[0] eq '4'}
                        {tr}Fourth{/tr}
                    {elseif $recurrence.yearlyWeekdayValue[0] eq '5'}
                        {tr}Fifth{/tr}
                    {elseif $recurrence.yearlyWeekdayValue[0] eq '-1'}
                        {tr}Last{/tr}
                    {/if}
                    {if strpos($recurrence.yearlyWeekdayValue, 'SU') eq true}
                        {tr}Sunday{/tr}
                    {elseif strpos($recurrence.yearlyWeekdayValue, 'MO') eq true}
                        {tr}Monday{/tr}
                    {elseif strpos($recurrence.yearlyWeekdayValue, 'TU') eq true}
                        {tr}Tuesday{/tr}
                    {elseif strpos($recurrence.yearlyWeekdayValue, 'WE') eq true}
                        {tr}Wednesday{/tr}
                    {elseif strpos($recurrence.yearlyWeekdayValue, 'TH') eq true}
                        {tr}Thursday{/tr}
                    {elseif strpos($recurrence.yearlyWeekdayValue, 'FR') eq true}
                        {tr}Friday{/tr}
                    {elseif strpos($recurrence.yearlyWeekdayValue, 'SA') eq true}
                        {tr}Saturday{/tr}
                    {/if}
                    {tr}of{/tr}
                    {if $recurrence.yearlyWeekMonth eq '1'}
                        {tr}January{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '2'}
                        {tr}February{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '3'}
                        {tr}March{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '4'}
                        {tr}April{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '5'}
                        {tr}May{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '6'}
                        {tr}June{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '7'}
                        {tr}July{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '8'}
                        {tr}August{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '9'}
                        {tr}September{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '10'}
                        {tr}October{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '11'}
                        {tr}November{/tr}
                    {elseif $recurrence.yearlyWeekMonth eq '12'}
                        {tr}December{/tr}
                    {/if}
                {/if}
            {/if}
            <br>
            {tr}Starting{/tr} {$recurrence.startPeriod|tiki_long_date}
            {if $recurrence.endPeriod gt 0}
                <br>{tr}Ending{/tr} {$recurrence.endPeriod|tiki_long_date}
            {/if}
        {/if}
    {/if}
    <div class="summary">
        {$calitem.display_datetimes}
    </div>
    <div class="description">
        {$calitem.parsed|default:"<em>{tr}No description{/tr}</em>"}
    </div>
    <div class="small mt-3">
        <div class="table-responsive">
            <table class="table table-borderless p-0 table-sm{if $preview} table-secondary{/if}">
                {* custom properties *}
                {if $calendar.customstatus eq 'y'}
                    <tr>
                        <td colspan="2">
                            {if $calitem.status eq 0}
                                <label class="badge bg-secondary mb-1">{tr}Tentative{/tr}</label>
                            {elseif $calitem.status eq 1}
                                <label class="badge bg-success mb-1">{tr}Confirmed{/tr}</label>
                            {elseif $calitem.status eq 2}
                                <label class="badge bg-danger mb-1">{tr}Cancelled{/tr}</label>
                            {/if}
                        </td>
                    </tr>
                {/if}
                {if $calendar.custompriorities eq 'y'}
                    <tr class="priority">
                        <th style="background-color:#{$listprioritycolors[$calitem.priority]}">
                            {tr}Priority:{/tr}
                        </th>
                        <td>{if $calitem.priority neq ''}{$calitem.priority|escape}{else}<em class="text-secondary">{tr}No priority set for this event{/tr}</em>{/if}</td>
                    </tr>
                {/if}
                {if $calendar.customcategories eq 'y'}
                    <tr class="category">
                        <th>{tr}Classification:{/tr}</th>
                        <td>{if $calitem.categoryName neq ''}{$calitem.categoryName|escape}{else}<em class="text-secondary">{tr}No classification added to this event{/tr}</em>{/if}</td>
                    </tr>
                {/if}
                {if $calendar.customlocations eq 'y'}
                    <tr class="location">
                        <th>{tr}Location:{/tr}</th>
                        <td>{if $calitem.locationName neq ''}{$calitem.locationName|escape}{else}<em class="text-secondary">{tr}No location added to this event{/tr}</em>{/if}</td>
                    </tr>
                {/if}
                {if $calendar.customurl eq 'y'}
                    <tr class="url">
                        <th>{tr}Custom url:{/tr}</th>
                        <td>
                            {if $calitem.customurl neq ''}
                                <a class="url" href="{$calitem.url}">
                                    {$calitem.url|escape}
                                </a>
                            {else}
                                <em class="text-secondary">{tr}No custom url added{/tr}</em>
                            {/if}
                        </td>
                    </tr>
                {/if}
                {if $calendar.customlanguages eq 'y'}
                    <tr class="language">
                        <th>{tr}Language:{/tr}</th>
                        <td>{if $calitem.lang neq ''}{$calitem.lang|langname}{else}<em class="text-secondary">{tr}No language added to this event{/tr}</em>{/if}</td>
                    </tr>
                {/if}
                {if $calendar.customparticipants eq 'y'}
                    <tr class="organizers">
                        <th>{tr}Organizers:{/tr}</th>
                        <td>
                            {if ! empty($calitem.organizers) && ($calitem.organizers|@count)}
                                <ul>
                                {foreach $calitem.organizers as $organizer}
                                    <li>{$organizer|userlink}</li>
                                {/foreach}
                                </ul>
                            {else}
                                <em class="text-secondary">{tr}No organizers{/tr}</em>
                            {/if}
                        </td>
                    </tr>
                    <tr class="participants">
                        {$particiapting = false}
                        <th>{tr}Participants:{/tr}</th>
                        <td>
                            <p>
                                {if $calitem.participants|@count}
                                    <ul>
                                    {foreach $calitem.participants as $person}
                                        <li>
                                            {$person.username|userlink}
                                            {if $listroles[$person.role]} ({$listroles[$person.role]}){/if}
                                        </li>
                                        {if $person.username eq $user}{$particiapting = true}{/if}
                                    {/foreach}
                                    </ul>
                                {else}
                                    <em class="text-secondary">{tr}No participants{/tr}</em>
                                {/if}
                            </p>
                            {if not $preview and $tiki_p_calendar_add_my_particip eq 'y'}
                                {if $particiapting}
                                    {button _text="{tr}Withdraw me from the list of participants{/tr}" href="{service controller='calendar' action='del_me' calitemId=$calitem.calitemId}" _class='btn-sm'}
                                {else}
                                    {button _text="{tr}Add me to the list of participants{/tr}" href="{service controller='calendar' action='add_me' calitemId=$calitem.calitemId}" _class='btn-sm'}
                                {/if}
                            {/if}
                        </td>
                    </tr>
                {/if}
            </table>
        </div>
    </div>
    {if not $preview}
        {permission name='change_events' type='calendaritem' object=$calitem.calitemId}
            <a href="{service controller='calendar' action='edit_item' calitemId=$calitem.calitemId|escape}" class="btn btn-primary edit-calendar-item-btn">
                {tr}Edit{/tr}
            </a>
        {/permission}
    {/if}
{/block}
