{strip}
{tikimodule error=$module_params.error title=$tpl_module_title name="groups_emulation" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

    {if isset($allGroups) && $showallgroups eq 'y'}
        <fieldset>
            <legend id='mge-all-legend' style="cursor: pointer;">
                {icon name='caret-right' _class='mge-icon'} <strong>{tr}All Groups{/tr}</strong>
                <span class="badge rounded-pill bg-secondary fw-normal" style="font-size: 0.7em; vertical-align: middle;">{$allGroups|@count}</span>
            </legend>
            <ul id='mge-all' >
            {foreach from=$allGroups key=groupname item=inclusion name=ix}
                <li>{$groupname|escape}</li>
            {/foreach}
            </ul >
        </fieldset>
    {/if}

    {if $showyourgroups eq 'y'}
        <fieldset>
            <legend id='mge-mine-legend' style="cursor: pointer;">
                {icon name='caret-right' _class='mge-icon'} <strong>{tr}Your Groups{/tr}</strong>
                <span class="badge rounded-pill bg-secondary fw-normal" style="font-size: 0.7em; vertical-align: middle;">{$userGroups|@count}</span>
            </legend>
            <ul id='mge-mine' >
            {foreach from=$userGroups key=groupname item=inclusion name=ix}
                {if $inclusion eq 'included'}
                    <li><i>{$groupname|escape}</i></li>
                {else}
                    <li>{$groupname|escape}</li>
                {/if}
            {/foreach}
            </ul >
        </fieldset>
    {/if}

    {if $groups_are_emulated eq 'y'}
        <fieldset>
            <legend id='mge-emulated-legend' style="cursor: pointer;">
                {icon name='caret-right' _class='mge-icon'} <strong>{tr}Emulating:{/tr}</strong>
                <span class="badge bg-info text-dark">{$groups_emulated_requested|@implode:", "|escape}</span>
            </legend>
            <ul id='mge-emulated' >
            {foreach from=$groups_emulated item=groupname}
                <li>
                    {if $groups_emulated_requested and $groupname|in_array:$groups_emulated_requested}
                        <strong>{$groupname|escape}</strong> <small class="text-muted">({tr}selected{/tr})</small>
                    {else}
                        <span class="text-muted"><i>{$groupname|escape}</i> <small>({tr}inherited{/tr})</small></span>
                    {/if}
                </li>
            {/foreach}
            </ul>
            <form method="get" action="tiki-emulate_groups_switch.php" target="_self">
                <div style="text-align: center"><button type="submit" class="btn btn-primary btn-sm" name="emulategroups" value="resetgroups">{tr}Reset{/tr}</button></div>
            </form>
        </fieldset>
    {/if}

    <form method="get" action="tiki-emulate_groups_switch.php" target="_self">
        <fieldset>
            <legend><strong>{tr}Switch to Groups{/tr}</strong></legend>
            <select name="switchgroups[]" size="{$module_rows}" multiple="multiple" class="form-select table" required="required" id="mge-select-groups">
                {foreach from=$chooseGroups key=groupname item=inclusion name=ix}
                    <option value="{$groupname|escape}" >{$groupname|escape}</option>
                {/foreach}
            </select>
            <div class="text-center"><button type="submit" class="btn btn-primary" name="emulategroups" value="setgroups" id="mge-simulate-btn">{tr}Simulate{/tr}</button></div>
        </fieldset>
    </form>

{/tikimodule}
{/strip}
