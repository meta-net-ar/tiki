<div class="form-contents">
    <input type="hidden" name="uid" value="{$definition.uid|escape}">
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}Summary{/tr}</label>
        <div class="col-sm-9">
            <input type="text" name="summary" value="{$definition.summary|escape}" class="form-control">
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}Description{/tr}</label>
        <div class="col-sm-9">
            <textarea name="description" class="form-control">{$definition.description|escape}</textarea>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}Period Start - End{/tr}</label>
        <div class="col-sm-9">
            {jscalendar date=$definition.dtstart fieldname="dtstart" showtime='y' endfieldname="dtend" enddate=$definition.dtend timezone=$displayTimezone}
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}Duration{/tr}</label>
        <div class="col-sm-9">
            <input type="text" name="duration" value="{$definition.duration|escape}" class="form-control">
            <div class="description">{tr}Example format: P7W for 7 weeks, P15DT5H for 15 days, 5 hours. Only enter if no end time specified.{/tr}</div>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}Appointment Slot Calendar{/tr}</label>
        <div class="col-sm-9">
            <select name="calendarId" class="form-control">
                <option value=""></option>
                {foreach from=$calendars item=calendar}
                    <option value="{$calendar.calendarId}" {if $definition.calendarId eq $calendar.calendarId}selected{/if}>{$calendar.name|escape}</option>
                {/foreach}
            </select>
            <div class="description">{tr}Choose calendar to store the upcoming appointments if this is an appointment slot availability block.{/tr}</div>
        </div>
    </div>
    <div class="mb-3 row">
        <label class="col-form-label col-sm-3">{tr}Availability Blocks{/tr}</label>
        <div class="col-sm-9">
            {foreach from=$definition.available item=available}
                <div class="availability-block" data-uid="{$available.uid|escape}">
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-3">{tr}UID{/tr}</label>
                        <div class="col-sm-9">
                            {$available.uid} <a href="#" class="btn btn-sm btn-danger availability-remove" role="button">{icon name=delete _menu_text='n' _menu_icon='y' alt="{tr}Delete{/tr}"}</a>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-3">{tr}Summary{/tr}</label>
                        <div class="col-sm-9">
                            <input type="text" name="available[summary][{$available.uid}]" value="{$available.summary|escape}" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-3">{tr}Description{/tr}</label>
                        <div class="col-sm-9">
                            <textarea name="available[description][{$available.uid}]" class="form-control">{$available.description|escape}</textarea>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-3">{tr}Period Start - End{/tr}</label>
                        <div class="col-sm-9">
                            {jscalendar date=$available.dtstart fieldname="available[dtstart][{$available.uid}]" showtime='y' endfieldname="available[dtend][{$available.uid}]" enddate=$available.dtend timezone=$displayTimezone}
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-3">{tr}Duration{/tr}</label>
                        <div class="col-sm-9">
                            <input type="text" name="available[duration][{$available.uid}]" value="{$available.duration|escape}" class="form-control">
                            <div class="description">{tr}Example format: P7W for 7 weeks, P15DT5H for 15 days, 5 hours. Only enter if no end time specified.{/tr}</div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-3">{tr}Appointment Slot Duration{/tr}</label>
                        <div class="col-sm-9">
                            <input type="text" name="available[slots][{$available.uid}]" value="{$available.slots|escape}" class="form-control">
                            <div class="description">{tr}Choose slot duration in minutes if this is an appointment slot availability block.{/tr}</div>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label class="col-form-label col-sm-3">{tr}Recurrence Rule{/tr}</label>
                        <div class="col-sm-9">
                            <input type="hidden" name="available[rrule][{$available.uid}]" value="{$available.rrule_string|escape}">
                            <a href="{bootstrap_modal controller=calendar_availability action=rrule rrule=$available.rrule_string start=$available.dtstart uid=$available.uid size='modal-lg'}" data-base-href="{service controller=calendar_availability action=rrule uid=$available.uid modal=1}" class="editable_rrule">{$available.rrule|escape}</a>
                        </div>
                    </div>
                </div>
            {/foreach}
            <a href="{service controller=calendar_availability action=new_availability}" class="btn btn-link availability-new" role="button">{icon name=create} {tr}New{/tr}</a>
        </div>
    </div>
</div>
