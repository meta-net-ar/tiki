{include file='token_view_actions.tpl'}
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
            <a class="btn btn-link border-radius--0" data-bs-toggle="dropdown" href="#" title="{tr}Calendar actions{/tr}" role="button">
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
            {$thiswatchingcateg=$watching_categories[i].categId}
            {button href="tiki-browse_categories.php?parentId=$thiswatchingcateg" _text=$watching_categories[i].name|escape}
            &nbsp;
        {/section}
    </div>
    {/if}

    {if $prefs.display_12hr_clock eq 'y'}
        {$timeFormat=true}
    {else}
        {$timeFormat=false}
    {/if}
    
    <div id="test"></div>
    
    <div id='currentcalitemId' class='d-none'>{$currentcalitemId}</div>
    <div class='row'>
        <div class='col-md-12'>
            <div class="d-flex align-items-center justify-content-between">
                {include file='calendar_header.tpl' isInMainCalendar="y"}
                {if count($calendars) >= 1}
                    <div class="d-flex align-items-center">
                        {include file="checkboxes_calendar_form.tpl" filterCal="filterMainCal" class="me-2"}
                        {include file="export_calendar_in_csv_or_ical.tpl" exportCal="exportMainCal"}
                    </div>
                {/if}
            </div>
            {if count($calendars) >= 1}
                <h6 class="text-secondary mt-3">{tr}Displayed calendar{/tr}</h6>
                {include file="configlinks_calendar.tpl" filterCal="filterMainCal" isInMainCalendar="y"}
            {/if}
        </div>
        <div class='col-md-12'>
            {if $viewlist eq 'list'}
                <div class="calendar-list-compact">
                    {include file='tiki-calendar_nav.tpl' module='y' ajax='n'}
                    {include file='tiki-calendar_listmode.tpl'}
                </div>
            {else}
                {jq}
                    let today = new Date();
                    var printingParams = {pdf_export: '{{$pdf_export}}', pdf_warning: '{{$pdf_warning}}', pref_print_pdf_from_url: '{{$prefs.print_pdf_from_url}}'};
                    $('.calendar-container').defineParameterOfMultipleCalendar({{$eventCalendarParams|json_encode}}, printingParams, '.calendar-container', today.toISOString().split('T')[0], undefined, undefined, undefined);
                {/jq}
            {/if}
            
            <div class='calendar-container'></div>
        </div>
        
    </div>
</div>
{if $prefs.feature_jscalendar eq 'y'}
    {js_insert_icon type="jscalendar"}
{/if}
