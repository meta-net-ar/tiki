{$filters}
<div id="{$trackercalendarData.id|escape}" class="wp-trackercalendar"></div>
{jq}
    $("#{{$trackercalendarData.id|escape}}").setupEventCalendar({{$trackercalendarData|json_encode}});
{/jq}
{if $pdf_export eq 'y' and $pdf_warning eq 'n'}
    <a id="calendar-pdf-btn" data-html2canvas-ignore="true"  href="#" style="text-align: right; display: none" role="button">{icon name="pdf"} {tr}Export as PDF{/tr}</a>
{/if}
