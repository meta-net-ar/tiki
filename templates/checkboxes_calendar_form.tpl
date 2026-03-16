{button _class="{$class}" _style="{$style}" href="#" _onclick="toggle('{$filterCal}');return false;" _text='{tr}Calendars{/tr}' _icon_name='eye' _type='info'}
<div class="d-inline-block">
    <form class="card" id="{$filterCal}" class="filtercal" method="get" action="{$returnURL}" name="f" style="display:none;">
        <div class="card-header caltitle py-1 px-2">
            <strong>{tr}Calendars{/tr}</strong>
            <button type="button" class="btn-close float-end"  onclick="toggle('{$filterCal}')" aria-hidden="true"></button>
        </div>
        <ul class="list-group list-group-flush list-unstyled mt-2">
            <li class="caltoggle">
                {select_all checkbox_names='calIds[]' label="{tr}Check / Uncheck All{/tr}"}
            </li>
            {foreach $calendars as $calendarId => $calendar}
                <li class="calcheckbox">
                    <input type="checkbox" name="calIds[]" value="{$calendarId|escape}" id="groupcal_{$calendarId}"
                        {if in_array($calendarId, $displayedcals)}checked="checked"{/if}>
                    <label for="groupcal_{$calendarId}" class="calId{$calendarId}">{$calendar.name|escape} ({tr}Id #{$calendarId}{/tr})</label>
                </li>
            {/foreach}
            <li class="calinput">
                <input type="hidden" name="todate" value="{$focusdate}">
                <input type="submit" class="btn btn-primary btn-sm" name="refresh" value="{tr}Refresh{/tr}">
            </li>
        </ul>
    </form>  
</div>
