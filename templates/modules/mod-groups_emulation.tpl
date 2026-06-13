{strip}
{if $user and $user|lower neq 'anonymous'}
{tikimodule error=$module_params.error title=$tpl_module_title name="groups_emulation" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}

    <style>
        .mge-collapse-icon {
            display: inline-block;
            transition: transform 0.35s ease;
            transform: rotate(90deg);
        }

        .collapsed .mge-collapse-icon {
            transform: rotate(0deg);
        }
    </style>

    {if isset($allGroups) && $showallgroups eq 'y'}
        <div>
            <button class="btn btn-link px-0 py-1 text-start text-decoration-none w-100 d-flex align-items-center gap-1 collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mge-all-{$moduleId}"
                    aria-expanded="false"
                    aria-controls="mge-all-{$moduleId}">
                {icon name='caret-right' iclass='mge-collapse-icon'}
                <strong>{tr}All Groups{/tr}</strong>
                &nbsp;<span class="badge rounded-pill bg-secondary fw-normal" style="font-size: 0.7em;">{$allGroups|@count}</span>
            </button>
            <div class="collapse" id="mge-all-{$moduleId}">
                <ul class="mt-1">
                {foreach from=$allGroups key=groupname item=inclusion name=ix}
                    <li>{$groupname|escape}</li>
                {/foreach}
                </ul>
            </div>
        </div>
    {/if}

    {if $showyourgroups eq 'y'}
        <div>
            <button class="btn btn-link px-0 py-1 text-start text-decoration-none w-100 d-flex align-items-center gap-1 collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mge-mine-{$moduleId}"
                    aria-expanded="false"
                    aria-controls="mge-mine-{$moduleId}">
                {icon name='caret-right' iclass='mge-collapse-icon'}
                <strong>{tr}Your Groups{/tr}</strong>
                &nbsp;<span class="badge rounded-pill bg-secondary fw-normal" style="font-size: 0.7em;">{$userGroups|@count}</span>
            </button>
            <div class="collapse" id="mge-mine-{$moduleId}">
                <ul class="mt-1">
                {foreach from=$userGroups key=groupname item=inclusion name=ix}
                    {if $inclusion eq 'included'}
                        <li><i>{$groupname|escape}</i></li>
                    {else}
                        <li>{$groupname|escape}</li>
                    {/if}
                {/foreach}
                </ul>
            </div>
        </div>
    {/if}

    {if $groups_are_emulated eq 'y'}
        <div>
            <button class="btn btn-link px-0 py-1 text-start text-decoration-none w-100 d-flex align-items-center gap-1 collapsed"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#mge-emulated-{$moduleId}"
                    aria-expanded="false"
                    aria-controls="mge-emulated-{$moduleId}">
                {icon name='caret-right' iclass='mge-collapse-icon'}
                <strong>{tr}Emulating{/tr}</strong>
            </button>
            <div class="collapse" id="mge-emulated-{$moduleId}">
                <ul class="mt-1">
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
                    <div class="text-center mt-1"><button type="submit" class="btn btn-primary btn-sm" name="emulategroups" value="resetgroups">{tr}Reset{/tr}</button></div>
                </form>
            </div>
        </div>
    {/if}

    {if $chooseGroups|@count > 0}
    <form method="get" action="tiki-emulate_groups_switch.php" target="_self" onsubmit="return !!document.getElementById('mge-select-groups-{$moduleId}').value;">
        <fieldset>
            <legend><strong>{tr}Switch to Groups{/tr}</strong></legend>
            <select name="switchgroups[]" size="{$module_rows}" multiple="multiple" class="form-select table" id="mge-select-groups-{$moduleId}">
                {foreach from=$chooseGroups key=groupname item=inclusion name=ix}
                    <option value="{$groupname|escape}">{$groupname|escape}</option>
                {/foreach}
            </select>
            <div class="text-center mt-2"><button type="submit" class="btn btn-primary" name="emulategroups" value="setgroups" id="mge-simulate-btn-{$moduleId}">{tr}Simulate{/tr}</button></div>
        </fieldset>
    </form>
    {/if}

{/tikimodule}
{else}
<span class="d-none"></span>
{/if}
{/strip}
