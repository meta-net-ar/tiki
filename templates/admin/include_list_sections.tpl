{remarksbox type="tip" title="{tr}Tip{/tr}"}
    {tr}Enable/disable Tiki features in {/tr}<a class="alert-link" href="tiki-admin.php?page=features">{tr}Control Panels{/tr}&nbsp;{$prefs.site_crumb_seper}&nbsp;{tr}Features{/tr}</a>{tr}, but configure them elsewhere{/tr}.
    <br/>
    {capture assign='filterIcon'}{icon name="filter"}{/capture}
    {tr _0="<strong>" _1="</strong>" _2="<a class='alert-link' target='tikihelp' href='https://doc.tiki.org/Preference-Filters'>" _3='</a>' _4=$filterIcon}See %0more options%1 after you enable more %2Preference Filters%3 above (%4){/tr}.
{/remarksbox}

{if $show_system_configuration_warning}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
    {tr _0="<strong>.ini</strong>" _1="<strong>.ini.php</strong>" _2='<strong><a href="https://doc.tiki.org/System-Configuration">https://doc.tiki.org/System-Configuration</a></strong>'}Tiki detected system configuration files with %0 extension, under the root folder of Tiki. It is recommended to change it to %1. Check %2 for examples.{/tr}
    {/remarksbox}
{/if}

{if $config_file_errors}
    {remarksbox type="warning" title="{tr}Warning{/tr}"}
        {tr _0='<strong><a href="https://doc.tiki.org/System-Configuration">https://doc.tiki.org/System-Configuration</a></strong>'}Possible error(s) in your system configuration file. Please check the doc %0 for the right syntax to use.{/tr}<br>
        <ul>
            {foreach from=$config_file_errors item=value}
                <li>{tr}Error in .ini file near{/tr} <strong>{$value|escape}</strong></li>
            {/foreach}
        </ul>
    {/remarksbox}
{/if}

{if $prefs.theme_unified_admin_backend eq 'y'}
    {modulelist zone='admin' id='admin_modules' class='mb-3 d-flex flex-wrap justify-content-between admin_modules'}

    <a href="tiki-admin.php?ticket={ticket mode=get}&profile=Unified_Admin_Backend_Default_Dashboard_1&show_details_for=Unified_Admin_Backend_Default_Dashboard_1&repository=http%3a%2f%2fprofiles.tiki.org%2fprofiles&page=profiles&preloadlist=y&list=List#step2" target="_blank" class="btn btn-secondary mb-3">
        {tr}Add default modules{/tr}
    </a>

    {include file='admin/version_check.tpl'}
{else}
    <div class="d-flex align-content-start flex-wrap">
        {foreach from=$admin_icons key=page item=info}
                {if !empty($info.disabled)}
                    {$class="admbox advanced btn btn-primary disabled"}
                {else}
                    {$class="admbox basic btn btn-primary"}
                {/if}
                    {* TODO: Buttons are forced to be squares, not fluid. Labels which exceed 2 lines will be cut. *}
                    <a href="{if !empty($info.url)}{$info.url}{else}tiki-admin.php?page={$page}{/if}" data-alt="{$info.title} {$info.description}" class="{$class} tips bottom slow {if !empty($info.disabled)}disabled-clickable{/if}" title="{$info.title|escape}{if !empty($info.disabled)} ({tr}Disabled{/tr}){/if}|{$info.description}">
                        {icon name="admin_$page"}
                        <span class="title">{$info.title|escape}</span>
                    </a>
        {/foreach}
    </div>
{/if}
