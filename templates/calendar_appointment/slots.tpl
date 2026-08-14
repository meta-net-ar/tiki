{extends $global_extend_layout|default:$layout_name}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <p>{$description}</p>
    <div class="row">
        <div class="col-sm-6">
            <p>{tr}Select a date{/tr}</p>
            <select name="date" class="form-control appointment-date-selector">
                <option value=""></option>
                {foreach from=$dates item=date}
                    <option value="{$date}">{$date}</option>
                {/foreach}
            </select>
            <p>{tr _0=$timezone}Timezone: %0{/tr}</p>
        </div>
        <div class="col-sm-6">
            <p>{tr}Select a slot{/tr}</p>
            {foreach from=$dates item=date}
                <div class="slot-container date{$date}" style="display: none">
                    {foreach from=$slots item=slot}
                        {if $slot.start|tiki_date_format:'%Y-%m-%d' eq $date}
                            {if $slot.free}
                                <a href="{bootstrap_modal controller=calendar action=edit_item prefill_start=$slot.start prefill_end=$slot.end prefill_title=$title defaultCalendarId=$calendarId target_user=$target_user size="modal-lg"}" class="btn btn-sm btn-primary mb-2">{$slot.start|tiki_date_format:'%H:%M'} - {$slot.end|tiki_date_format:'%H:%M'}</a>
                            {else}
                                <input type="button" class="btn btn-sm btn-secondary mb-2" value="{$slot.start|tiki_date_format:'%H:%M'} - {$slot.end|tiki_date_format:'%H:%M'}">
                            {/if}
                        {/if}
                    {/foreach}
                </div>
            {/foreach}
        </div>
    </div>
    {if $target_user eq $user && ! $embed}
    <p>{tr}You can embed this widget in external sites using the following code:{/tr} <a href="#" onclick="$(this).parent().next().show(); return false" role="button">show</a></p>
    <textarea name="embed" class="form-control" style="display: none">&lt;iframe src="{mailurl}{service controller=calendar_appointment action=slots user=$target_user uid=$uid embed=1}{/mailurl}" style="width: 100%; height: 400px; border: 0px;"&gt;&lt;/iframe&gt;</textarea>
    {/if}
{/block}
