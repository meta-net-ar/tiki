<div class="table-responsive">
    <table class="table normal table-striped table-hover">
        <tr>
            <th><a href="{$myurl}?sort_mode={if $sort_mode eq 'start_desc'}start_asc{else}start_desc{/if}">{tr}Start{/tr}</a></th>
            <th><a href="{$myurl}?sort_mode={if $sort_mode eq 'end_desc'}end_asc{else}end_desc{/if}">{tr}End{/tr}</a></th>
            <th><a href="{$myurl}?sort_mode={if $sort_mode eq 'name_desc'}name_asc{else}name_desc{/if}">{tr}Name{/tr}</a></th>
            <th></th>
        </tr>

        {foreach from=$listevents item=event}
            {assign var=calendarId value=$event.calendarId}
            <tr class="{cycle}{if $event.start <= $smarty.now and $event.end >= $smarty.now} selected{/if} vevent">
                <td class="date">
                    <div class="row">
                        <div class="dtstart col-sm-7 text-nowrap" title="{$event.start|tiki_short_date:'n'}">
                            {$event.start|tiki_short_date:'n'}
                        </div>
                        <div class="dtstart-time col-sm-5 text-end text-nowrap">
                            {if !empty($event.allday)}{tr}All day{/tr}{else}{$event.start|tiki_short_time}{/if}
                        </div>
                    </div>
                </td>
                <td class="date">
                    <div class="row">
                        {if $event.start|tiki_short_date:'n' ne $event.end|tiki_short_date:'n'}
                            <div class="dtend col-sm-7 text-nowrap" title="{$event.end|tiki_short_date:'n'}">
                                {$event.end|tiki_short_date:'n'}
                            </div>
                        {/if}
                        <div class="dtstart-time col-sm-5 text-end text-nowrap">
                            {if $event.start ne $event.end and $event.allday ne 1}{$event.end|tiki_short_time}{/if}
                        </div>
                    </div>
                </td>
                <td style="word-wrap:break-word; {if isset($infocals.$calendarId.custombgcolor) && $infocals.$calendarId.custombgcolor ne ''}background-color:#{$infocals.$calendarId.custombgcolor};{/if}">
                    <a href="{bootstrap_modal controller='calendar' action='view_item' size='modal-lg' calitemId=$event.calitemId}" title="{tr}View{/tr}">
                    {if isset($infocals.$calendarId.customfgcolor) && $infocals.$calendarId.customfgcolor ne ''}<span style="color:#{$infocals.$calendarId.customfgcolor};">{/if}
                    <span class="summary">{$event.name|escape}</span></a><br>
                    <span class="description" style="font-style:italic">{$event.parsed}</span>
                    {if !empty($event.web)}
                        <br><a href="{$event.web}" target="_other" class="calweb" title="{$event.web}">{icon name='link-external'}</a>
                        {if isset($infocals.$calendarId.customfgcolor) && $infocals.$calendarId.customfgcolor ne ''}</span>{/if}
                    {/if}
                </td>
                <td class="action">
                    {if $event.perms && $event.perms->change_events}
                        {actions}
                            {strip}
                                <action>
                                    <a href="{bootstrap_modal controller='calendar' action=$prefs.calendar_event_click_action size='modal-lg' calitemId=$event.calitemId}">
                                        {icon name='edit' _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                    </a>
                                </action>
                                <action>
                                    <a class="text-danger" href="#"  onclick="deletecalItem({$event.calitemId}, '{$event.name|escape}')">
                                        {icon name='remove' _menu_text='y' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                    </a>
                                </action>
                            {/strip}
                        {/actions}
                    {/if}
                </td>
            </tr>
        {foreachelse}
            {norecords _colspan=4}
        {/foreach}
    </table>
</div>
