{title admpage="calendar"}
    {if $displayedcals|@count eq 1}
    {tr}Calendar:{/tr}{$calendars[$displayedcals[0]].displayName|escape}
    {else}
        {tr}Calendar{/tr}
    {/if}
{/title}
<div id="calscreen">
    <div class="t_navbar mb-4">
        <div class="btn-group float-end">
            {if ! $js}<ul><li>{/if}
            <a class="btn btn-link border-radius--0" data-bs-toggle="dropdown" data-hover="dropdown" href="#" title="{tr}Calendar actions{/tr}">
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
                            <a href="tiki-calendar.php?watch_event=calendar_changed&amp;watch_action=remove">
                                {icon name="stop-watching"} {tr}Stop monitoring{/tr}
                            </a>
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
                    <a href="tiki-calendar.php?generate_availability=1&amp;ltodate={$smarty.request.todate}&amp;calIds[]={"&calIds[]="|implode:$displayedcals}">
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
                    $("#filtercal").submit(function () {
                        if ($("input[type=checkbox]:not(#clickall):not(:checked)", this).length === 0) {
                            location.href = (jqueryTiki.sefurl ? "calendar" : "tiki-calendar.php") + "?allCals=y";
                            return false;
                        } else {
                            return true;
                        }
                    });
                {/jq}
            </div>

            {include file="export_calendar_in_csv_or_ical.tpl"}

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
        <div class="jscalrow" style="display: inline-block">
            <form action="{$myurl}" method="post" name="f">
                {jscalendar date="$focusdate" id="trig" goto="$jscal_url" align="Bc"}
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
            const mainCalendarContainer = [window.calendar];
            $("#calendar").setupFullCalendar({{$fullCalendarParams|json_encode}}, mainCalendarContainer);
            {{if $prefs.print_pdf_from_url neq 'none'}$("#calendar").addFullCalendarPrint('#calendar-pdf-btn', mainCalendarContainer[0]);{/if}}
        {/jq}
    {/if}
    {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
        <a id="calendar-pdf-btn"  href="#" class="text-end d-none">{icon name='pdf'} {tr}Export as PDF{/tr}</a>
    {/if}
    <div id="test"></div>
    
    <div id='currentcalitemId' class='d-none'>{$currentcalitemId}</div>
    <div id='calendar'></div>
</div>
{if $prefs.feature_jscalendar eq 'y' and $prefs.javascript_enabled eq 'y'}
    {js_insert_icon type="jscalendar"}
{/if}
