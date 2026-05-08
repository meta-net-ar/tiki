{if $tiki_p_view_events eq 'y' and $prefs.calendar_export eq 'y'}
    {button href="#" _onclick="toggle('{$exportCal}');return false;" _text='{tr}Export{/tr}' _icon_name='export' _type='info' _style="{$style}"}
    <div class="my-2">
        <form id="{$exportCal}" class="card p-sm-1 d-none" method="post" action="tiki-calendar_export_ical.php" name="f">
            <input type="hidden" name="export" value="y">
            <div class="card-header caltitle py-2 px-3 d-flex justify-content-between align-items-center">
                <strong class="mb-0">{tr}Export calendars{/tr}</strong>
                <button type="button" class="btn-close" onclick="toggle('{$exportCal}')" aria-label="Close"></button>
            </div>
            <div class="card-body p-sm-1">
                <ul class="list-unstyled ps-1 mb-1">
                    <li class="form-check small">
                        {select_all checkbox_names='calendarIds[]' label="{tr}Check/Uncheck All{/tr}"}
                    </li>
                    {foreach $calendars as $calendarId => $calendar}
                        <li class="form-check">
                            <input type="checkbox"
                                   name="calendarIds[]"
                                   value="{$calendarId|escape}"
                                   class="form-check-input"
                                   id="groupexcal_{$calendarId}"
                                   {if in_array($calendarId, $displayedcals)}checked="checked"{/if}>

                            <label for="groupexcal_{$calendarId}" class="calId{$calendarId} form-check-label small">
                                {$calendar.name|escape} ({tr}Id #{$calendarId}{/tr})
                            </label>
                        </li>
                    {/foreach}
                </ul>
                <div class="calcheckbox">
                    <a href="{$iCalAdvParamsUrl}" class="small border border-primary rounded p-sm-1">{tr}advanced parameters{/tr}</a>
                </div>
                <div class="d-flex flex-column gap-2 flex-wrap small">
                    <span class="small text-muted">{tr}Export as{/tr}</span>
                    <div>
                        <input type="submit"
                               class="btn btn-primary btn-sm"
                               name="ical"
                               value="iCal">

                        <input type="submit"
                               class="btn btn-primary btn-sm"
                               name="csv"
                               value="CSV">
                    </div>
                </div>
            </div>
        </form>
    </div>
{/if}
