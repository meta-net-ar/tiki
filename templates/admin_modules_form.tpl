{* include file for module edit form - to be called by ajax *}
{if ! empty($assign_name)}
    {ticket}
{/if}
<div class="module_selector mb-3">
    <label for="assign_name">{tr}Module Name{/tr}</label>
    <select id="assign_name" name="assign_name" class="form-control">
        <option value="" disabled selected>{tr}Select Module{/tr}</option>
        {foreach key=name item=info from=$all_modules_info}
            <option value="{$name|escape}" {if $assign_name eq $name || $assign_selected eq $name}selected="selected"{/if}>{$info.name}</option>
        {/foreach}
    </select>
    {if isset($assign_info)}<div class="description form-text">{$assign_info.description}{if isset($assign_info.documentation)} {help url=$assign_info.documentation}{/if}</div>{/if}
</div>
<div id="module_params">
{if !empty($assign_name)}
    {tabset name="module_params{$assign_name}" toggle="n" cookietab=1}
        {tab name="{tr}Basic{/tr}" key=basic}
            <fieldset id="param_section_basic">
                <div class="clearfix mb-3">
                    <label for="assign_position">{tr}Position{/tr}</label>
                    <select id="assign_position" name="assign_position" class="form-control">
                        {foreach from=$module_zone_list key=code item=zone}
                            <option value="{$code|escape}"{if $code eq $assign_position} selected="selected"{/if}>{$zone.name|escape}</option>
                        {/foreach}
                    </select>
                </div>

                <div class="clearfix mb-3">
                    <label for="assign_order">{tr}Order{/tr}</label>
                    <select id="assign_order" name="assign_order" class="form-control">
                        {section name=ix loop=$orders}
                            <option value="{$orders[ix]|escape}" {if $assign_order eq $orders[ix]}selected="selected"{/if}>{$orders[ix]}</option>
                        {/section}
                    </select>
                </div>

                <div class="clearfix mb-3">
                    <label for="assign_cache">{tr}Cache Time{/tr} ({tr}secs{/tr})</label>
                    <input type="text" id="assign_cache" name="assign_cache" class="form-control" value="{$assign_cache|escape}">
                </div>
                {if !isset($assign_info.type) or $assign_info.type neq 'function'}
                    <div class="clearfix mb-3">
                        <label for="assign_rows">{tr}Rows{/tr}</label>
                        <input type="text" id="assign_rows" name="assign_rows" value="{$assign_rows|escape}" class="form-control">
                    </div>
                {/if}
                <div class="admin2cols adminoptionbox clearfix">
                    <label class="d-block min-w-25" for="groups">{tr}Groups{/tr}</label>
                    <select multiple="multiple" id="groups" name="groups[]" class="form-control mb-4 resize-vertical" style="width:100%;">
                        {section name=ix loop=$groups}
                            <option value="{$groups[ix].groupName|escape}" {if $groups[ix].selected eq 'y'}selected="selected"{/if}>{$groups[ix].groupName|escape}</option>
                        {/section}
                    </select>
                    {if $prefs.elementplus_select ne 'y'}
                        {remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use Ctrl+Click to select multiple options{/tr}{/remarksbox}
                    {/if}
                    {if $prefs.modallgroups eq 'y'}
                        <div class="card"><div class="card-body">
                            {icon name="information" style="vertical-align:middle;float:left"} {tr}The{/tr} <a class="rbox-link" href="tiki-admin.php?page=module">{tr}Display Modules to All Groups{/tr}</a> {tr}setting will override your selection of specific groups.{/tr}
                        </div></div>
                        <br>
                    {/if}
                </div>
                {if $prefs.user_assigned_modules eq 'y'}
                    <div class="admin2cols adminoptionbox clearfix">
                        {tr}Visibility{/tr}
                            <select name="assign_type">
                                <option value="D" {if $assign_type eq 'D'}selected="selected"{/if}>
                                    {tr}Displayed now for all eligible users even with personal assigned modules{/tr}
                                </option>
                                <option value="d" {if $assign_type eq 'd'}selected="selected"{/if}>
                                    {tr}Displayed for the eligible users with no personal assigned modules{/tr}
                                </option>
                                <option value="P" {if $assign_type eq 'P'}selected="selected"{/if}>
                                    {tr}Displayed now, can't be unassigned{/tr}
                                </option>
                                <option value="h" {if $assign_type eq 'h'}selected="selected"{/if}>
                                    {tr}Not displayed until a user chooses it{/tr}
                                </option>
                            </select>
                            <div class="card"><div class="card-body">
                                {icon name="information" style="vertical-align:middle;float:left;"}{tr}Because <a class="rbox-link" href="tiki-admin.php?page=module">Users can Configure Modules</a>, select either{/tr} &quot;{tr}Displayed now for all eligible users even with personal assigned modules{/tr}&quot; {tr}or{/tr} &quot;{tr}Displayed now, can't be unassigned{/tr}&quot; {tr}to make sure users will notice any newly assigned modules.{/tr}
                            </div></div>
                        </div>
                    {/if}
                </fieldset>
            {/tab}
            {if isset($assign_info.params) and !empty($assign_info.params)}
                {foreach from=$assign_info.params key=sect item=params}
                    {tab name="{$sect|capitalize}" key="{$sect}"}
                        <fieldset id="param_section_{$sect}">
                            {foreach from=$params key=name item=param}
                                <div class="admin2cols adminoptionbox clearfix">
                                    <label class="d-block min-w-25" for="assign_params[{$name|escape}]">{$param.name|escape}{if !empty($param.required)} <span class="attention">({tr}required{/tr})</span>{/if}</label>
                                    {if isset($name) and $name eq 'id' and $assign_info.name eq 'Menu'}
                                        <select id="assign_params[{$name|escape}]" name="assign_params[{$name|escape}]" class="form-control" aria-label="{tr}Select Menu{/tr}">
                                            <option value="" disabled {if !isset($param.value)}selected{/if}>{tr}Select Menu{/tr}</option>
                                            {foreach from=$channels item=channel}
                                                {$menuId=$channel.menuId}
                                                {$truncatedDescription=$channel.description|truncate:30:"..."}
                                                <option value="{$menuId|escape}" {if isset($param.value) && $param.value eq $menuId}selected="selected"{/if}>
                                                    {$channel.name|escape} ({$menuId|escape}){if $channel.description} - {$truncatedDescription|escape}{/if}
                                                </option>
                                            {/foreach}
                                        </select>
                                    {elseif $param.type eq 'textarea'}
                                        <textarea type="text" id="assign_params[{$name|escape}]" name="assign_params[{$name|escape}]"{if !empty($param.filter)} class="{$param.filter} form-control"{else} class="{$param.filter} form-control"{/if}>{$param.value|escape}</textarea>
                                    {elseif $param.type eq 'select' or ! empty($param.options)}
                                        <select id="assign_params[{$name|escape}]" name="assign_params[{$name|escape}]{if isset($param.separator)}[]{/if}" class="form-control" {if isset($param.separator)}multiple="multiple"{/if}>
                                            {foreach from=$param.options item=option}
                                                <option value="{$option.value|escape}" {if (is_array($param.value) and in_array($option.value, $param.value)) or $param.value eq $option.value}selected="selected"{/if}>{$option.text|escape}</option>
                                            {/foreach}
                                        </select>
                                    {else}
                                        <input type="text" id="assign_params[{$name|escape}]" name="assign_params[{$name|escape}]" value="{$param.value|escape}"{if !empty($param.filter)} class="{$param.filter} form-control"{else} class="{$param.filter} form-control"{/if}>
                                    {/if}
                                    <div class="description mb-3">
                                        {$param.description}
                                        {if !empty($param.default)} - {tr}Default:{/tr} {$param.default|escape}{/if}
                                    </div>
                                </div>
                            {/foreach}
                        </fieldset>
                    {/tab}
                {/foreach}
                {autocomplete element=".pagename" type="pagename" options="multiple: true, multipleSeparator:';'"}
            {else}
                <div class="admin2cols adminoptionbox clearfix">
                    <a title="{tr}Parameters{/tr}" {popup text="{tr}Params: specific params to the module and/or general params ('lang', 'flip', 'title', 'decorations', 'section', 'overflow', 'page', 'nobox', 'bgcolor', 'color', 'theme', 'notitle', 'nopage'). Separator between params:'&amp;'. E.g. maxlen=15&amp;nonums=y.{/tr}" width=200 center=true}>
                        <label for="assign_params">{tr}Parameters{/tr}</label>
                    </a>
                    <textarea id="assign_params" name="assign_params"s="1" cols="60" class="form-control">{$assign_params|escape}</textarea>
                    {help url="Module-Settings-Parameters" desc="{tr}Enter the parameters in URL format, e.g. 'nobox=y&class=rbox-data'{/tr}"}
                    {self_link um_edit=$assign_name cookietab="2" _anchor="editcreate"}{tr}Edit custom module{/tr} {icon name="next"}{/self_link}
                </div>
            {/if}
        <div class="d-flex justify-content-center submit-container mt-2">
            {button edit_assign=0 cookietab=2 _type="secondary" _class="me-2" href="#assign" _auto_args="edit_assign,cookietab" _text="{tr}Preview{/tr}"}
            <input type="submit" class="btn btn-primary" name="assign" value="{tr}Assign{/tr}" onclick="needToConfirm=false;">
        </div>
    {/tabset}
{/if}

</div>

<script type="module">
    import "@tiki/modules/search";
</script>
