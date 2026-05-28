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
            <div class="calendar-list-compact">
                {include file='tiki-calendar_nav.tpl' module='y'}
                {include file='tiki-calendar_listmode.tpl'}
            </div>
        {else}
            <div>
                {if count($calendars) >= 1 && $viewnavbar eq 'y'}
                    {include file="checkboxes_calendar_form.tpl" filterCal="filterModuleCal"}
                    {include file="export_calendar_in_csv_or_ical.tpl" exportCal="exportModuleCal"}
                {/if}
                {if $viewlist neq 'list'}
                    {jq}
                        var printingParams = {pdf_export: '{{$pdf_export}}', pdf_warning: '{{$pdf_warning}}', pref_print_pdf_from_url: '{{$prefs.print_pdf_from_url}}'};
                        var associatedWikiPage = {{$associatedWikiPage|json_encode}} || null;
                        var eventCalendarParams = {{$eventCalendarParams|json_encode}};
                        var returnUrl = ('{{$returnURL}}');
                        var uniqueId = ({{$uniqueId|json_encode}}) || null; // In the case user want to display the same calendar with the same parameter more than one time
                        var moduleCalendarFocusdate = '{{$moduleCalendarFocusdate}}';

                        var dataToBuildUrl = '{{$moduleCalendarIds}}' ? null : {{$urlOfFetchingData|json_encode}};
                        var divClassContainer = ".calendar-container";
                        var urlOfFetchingData = $.service("tracker_calendar", "list", $.extend(dataToBuildUrl, dataToBuildUrl));
                        var linkToFindItemsOfCalendar = dataToBuildUrl ? urlOfFetchingData : 'tiki-ajax_services.php?controller=calendar&action=list_items&calIds=' + '{{$moduleCalendarIds}}'
                        $('.calendar-container').defineParameterOfMultipleCalendar(eventCalendarParams, printingParams, divClassContainer, moduleCalendarFocusdate, linkToFindItemsOfCalendar, associatedWikiPage, returnUrl);
                    {/jq}
                {/if}
                {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
                    
                {/if}
                <div id="test"></div>
                {include file="configlinks_calendar.tpl" filterCal="filterModuleCal"}
                <div class='calendar-container'></div>
                {if $viewlist eq 'list'}
                    {$out}
                {/if}
            </div>
        {/if}
        
    {/tikimodule}
{/if}
