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
            {include file="checkboxes_calendar_form.tpl" filterCal="filterMainCal"}

            {include file="export_calendar_in_csv_or_ical.tpl" exportCal="exportMainCal"}

            {include file="configlinks_calendar.tpl" filterCal="filterMainCal" isInMainCalendar="y"}
        {/if}
        {if $nlg_availability}
            <div class="alert alert-info">
                {$nlg_availability}
            </div>
        {/if}
    </div>
    {* show jscalendar if set *}

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
        <div class="calendar-list-compact">
            {include file='tiki-calendar_nav.tpl' module='y' ajax='n'}
            {include file='tiki-calendar_listmode.tpl'}
        </div>
    {else}
        {jq}
            let today = new Date();
            var printingParams = {pdf_export: '{{$pdf_export}}', pdf_warning: '{{$pdf_warning}}', pref_print_pdf_from_url: '{{$prefs.print_pdf_from_url}}'};
            $('.calendar-container').defineParameterOfMultipleCalendar({{$fullCalendarParams|json_encode}}, printingParams, '.calendar-container', today.toISOString().split('T')[0], undefined, undefined, undefined);
        {/jq}
    {/if}

    <div id="test"></div>

    <div id='currentcalitemId' class='d-none'>{$currentcalitemId}</div>
    <div class='calendar-container'></div>
</div>
{if $prefs.feature_jscalendar eq 'y' and $prefs.javascript_enabled eq 'y'}
    {js_insert_icon type="jscalendar"}
{/if}
