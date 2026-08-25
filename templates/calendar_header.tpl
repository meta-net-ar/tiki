<div class="d-flex align-items-center gap-2">
    {* avoid Add Event being shown if no calendar is displayed *}
    {if $tiki_p_add_events eq 'y'}
        <a {if $isInMainCalendar neq 'y'} style="display:none;"{/if} href="{bootstrap_modal controller='calendar' action='edit_item' size='modal-lg' defaultCalendarId=$defaultCalendarId returnURL=$returnURL}" class="btn btn-primary">{icon name='create'} {tr}Add Event{/tr}</a>
    {/if}

    {if $tiki_p_admin_calendar eq 'y' or $tiki_p_admin eq 'y'}
        {if $displayedcals|@count eq 1}
            {if $displayedcals[0]|substr:0:1 eq 's'}
                {button href="tiki-admin_calendars.php?subscriptionId={$displayedcals[0]|substr:1}&cookietab=3" _type="link" _text="{tr}Edit{/tr}" _icon_name="edit"}
            {else}
                {button href="tiki-admin_calendars.php?calendarId={$displayedcals[0]}&cookietab=2" _type="link" _text="{tr}Edit{/tr}" _icon_name="edit"}
            {/if}
        {/if}
    {/if}
    {if $tiki_p_admin_calendar eq 'y' or $tiki_p_admin eq 'y' or $tiki_p_admin_private_calendar eq 'y'}
        {button href="tiki-admin_calendars.php?cookietab=1" _type="link" _text="{tr}Admin{/tr}" _icon_name="admin"}
    {/if}

    {if $viewlist eq 'list'}
        {capture name=href}?viewlist=table{if !empty($smarty.request.todate)}&amp;todate={$smarty.request.todate}{/if}{/capture}
        {if $isInMainCalendar eq 'y'}
            {button _style="display: block;" _class="mt-2" href=$smarty.capture.href _text='{tr}Calendar View{/tr}' _icon_name='calendar' _type='info'}
        {else}
            {button _class="mt-2" href=$smarty.capture.href _text='{tr}Calendar View{/tr}' _icon_name='calendar' _type='info'}
        {/if}
    {elseif $viewlist eq 'listEventView'}
        {button _class="mt-2" href='tiki-calendar.php' _text='{tr}List View{/tr}' _icon_name='list' _type='info'}
    {else}
        {capture name=href}?viewlist=list{if !empty($smarty.request.todate)}&amp;todate={$smarty.request.todate}{/if}{/capture}
        
        {if $isInMainCalendar neq 'y'}
            {button _class="mt-2" href=$smarty.capture.href _text='{tr}List View{/tr}' _icon_name='list' _type='info'} 
        {/if}
    {/if}
</div>
