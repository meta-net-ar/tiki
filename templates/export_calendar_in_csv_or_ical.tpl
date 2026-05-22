{if $tiki_p_view_events eq 'y' and $prefs.calendar_export eq 'y'}
    <div class="dropdown d-inline-block" style="{$style}">
        <button class="btn btn-info dropdown-toggle" type="button" id="{$exportCal}_toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            {icon name='export'} {tr}Export{/tr}
        </button>
        <div class="dropdown-menu p-0" id="{$exportCal}" aria-labelledby="{$exportCal}_toggle">
            <form method="post" action="tiki-calendar_export_ical.php" name="f">
                <input type="hidden" name="export" value="y">
                <h6 class="dropdown-header caltitle">{tr}Export calendars{/tr}</h6>
                <div class="dropdown-divider"></div>
                <div class="px-3 py-1 caltoggle">
                    {select_all checkbox_names='calendarIds[]' label="{tr}Check / Uncheck All{/tr}"}
                </div>
                <div class="dropdown-divider"></div>
                {foreach $calendars as $calendarId => $calendar}
                    <div class="dropdown-item calcheckbox">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="calendarIds[]" value="{$calendarId|escape}" id="groupexcal_{$calendarId}"
                                {if in_array($calendarId, $displayedcals)}checked="checked"{/if}>
                            <label class="form-check-label calId{$calendarId}" for="groupexcal_{$calendarId}">{$calendar.name|escape} ({tr _0=$calendarId}Id #%0{/tr})</label>
                        </div>
                    </div>
                {/foreach}
                <div class="dropdown-divider"></div>
                <div class="px-3 py-1">
                    <a href="{$iCalAdvParamsUrl}">{tr}advanced parameters{/tr}</a>
                </div>
                <div class="dropdown-divider"></div>
                <div class="px-3 py-2 calinput d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm flex-fill" name="ical" value="{tr}Export as iCal{/tr}">{tr}Export as iCal{/tr}</button>
                    <button type="submit" class="btn btn-primary btn-sm flex-fill" name="csv" value="{tr}Export as CSV{/tr}">{tr}Export as CSV{/tr}</button>
                </div>
            </form>
        </div>
    </div>
{/if}
