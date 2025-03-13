{title admpage="calendar"}
    {if $displayedcals|@count eq 1}
    {tr}Calendar:{/tr} {$calendars[$displayedcals[0]].displayName|escape}
    {else}
        {tr}Calendar{/tr}
    {/if}
{/title}
<div id="calscreen">
    <div class="t_navbar mb-4">
        <div class="btn-group float-end">
            {if ! $js}<ul><li>{/if}
            <a class="btn btn-link border-radius--0" data-bs-toggle="dropdown" href="#" title="{tr}Calendar actions{/tr}">
                {icon name='menu-extra'}
            </a>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="dropdown-header">
                    {tr}Monitoring{/tr}
                </li>
                <li class="dropdown-divider"></li>
                {if $displayedcals|@count eq 1 and $user and $prefs.feature_user_watches eq 'y'}
                    <li class="dropdown-item">
                        {if $user_watching eq 'y'}
                            <form action="tiki-calendar.php" method="post">
                                {ticket}
                                <input type="hidden" name="watch_event" value="calendar_changed">
                                <button type="submit" name="watch_action" value="remove" class="btn btn-link">
                                {icon name="stop-watching"} {tr}Stop monitoring{/tr}
                                </button>
                            </form>
                        {else}
                            <a href="tiki-calendar.php?watch_event=calendar_changed&amp;watch_action=add">
                                {icon name="watch"} {tr}Monitor{/tr}
                            </a>
                        {/if}
                    </li>
                {/if}
                {if $displayedcals|@count eq 1 and $prefs.feature_group_watches eq 'y' and ( $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y' )}
                    <li class="dropdown-item">
                        <a href="tiki-object_watches.php?objectId={$displayedcals[0]|escape:"url"}&amp;watch_event=calendar_changed&amp;objectType=calendar&amp;objectName={$calendars[$x].name|escape:"url"}&amp;objectHref={'tiki-calendar.php?calIds[]='|cat:$displayedcals[0]|escape:"url"}">
                            {icon name="watch-group"} {tr}Group Monitor{/tr}
                        </a>
                    </li>
                {/if}
                <li class="dropdown-item">
                    <a href="{service controller=calendar_availability action=index}">
                        {icon name="calendar-week"} {tr}Availability{/tr}
                    </a>
                </li>
                <li class="dropdown-item">
                    <a href="tiki-calendar.php?generate_availability=1&amp;ltodate={$smarty.request.todate}&amp;calIds[]={$displayedcals|join:"&calIds[]="}">
                        {icon name="calendar-week"} {tr}Availability (NLG){/tr}
                    </a>
                </li>
            </ul>
        </div>
        {if $tiki_p_admin_calendar eq 'y' or $tiki_p_admin eq 'y'}
            {if $displayedcals|@count eq 1}
                {if $displayedcals[0]|substr:0:1 eq 's'}
                    {button href="tiki-admin_calendars.php?subscriptionId={$displayedcals[0]|substr:1}&cookietab=3" _type="link" _text="{tr}Edit{/tr}" _icon_name="edit"}
                {else}
                    {button href="tiki-admin_calendars.php?calendarId={$displayedcals[0]}&cookietab=2" _type="link" _text="{tr}Edit{/tr}" _icon_name="edit"}
                {/if}
            {/if}
            {button href="tiki-admin_calendars.php?cookietab=1" _type="link" _text="{tr}Admin{/tr}" _icon_name="admin"}
        {elseif $tiki_p_admin_private_calendar eq 'y'}
            {button href="tiki-admin_calendars.php?cookietab=1" _type="link" _text="{tr}Admin{/tr}" _icon_name="admin"}
        {/if}

        {* avoid Add Event being shown if no calendar is displayed *}
        {if $tiki_p_add_events eq 'y'}
            <a href="{bootstrap_modal controller='calendar' action='edit_item' size='modal-lg' defaultCalendarId=$defaultCalendarId}" class="btn btn-primary">{icon name='create'} {tr}Add Event{/tr}</a>
        {/if}

        {if $viewlist eq 'list'}
            {capture name=href}?viewlist=table{if !empty($smarty.request.todate)}&amp;todate={$smarty.request.todate}{/if}{/capture}
            {button href=$smarty.capture.href _text='{tr}Calendar View{/tr}' _icon_name='calendar' _type='info'}
        {else}
            {capture name=href}?viewlist=list{if !empty($smarty.request.todate)}&amp;todate={$smarty.request.todate}{/if}{/capture}
            {button href=$smarty.capture.href _text='{tr}List View{/tr}' _icon_name='list' _type='info'}
        {/if}

        {if count($calendars) >= 1}
            {button href="#" _onclick="toggle('filtercal');return false;" _text='{tr}Calendars{/tr}' _icon_name='eye' _type='info'}
            <div class="d-inline-block">
                <form class="card" id="filtercal" method="get" action="{$myurl}" name="f" style="display:none;">
                    <div class="card-header caltitle py-1 px-2">
                        <strong>{tr}Calendars{/tr}</strong>
                        <button type="button" class="btn-close float-end"  onclick="toggle('filtercal')" aria-hidden="true"></button>
                    </div>
                    <ul class="list-group list-group-flush list-unstyled mt-2">
                        <li class="caltoggle">
                            {select_all checkbox_names='calIds[]' label="{tr}Check / Uncheck All{/tr}"}
                        </li>
                        {foreach $calendars as $calendarId => $calendar}
                            <li class="calcheckbox">
                                <input type="checkbox" name="calIds[]" value="{$calendarId|escape}" id="groupcal_{$calendarId}"
                                    {if in_array($calendarId, $displayedcals)}checked="checked"{/if}>
                                <label for="groupcal_{$calendarId}" class="calId{$calendarId}">{$calendar.name|escape} ({tr}Id #{$calendarId}{/tr})</label>
                            </li>
                        {/foreach}
                        <li class="calinput">
                            <input type="hidden" name="todate" value="{$focusdate}">
                            <input type="submit" class="btn btn-primary btn-sm" name="refresh" value="{tr}Refresh{/tr}">
                        </li>
                    </ul>
                </form>
                {jq}
                    // handle calendar switcher form submit
                    $("#filtercal").on("submit", function () {
                        if ($("input[type=checkbox]:not(#clickall):not(:checked)", this).length === 0) {
                            location.href = (jqueryTiki.sefurl ? "calendar" : "tiki-calendar.php") + "?allCals=y";
                            return false;
                        } else {
                            return true;
                        }
                    });
                {/jq}
            </div>

            {if $tiki_p_view_events eq 'y' and $prefs.calendar_export eq 'y'}
                {button href="#" _onclick="toggle('exportcal');return false;" _text='{tr}Export{/tr}' _icon_name='export' _type='info'}
                <div class="d-inline-block">
                    <form id="exportcal" class="card" method="post" action="tiki-calendar_export_ical.php" name="f" style="display:none;">
                        <input type="hidden" name="export" value="y">
                        <div class="card-header caltitle py-1 px-2">
                            <strong>{tr}Export calendars{/tr}</strong>
                            <button type="button" class="btn-close float-end"  onclick="toggle('exportcal')" aria-hidden="true"></button>
                        </div>
                        <div class="caltoggle">
                            {select_all checkbox_names='calendarIds[]' label="{tr}Check / Uncheck All{/tr}"}
                        </div>
                        {foreach $calendars as $calendarId => $calendar}
                            <div class="calcheckbox">
                                <input type="checkbox" name="calendarIds[]" value="{$calendarId|escape}" id="groupexcal_{$calendarId}"
                                    {if in_array($calendarId, $displayedcals)}checked="checked"{/if}>
                                <label for="groupexcal_{$calendarId}" class="calId{$calendarId}">{$calendar.name|escape} ({tr}Id #{$calendarId}{/tr})</label>
                            </div>
                        {/foreach}
                        <div class="calcheckbox">
                            <a href="{$iCalAdvParamsUrl}">{tr}advanced parameters{/tr}</a>
                        </div>
                        <div class="calinput">
                            <input type="submit" class="btn btn-primary btn-sm" name="ical" value="{tr}Export as iCal{/tr}">
                            <input type="submit" class="btn btn-primary btn-sm" name="csv" value="{tr}Export as CSV{/tr}">
                        </div>
                    </form>
                </div>
            {/if}

            <div id="configlinks" class="mb-3 text-end">
                {if count($checkedCalIds)}
                    {$maxCalsForButton = 20}
                    {if count($checkedCalIds) > $maxCalsForButton}<select size="5">{/if}
                    {foreach $checkedCalIds as $checkedCalId}
                        {if $calendarId}
                            {$thiscustombgcolor = $calendars[$checkedCalId].custombgcolor}
                            {$thiscustomfgcolor = $calendars[$checkedCalId].customfgcolor}
                            {$thiscalendarsname = $calendars[$checkedCalId].displayName|escape}
                            {if count($checkedCalIds) > $maxCalsForButton}
                                <option style="background:#{$thiscustombgcolor};color:#{$thiscustomfgcolor};" onclick="toggle('filtercal')">
                                    {$thiscalendarsname}
                                </option>
                            {else}
                                {button href="{$checkedCalId|sefurl:'calendar'}" _style="background:#$thiscustombgcolor;color:#$thiscustomfgcolor;border:1px solid #$thiscustomfgcolor;" _text="{$thiscalendarsname}" _class='btn btn-sm me-2'}
                            {/if}
                        {/if}
                    {/foreach}
                    {if count($checkedCalIds) > $maxCalsForButton}</select>{/if}
                {/if}
            </div>
        {/if}
        {if $nlg_availability}
            <div class="alert alert-info">
                {$nlg_availability}
            </div>
        {/if}
    </div>
    {* show jscalendar if set *}
    {if $prefs.feature_jscalendar eq 'y'}
        <div class=" mb-2" style="display: inline-block">
            <form action="{$myurl}" method="post" name="f">
                {jscalendar date="$focusdate" goto="$jscal_url" showtime="n"}
            </form>
        </div>
    {/if}

    {if $user and $prefs.feature_user_watches eq 'y' and isset($category_watched) and $category_watched eq 'y'}
    <div class="categbar">
        {tr}Watched by categories:{/tr}
        {section name=i loop=$watching_categories}
            {assign var=thiswatchingcateg value=$watching_categories[i].categId}
            {button href="tiki-browse_categories.php?parentId=$thiswatchingcateg" _text=$watching_categories[i].name|escape}
            &nbsp;
        {/section}
    </div>
    {/if}

    {if $prefs.display_12hr_clock eq 'y'}
        {assign var="timeFormat" value=true}
    {else}
        {assign var="timeFormat" value=false}
    {/if}
    {if $viewlist eq 'list'}
        {include file='tiki-calendar_listmode.tpl'}
    {else}
        {jq}
            $("#calendar").setupFullCalendar({{$fullCalendarParams|json_encode}});
            {{if $prefs.print_pdf_from_url neq 'none'}$("#calendar").addFullCalendarPrint('#calendar-pdf-btn', calendar);{/if}}
        {/jq}
    {/if}
    {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
        <a id="calendar-pdf-btn"  href="#" class="text-end d-none">{icon name='pdf'} {tr}Export as PDF{/tr}</a>
    {/if}
    <div id="test"></div>
    <style type='text/css'>
        /* Fix pb with DatePicker */
        .ui-datepicker {
            z-index:9999 !important;
        }
        .ec .ec-scrollgrid, .ec .ec-scrollgrid table,
        .ec .ec-day-grid {
            width: 100% !important;
        }
        .ec-day-grid-harness {
            border-radius: 4px;
            margin: 0px 3px 0px;
        }
        .ec-event {
            display: block;
            white-space: break-spaces;
        }
        .ec-events .ec-event-time {
            color: #ffffff;
            font-weight: bold;
        }
        .ec-events .ec-event-title {
            color: #ffffff;
            font-weight: normal;
        }
        .ec-time-grid-event .ec-event-time {
            font-weight: bold;
        }
        .ec-time-grid .ec-event-title {
            font-weight: normal;
        }
        .ec-dark .ec-active {
            color: white;
        }
        @media only screen and (max-width: 767px) {
            .ec-toolbar {
                display: block !important;
            }
            .ec-toolbar .ec-button .btn-group .btn {
                padding: 0.375rem 0.1rem;
            }
        }
        @media print {
            .ec .ec-day-grid-day-top {
                border-bottom: 1px solid #dee2e6;
            }
        }
    </style>
    <div id='currentcalitemId' class='d-none'>{$currentcalitemId}</div>
    <div id='calendar'></div>
</div>
{if $prefs.feature_jscalendar eq 'y'}
    {js_insert_icon type="jscalendar"}
{/if}
