<div>
    {if $tiki_p_add_events eq 'y'}
        <a href="{bootstrap_modal controller='calendar' action='edit_item' size='modal-lg' defaultCalendarId=$defaultCalendarId}" class="btn btn-primary">{icon name='create'} {tr}Add Event{/tr}</a>
    {/if}
    {if count($calendars) >= 1 && $viewnavbar eq 'y'}
        {include file="export_calendar_in_csv_or_ical.tpl"}
    {/if}

    {if $viewlist neq 'list'}
        {jq}
            $("#calendar").setupFullCalendar({{$fullCalendarParams|json_encode}});
            {{if $prefs.print_pdf_from_url neq 'none'}$("#calendar").addEventCalendarPrint('#calendar-pdf-btn', calendar);{/if}}
        {/jq}
    {/if}
    {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
        <a id="calendar-pdf-btn" href="#" class="text-end d-none" role="button">{icon name='pdf'} {tr}Export as PDF{/tr}</a>
    {/if}
    <div id="test"></div>
    <div id='calendar'></div>
    {if $viewlist eq 'list'}
        {$out}
    {/if}
</div>
