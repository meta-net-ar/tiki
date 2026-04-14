{button _class="{$class}" _style="{$style}" href="#" _onclick="toggle('{$filterCal}');return false;" _text='{tr}Calendars{/tr}' _icon_name='eye' _type='info'}
<div class="my-2">
    <form class="card filtercal d-none" id="{$filterCal}" method="get" action="{$returnURL}" name="f">
        <div class="card-header caltitle py-2 px-3 d-flex justify-content-between align-items-center">
            <strong class="mb-0">{tr}Calendars{/tr}</strong>
            <button type="button" class="btn-close" onclick="toggle('{$filterCal}')" aria-label="Close"></button>
        </div>
        <div class="card-body p-sm-1">
            <ul class="list-group list-group-flush list-unstyled mt-2">
                <li class="form-check small">
                    {select_all checkbox_names='calIds[]' label="{tr}Check / Uncheck All{/tr}"}
                </li>
                {foreach $calendars as $calendarId => $calendar}
                    <li class="calcheckbox form-check">
                        <input type="checkbox" class="form-check-input" name="calIds[]" value="{$calendarId|escape}" id="groupcal_{$calendarId}"
                               {if in_array($calendarId, $displayedcals)}checked="checked"{/if}>
                        <label for="groupcal_{$calendarId}" class="calId{$calendarId} form-check-label small">{$calendar.name|escape} ({tr}Id #{$calendarId}{/tr})</label>
                    </li>
                {/foreach}
                <li class="calinput small">
                    <input type="hidden" name="todate" value="{$focusdate}">
                    <input type="submit" class="btn btn-primary btn-sm" name="refresh" value="{tr}Refresh{/tr}">
                </li>
            </ul>
        </div>
    </form>  
</div>
