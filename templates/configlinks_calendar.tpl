<div id="configlinks" class="mb-3 d-flex flex-wrap justify-content-start">
    {if count($checkedCalIds)}
        {$maxCalsForButton = 20}
        {if count($checkedCalIds) > $maxCalsForButton}<select size="5">{/if}
        {foreach $checkedCalIds as $checkedCalId}
            {if $calendars}
                {$thiscustombgcolor = $calendars[$checkedCalId].custombgcolor}
                {$thiscustomfgcolor = $calendars[$checkedCalId].customfgcolor}
                {$thiscalendarsname = $calendars[$checkedCalId].displayName|escape}
                {if count($checkedCalIds) > $maxCalsForButton}
                    <option style="background:#{$thiscustombgcolor};color:#{$thiscustomfgcolor};" onclick="toggle('{$filterCal}')">
                        {$thiscalendarsname}
                    </option>
                {else}
                    {assign var="url" value="{$checkedCalId|sefurl:'calendar'}"}
                    {if $isInMainCalendar neq 'y'}
                        {assign var="url" value=$returnURL|cat:(strpos($returnURL,'?')!==false ? '&' : '?')|cat:"calIds[]={$checkedCalId}"}
                    {/if}
                    {button href="{$url}" _style="border-radius:16px;background:#$thiscustombgcolor;color:#$thiscustomfgcolor;border:1px solid #$thiscustomfgcolor; {$style}" _text="{$thiscalendarsname}" _class='btn btn-sm me-2 mt-2' _icon_name='calendar'}
                {/if}
            {/if}
        {/foreach}
        {if count($checkedCalIds) > $maxCalsForButton}</select>{/if}
    {/if}
</div>
