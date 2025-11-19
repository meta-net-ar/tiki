<div class="border border-secondary p-2">
    {if $tiki_p_add_events eq 'y' and $viewlist neq 'list'}
        <a href="{bootstrap_modal controller='calendar' action='edit_item' size='modal-lg' defaultCalendarId=$defaultCalendarId return_url=$returnURL}" class="btn btn-primary">{icon name='create'} {tr}Add Event{/tr}</a>
    {/if}
    {if count($calendars) >= 1 && $viewnavbar eq 'y'}
        {include file="export_calendar_in_csv_or_ical.tpl"}
    {/if}

    {if $viewlist neq 'list'}
        {jq}
            let content = {{$eventCalendarParams|json_encode}};
            const elt = document.getElementById('date-plugin-calendar');
            content['initialDate'] = $('#date-plugin-calendar').val();
            const wikipluginCalendar = [window.pluginCalendar];
            let returnUrlForPlugin = ('{{$returnURL}}');
            returnUrlForPlugin = returnUrlForPlugin.toString();
            $("#plugin-calendar").setupEventCalendar(content,  wikipluginCalendar, 'plugin-calendar', 'tiki-ajax_services.php?controller=calendar&action=list_items&calIds={{$pluginCalendarIds}}', returnUrlForPlugin);
            elt.addEventListener('change', () => {
                document.getElementById('plugin-calendar').innerHTML = "";
                content['initialDate'] = $('#date-plugin-calendar').val();
                $("#plugin-calendar").setupEventCalendar(content,  wikipluginCalendar, 'plugin-calendar', 'tiki-ajax_services.php?controller=calendar&action=list_items&calIds={{$pluginCalendarIds}}', returnUrlForPlugin);
            })
            {{if $prefs.print_pdf_from_url neq 'none'}$("#plugin-calendar").addEventCalendarPrint('#calendar-pdf-btn', wikipluginCalendar[0]);{/if}}
        {/jq}
    {/if}
    {if $pdf_export eq 'y' and $pdf_warning eq 'n'}
        <a id="calendar-pdf-btn" href="#" class="text-end d-none" role="button">{icon name='pdf'} {tr}Export as PDF{/tr}</a>
    {/if}
    <div id="test"></div>
    <div id="configlinks" class="mb-3 text-end">
        <div id="configlinks" class="mb-3 text-end">
            {if count($checkedCalIds)}
                {$maxCalsForButton = 20}
                {if count($checkedCalIds) > $maxCalsForButton}<select size="5">{/if}
                {foreach $checkedCalIds as $checkedCalId}
                    {if $calendars}
                        {$thiscustombgcolor = $calendars[$checkedCalId].custombgcolor}
                        {$thiscustomfgcolor = $calendars[$checkedCalId].customfgcolor}
                        {$thiscalendarsname = $calendars[$checkedCalId].displayName|escape}
                        {if count($checkedCalIds) > $maxCalsForButton}
                            <option style="background:#{$thiscustombgcolor};color:#{$thiscustomfgcolor};" onclick="toggle('filtercal')">
                                {$thiscalendarsname}
                            </option>
                        {else}
                            {button href="{$checkedCalId|sefurl:'calendar'}" _style="background:#$thiscustombgcolor;color:#$thiscustomfgcolor;border:1px solid #$thiscustomfgcolor; " _text="{$thiscalendarsname}" _class='btn btn-sm me-2 mt-2' _icon_name='calendar'}
                        {/if}
                    {/if}
                {/foreach}
                {if count($checkedCalIds) > $maxCalsForButton}</select>{/if}
            {/if}
        </div>    
    </div>
    {if $viewlist neq 'list'}
    <input type="date" value="{$focusdate}" id="date-plugin-calendar">
    {/if}
    <div id='plugin-calendar'></div>
    {if $viewlist eq 'list'}
        {$out}
    {/if}
</div>
