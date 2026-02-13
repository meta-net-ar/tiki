{tikimodule error=$module_params.error title=$tpl_module_title name=$tpl_module_name flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
    {strip}
    {if $prefs.feature_siteloc eq 'y' and $prefs.feature_breadcrumbs eq 'y'}
        <nav id="sitelocbar" aria-label="breadcrumb">
            {if !empty($module_params.label) and not $crumbs_all_hidden}{tr}{$module_params.label|escape:"html"}{/tr} {/if}
            <ol class="breadcrumb"{if $prefs.site_crumb_seper} style="--bs-breadcrumb-divider: '{$prefs.site_crumb_seper|escape:'quotes'}'"{/if}>
                {if $trail}
                    {breadcrumbs type="trail" loc="site" crumbs=$trail showLinks=$module_params.showLinks|default:null}
                {else}
                    {if !empty($crumbs[0])}
                        <li class="breadcrumb-item">
                            <a title="{tr}{$crumbs[0]->description}{/tr}" href="{$crumbs[0]->url}" accesskey="1">{tr}{$crumbs[0]->title}{/tr}</a>
                        </li>
                    {/if}
                    {if $structure eq 'y'}
                        {section name=ix loop=$structure_path}
                            {if $smarty.section.ix.last}
                                <li class="breadcrumb-item active" aria-current="page">
                                    {if $structure_path[ix].page_alias}
                                        {$structure_path[ix].page_alias}
                                    {else}
                                        {$structure_path[ix].pageName}
                                    {/if}
                                </li>
                            {else}
                                <li class="breadcrumb-item">
                                    <a href="tiki-index.php?page_ref_id={$structure_path[ix].page_ref_id}">
                                        {if $structure_path[ix].page_alias}
                                            {$structure_path[ix].page_alias}
                                        {else}
                                            {$structure_path[ix].pageName}
                                        {/if}
                                    </a>
                                </li>
                            {/if}
                        {/section}
                    {elseif $module_params.showLast eq 'y'}
                        {if $page ne ''}
                            <li class="breadcrumb-item active" aria-current="page">{$page|escape}</li>
                        {elseif $title ne ''}
                            <li class="breadcrumb-item active" aria-current="page">{$title}</li>
                        {elseif $thread_info.title ne ''}
                            <li class="breadcrumb-item active" aria-current="page">{$thread_info.title}</li>
                        {elseif $forum_info.name ne ''}
                            <li class="breadcrumb-item active" aria-current="page">{$forum_info.name}</li>
                        {/if}
                    {/if}
                {/if}
            </ol>
        </nav>
    {/if}
    {/strip}
{/tikimodule}
