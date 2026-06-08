<div class="highlightable">

    {include file='token_view_actions.tpl'}
    {if !$viewItemPretty.override}
        {title help="trackers"}{$tracker_item_main_value}{/title}
    {/if}


    {if ! isset($print_page) || $print_page ne 'y'}

        {* --------- navigation ------ *}
        <div class="t_navbar mb-4">
            <div class="float-sm-end btn-group">
                {if ! $js}<ul><li>{/if}
                <a class="btn btn-link" data-bs-toggle="dropdown" href="#" role="button">
                    {icon name='menu-extra'}
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li class="dropdown-header">
                        {tr}Tracker item actions{/tr}
                    </li>
                    <li class="dropdown-divider"></li>
                    {if $tiki_p_print eq 'y'}
                    <li class="dropdown-item">
                        {if !empty($viewItemPretty.override)}
                            {self_link print='y' vi_tpl={$viewItemPretty.value}}
                                {icon name="print"} {tr}Print{/tr}
                            {/self_link}
                        {else}
                            {self_link print='y'}
                                {icon name="print"} {tr}Print{/tr}
                            {/self_link}
                        {/if}
                    </li>
                    {/if}

                    {if $pdf_export eq 'y' and $tiki_p_export_pdf eq 'y'}
                        <li class="dropdown-item">
                            <a href="{$smarty.server.SCRIPT_NAME}?{query pdf='y'}">
                                {icon name="pdf"} {tr}PDF{/tr}
                            </a>
                        </li>
                    {/if}

                    {if $item_info.logs.count|default:null and $item_info.canViewHistory}
                        <li class="dropdown-item">
                            <a href="tiki-tracker_view_history.php?itemId={$itemId}">
                                {icon name="history"} {tr}History{/tr}
                            </a>
                        </li>
                    {/if}
                    {if $canRemove}
                        <li class="dropdown-item">
                            <form action="tiki-view_tracker_item.php" method="post">
                                {ticket}
                                <input type="hidden" name="remove" value="{$itemId}">
                                <input type="hidden" name="itemId" value={$itemId}>
                                <button type="submit" name="trackerId" value="{$trackerId}" class="btn btn-link px-0 pt-0 pb-0" onclick="confirmPopup('{tr}Are you sure you want to permanently delete this item?{/tr}')">
                                    {icon name="delete"} {tr}Delete{/tr}
                                </button>
                            </form>
                        </li>
                    {/if}
                    {if $prefs.monitor_enabled eq 'y'}
                        <li class="dropdown-item">
                            {monitor_link type=trackeritem object=$itemId linktext="{tr}Notification{/tr}" class="link" title=""}
                        </li>
                    {/if}
                    {if $prefs.feature_user_watches eq 'y' and $tiki_p_watch_trackers eq 'y'}
                        <li class="dropdown-item">
                            {if $user_watching_tracker ne 'y'}
                                <form action="tiki-view_tracker_item.php" method="post" >
                                    {ticket}
                                    <input type="hidden" name="itemId" value={$itemId}>
                                    <input type="hidden" name="watch" value="add">
                                    <button type="submit"  name="trackerId" value={$trackerId} class="tips btn btn-link  px-0 pt-0 pb-0">
                                        {icon name="watch"} {tr}Monitor{/tr}
                                    </button>
                                </form>
                            {else}
                                <a href="tiki-view_tracker_item.php?trackerId={$trackerId}&amp;itemId={$itemId}&amp;watch=stop">
                                    {icon name="stop-watching"} {tr}Stop monitoring{/tr}
                                </a>
                            {/if}
                        </li>
                        {if $prefs.feature_group_watches eq 'y' and ( $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y' )}
                            <li class="dropdown-item">
                                <a href="tiki-object_watches.php?objectId={$itemId|escape:"url"}&amp;watch_event=tracker_item_modified&amp;objectType=tracker+{$trackerId}&amp;objectName={$tracker_info.name|escape:"url"}&amp;objectHref={'tiki-view_tracker_item.php?trackerId='|cat:$trackerId|cat:'&itemId='|cat:$itemId|escape:"url"}">
                                    {icon name="watch-group"} {tr}Group monitor{/tr}
                                </a>
                            </li>
                        {/if}
                    {/if}
                    {if $prefs.sefurl_short_url eq 'y'}
                        <li class="dropdown-item">
                            <a id="short_url_link" href="#" role="button" onclick="(function() { $(document.activeElement).attr('href', 'tiki-short_url.php?url=' + encodeURIComponent(window.location.href) + '&title=' + encodeURIComponent(document.title)); })();">
                                {icon name="link"} {tr}Get a short URL{/tr}
                                {$hasPageAction="1"}
                            </a>
                        </li>
                    {/if}
                    {if $tiki_p_admin_trackers eq "y"}
                        <li class="dropdown-item">
                            {permission_link mode=text type=trackeritem id=$itemId permType=trackers parentId=$trackerId}
                        </li>
                    {/if}
                    {if $prefs.user_favorites eq 'y' and isset($itemId)}
                        <li class="dropdown-item">
                            {favorite button_classes="favorite-icon" label="{tr}Favorite{/tr}"  type="trackeritem" object=$itemId}
                        </li>
                    {/if}
                </ul>
                {if ! $js}</li></ul>{/if}
            </div>
            {if $canModify && $prefs.tracker_legacy_insert neq 'y'}
                {if not empty($smarty.request.from) and $prefs.pwa_feature ne 'y'}{$from = $smarty.request.from}{else}{$from=''}{/if}
                <a class="btn btn-primary" href="{bootstrap_modal controller=tracker action=update_item trackerId=$trackerId itemId=$itemId redirect=$from size='modal-lg'}" role="button">{icon name="edit"} {tr}Edit{/tr}</a>
            {/if}

            {* only include actions bar if no custom view template is assigned *}
            {if !$viewItemPretty.override}
                {include file="tracker_actions.tpl"}
            {/if}

            {* show button back only if tpl has been set with vi_tpl or ei_tpl *}
            {if $viewItemPretty.override and !empty($referer)}
                <a class="btn btn-primary" href="{$referer}" title="{tr}Back{/tr}" role="button">{icon name="arrow-circle-left"} {tr}Back{/tr}</a>
            {/if}
        </div>

        {if $user and $prefs.feature_user_watches eq 'y' and $category_watched eq 'y'}
        <div class="categbar">
            {tr}Watched by categories:{/tr}
            {section name=i loop=$watching_categories}
                <a href="tiki-browse_categories.php?parentId={$watching_categories[i].categId}">{$watching_categories[i].name|escape}</a>&nbsp;
            {/section}
        </div>
        {/if}

        {* ------- return/next/previous tab --- *}
        {if $canView}
            {pagination_links count=$count|default:null offset=$offset reloff=$smarty.request.reloff|default:null itemname="{tr}Item{/tr}"}
                {* Do not specify an itemId in URL used for pagination, because it will use the specified itemId instead of moving to another item *}
                {$smarty.server.php_self|default:null}?{query itemId=NULL trackerId=$trackerId}
            {/pagination_links}
        {/if}

        {include file='tracker_error.tpl'}
    {else}
        <style>
        .tab-content .tab-pane {
            display:block;
        }</style>
    {/if}
    {* print_page *}

    {tabset name='tabs_view_tracker_item' skipsingle=1 toggle=n}

        {* when printing, no js is called to select the tab thus no class "active" assigned (would show nothing). print=y sets this class on printing *}
        {tab name="{tr}View{/tr}" print=y}
            {* --- tab with view ------------------------------------------------------------------------- *}
            {* In most cases one will not want this header when viewing an item *}
            {* <h3>{$tracker_info.name|escape}</h3> *}
            {if $tracker_is_multilingual}
                <div class="translations">
                    <a href="{bootstrap_modal controller=translation action=manage type=trackeritem source=$itemId}">{tr}Translations{/tr}</a>
                </div>
            {/if}

            {* show item *}
            {trackerfields mode=view trackerId=$trackerId itemId=$itemId fields=$fields itemId=$itemId viewItemPretty=$viewItemPretty.value}

            {* -------------------------------------------------- section with comments --- *}
            {if $tracker_info.useComments eq 'y' and ($tiki_p_tracker_view_comments ne 'n' or $tiki_p_comment_tracker_items ne 'n' or $canViewCommentsAsItemOwner) and $prefs.tracker_show_comments_below eq 'y'}
                <a id="Comments"></a>
                <div id="comment-container-below" class="well well-sm" data-bs-target="{service controller=comment action=list type=trackeritem objectId=$itemId}"></div>
                {jq}
                    var id = '#comment-container-below';
                    $(id).comment_load($(id).data('bs-target'));
                    $(document).on("ajaxComplete", function(){
                        $(id).tiki_popover();
                        $(id).applyColorbox();
                    });
                {/jq}

            {/if}

        {/tab}

        {* -------------------------------------------------- tab with comments --- *}
        {if $tracker_info.useComments eq 'y' and ($tiki_p_tracker_view_comments ne 'n' or $tiki_p_comment_tracker_items ne 'n' or $canViewCommentsAsItemOwner) and $prefs.tracker_show_comments_below ne 'y'}

            {tab name="{tr}Comments{/tr} (`$comCount`)" print=n}
                <div id="comment-container" data-bs-target="{service controller=comment action=list type=trackeritem objectId=$itemId}"></div>
                {jq}
                    var id = '#comment-container';
                    $(id).comment_load($(id).data('bs-target'));
                    $(document).on("ajaxComplete", function(){$(id).tiki_popover();});
                {/jq}

            {/tab}
        {/if}

        {* ---------------------------------------- tab with attachments --- *}
        {if $tracker_info.useAttachments eq 'y' and $tiki_p_tracker_view_attachments eq 'y'}
            {tab name="{tr}Attachments{/tr} (`$attCount`)" print=n}
                {include file='attachments_tracker.tpl'}
            {/tab}
        {/if}

    {* --------------------------------------------------------------- tab with edit --- *}
    {if (! isset($print_page) || $print_page ne 'y') && $canModify && $prefs.tracker_legacy_insert eq 'y'}
        {tab name=$editTitle}
            <h2>{tr}Edit Item{/tr}</h2>
            {* FIXME: format='flat' here prevents the view and edit tabs getting mixed up *}
            {service_inline controller='tracker' action='update_item' trackerId=$trackerId itemId=$itemId format='flat' save_return='y'}

        {/tab}
    {/if}

    {/tabset}

    <br><br>

    {if isset($print_page) and $print_page eq 'y' and $prefs.print_original_url_tracker eq 'y'}
        {tr}The original document is available at{/tr} <a href="{$base_url|escape}{$itemId|sefurl:trackeritem}">{$base_url|escape}{$itemId|sefurl:trackeritem}</a>
    {/if}
</div>
