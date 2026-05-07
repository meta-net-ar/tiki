<div class="dropdown d-inline-block {$class}" style="{$style}">
    <button class="btn btn-info dropdown-toggle" type="button" id="{$filterCal}_toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
        {icon name='eye'} {tr}Calendars{/tr}
    </button>
    <div class="dropdown-menu p-0" id="{$filterCal}" aria-labelledby="{$filterCal}_toggle">
        <form class="filtercal" method="get" action="{$returnURL}" name="f">
            <h6 class="dropdown-header caltitle">{tr}Calendars{/tr}</h6>
            <div class="dropdown-divider"></div>
            <div class="px-3 py-1 caltoggle">
                {select_all checkbox_names='calIds[]' label="{tr}Check / Uncheck All{/tr}"}
            </div>
            <div class="dropdown-divider"></div>
            {foreach $calendars as $calendarId => $calendar}
                <div class="dropdown-item calcheckbox">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="calIds[]" value="{$calendarId|escape}" id="groupcal_{$calendarId}"
                            {if in_array($calendarId, $displayedcals)}checked="checked"{/if}>
                        <label class="form-check-label calId{$calendarId}" for="groupcal_{$calendarId}">{$calendar.name|escape} ({tr _0=$calendarId}Id #%0{/tr})</label>
                    </div>
                </div>
            {/foreach}
            <div class="dropdown-divider"></div>
            <div class="px-3 py-2 calinput">
                <input type="hidden" name="todate" value="{$focusdate}">
                <button type="submit" class="btn btn-primary btn-sm w-100" name="refresh" value="{tr}Refresh{/tr}">{tr}Refresh{/tr}</button>
            </div>
        </form>
    </div>
</div>
