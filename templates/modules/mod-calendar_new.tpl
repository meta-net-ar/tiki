{if isset($show_calendar_module) and $show_calendar_module eq 'y'}
    {tikimodule error=$module_params.error title=$tpl_module_title name=$name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
        {if $tiki_p_add_events eq 'y' && (empty($module_params.showaction) || $module_params.showaction ne 'n')}
            <br>
            <p>
                <a class="btn btn-link" href="{bootstrap_modal controller='calendar' action=$prefs.calendar_event_click_action size='modal-lg'}" role="button">
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
                    {include file="export_calendar_in_csv_or_ical.tpl"}
                {/if}

                {if $viewlist neq 'list'}
                    {jq}
                        const calendarContainer = [window.moduleCalendar];
                        $("#module-calendar").setupEventCalendar({{$eventCalendarParams|json_encode}}, calendarContainer, 'module-calendar', 'tiki-ajax_services.php?controller=calendar&action=list_items&calIds={{$moduleCalendarIds}}');
                        {{if $prefs.print_pdf_from_url neq 'none'}$("#module-calendar").addEventCalendarPrint('#module-calendar-pdf-btn', calendarContainer[0]);{/if}}
                    {/jq}
                {/if}
                {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
                    <a id="module-calendar-pdf-btn" href="#" class="text-end d-none" role="button">{icon name='pdf'} {tr}Export as PDF{/tr}</a>
                {/if}
                <div id="test"></div>
                <div id='module-calendar'></div>
                {if $viewlist eq 'list'}
                    {$out}
                {/if}
            </div>
        {/if}
        
    {/tikimodule}
{/if}
