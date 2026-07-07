{include file='token_view_actions.tpl'}
{title url=$trackerId|sefurl:'tracker' adm="trackers"}{tr}{$tracker_info.name}{/tr}{/title}
{if !empty($tracker_info.description)}
    {if $tracker_info.descriptionIsParsed eq 'y'}
        <div class="description form-text mb-2">{wiki objectId=$trackerId objectType="tracker" fieldName="description"}{$tracker_info.description}{/wiki}</div>
    {else}
        <div class="description form-text mb-2">{$tracker_info.description|escape|nl2br}</div>
    {/if}
{/if}
<div class="t_navbar mb-4">
    {if $tiki_p_create_tracker_items eq 'y' && $prefs.tracker_legacy_insert neq 'y'}
        {if $fields_count gt 0}
            <a class="btn btn-primary" href="{bootstrap_modal controller=tracker action=insert_item trackerId=$trackerId size='modal-lg'}" role="button">
                {icon name="create"} {tr}Create Item{/tr}
            </a>
        {else}
            <a class="btn btn-primary disabled" aria-disabled="true" href="{bootstrap_modal controller=tracker action=insert_item trackerId=$trackerId size='modal-lg'}" role="button">
                {icon name="create"} {tr}Create Item{/tr}
            </a>
        {/if}
        
    {/if}
    {include file="tracker_actions.tpl"}
    <div class="btn-group float-sm-end">
        {if ! $js}<ul><li>{/if}
                {if $prefs.feature_group_watches eq 'y' or $prefs.feature_user_watches eq 'y' or $prefs.feed_tracker eq 'y' or $tiki_p_admin_trackers eq 'y' or $tiki_p_export_tracker eq 'y' or $prefs.sefurl_short_url eq 'y'}
                    <a class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" href="#" title="{tr}Tracker actions{/tr}" role="button">
                        {icon name="menu-extra"}
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="dropdown-header">
                            {tr}Tracker actions{/tr}
                        </li>
                        <li class="dropdown-divider"></li>
                        {if $prefs.feature_group_watches eq 'y' and ( $tiki_p_admin_users eq 'y' or $tiki_p_admin eq 'y' )}
                            <li class="dropdown-item">
                                <a href="tiki-object_watches.php?objectId={$trackerId|escape:"url"}&amp;watch_event=tracker_modified&amp;objectType=tracker&amp;objectName={$tracker_info.name|escape:"url"}&amp;objectHref={'tiki-view_tracker.php?trackerId='|cat:$trackerId|escape:"url"}">
                                    {icon name="watch-group"} {tr}Group Monitoring{/tr}
                                </a>
                            </li>
                        {/if}
                        {if $prefs.feature_user_watches eq 'y' and $tiki_p_watch_trackers eq 'y' and $user}
                            <li class="dropdown-item">
                                {if $user_watching_tracker ne 'y'}
                                    <form action="tiki-view_tracker.php" method="post">
                                        <input type="hidden" name="trackerId" value="{$trackerId|escape}">
                                        {ticket}
                                        <button
                                                type="submit"
                                                name="watch"
                                                value="add"
                                                class="btn btn-link link-list"
                                        >
                                            {icon name="watch"} {tr}Monitor{/tr}
                                        </button>
                                    </form>
                                {else}
                                    <form action="tiki-view_tracker.php" method="post">
                                        <input type="hidden" name="trackerId" value="{$trackerId|escape}">
                                        {ticket}
                                        <button
                                                type="submit"
                                                name="watch"
                                                value="stop"
                                                class="btn btn-link link-list"
                                        >
                                            {icon name="stop-watching"} {tr}Stop monitoring{/tr}
                                        </button>
                                    </form>
                                {/if}
                            </li>
                        {/if}
                        {if $prefs.feed_tracker eq "y"}
                            <li class="dropdown-item">
                                <a href="tiki-tracker_rss.php?trackerId={$trackerId}">
                                    {icon name="rss"} {tr}RSS{/tr}
                                </a>
                            </li>
                        {/if}
                        {if $tiki_p_admin_trackers eq "y"}
                            <li class="dropdown-item">
                                <a class="import dialog" href="{bootstrap_modal controller=tracker action=import_items trackerId=$trackerId}">
                                    {icon name="import"} {tr}Import{/tr}
                                </a>
                            </li>
                        {/if}
                        {if $tiki_p_export_tracker eq "y"}
                            <li class="dropdown-item">
                                <a class="export dialog" href="{bootstrap_modal controller=tracker action=export trackerId=$trackerId filterfield=$filterfield filtervalue=$filtervalue}">
                                    {icon name="export"} {tr}Export{/tr}
                                </a>
                                
                            </li>
                            
                            <li class="dropdown-item">
                                <a href="tiki-export_tracker_schema.php?trackerIds[]={$trackerId}">
                                    {icon name="diagram"}</i> {tr}Show in ER Diagram{/tr}
                                </a>
                                    
                            </li>
                        {/if}
                        {if $tiki_p_admin_trackers eq "y"}
                            <li class="dropdown-item">
                                {permission_link mode=text type=tracker id=$trackerId permType=trackers}
                            </li>
                        {/if}
                        {if $prefs.sefurl_short_url eq 'y'}
                            <li class="dropdown-item">
                                <a id="short_url_link" href="#" onclick="(function() { $(document.activeElement).attr('href', 'tiki-short_url.php?url=' + encodeURIComponent(window.location.href) + '&title=' + encodeURIComponent(document.title)); })();" role="button">
                                    {icon name="link"} {tr}Get a short URL{/tr}
                                    {$hasPageAction="1"}
                                </a>
                            </li>
                        {/if}
                    </ul>
                {/if}
        {if ! $js}</li></ul>{/if}
    </div>
</div>

{if $user and $prefs.feature_user_watches eq 'y' and $category_watched eq 'y'}
    <div class="categbar">
        {tr}Watched by categories:{/tr}
        {section name=i loop=$watching_categories}
            <a href="tiki-browse_categories.php?parentId={$watching_categories[i].categId}">{$watching_categories[i].name|escape}</a>&nbsp;
        {/section}
    </div>
{/if}

{if !empty($mail_msg)}
    <div class="wikitext">{$mail_msg}</div>
{/if}

{include file='tracker_error.tpl'}

{tabset name='tabs_view_tracker' skipsingle=1}

    {if $tiki_p_view_trackers eq 'y' or (($tracker_info.writerCanModify eq 'y' or $tracker_info.userCanSeeOwn eq 'y' or $tracker_info.groupCanSeeOwn eq 'y' or $tracker_info.writerGroupCanModify eq 'y') and $user)}
        {tab name="{tr}Tracker Items{/tr}"}
            <h2>{tr}Items{/tr} <span class="badge bg-secondary" style="vertical-align: middle">{$item_count}</span></h2>
            {* -------------------------------------------------- tab with list --- *}

            {if (($tracker_info.showStatus eq 'y' and $tracker_info.showStatusAdminOnly ne 'y') or $tiki_p_admin_trackers eq 'y') or $show_filters eq 'y'}
                {include file='tracker_filter.tpl'}
            {/if}

            {if (isset($pages_count) && $pages_count > 1) or $initial}{initials_filter_links}{/if}

            {if $items|@count ge '1'}
                {* ------- list toggleable fields --- *}

                {if $tracker_info.allowChooseFieldsToDisplay eq 'y'}
                    {if $trk_items_col_pref neq ''}
                        <textarea id="savedcolpref" class="d-none">{$trk_items_col_pref}</textarea>
                    {/if}
                    <form id="savecolprefsform" method="post" action="{service controller=user action=save_column_prefs}">
                        {ticket}
                        <div class="mb-3">
                            <div class="toggle-col-warning d-none">
                                {remarksbox type="danger" title="{tr}Warning{/tr}" close="y"}
                                    {tr}{"At least one column must remain visible."}{/tr}
                                {/remarksbox}
                            </div>
                            {foreach from=$listfields key=ix item=field_value}
                                {if $field_value.isTblVisible eq 'y' and ( $field_value.type ne 'x' and $field_value.type ne 'h') and ($field_value.type ne 'p' or $field_value.options_array[0] ne 'password') and $field_value.visibleInViewMode eq 'y'}
                                    <div class="form-check form-check-inline">
                                        <input type="checkbox" class="form-check-input toggle-col" data-col-name="{$field_value.name}" checked>
                                        <label class="form-check-label">{$field_value.name|tra|truncate:255:"..."|escape|default:"&nbsp;"}</label>
                                    </div>
                                {/if}
                            {/foreach}
                        </div>
                        <div class="mb-3">
                            <textarea name="prefs" id="prefs" class="d-none"></textarea>
                            <input name="trackerId" type="hidden" id="col_pref_trackerid" value="{$trackerId}">
                            <input name="trackerName" type="hidden" value="{tr}{$tracker_info.name}{/tr}">
                        </div>
                    </form>
                {/if}
                {* ------- list headings --- *}
                <form name="checkform" method="post">
                    {ticket}
                    <div class="{if $js}table-responsive{/if}"> {*the table-responsive class cuts off dropdown menus *}
                        <table class="table table-striped table-hover" id="trItemTable">
                            <tr>
                                {if $tracker_info.showStatus eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')}
                                    <td class="auto" style="width:20px;"></td> {* th changed to td to prevent ARIA empty header error *}
                                {/if}

                                {if $tiki_p_admin_trackers eq 'y'}
                                    <td style="width:20px;">  {* th changed to td to prevent ARIA empty header error *}
                                        {select_all checkbox_names='action[]'}
                                    </td>
                                {/if}

                                {foreach from=$listfields key=ix item=field_value}
                                    {if $field_value.isTblVisible eq 'y' and ( $field_value.type ne 'x' and $field_value.type ne 'h') and ($field_value.type ne 'p' or $field_value.options_array[0] ne 'password') and $field_value.visibleInViewMode eq 'y'}
                                        <th class="auto" data-sort-field="f_{$field_value.fieldId}">
                                            {self_link _sort_arg='sort_mode' _sort_field='f_'|cat:$field_value.fieldId _onclick="return handleSortClick(event, 'f_{$field_value.fieldId}');"}
                                                {$field_value.name|tra|truncate:255:"..."|escape|default:"&nbsp;"}
                                            {/self_link}
                                        </th>
                                    {/if}
                                {/foreach}

                                {if $tracker_info.showCreated eq 'y'}
                                    <th class="auto" data-sort-field="created">
                                        {self_link _sort_arg='sort_mode' _sort_field='created' _onclick="return handleSortClick(event, 'created');"}{tr}Created{/tr}{/self_link}
                                        <span class="sort-indicator"></span>
                                    </th>
                                {/if}
                                {if $tracker_info.showLastModif eq 'y'}
                                    <th class="auto" data-sort-field="lastModif">
                                        {self_link _sort_arg='sort_mode' _sort_field='lastModif' _onclick="return handleSortClick(event, 'lastModif');"}{tr}Last modified{/tr}{/self_link}
                                    </th>
                                {/if}
                                {if $tracker_info.showLastModifBy eq 'y'}
                                    <th class="auto" data-sort-field="lastModifby">
                                        {self_link _sort_arg='sort_mode' _sort_field='lastModifby' _onclick="return handleSortClick(event, 'lastModifby');"}{tr}Last modifier{/tr}{/self_link}
                                    </th>
                                {/if}
                                {if $tracker_info.useComments eq 'y' and ($tracker_info.showComments eq 'y' || $tracker_info.showLastComment eq 'y') and $tiki_p_tracker_view_comments ne 'n'}
                                    <th{if $tracker_info.showLastComment ne 'y'} style="width:5%"{/if}>{tr}Comments{/tr}</th>
                                {/if}
                                {if ($tiki_p_tracker_view_attachments eq 'y' or $tiki_p_admin_trackers eq 'y') and $tracker_info.useAttachments eq 'y' and $tracker_info.showAttachments eq 'y'}
                                    <th style="width:5%">{tr}atts{/tr}</th>
                                    {if $tiki_p_admin_trackers eq 'y'}<th style="width:5%">{tr}dls{/tr}</th>{/if}
                                {/if}
                                {if $tiki_p_admin_trackers eq 'y' or $tiki_p_remove_tracker_items eq 'y' or $tiki_p_remove_tracker_items_pending eq 'y' or $tiki_p_remove_tracker_items_closed eq 'y'}
                                    <td style="width:20px"></td> {* th changed to td to prevent ARIA empty header error *}
                                {/if}
                            </tr>

                            {* ------- Items loop --- *}
                            {$itemoff=0}

                            {section name=user loop=$items}
                                <tr>
                                    {if $tracker_info.showStatus eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')}
                                        <td class="icon">
                                            {$ustatus=$items[user].status|default:"c"}
                                            {icon name=$status_types.$ustatus.iconname iclass='tips' ititle=":{$status_types.$ustatus.label}"}
                                        </td>
                                    {/if}
                                    {if $tiki_p_admin_trackers eq 'y'}
                                        <td class="checkbox-cell">
                                            <input type="checkbox" class="form-check-input" name="action[]" aria-label="{tr}Select{/tr}" value='{$items[user].itemId}'>
                                        </td>
                                    {/if}

                                    {* ------- list values --- *}
                                    {$ajaxedit = (empty($tracker_info.allowInlineEditing) and $prefs.ajax_inline_edit_trackerlist eq 'y') or
                                            ($tracker_info.allowInlineEditing eq 'y') and
                                            ($tiki_p_modify_tracker_items eq 'y' and $items[user].status ne 'p' and $items[user].status ne 'c') or
                                            ($tiki_p_modify_tracker_items_pending eq 'y' and $items[user].status eq 'p') or
                                            ($tiki_p_modify_tracker_items_closed eq 'y' and $items[user].status eq 'c')
                                    }
                                    {foreach from=$items[user].field_values key=ix item=field_value}
                                        {if $field_value.isTblVisible eq 'y' and $field_value.type ne 'x' and $field_value.type ne 'h' and ($field_value.type ne 'p' or $field_value.options_array[0] ne 'password') and $field_value.visibleInViewMode eq 'y'}
                                            <td class={if $field_value.type eq 'n' or $field_value.type eq 'q' or $field_value.type eq 'b'}"numeric"{else}"auto"{/if} data-col-name="{$field_value.name}">
                                                {if $field_value.type eq 'wiki'}
                                                    <a href="tiki-index.php?page={$field_value.value|escape:"url"}">{$field_value.value}</a>
                                                {else}
                                                    {trackeroutput field=$field_value showlinks=y showpopup="y" item=$items[user] list_mode=y inTable=formcolor reloff=$itemoff editable=($ajaxedit and $listfields[$field_value.fieldId].editable) ? 'block' : ''}
                                                {/if}
                                            </td>
                                        {/if}
                                    {/foreach}

                                    {if $tracker_info.showCreated eq 'y'}
                                        <td class="date">{if !empty($tracker_info.showCreatedFormat)}{$items[user].created|tiki_date_format:$tracker_info.showCreatedFormat}{else}{$items[user].created|tiki_short_datetime}{/if}</td>
                                    {/if}
                                    {if $tracker_info.showLastModif eq 'y'}
                                        <td class="date">{if !empty($tracker_info.showLastModifFormat)}{$items[user].lastModif|tiki_date_format:$tracker_info.showLastModifFormat}{else}{$items[user].lastModif|tiki_short_datetime}{/if}</td>
                                    {/if}
                                    {if $tracker_info.showLastModifBy eq 'y'}
                                        <td class="date">
                                            {if empty($items[user].lastModifBy)}Unknown{else}{$items[user].lastModifBy|username}{/if}
                                        </td>
                                    {/if}
                                    {if $tracker_info.useComments eq 'y' and ($tracker_info.showComments eq 'y' or $tracker_info.showLastComment eq 'y') and $tiki_p_tracker_view_comments ne 'n'}
                                        <td style="text-align:center;">{if $tracker_info.showComments eq 'y'}{$items[user].comments}{/if}{if $tracker_info.showComments eq 'y' and $tracker_info.showLastComment eq 'y'}<br>{/if}{if $tracker_info.showLastComment eq 'y' and !empty($items[user].lastComment)}{$items[user].lastComment.userName|escape}-{$items[user].lastComment.commentDate|tiki_short_date}{/if}</td>
                                    {/if}
                                    {if ($tiki_p_tracker_view_attachments eq 'y' or $tiki_p_admin_trackers eq 'y') and $tracker_info.useAttachments eq 'y' and $tracker_info.showAttachments eq 'y'}
                                        <td class="icon"><a href="tiki-view_tracker_item.php?itemId={$items[user].itemId}&amp;show=att{if $offset}&amp;offset={$offset}{/if}{foreach key=urlkey item=urlval from=$urlquery}{if $urlval}&amp;{$urlkey}={$urlval|escape:"url"}{/if}{/foreach}"
                                        link="{tr}List Attachments{/tr}">{icon name="attach"}</a> {$items[user].attachments}</td>
                                        {if $tiki_p_admin_trackers eq 'y'}<td style="text-align:center;">{$items[user].hits}</td>{/if}
                                    {/if}
                                    <td class="action">
                                        {if $tiki_p_admin_trackers eq 'y' or ($tiki_p_remove_tracker_items eq 'y' and $items[user].status ne 'p' and $items[user].status ne 'c') or ($tiki_p_remove_tracker_items_pending eq 'y' and $items[user].status eq 'p') or ($tiki_p_remove_tracker_items_closed eq 'y' and $items[user].status eq 'c')}
                                            {actions}
                                                {strip}
                                                    {if $prefs.tracker_legacy_insert neq 'y'}
                                                        <action>
                                                            <a href="{bootstrap_modal controller=tracker action=update_item trackerId=$trackerId itemId=$items[user].itemId size='modal-lg'}"
                                                                onclick="$('[data-bs-toggle=popover]').popover('hide');"
                                                            >
                                                                {icon name="edit" _menu_text='y' _menu_icon='y' alt="{tr}Edit{/tr}"}
                                                            </a>
                                                        </action>
                                                    {else}
                                                        <action>
                                                            <a href="tiki-view_tracker_item.php?itemId={$items[user].itemId}&amp;show=mod"
                                                                onclick="$('[data-bs-toggle=popover]').popover('hide');"
                                                            >
                                                                {icon name="post" _menu_text='y' _menu_icon='y' alt="{tr}View/Edit{/tr}"}
                                                            </a>
                                                        </action>
                                                    {/if}
                                                    {if $tiki_p_create_tracker_items eq 'y' and ($prefs.tracker_clone_item eq 'y' or $tracker_info.enableDuplicate eq 'y')}
                                                        <action>
                                                            <a href="{bootstrap_modal controller=tracker action=insert_item trackerId=$trackerId clone_from=$items[user].itemId size='modal-lg'}"
                                                                onclick="$('[data-bs-toggle=popover]').popover('hide');"
                                                            >
                                                                {icon name="copy" _menu_text='y' _menu_icon='y' alt="{tr}Duplicate{/tr}"}
                                                            </a>
                                                        </action>
                                                    {/if}
                                                    <action>
                                                        <a href="{bootstrap_modal controller=tracker action=remove_item trackerId=$trackerId itemId=$items[user].itemId}"
                                                            onclick="$('[data-bs-toggle=popover]').popover('hide');"
                                                        >
                                                            {icon name="delete" _menu_text='y' _menu_icon='y' alt="{tr}Delete{/tr}"}
                                                        </a>
                                                    </action>
                                                    {if $tiki_p_admin_trackers eq 'y'}
                                                        <action>
                                                            {permission_link mode=text type=trackeritem id=$items[user].itemId permType=trackers parentId=$trackerId}
                                                        </action>
                                                        <action>
                                                            <a href="tiki-tracker_view_history.php?itemId={$items[user].itemId}"
                                                                onclick="$('[data-bs-toggle=popover]').popover('hide');"
                                                            >
                                                                {icon name="history" _menu_text='y' _menu_icon='y' alt="{tr}History{/tr}"}
                                                            </a>
                                                        </action>
                                                    {/if}
                                                {/strip}
                                            {/actions}
                                        {/if}
                                    </td>
                                </tr>
                                {$itemoff=$itemoff+1}
                            {/section}
                        </table>
                    </div>
                    
                    {* JavaScript to handle the sorting functionality *}
                    {jq}
                    if (typeof $.trackerInitMultiSort === 'function') {
                        $.trackerInitMultiSort();
                    }
                    {/jq}
                    
                    {if $tiki_p_admin_trackers eq 'y'}
                        <div class="mb-3 row">
                            <div class="input-group min-width-customized">
                                <select name="batchaction" class="form-select trackerbatchaction">
                                    <option value="" selected="selected">
                                        {tr}Select the action to be performed with checked{/tr}...
                                    </option>
                                    <option value="delete">{tr}Delete Selected{/tr}</option>
                                    {if $tracker_info.showStatus eq 'y' or ($tracker_info.showStatusAdminOnly eq 'y' and $tiki_p_admin_trackers eq 'y')}
                                        {foreach $status_types as $key => $status}
                                            <option value="{$key}">
                                                {tr}{$status.label}{/tr}
                                            </option>
                                        {/foreach}
                                    {/if}
                                </select>
                                {ticket}
                                <input type="hidden" name="trackerId" value="{$trackerId}">
                                <input type="submit" class="btn btn-primary js-confirm-delete" name="act" value="{tr}OK{/tr}">
                            </div>
                        </div>
                    {/if}
                </form>
                {pagination_links count=$item_count step=$maxRecords offset=$offset}{/pagination_links}
                {jq}
                    loadColumnPrefs();

                    $('.toggle-col').on('change', function () {
                        const checkedboxes = $('.toggle-col:checked');
                        let form = $('#savecolprefsform');

                        if (checkedboxes.length === 0) {
                            $(this).prop('checked', true);
                            $('.toggle-col-warning').removeClass('d-none');
                            return;
                        }
                        $('.toggle-col').each(function () {
                            const columnName = $(this).data('col-name');
                            const isVisible = $(this).is(':checked');
                            $('#trItemTable [data-col-name="' + columnName + '"]').toggle(isVisible);
                        });
                        $('#prefs').val(getColumnPrefs());
                        saveColsPref();
                    });

                    function getColumnPrefs() {
                        const prefs = {};
                        let currentSavedPrefs = JSON.parse($('#savedcolpref').val());
                        const trackerId = $('#col_pref_trackerid').val();
                        const colprefkey = 'tracker_' + trackerId;

                        $('.toggle-col').each(function() {
                            const columnName = $(this).data('col-name');
                            const isChecked = $(this).is(':checked');
                            prefs[columnName] = isChecked;
                        });
                        currentSavedPrefs[colprefkey] = prefs;
                        return JSON.stringify(currentSavedPrefs);
                    }

                    function loadColumnPrefs() {
                        let savedPrefs = $('#savedcolpref').val();
                        const trackerId = $('#col_pref_trackerid').val();
                        const colprefkey = 'tracker_' + trackerId;

                        if (savedPrefs) {
                            let prefs = JSON.parse(savedPrefs);
                            const actual_tracker_prefs = prefs[colprefkey];
                            if (! actual_tracker_prefs) {
                                return;
                            }
                            Object.keys(actual_tracker_prefs).forEach(function (columnName) {
                                const checkbox = $('.toggle-col[data-col-name="' + columnName +'"]');
                                checkbox.prop('checked', actual_tracker_prefs[columnName]);
                                $('#trItemTable [data-col-name="' + columnName + '"]').toggle(actual_tracker_prefs[columnName]);
                            });
                        }
                    }

                    const saveColsPref = delayedExecutor(2000, function () {
                        let form = $('#savecolprefsform');
                        $.ajax({
                            url: form.attr('action'),
                            type: 'POST',
                            data: form.serialize(),
                            success: function (message) {
                                if (! message) {
                                    feedback(tr('An error occured while attempting to save your column preference.'), 'error');
                                }
                                feedback(message, 'success');
                            }
                        });
                    });
                {/jq}
            {/if}
        {/tab}
    {/if}

    {if $tiki_p_create_tracker_items eq 'y' && $prefs.tracker_legacy_insert eq 'y'}
        {* --------------------------------------------------------------------------------- tab with edit --- *}
        {tab name="{tr}Insert New Item{/tr}"}
            <h2>{tr}Insert New Item{/tr}</h2>
            {service_inline controller='tracker' action='insert_item' trackerId=$trackerId itemId=$itemId save_return='y'}
        {/tab}
    {/if}

    {if $tracker_sync}
        {tab name="{tr}Synchronization{/tr}"}
            <h2>{tr}Synchronization{/tr}</h2>
            <p>
                {tr _0=$tracker_sync.provider|cat:'/tracker'|cat:$tracker_sync.source}This tracker is a remote copy of <a href="%0">%0</a>.{/tr}
                {if !empty($tracker_sync.last)}
                    {tr _0=$tracker_sync.last|tiki_short_date}It was last updated on %0.{/tr}
                {/if}
            </p>
            {permission name=tiki_p_admin_trackers}
                <form class="sync-refresh" method="post" action="{service controller=tracker_sync action=sync_meta trackerId=$trackerId}">
                    <p><input type="submit" class="btn btn-primary btn-sm" value="{tr}Reload field definitions{/tr}"></p>
                </form>
                <form class="sync-refresh" method="post" action="{service controller=tracker_sync action=sync_new trackerId=$trackerId}">
                    <p>{tr}Items added locally{/tr}</p>
                    <ul class="load-items items">
                    </ul>
                    <p><input type="submit" class="btn btn-primary btn-sm" value="{tr}Push new items{/tr}"></p>
                </form>
                <form class="sync-refresh" method="post" action="{service controller=tracker_sync action=sync_edit trackerId=$trackerId}">
                    <div class="item-block">
                        <p>{tr}Safe modifications (no remote conflict){/tr}</p>
                        <ul class="load-items automatic">
                        </ul>
                    </div>
                    <div class="item-block">
                        <p>{tr}Dangerous modifications (remote conflict){/tr}</p>
                        <ul class="load-items manual">
                        </ul>
                    </div>
                    <p>{tr}On push, local items will be removed until data reload.{/tr}</p>
                    <p><input type="submit" class="btn btn-primary btn-sm" value="{tr}Push local changes{/tr}"></p>
                </form>
                <form class="sync-refresh" method="post" action="{service controller=tracker_sync action=sync_refresh trackerId=$trackerId}">
                    {if !empty($tracker_sync.modified)}
                        {remarksbox type=warning title="{tr}Local changes will be lost{/tr}"}
                            <p>{tr}When reloading the data from the source, all local changes will be lost.{/tr}</p>
                            <ul>
                                <li>{tr}New items that must be preserved should be pushed using the above controls.{/tr}</li>
                                <li>
                                    {tr}Modifications that must be preserved should be replicated.{/tr}
                                    <ul>
                                        <li>{tr}Without conflicts: Using the above controls{/tr}</li>
                                        <li>{tr}With conflicts: Manually on the source.{/tr} <em>{tr}Using the above controls will cause information loss.{/tr}</em></li>
                                    </ul>
                                </li>
                            </ul>
                        {/remarksbox}
                    {/if}
                    <div class="submit">
                        <input type="hidden" name="confirm" value="1">
                        <input type="submit" class="btn btn-primary btn-sm" name="submit" value="{tr}Reload data from source{/tr}">
                    </div>
                </form>
                {jq}
                    $('.sync-refresh').on("submit", function () {
                        var form = this;
                        $.ajax({
                            type: 'post',
                            url: $(form).attr('action'),
                            dataType: 'json',
                            data: $(form).serialize(),
                            error: function (jqxhr) {
                                $(':submit', form).showError(jqxhr);
                            },
                            success: function () {
                                document.location.reload();
                            }
                        });
                        return false;
                    });
                    $('.load-items').closest('form').each(function () {
                        var form = this;
                        $(form).hide();
                        $.getJSON($(this).attr('action'), function (data) {
                            $.each(data.sets, function (k, name) {
                                var list = $(form).find('.load-items.' + name)[0];

                                $.each(data[name], function (k, info) {
                                    var li = $('<li/>');
                                    li.append($('<label/>')
                                        .text(info.title)
                                        .prepend($('<input type="checkbox" class="form-check-input" name="' + name + '[]">').attr('value', info.itemId))
                                    );

                                    $.each({localUrl: "{tr}Local{/tr}", remoteUrl: "{tr}Remote{/tr}"}, function (key, label) {
                                        if (info[key]) {
                                            li
                                                .append(' ')
                                                .append($('<a/>')
                                                    .attr('href', info[key])
                                                    .text(label));
                                        }
                                    });

                                    $(list).append(li);
                                });

                                if (data[name].length === 0) {
                                    $(list).closest('.item-block').hide();
                                } else {
                                    $(form).show();
                                }
                            });
                        });
                    });
                {/jq}
            {/permission}
        {/tab}
    {/if}
{/tabset}

{jq}
    const confirmDeleteMsg = '{tr}Are you sure you want to delete the selected items?{/tr}';
    const selectItemMsg = '{tr}Please select at least one item.{/tr}';
    // Target the batch delete button to add validation.
    $('.js-confirm-delete').on('click', function(event) {
        const form = $(this).closest('form');
        const batchAction = form.find('select.trackerbatchaction').val();

        // For any other action, the form submits normally without interference.
        if (batchAction === 'delete') {
            // Stop the default form submission to perform our checks.
            event.preventDefault();

            const itemsSelected = form.find('input[name="action[]"]:checked').length;

            if (itemsSelected > 0) {
                // Validation passed: proceed with the confirmation popup.
                confirmPopup.call({event: event}, confirmDeleteMsg);
            } else {
                // Validation failed: show an feedback and do nothing else.
                feedback(selectItemMsg,'warning');
            }
        }
    });
{/jq}
