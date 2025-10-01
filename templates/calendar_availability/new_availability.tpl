{extends $global_extend_layout|default:'internal/layout_view.tpl'}

{block name="content"}
    <div class="availability-block" data-uid="{$uid|escape}">
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">{tr}UID{/tr}</label>
            <div class="col-sm-9">
                {$uid} <a href="#" class="btn btn-sm btn-danger availability-remove" role="button">{icon name=delete _menu_text='n' _menu_icon='y' alt="{tr}Delete{/tr}"}</a>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">{tr}Summary{/tr}</label>
            <div class="col-sm-9">
                <input type="text" name="available[summary][{$uid}]" value="" class="form-control">
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">{tr}Description{/tr}</label>
            <div class="col-sm-9">
                <textarea name="available[description][{$uid}]" class="form-control"></textarea>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">{tr}Period Start - End{/tr}</label>
            <div class="col-sm-9">
                {jscalendar fieldname="available[dtstart][{$uid}]" showtime='y' endfieldname="available[dtend][{$uid}]" timezone=$displayTimezone}
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">{tr}Duration{/tr}</label>
            <div class="col-sm-9">
                <input type="text" name="available[duration][{$uid}]" value="" class="form-control">
                <div class="description">{tr}Example format: P7W for 7 weeks, P15DT5H for 15 days, 5 hours. Only enter if no end time specified.{/tr}</div>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">{tr}Appointment Slot Duration{/tr}</label>
            <div class="col-sm-9">
                <input type="text" name="available[slots][{$uid}]" value="" class="form-control">
                <div class="description">{tr}Choose slot duration in minutes if this is an appointment slot availability block.{/tr}</div>
            </div>
        </div>
        <div class="mb-3 row">
            <label class="col-form-label col-sm-3">{tr}Recurrence Rule{/tr}</label>
            <div class="col-sm-9">
                <input type="hidden" name="available[rrule][{$uid}]" value="FREQ=DAILY">
                <a href="{bootstrap_modal controller=calendar_availability action=rrule uid=$uid size='modal-lg'}" data-base-href="{service controller=calendar_availability action=rrule uid=$uid modal=1}" class="editable_rrule">FREQ=DAILY</a>
            </div>
        </div>
    </div>
{/block}
