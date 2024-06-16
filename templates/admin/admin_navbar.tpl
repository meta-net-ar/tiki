<nav class="navbar-expand-md navbar-{$prefs.theme_navbar_color_variant_admin} bg-{$prefs.theme_navbar_color_variant_admin} tiki-topbar-nav-{$prefs.theme_navbar_color_variant_admin} tiki-admin-top-nav-{$prefs.theme_navbar_color_variant_admin} admin-navbar mb-4 border-0 shadow-none" role="navigation">
    {if $prefs.theme_unified_admin_backend eq 'y'}
        <a class="navbar-brand" href="./" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{tr}Back to the home page{/tr}">
            {if $prefs.sitelogo_icon && $prefs.sitelogo_icon neq 'img/tiki/tikilogo_icon.png'}
                <img src="{$prefs.sitelogo_icon}" alt="{if !empty($prefs.sitelogo_alt)}{$prefs.sitelogo_alt|escape}{else}{tr}Site logo{/tr}{/if}">
            {else}
                <img src="img/tiki/tiki-icon-flat-{if $navbar_color_variant eq 'light'}black{else}white{/if}.png" alt="{if !empty($prefs.sitelogo_alt)}{$prefs.sitelogo_alt|escape}{else}{tr}Tiki icon{/tr}{/if}" height="24">
            {/if}
        </a>
    {/if}
    {if $prefs.theme_unified_admin_backend neq 'y'}
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#admin-navbar-collapse-1" aria-controls="admin-navbar-collapse-1" aria-expanded="false" aria-label="{tr}Toggle navigation{/tr}">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse justify-content-between" id="admin-navbar-collapse-1">
        {* form copied to include anchors.tpl *}
       <form method="post" class="form g-3 align-items-center" role="form"{* style="width: 15rem;"*}> {* Specified width in rem so larger fonts wouldn't cause wrapping -- This width was overridden in the stylesheet so removed (6/8/2023) *}
            {* <div class="col-auto form-check">
                {ticket}
                <input type="checkbox" id="preffilter-toggle-1" class="preffilter-toggle preffilter-toggle-round form-check-input {$pref_filters.advanced.type|escape}" value="advanced"{if !empty($pref_filters.advanced.selected)} checked="checked"{/if}>
                <label for="preffilter-toggle-1" class="form-check-label"></label>
            </div> *}

            <ul class="col-auto nav navbar-nav filter-menu">
                <li class="nav-item dropdown me-0" style="padding-top: 6px;">
                    <a href="#" class="nav-link dropdown-toggle pe-0 me-2 py-0" data-bs-toggle="dropdown" title="{tr}Settings{/tr}"{* style="width: 48px;" Causes wrapping in large font sizes. *}>
                        {icon name="filter"} {tr}Preference Filters{/tr}
                    </a>
                    <ul class="dropdown-menu {if $prefs.theme_navbar_color_variant_admin eq 'dark'} dropdown-menu-dark{/if} border" role="menu">
                        <li class="dropdown-item d-none">
                            <span class="dropdown-header">{tr}Preference Filters{/tr}</span>
                            <input type="hidden" name="pref_filters[]" value="basic">
                        </li>
                        {foreach from=$pref_filters key=name item=info}
                            <li class="dropdown-item">
                                <div class="form-check justify-content-start form-switch">
                                    <label>
                                        <input type="checkbox" class="form-check-input preffilter {$info.type|escape} input-pref_filters" name="pref_filters[]" value="{$name|escape}"{if !empty($info.selected)} checked="checked"{/if}{if $name eq basic} disabled="disabled"{/if}>{$info.label|escape}
                                    </label>
                                </div>
                            </li>
                        {/foreach}
                        <li class="dropdown-item d-none" id="preffilter-loader">
                            <i class="fa fa-spinner fa-spin text-white"></i> <span class="text-white">{tr}Changing default preferences...{/tr}</span>
                        </li>
                        {if $prefs.connect_feature eq "y"}
                            {capture name=likeicon}{icon name="thumbs-up"}{/capture}
                            <div class="form-check">
                                <label class="form-check-label">
                                    <input type="checkbox" id="connect_feedback_cbx" class="form-check-input"{if !empty($connect_feedback_showing)} checked="checked"{/if}>
                                    {tr}Provide Feedback{/tr}
                                    <a href="https://doc.tiki.org/Connect" target="tikihelp" class="tikihelp" title="{tr}Provide Feedback:{/tr}
                                        {tr}Once selected, some icon/s will be shown next to all features so that you can provide some on-site feedback about them{/tr}.
                                        <br/><br/>
                                        <ul>
                                            <li>{tr}Icon for 'Like'{/tr} {$smarty.capture.likeicon|escape}</li>
                                            {* <li>{tr}Icon for 'Fix me'{/tr} <img src=img/icons/connect_fix.png></li>
                                            <li>{tr}Icon for 'What is this for?'{/tr} <img src=img/icons/connect_wtf.png></li>  *}
                                        </ul>
                                        <br>
                                        {tr}Your votes will be sent when you connect with mother.tiki.org (currently only by clicking the 'Connect > <strong>Send Info</strong>' button){/tr}
                                        <br/><br/>
                                        {tr}Click to read more{/tr}
                                    ">
                                        {icon name="help"}
                                    </a>
                                </label>
                            </div>
                            {$headerlib->add_jsfile("lib/jquery_tiki/tiki-connect.js")}
                        {/if}
                    </ul>
                </li>
            </ul>
        </form>

        {include file="admin/admin_navbar_menu.tpl"}
        {if $prefs.theme_unified_admin_backend neq 'y'}
            <ul class="navbar-nav flex-row d-md-flex me-4">
                <li class="nav-item">
                    <form method="post" class="d-flex flex-row flex-wrap align-items-center my-2 my-md-0 ms-auto" role="form">
                        <div class="mx-0">
                            <input type="hidden" name="filters">
                            <div class="input-group">
                                <input type="text" name="lm_criteria" value="{$lm_criteria|escape}" class="form-control form-control-sm" placeholder="{tr}Search preferences{/tr}...">
                                <button type="submit" class="btn btn-info btn-sm"{if $indexNeedsRebuilding} class="tips" title="{tr}Configuration search{/tr}|{tr}Note: The search index needs rebuilding, this will take a few minutes.{/tr}"{/if}>{icon name="search"}</button>
                            </div>
                        </div>
                    </form>
                </li>
            </ul>
        {/if}
    </div>
    {/if}
    {if $include != "list_sections" and $prefs.theme_unified_admin_backend neq 'y'}
        <div class="adminanchors card"><div class="{*card-body*}p-3 navbar-{$navbar_color_variant} bg-{$navbar_color_variant} tiki-nav-{$navbar_color_variant}"><ul class="nav navbar-nav d-flex flex-wrap justify-content-between" style="gap: 0 1rem;">{include file='admin/include_anchors.tpl'}</ul></div></div>
    {/if}
</nav>

{if $lm_searchresults}
    <div class="alert alert-secondary alert-dismissible pe-3" id="pref_searchresults">
        <button type="button" class="btn-close mt-3" aria-hidden="true" data-bs-dismiss="alert"></button>
            <h3 class="alert-heading">{tr}Preference Search Results{/tr}</h3>
        <form method="post" href="tiki-admin.php" class="px-4" role="form">
            <div class="pref_search_results">
                {foreach from=$lm_searchresults item=prefName}
                    {preference name=$prefName get_pages='y' visible='always'}
                {/foreach}
            </div>
            <div class="text-center">
                <input class="btn btn-primary" type="submit" title="{tr}Apply Changes{/tr}" value="{tr}Apply{/tr}">
            </div>
            <input type="hidden" name="lm_criteria" value="{$lm_criteria|escape}">
            {ticket}
        </form>
    </div>
{elseif $lm_criteria}
    {remarksbox type="note" title="{tr}No results{/tr}" icon="magnifier"}
        {tr}No preferences were found for your search query.{/tr}<br>
        {tr _0='<a class="alert-link" href="tiki-admin.php?prefrebuild">' _1='</a>'}Not what you expected? Try %0rebuilding%1 the preferences search index.{/tr}
    {/remarksbox}
{/if}
