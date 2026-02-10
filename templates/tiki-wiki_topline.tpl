<div class="wikitopline d-flex justify-content-between w-100">
    <div class="content mb-2">
        {if !isset($hide_page_header) or !$hide_page_header}
            <div class="wikiinfo">
                {if $prefs.wiki_page_name_above eq 'y' and $print_page ne 'y'}
                    <a href="tiki-index.php?page={$page|escape:"url"}" class="titletop" title="{tr}refresh{/tr}">{$page|escape}</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{* The hard-coded spaces help selecting the page name for inclusion in a wiki link *}
                {/if}

                {if $prefs.feature_wiki_pageid eq 'y' and $print_page ne 'y'}
                    <small><a class="link" href="tiki-index.php?page_id={$page_id}">{tr}Page id:{/tr} {$page_id}</a></small>
                {/if}

                <div class="description page-description">{breadcrumbs type="desc" loc="page" crumbs=$crumbs}</div>

                {if $cached_page eq 'y'}<span class="cachedStatus">({tr}Cached{/tr})</span>{/if}
                {if $is_categorized eq 'y' and $prefs.feature_categories eq 'y' and $prefs.feature_categorypath eq 'y' and $tiki_p_view_category eq 'y'}
                    {$display_catpath}
                {/if}
            </div>
        {/if} {*hide_page_header*}
    </div> {* div.content *}
     {*   {if $pdf_export eq 'y'} *}
            <div class="wikiinfo" id="pdfinfo" style="display:none">
                <div class="alert alert-info mx-2" style="min-width: 380px;"><h4><span class="icon icon-information fas fa-info-circle fa-fw "></span>&nbsp;<span class="rboxtitle alert-heading">{tr}Please wait{/tr}</span></h4><div class="rboxcontent" style="display: inline"><span class="fas fa-circle-notch fa-spin me-2" style="font-size:24px"></span>{tr}The PDF is being prepared....{/tr}</div></div>
            </div>
    {*    {/if} *}


{if !isset($versioned) and $print_page ne 'y'}
  {*  <div class="wikiactions_wrapper clearfix"> *}
    {strip}
    {if $show_wiki_actions}
        <div class="wikiactions d-flex justify-content-end mb-2 align-self-end">
            <div class="btn-group ms-2" style="min-width: 6rem;">
                {* Show language dropdown only if there is more than 1 language or user has right to edit *}
                {if ($tiki_p_admin eq 'y' or $tiki_p_admin_wiki eq 'y' or $tiki_p_edit eq 'y' or $tiki_p_edit_inline eq 'y') or (isset($translationsCount) and $translationsCount gt 1)}
                    {if $prefs.feature_multilingual eq 'y' && $prefs.show_available_translations eq 'y' && $machine_translate_to_lang eq '' }
                        {*span class="btn-i18n" *}
                        {include file='translated-lang.tpl' object_type='wiki page'}
                        {*/span *}
                    {/if}
                {/if}

                {* if we want a ShareThis icon and we want it displayed prominently *}
                {if $prefs.feature_wiki_sharethis eq "y" and $prefs.wiki_sharethis_encourage eq "y"}
                    {* Similar as in the blogs except there can be only one per page, so it is simpler *}
                    <div class="btn-group sharethis">
                        {literal}
                        <script type="text/javascript">
                            //Create your sharelet with desired properties and set button element to false
                            var object = SHARETHIS.addEntry({ title:'{/literal}{$page|escape:"url"}{literal}'}, {button:false});
                            //Output your customized button
                            document.write('<a class="btn btn-outline-secondary btn-sm opacity-75 tips" id="share" href="#"{/literal} title="{tr}ShareThis{/tr}" role="button">{icon name="sharethis"}{literal}</a>');
                            //Tie customized button to ShareThis button functionality.
                            var element = document.getElementById("share");
                            object.attachButton(element);
                        </script>
                        {/literal}
                    </div>
                {/if}

                {if $prefs.feature_backlinks eq 'y' and $backlinks|default:null and $tiki_p_view_backlink eq 'y'}
                    <div class="btn-group backlinks">
                        {if ! $js}<ul><li>{/if}
                        {if $backlinks|count eq 1}
                        <a href="#" role="button" data-bs-toggle="dropdown" class="btn btn-outline-secondary btn-sm opacity-75 dropdown-toggle" title="{tr}1 page is linked to this page{/tr}">
                        {elseif $backlinks|count gt 1}
                        <a href="#" role="button" data-bs-toggle="dropdown" class="btn btn-outline-secondary btn-sm opacity-75 dropdown-toggle" title="{tr _0=$backlinks|count}%0 pages are linked to this page{/tr}">
                        {/if}
                            {icon name="backlink"}
                            <span class="position-absolute top-100 start-0 translate-middle badge rounded-pill bg-secondary">{$backlinks|count}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" role="menu">
                            <h6 class="dropdown-header">
                                {tr}Backlinks{/tr}
                            </h6>
                            <div class="dropdown-divider"></div>
                            {section name=back loop=$backlinks}
                                {capture name=backlink_title}{object_title id=$backlinks[back].objectId type=$backlinks[back].type}{/capture}
                                {*<li role="presentation">*}
                                    <span class="dropdown-item" role="menuitem" tabindex="-1">
                                        {if $backlinks[back].type eq 'wiki page'}
                                            <span class="me-1">{icon name="notepad"}</span>
                                        {elseif $backlinks[back].type eq 'trackeritemfield'}
                                            <span class="me-1">{icon name="database"}</span>
                                        {/if}
                                        {if $prefs.wiki_backlinks_name_len ge '1'}
                                            {object_link id=$backlinks[back].objectId type=$backlinks[back].type title=$smarty.capture.backlink_title|truncate:$prefs.wiki_backlinks_name_len:"...":true}
                                        {else}
                                            {object_link id=$backlinks[back].objectId type=$backlinks[back].type title=$smarty.capture.backlink_title|truncate}
                                        {/if}
                                    </span>
                                {*</li>*}
                            {/section}
                        </div>
                        {if ! $js}</li></ul>{/if}
                    </div>
                {/if}
                {if $structure eq 'y' or ( $structure eq 'n' and count($showstructs) neq 0 )}
                    <div class="btn-group structures">
                        {if ! $js}<ul><li>{/if}
                        <a href="#" class="btn btn-outline-secondary btn-sm opacity-75 dropdown-toggle" data-bs-toggle="dropdown" title="{tr}Structures{/tr}" role="button">
                            {icon name="structure"}
                        </a>
                        <div class="dropdown-menu dropdown-menu-end" role="menu">
                            <h6 class="dropdown-header">
                                {tr}Structures{/tr}
                            </h6>
                            <div class="dropdown-divider"></div>
                            {*<li role="presentation">*}
                                {section name=struct loop=$showstructs}
                                    <a href="tiki-index.php?page={$page|escape:url}&amp;structure={$showstructs[struct].pageName|escape:url}" {if isset($structure_path[0].pageName) and $showstructs[struct].pageName eq $structure_path[0].pageName} title="Current structure: {$showstructs[struct].pageName|escape}" class="dropdown-item selected tips" {else} class="dropdown-item tips" title="{tr}Show structure:{/tr} {$showstructs[struct].pageName|escape}"{/if}>
                                        {if $showstructs[struct].page_alias}
                                            {$showstructs[struct].page_alias}
                                        {else}
                                            {$showstructs[struct].pageName|escape}
                                        {/if}
                                    </a>
                                {/section}
                            {*</li>*}
                            {if isset($structure_path[0].pageName) && isset($showstructs[struct].pageName) && $showstructs[struct].pageName neq $structure_path[0].pageName and $prefs.feature_wiki_open_as_structure neq 'y'}
                                <div role="presentation" class="dropdown-divider"></div>
                                {*<li role="presentation">*}
                                    <a href="tiki-index.php?page={$page|escape:url}" class="dropdown-item tips" title=":{tr}Hide structure bar and any toc{/tr}">
                                        {tr}Hide structure{/tr}
                                    </a>
                                {*</li>*}
                            {/if}
                        </div>
                        {if ! $js}</li></ul>{/if}
                    </div>
                {/if}

                {* all single-action icons under one dropdown*}
                {$hasPageAction="0"}
                {capture name="pageActions"}
                    {if ! $js}<ul><li>{/if}
                    <a class="btn btn-outline-secondary btn-sm opacity-75 dropdown-toggle" data-bs-toggle="dropdown" href="#"  title="{tr}Page actions{/tr}" role="button">
                        {icon name="menu-extra"}
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">
                            {tr}Page actions{/tr}
                        </h6>
                        <div class="dropdown-divider"></div>
                        {if $prefs.wiki_collapse_expand_all_headings eq 'y'}
                            <a class="dropdown-item" id="expandCollapseCollapsibleHeadings" href="#" data-action="expand">
                                {icon name="heading"} <span>{tr}Expand all collapsible headings{/tr}</span>
                            </a>
                        {/if}
                        {if $pdf_export eq 'y' && $pdf_warning eq 'n' && $prefs["feature_wiki_print"] eq 'y' && $tiki_p_export_pdf eq 'y'}
                            <a class="dropdown-item generate-pdf" href="tiki-print.php?{query _keepall='y' display="pdf" page=$page}">
                                {icon name="pdf"} {tr} PDF{/tr}
                                {$hasPageAction="1"}
                            </a>
                        {elseif $tiki_p_admin eq "y" and $pdf_warning eq 'y'}
                            <a href="tiki-admin.php?page=packages" target="_blank" class="dropdown-item text-danger" title="{tr}Warning:mPDF Package Missing{/tr}">
                                {icon name="warning"} {tr} PDF{/tr}
                                {$hasPageAction="1"}
                            </a>
                        {/if}
                        {if !($prefs.flaggedrev_approval neq 'y' or ! $revision_approval or $lastVersion eq $revision_displayed)}
                            {jq}
                                $(".editplugin, .icon_edit_section").hide();
                            {/jq}
                        {/if}
                        {if $prefs.flaggedrev_approval neq 'y' or ! $revision_approval or $lastVersion eq $revision_displayed}
                            {if $editable and ($tiki_p_edit eq 'y' or $page|lower eq 'sandbox') and $beingEdited ne 'y' and $machine_translate_to_lang eq ''}
                                <a class="dropdown-item" {ajax_href template="tiki-editpage.tpl"}tiki-editpage.php?page={$page|escape:"url"}{if !empty($page_ref_id) and (empty($needsStaging) or $needsStaging neq 'y')}&amp;page_ref_id={$page_ref_id}{/if}{/ajax_href}>
                                        {icon name="edit"} {tr}Edit{/tr}
                                        {$hasPageAction="1"}</a>
                                {if $prefs.wiki_edit_icons_toggle eq 'y' and ($prefs.wiki_edit_plugin eq 'y' or $prefs.wiki_edit_section eq 'y')}
                                    {jq}
                                        $("#wiki_plugin_edit_view").on("click", function () {
                                        var $icon = $("#wiki_plugin_edit_view");
                                        var $toggleOnIcon = '{{icon iclass="toggle-icon" name="toggle-on"}}';
                                        var $toggleOffIcon = '{{icon iclass="toggle-icon" name="toggle-off"}}';
                                        if (! $icon.hasClass("active highlight")) {
                                            $(".editplugin, .icon_edit_section").show();
                                            $icon.addClass("active highlight");
                                            setCookie("wiki_plugin_edit_view", true, "", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);
                                            $(".toggle-icon", this).replaceWith($toggleOnIcon);
                                        } else {
                                            $(".editplugin, .icon_edit_section").hide();
                                            $icon.removeClass("active highlight");
                                            deleteCookie("wiki_plugin_edit_view");
                                            $(".toggle-icon", this).replaceWith($toggleOffIcon);
                                        }
                                        return false;
                                        });
                                        if (!getCookie("wiki_plugin_edit_view")) {$(".editplugin, .icon_edit_section").hide(); } else { $("#wiki_plugin_edit_view").trigger("click"); }
                                    {/jq}
                                    <a class="dropdown-item" href="#" role="button" id="wiki_plugin_edit_view" title="{tr}Click to toggle on/off{/tr}">
                                        <span class="align-items-center text-with-toggle"><span class="text">{icon name='plugin' iclass="d-inline"} <span class="mx-1">{tr}Edit icons{/tr}</span> </span> {icon iclass="toggle-icon" name="toggle-off"}</span>
                                            {$hasPageAction="1"}
                                        </a>
                                {/if}
                            {/if}
                            {if ($tiki_p_edit eq 'y' or $tiki_p_edit_inline eq 'y' or $page|lower eq 'sandbox') and $beingEdited ne 'y' and $machine_translate_to_lang eq ''}
                                {if $prefs.wysiwyg_inline_editing eq 'y' and $prefs.feature_wysiwyg eq 'y'}
                                    <a class="dropdown-item" href="#" role="button" id="wysiwyg_inline_edit" title="{tr}Click to toggle on/off{/tr}">
                                            <span class="d-flex align-items-center text-with-toggle"><span class="text flex-fill me-3">{icon name='edit'} {tr}Inline edit{/tr} ({tr}Wysiwyg{/tr})</span> {icon iclass="toggle-icon" name="toggle-off"} {icon iclass="toggle-icon d-none" name="toggle-on"}</span>
                                            {$hasPageAction="1"}
                                    </a>
                                {/if}
                            {/if}
                        {/if}
                        {if $cached_page eq 'y'}
                            <a class="dropdown-item" href="{$page|sefurl:'wiki':'with_next'}refresh=1">
                                    {icon name="refresh"} {tr}Refresh{/tr}
                                    {$hasPageAction="1"}
                            </a>
                        {/if}
                        {if $prefs.feature_wiki_print eq 'y' and $tiki_p_print eq 'y'}
                            <a class="dropdown-item" href="tiki-print.php?{query _keepall='y' page=$page}">
                                    {icon name="print"} {tr}Print{/tr}
                                    {$hasPageAction="1"}
                            </a>
                        {/if}
                        {if $prefs.feature_share eq 'y' && $tiki_p_share eq 'y'}
                            <a class="dropdown-item" href="tiki-share.php?url={$smarty.server.REQUEST_URI|escape:'url'}">
                                    {icon name="share"} {tr}Share{/tr}
                                    {$hasPageAction="1"}
                            </a>
                        {/if}
                        {* if we want a ShareThis icon and we show it under the single-action icons dropdown singl-click *}
                        {if $prefs.feature_wiki_sharethis eq "y" and $prefs.wiki_sharethis_encourage neq "y"}
                            {* Similar as in the blogs except there can be only one per page, so it is simpler *}
                            {literal}
                                <script type="text/javascript">
                                    //Create your sharelet with desired properties and set button element to false
                                    var object = SHARETHIS.addEntry({ title:'{/literal}{$page|escape:"url"}{literal}'}, {button:false});
                                    //Output your customized button
                                    document.write('<a class="dropdown-item" id="share" role="button" href="#"{/literal} title="{tr}ShareThis{/tr}">{icon name="sharethis"} {tr}ShareThis{/tr}{literal}</a>');
                                    //Tie customized button to ShareThis button functionality.
                                    var element = document.getElementById("share");
                                    object.attachButton(element);
                                </script>
                            {/literal}
                        {/if}
                        {if $prefs.sefurl_short_url eq 'y'}
                            <a class="dropdown-item" id="short_url_link" href="#" role="button" onclick="(function() { $(document.activeElement).attr('href', 'tiki-short_url.php?url=' + encodeURIComponent(window.location.href) + '&title=' + encodeURIComponent(document.title)); })();">
                                    {icon name="link"} {tr}Get a short URL{/tr}
                                    {$hasPageAction="1"}
                            </a>
                        {/if}
                        {if !empty($user) and $prefs.feature_notepad eq 'y' and $tiki_p_notepad eq 'y'}
                            <form action="tiki-index.php" method="post" >
                                {ticket}
                                {if !empty($page_ref_id)}
                                <input type="hidden" name="page_ref_id" value={$page_ref_id}>
                                {/if}
                                <input type="hidden" name="savenotepad" value=1>
                                <button type="submit" name="page" value={$page|escape:"url"} class="tips dropdown-item">
                                    {icon name="notepad"} {tr}Save to notepad{/tr}
                                    {$hasPageAction="1"}
                                </button>
                            </form>
                        {/if}
                            {monitor_link type="wiki page" object=$page class="dropdown-item" linktext="{tr}Notification{/tr}"}
                        {if !empty($user) and $prefs.feature_user_watches eq 'y'}
                            {if $user_watching_page eq 'n'}
                                <form action="tiki-index.php" method="post" >
                                    {ticket}
                                    {if $structure eq 'y'}
                                        <input type="hidden" name="structure" value={$home_info.pageName|escape:'url'}>
                                    {/if}
                                    <input type="hidden" name="watch_action" value="add">
                                    <input type="hidden" name="watch_object" value={$page|escape:"url"}>
                                    <input type="hidden" name="watch_event" value="wiki_page_changed">
                                    <button type="submit" name="page" value={$page|escape:"url"} class="tips dropdown-item">
                                        {icon name="watch"} {tr}Monitor page{/tr}
                                        {$hasPageAction="1"}
                                    </button>
                                </form>
                            {else}
                                <a class="dropdown-item" href="tiki-index.php?page={$page|escape:"url"}&amp;watch_event=wiki_page_changed&amp;watch_object={$page|escape:"url"}&amp;watch_action=remove{if $structure eq 'y'}&amp;structure={$home_info.pageName|escape:'url'}{/if}" class="icon">
                                        {icon name="stop-watching"} {tr}Stop monitoring page{/tr}
                                        {$hasPageAction="1"}
                                </a>
                            {/if}
                            {if $structure eq 'y' and $tiki_p_watch_structure eq 'y'}
                                {if $user_watching_structure ne 'y'}
                                    <a class="dropdown-item" href="tiki-index.php?page={$page|escape:"url"}&amp;watch_event=structure_changed&amp;watch_object={$page_info.page_ref_id}&amp;watch_action=add_desc&amp;structure={$home_info.pageName|escape:'url'}">
                                            {icon name="watch"} {tr}Monitor sub-structure{/tr}
                                            {$hasPageAction="1"}
                                    </a>
                                {else}
                                    <a class="dropdown-item" href="tiki-index.php?page={$page|escape:"url"}&amp;watch_event=structure_changed&amp;watch_object={$page_info.page_ref_id}&amp;watch_action=remove_desc&amp;structure={$home_info.pageName|escape:'url'}">
                                            {icon name="stop-watching"} {tr}Stop monitoring sub-structure{/tr}
                                            {$hasPageAction="1"}
                                    </a>
                                {/if}
                            {/if}
                        {/if}
                        {if $prefs.feature_group_watches eq 'y' and ( $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y' )}
                            <a href="tiki-object_watches.php?objectId={$page|escape:"url"}&amp;watch_event=wiki_page_changed&amp;objectType=wiki+page&amp;objectName={$page|escape:"url"}&amp;objectHref={'tiki-index.php?page='|cat:$page|escape:"url"}" class="dropdown-item">
                                    {icon name="watch-group"} {tr}Group monitor{/tr}
                                    {$hasPageAction="1"}
                                </a>
                            {if $structure eq 'y'}
                                <a class="dropdown-item" href="tiki-object_watches.php?objectId={$page_info.page_ref_id|escape:"url"}&amp;watch_event=structure_changed&amp;objectType=structure&amp;objectName={$page|escape:"url"}&amp;objectHref={'tiki-index.php?page_ref_id='|cat:$page_ref_id|escape:"url"}" class="icon">
                                        {icon name="watch-group"} {tr}Group monitor structure{/tr}
                                        {$hasPageAction="1"}
                                </a>
                            {/if}
                        {/if}
                        {if $prefs.feature_webdav eq 'y'}
                            <a class="dropdown-item" href="javascript:open_webdav('{$page|virtual_path:'wiki page'|escape:'javascript'|escape}')" class="icon">
                                {icon name="file-archive-open"} {tr}Open in WebDAV{/tr}
                                {$hasPageAction="1"}
                            </a>
                        {/if}
                        {if $user and $prefs.user_favorites eq 'y'}
                            {favorite type="wiki page" object=$page button_classes="dropdown-item icon"}
                                {$hasPageAction="1"}
                        {/if}
                    </div>
                    {if ! $js}</li></ul>{/if}
                {/capture}
                {if $hasPageAction eq '1'}
                    <div class="btn-group page_actions" role="group">
                        {$smarty.capture.pageActions}
                    </div>
                {/if}
            </div>
        </div> {* END of wikiactions *}
        {/if}
    {/strip}
    </div>
{/if}
{*</div> div.wikitopline *}
