{tikimodule error=$module_params.error title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {strip}
    {if $prefs.feature_siteloc eq 'y' and $prefs.feature_breadcrumbs eq 'y'}
        <nav id="sitelocbar" aria-label="breadcrumb">
            {if !empty($module_params.label) and not $crumbs_all_hidden}{tr}{$module_params.label|escape:"html"}{/tr} {/if}
            <ol class="breadcrumb"{if $prefs.site_crumb_seper} style="--bs-breadcrumb-divider: '{$prefs.site_crumb_seper|escape:'quotes'}'"{/if}>
            {if $trail}
                {breadcrumbs type="trail" loc="site" crumbs=$trail showLinks=$module_params.showLinks|default:null}
            {else}
                <li class="breadcrumb-item pe-2">
                    <a title="{tr}{$crumbs[0]->description}{/tr}" href="{$crumbs[0]->url}" accesskey="1">{tr}{$crumbs[0]->title}{/tr}</a>
                </li>
                {if $structure eq 'y'}
                    {section loop=$structure_path name=ix}
                        <li class="breadcrumb-item pe-2">
                            {if $structure_path[ix].pageName ne $page or $structure_path[ix].page_alias ne $page_info.page_alias}
                                <a href="tiki-index.php?page_ref_id={$structure_path[ix].page_ref_id}">
                                    {if $structure_path[ix].page_alias}
                                        {$structure_path[ix].page_alias}
                                    {else}
                                        {$structure_path[ix].pageName}
                                    {/if}
                                </a>
                            {else}
                                {if $structure_path[ix].page_alias}
                                    {$structure_path[ix].page_alias}
                                {else}
                                    {$structure_path[ix].pageName}
                                {/if}
                            {/if}
                        </li>
                    {/section}
                {elseif $module_params.showLast eq 'y'}
                    {if $page ne ''}
                        <li class="breadcrumb-item pe-2">{$page|escape}</li>
                    {elseif $title ne ''}
                        <li class="breadcrumb-item pe-2">{$title}</li>
                    {elseif $thread_info.title ne ''}
                        <li class="breadcrumb-item pe-2">{$thread_info.title}</li>
                    {elseif $forum_info.name ne ''}
                        <li class="breadcrumb-item pe-2">{$forum_info.name}</li>
                    {/if}
                {/if}
            {/if}
            </ol>
        </nav>{* bar with location indicator *}
        {if $trail}
            {breadcrumbs type="desc" loc="site" crumbs=$trail}
        {else}
            {breadcrumbs type="desc" loc="site" crumbs=$crumbs}
        {/if}
    {/if}
    {/strip}
{/tikimodule}
