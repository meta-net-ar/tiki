{if $tiki_p_view_events eq 'y' and $prefs.calendar_export eq 'y'}
    {if $isInMainCalendar eq 'y'}
        {button href="#" _onclick="toggle('exportcal');return false;" _text='{tr}Export{/tr}' _icon_name='export' _type='info' _style='display: block;'}
    {else}
        {button href="#" _onclick="toggle('exportcal');return false;" _text='{tr}Export{/tr}' _icon_name='export' _type='info'}
    {/if}
    
    <div class="d-inline-block">
        <form id="exportcal" class="card" method="post" action="tiki-calendar_export_ical.php" name="f" style="display:none;">
            <input type="hidden" name="export" value="y">
            <div class="card-header caltitle py-1 px-2">
                <strong>{tr}Export calendars{/tr}</strong>
                <button type="button" class="btn-close float-end"  onclick="toggle('exportcal')" aria-hidden="true"></button>
            </div>
            <div class="caltoggle">
                {select_all checkbox_names='calendarIds[]' label="{tr}Check / Uncheck All{/tr}"}
                </div>
            {foreach $calendars as $calendarId => $calendar}
                <div class="calcheckbox">
                    <input type="checkbox" name="calendarIds[]" value="{$calendarId|escape}" id="groupexcal_{$calendarId}"
                        {if in_array($calendarId, $displayedcals)}checked="checked"{/if}>
                    <label for="groupexcal_{$calendarId}" class="calId{$calendarId}">{$calendar.name|escape} ({tr}Id #{$calendarId}{/tr})</label>
                </div>
            {/foreach}
            <div class="calcheckbox">
                <a href="{$iCalAdvParamsUrl}">{tr}advanced parameters{/tr}</a>
            </div>
            <div class="calinput">
                <input type="submit" class="btn btn-primary btn-sm" name="ical" value="{tr}Export as iCal{/tr}">
                <input type="submit" class="btn btn-primary btn-sm" name="csv" value="{tr}Export as CSV{/tr}">
            </div>
        </form>
    </div>
{/if}
