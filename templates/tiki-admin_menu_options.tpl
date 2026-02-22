{title help="Menus" url="tiki-admin_menu_options.php?menuId=$menuId" admpage="general&amp;cookietab=3"}{tr}Menu:{/tr} {$editable_menu_info.name}{/title}

<div class="t_navbar mb-4">
    <a class="btn btn-link" href="tiki-admin_menus.php">
        {icon name="list"} {tr}List Menus{/tr}
    </a>
    {if $tiki_p_edit_menu eq 'y'}
        <a class="btn btn-primary" href="{bootstrap_modal controller=menu action=edit_option menuId=$menuId}">
            {icon name="create"} {tr}Create menu option{/tr}
        </a>
        <a class="btn btn-primary" href="{bootstrap_modal controller=menu action=edit menuId=$menuId}">
            {icon name="edit"} {tr}Edit This Menu{/tr}
        </a>
        <a class="btn btn-primary" href="{service controller=menu action=export_menu_options menuId=$menuId}" title="{tr}Export menu options{/tr}">
            {icon name="export"} {tr}Export{/tr}
        </a>
        <a class="btn btn-primary no-ajax" href="{bootstrap_modal controller=menu action=import_menu_options menuId=$menuId}" title="{tr}Import menu options{/tr}">
            {icon name="import"} {tr}Import{/tr}
        </a>
    {/if}
</div>

{tabset name="admin_menu_options"}
{tab name="{tr}Manage menu{/tr} {$editable_menu_info.name}"}
    <div>
        <h2>{tr}Menu options{/tr} <span class="badge bg-secondary">{$pages_count}</span></h2>
        {include file='tiki-admin_menu_options_insert_component.tpl'}
        <form method="get" action="tiki-admin_menu_options.php">
            {ticket}
            <input type="hidden" name="find" value="{$find|escape}">
            <input type="hidden" name="sort_mode" value="{$sort_mode|escape}">
            <input type="hidden" name="menuId" value="{$menuId}">
            <input type="hidden" name="offset" value="{$offset}">

            <div class="options-container">
                <ol id="options" class="p-2">
                    {$prevpos = 0}
                    {foreach $options as $option}
                        <li class="p-2 {if in_array($option.url, $no_editable_page)} opacity-25 {/if}" style="{if in_array($option.url, $no_editable_page)} pointer-events: none; {/if}" id="node_{$option.optionId}" data-id="{$option.optionId}" data-position="{$option.position}" data-parent="{$option.parent}" data-type="{$option.type}">
                            <div class="options--group-container">
                                {if !empty($option.name)}
                                    {capture assign='tooltip'}{strip}
                                        {if $editable_menu_info.parse eq 'y'}
                                            {wiki}{$option.name}{/wiki}
                                        {else}
                                            {$option.name|escape}
                                        {/if}
                                        |
                                        <dl>
                                            {if !empty($option.url)}
                                                <dt>{tr}URL:{/tr}</dt>
                                                <dd>{$option.canonic|truncate:40:' ...'|escape}</dd>
                                            {/if}
                                            {if !empty($option.section)}
                                                <dt>{tr}Sections:{/tr}</dt>
                                                <dd>{$option.section}</dd>
                                            {/if}
                                            {if !empty($option.perm)}
                                                <dt>{tr}Permissions:{/tr}</dt>
                                                <dd>{$option.perm}</dd>
                                            {/if}
                                            {if !empty($option.groupname)}
                                                <dt>{tr}Groups:{/tr}</dt>
                                                <dd>{$option.groupname|escape}</dd>
                                            {/if}
                                            {if !empty($option.class)}
                                                <dt>{tr}Class:{/tr}</dt>
                                                <dd>{$option.class|escape}</dd>
                                            {/if}

                                            {if $prefs.feature_userlevels eq 'y' and not empty($option.userlevel)}
                                                {$it=$option.userlevel}
                                                <dt>{tr}User Level:{/tr}</dt>
                                                <dd>{$prefs.userlevels.$it}</dd>
                                            {/if}
                                            {if $prefs.menus_items_icons eq 'y' and $option.icon}
                                                <dt>{tr}Icon:{/tr}</dt>
                                                <dd>
                                                    {if $prefs.theme_iconset eq 'legacy'}
                                                        {icon _id=$option.icon _defaultdir=$prefs.menus_items_icons_path}
                                                    {else}
                                                        {icon name=$option.icon|replace:'48x48':''}{* remove size for legacy menu 42 icons *}
                                                    {/if}
                                                    &nbsp;
                                                    {$option.icon|escape}
                                                </dd>
                                            {/if}
                                        </dl>
                                    {/strip}{/capture}
                                {else}
                                    {$tooltip = "|{tr}separator{/tr}"}
                                {/if}

                                {if $editable_menu_info.use_items_icons eq 'y'}
                                    <div class="label-group input-group-sm">
                                        <input name="menu-icon" class="field-icon form-control" placeholder="Icon" value="{$option.icon}" id="icon-{$option.name}" />
                                        {autocomplete type="icon" element="#icon-{$option.name}"}
                                    </div>
                                {/if}
                                <div class="label-group">
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">{icon name='sort'}</span>
                                        {if $option.type eq '-'}
                                            <div class="form-control d-flex align-items-center" style="background-color: #f8f9fa; padding: 0.5rem;">
                                                <hr class="menu-separator">
                                            </div>
                                        {else}
                                            <input type="text" class="field-label form-control" value="{$option.name|escape}" placeholder="{tr}Label{/tr}">
                                        {/if}
                                        <span class="tips input-group-text option-edit" title="|{tr}Check this if the option is an alternative to the previous one.{/tr}">
                                                <input type="checkbox" class="samepos"{if $option.position eq $prevpos} checked="checked"{/if}>
                                            {$prevpos = $option.position}
                                            </span>
                                            <a href="{bootstrap_modal controller=menu action=edit_option menuId=$menuId optionId=$option.optionId}" class="tips input-group-text" title='{$tooltip|escape}'>
                                                {icon name='info'}
                                            </a>
                                    </div>
                                </div>
                                <div class="flex-grow-1 url-group">
                                    <div class="input-group input-group-sm">
                                        <a href="{$option.sefurl|escape}" class="input-group-text tips confirm" title="|{tr}Test URL{/tr}">
                                            {icon name='link'}
                                        </a>
                                        <input type="text" class="field-url form-control" value="{$option.canonic|escape}" placeholder="{tr}URL or ((page name)){/tr}">
                                        <a href="{bootstrap_modal controller=menu action=edit_option menuId=$menuId optionId=$option.optionId}" class="tips input-group-text option-edit confirm" title="|{tr}Details{/tr}">
                                                {icon name='edit' _menu_icon='y' alt="{tr}Details{/tr}"}
                                            </a>
                                            <a href="#" class="tips input-group-text text-danger option-remove" title="|{tr}Remove Option{/tr}" role="button">
                                                {icon name='remove' _menu_icon='y' alt="{tr}Remove{/tr}"}
                                            </a>
                                    </div>
                                </div>
                            </div>
                            <ol class="child-options"></ol>
                        </li>
                    {/foreach}
                    {capture name='options'}select:function(event,ui){ldelim}ui.item.value='(('+ui.item.value+'))';{rdelim}{/capture}
                    {autocomplete element='.field-url' type='pagename' options=$smarty.capture.options}
                </ol>
            </div>
            <input type="hidden" id="deletemenu" name="deletemenu">
        </form>
        {include file='tiki-admin_menu_options_insert_component.tpl'}
    </div>
{/tab}
{tab name="{tr}Preview and Deploy{/tr}"}
    <h2>{tr}Preview menu{/tr}</h2>


            <form action="{service controller='menu' action='preview'}" class="d-flex flex-row flex-wrap align-items-end preview mb-4">
                <input type="hidden" name="menuId" value="{$menuId}">
                <div class="col-6 col-sm-3">
                    <label for="preview_type" class="col-form-label me-2">{tr}Type:{/tr}</label>
                    <select id="preview_type" class="form-control" name="preview_type">
                        <option value="vert"{if $preview_type eq 'vert'} selected{/if}>{tr}Vertical{/tr}</option>
                        <option value="horiz"{if $preview_type eq 'horiz'} selected{/if}>{tr}Horizontal{/tr}</option>
                    </select>
                </div>
                <div class="col-6 col-sm-4 ps-2">
                    <div class="form-check">
                        <input type="checkbox" id="preview_bootstrap" class="form-check-input" name="preview_bootstrap"{if $preview_bootstrap eq 'y'} checked="checked"{/if}>
                        <label for="preview_bootstrap" class="form-check-label">
                            Bootstrap
                        </label>
                    </div>
                </div>
                <div class="col-6 col-sm-3 pe-2">
                    <label for="preview_position" class="col-form-label">
                        {tr}Position:{/tr}
                    </label>
                    <select id="preview_position" class="form-control">
                        {foreach from=$module_zone_list key=code item=zone}
                            <option value="{$code|escape}">{$zone.name|escape}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-6 col-sm-2">
                    {button _text='{tr}Deploy{/tr}' _class='deploy_menu btn btn-primary' _type='primary' _ajax='n'}
                </div>
            </form>


    <div class="preview-menu">
        &nbsp;
    </div>
{/tab}
{/tabset}
