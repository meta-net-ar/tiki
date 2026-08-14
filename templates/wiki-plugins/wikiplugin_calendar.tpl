<div class="border border-secondary p-2">
    {if $tiki_p_add_events eq 'y' and $viewlist neq 'list'}
        <a href="{bootstrap_modal controller='calendar' action='edit_item' size='modal-lg' defaultCalendarId=$defaultCalendarId return_url=$returnURL}" class="btn btn-primary">{icon name='create'} {tr}Add Event{/tr}</a>
    {/if}
    {if count($calendars) >= 1 && $viewnavbar eq 'y'}
        {include file="checkboxes_calendar_form.tpl" filterCal="filterPluginCal"}
        {include file="export_calendar_in_csv_or_ical.tpl" exportCal="exportPluginCal"}
    {/if}

    {if $viewlist neq 'list'}
        {jq}
            var printingParams = {pdf_export: '{{$pdf_export}}', pdf_warning: '{{$pdf_warning}}', pref_print_pdf_from_url: '{{$prefs.print_pdf_from_url}}'}
            var uniqueId = ({{$uniqueId|json_encode}}) || null; // In the case user want to display the same calendar with the same parameter more than one time
            var moduleCalendarFocusdate = '{{$focusdate}}';
            var divClassContainer = ".calendar-container";
            var uniqueId = ({{$uniqueId|json_encode}}) || null;
            var linkToFindItemsOfCalendar = 'tiki-ajax_services.php?controller=calendar&action=list_items&calIds={{$pluginCalendarIds}}'
            $('.calendar-container').defineParameterOfMultipleCalendar({{$eventCalendarParams|json_encode}}, printingParams, divClassContainer, moduleCalendarFocusdate, linkToFindItemsOfCalendar, undefined, '{{$returnURL}}');
        {/jq}
    {/if}
    
    <div id="test"></div>
    {include file="configlinks_calendar.tpl" filterCal="filterPluginCal"}

    <div class='calendar-container'></div>
    {if $viewlist eq 'list'}
        {$out}
    {/if}
</div>
