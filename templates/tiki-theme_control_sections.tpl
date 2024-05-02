{title help="Theme Control"}{tr}Theme Control:{/tr} {tr}Sections{/tr}{/title}
<div class="t_navbar btn-group">
    {button href="tiki-theme_control.php" class="btn btn-primary" _text="{tr}Control by Categories{/tr}"}
    {button href="tiki-theme_control_objects.php" class="btn btn-primary" _text="{tr}Control by Objects{/tr}"}
</div>
<h2 class="my-3">{tr}Assign themes to sections{/tr}</h2>
<form action="tiki-theme_control_sections.php" method="post" class="row gy-2 gx-3 align-items-center mb-4" role="form">
    {ticket}
    <div class="col-auto">
        <label for="section">{tr}Section{/tr}</label>
        <select name="section" id="section" class="form-select">
            {foreach key=sec item=ix from=$sections}
                <option value="{$sec|escape}" {if $a_section eq $sec}selected="selected"{/if}>{$sec}</option>
            {/foreach}
        </select>
    </div>
    <div class="col-auto">
        <label for="theme">{tr}Theme{/tr}</label>
        <select name="theme" id="theme" class="form-select">
            {foreach from=$themes key=theme item=theme_name}
                <option value="{$theme|escape}">{$theme_name}</option>
            {/foreach}
        </select>
    </div>
    <div class="col-auto pt-4">
        <input type="submit" class="btn btn-primary" name="assign" value="{tr}Assign{/tr}">
    </div>
</form>

<h2>{tr}Assigned sections{/tr}</h2>
<form action="tiki-theme_control_sections.php" method="post" role="form" class="form">
    {ticket}
    <div class="table-responsive">
        <table class="table">
            <tr>
                <th>
                    <button type="submit" class="btn btn-outline-danger btn-sm p-0 tips" name="delete" title="{tr}Delete selected{/tr}" aria-label="{tr}Delete selected{/tr}">{icon name="delete"}</button>
                </th>
                <th>
                    <a href="tiki-theme_control_sections.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'section_desc'}section_asc{else}section_desc{/if}">
                        {tr}Section{/tr}
                    </a>
                </th>
                <th>
                    <a href="tiki-theme_control_sections.php?offset={$offset}&amp;sort_mode={if $sort_mode eq 'theme_desc'}theme_asc{else}theme_desc{/if}">
                        {tr}Theme{/tr}
                    </a>
                </th>
            </tr>
            {section name=user loop=$channels}
                <tr>
                    <td class="checkbox-cell">
                        <input type="checkbox" class="form-check-input" aria-label="{tr}Select{/tr}" name="sec[{$channels[user].section}]">
                    </td>
                    <td class="text">
                        {$channels[user].section}
                    </td>
                    <td class="text">
                        {$channels[user].theme}
                    </td>
                </tr>
            {/section}
        </table>
    </div>
</form>
