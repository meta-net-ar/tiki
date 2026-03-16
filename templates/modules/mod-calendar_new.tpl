{if isset($show_calendar_module) and $show_calendar_module eq 'y'}
    {tikimodule error=$module_params.error title=$tpl_module_title name=$name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
        {if $tiki_p_add_events eq 'y' && (empty($module_params.showaction) || $module_params.showaction ne 'n')}
            <br>
            <p>
                <a class="btn btn-link" href="{bootstrap_modal controller='calendar' action='edit_item' size='modal-lg' defaultCalendarId=$defaultCalendarId return_url=$returnURL}" role="button">
                    {icon name="add"}
                    {tr}Add Event{/tr}
                </a>
            </p>
        {/if}
        {if $viewlist eq 'list'}
            {include file='tiki-calendar_listmode.tpl'}
        {else}
            <div>
                {if count($calendars) >= 1 && $viewnavbar eq 'y'}
                    {include file="checkboxes_calendar_form.tpl" filterCal="filterModuleCal"}
                    {include file="export_calendar_in_csv_or_ical.tpl" exportCal="exportModuleCal"}
                {/if}
                {if $viewlist neq 'list'}
                    {jq}
                        let paramOfModuleCalendar = {{$eventCalendarParams|json_encode}};
                        const moduleCalendarFocusDate = document.getElementById('date-module-calendar');
                        paramOfModuleCalendar['initialDate'] = $('#date-module-calendar').val();
                        let returnUrl = ('{{$returnURL}}');
                        let associatedWikiPage = {{$associatedWikiPage|json_encode}} || null;
                        returnUrl = returnUrl.toString();
                        let dataToBuildUrl = {{$urlOfFetchingData|json_encode}} || null;
                        let urlOfFetchingData = $.service("tracker_calendar", "list", $.extend(dataToBuildUrl, dataToBuildUrl));

                        const calendarContainer = [window.moduleCalendar];
                        $("#module-calendar").setupEventCalendar({{$eventCalendarParams|json_encode}}, calendarContainer, 'module-calendar',dataToBuildUrl ? urlOfFetchingData : 'tiki-ajax_services.php?controller=calendar&action=list_items&calIds={{$moduleCalendarIds}}', returnUrl, associatedWikiPage);
                        moduleCalendarFocusDate.addEventListener('change', () => {
                            document.getElementById('module-calendar').innerHTML = "";
                            paramOfModuleCalendar['initialDate'] = $('#date-module-calendar').val();
                            $("#module-calendar").setupEventCalendar(paramOfModuleCalendar, calendarContainer, 'module-calendar',dataToBuildUrl ? urlOfFetchingData : 'tiki-ajax_services.php?controller=calendar&action=list_items&calIds={{$moduleCalendarIds}}', returnUrl, associatedWikiPage);
                        })
                        {{if $prefs.print_pdf_from_url neq 'none'}$("#module-calendar").addEventCalendarPrint('#module-calendar-pdf-btn', calendarContainer[0]);{/if}}
                    {/jq}
                {/if}
                {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
                    <a id="module-calendar-pdf-btn" href="#" class="text-end d-none" role="button">{icon name='pdf'} {tr}Export as PDF{/tr}</a>
                {/if}
                <div id="test"></div>
                {include file="configlinks_calendar.tpl" filterCal="filterModuleCal"}
                <input type="date" value="{$moduleCalendarFocusdate}" id="date-module-calendar">
                <div id='module-calendar'></div>
                {if $viewlist eq 'list'}
                    {$out}
                {/if}
            </div>
        {/if}
        
    {/tikimodule}
{/if}
