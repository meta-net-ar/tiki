{title admpage="wiki" url='tiki-pagehistory.php?page='|cat:$page|escape}{tr}History:{/tr} {$page}{/title}

<div class="t_navbar mb-4">
    {$thispage=$page|escape:url}
    {button href="{$page|sefurl}" _class="btn-info" _text="{tr}View published page{/tr}" _icon_name="view"}
    {if $editable}
        {button href="tiki-editpage.php?page=$thispage" class="btn btn-primary" _text="{tr}Edit published page{/tr}" _icon_name="edit"}
    {/if}
    {if !isset($noHistory)}
        {if $show_all_versions eq "y"}
            {button _text="{tr}Collapse Into Edit Sessions{/tr}" href="?clear_versions=1&show_all_versions=n" _auto_args="*" _class="btn-info" _icon_name="expanded"}
        {else}
            {button _text="{tr}Show All Versions{/tr}" href="?clear_versions=1&show_all_versions=y" _auto_args="*" _class="btn-info" _icon_name="collapsed"}
        {/if}
    {/if}
</div>

{if $preview}
    <h2>{tr}Preview of version:{/tr} {$preview}
        {if $info.version eq $preview}<small><small>{tr}(current){/tr}</small></small>{/if}
    </h2>
    {if $info.version ne $preview and $tiki_p_rollback eq 'y'}
        <div class="d-flex flex-column">
            {self_link _script="tiki-pagehistory.php" page=$page source=$preview _title="{tr}View source of this version{/tr}"}{tr}View source of this version{/tr}{/self_link}
            {self_link _script="tiki-rollback.php" page=$page version=$preview _title="{tr _0=$element.version}Revert to version %0{/tr}"}{tr}Revert to this version{/tr}{/self_link}
        </div>
    {/if}
    <div>
        {if !isset($noHistory)}
            {if isset($show_all_versions) and $show_all_versions eq "n"}
                {pagination_links count=$ver_count offset=$smarty.request.preview_idx offset_arg="preview_idx" itemname="{tr}Session{/tr}" show_numbers="n"}{/pagination_links}
            {else}
                {pagination_links count=$ver_count offset=$smarty.request.preview_idx offset_arg="preview_idx" itemname="{tr}Version{/tr}" show_numbers="n"}{/pagination_links}
            {/if}
        {/if}
    </div>
    {if (isset($flaggedrev_approval) and $flaggedrev_approval) and $tiki_p_wiki_approve eq 'y' && !$flaggedrev_preview_rejected}
        {remarksbox type=comment title="{tr}Content Approval{/tr}"}
            <form method="post" action="tiki-pagehistory.php?page={$page|escape:'url'}&amp;preview={$preview|escape:'url'}">
                {if $flaggedrev_preview_approved}
                    <p>{tr}This revision is currently marked as approved.{/tr}<p>
                    <div class="submit">
                        <input type="hidden" name="unapprove" value="{$preview|escape}">
                        <input type="submit" class="btn btn-primary btn-sm" name="flaggedrev" value="{tr}Remove Approval{/tr}">
                    </div>
                {else}
                    <p>{tr}This revision has not been approved.{/tr}<p>
                    <div class="submit">
                        <input type="hidden" name="approve" value="{$preview|escape}">
                        <input type="submit" class="btn btn-primary btn-sm" name="flaggedrev" value="{tr}Approve Revision{/tr}">
                    </div>
                {/if}
            </form>
        {/remarksbox}
    {/if}

    {if (isset($flaggedrev_approval) and $flaggedrev_approval)}
        {remarksbox type=warning title="{tr}History view{/tr}"}
            <p>
                {if $flaggedrev_preview_approved}
                        {tr}This revision may not be the latest approved revision{/tr}!
                {else}
                    {if $flaggedrev_preview_rejected}
                        {tr}This revision has been rejected.{/tr}
                    {else}
                        {tr}This revision has not been approved.{/tr}
                    {/if}
                {/if}
            </p>
        {/remarksbox}
    {/if}

    <div class="wikitext" id="page-data">
        {$previewd}
    </div>
{/if}

{if $source}
    <h2>
        {tr}Source of version:{/tr} {$source}
        {if $info.version eq $source}<small><small>{tr}(current){/tr}</small></small>{/if}
    </h2>
    {if $info.version ne $source and $tiki_p_rollback eq 'y'}
        <div class="d-flex flex-column">
            {self_link _script="tiki-rollback.php" page=$page version=$source _title="{tr}Revert{/tr}"}{tr}Revert to this version{/tr}{/self_link}
        </div>
    {/if}
    <div>
        {if !isset($noHistory)}
            {if isset($show_all_versions) and $show_all_versions eq "n"}
                {pagination_links count=$ver_count offset=$smarty.request.source_idx offset_arg="source_idx" itemname="{tr}Session{/tr}" show_numbers="n"}{/pagination_links}
            {else}
                {pagination_links count=$ver_count offset=$smarty.request.source_idx offset_arg="source_idx" itemname="{tr}Version{/tr}" show_numbers="n"}{/pagination_links}
            {/if}
        {/if}
    </div>

    <div>
        <div class="icon_copy_code far fa-clipboard" tabindex="0" data-clipboard-target="#page-source"><span class="copy_code_tooltiptext copy-html" id='copy_source'>Copy to clipboard</span></div>
        <pre class="codelisting preview-html" data-theme="default" data-syntax="{$syntax_type}" data-wrap="1" dir="ltr" style="white-space:pre-wrap; overflow-wrap: break-word; word-wrap: break-word;" id="page-source">{$sourced|escape}</pre>
    </div>
{/if}

{if (isset($flaggedrev_approval) and $flaggedrev_approval) and $tiki_p_wiki_approve eq 'y' and $flaggedrev_compare_approve}
    {remarksbox type=comment title="{tr}Content Approval{/tr}"}
        <form method="post" action="tiki-pagehistory.php?page={$page|escape:'url'}&amp;preview={$new.version|escape:'url'}">
            <p>{tr}This revision has not been approved.{/tr}<p>
            <div class="submit">
                <input type="hidden" name="approve" value="{$new.version|escape}">
                <input type="submit" class="btn btn-primary btn-sm" name="flaggedrev" value="{tr}Approve Revision{/tr}">
            </div>
        </form>
    {/remarksbox}
{/if}

{include file='pagehistory.tpl'}

{if !isset($noHistory)}
    {if $preview || $source || $diff_style}
        <h2>
            {tr}History{/tr}
        </h2>
    {/if}
    <form id="pagehistory" action="tiki-pagehistory.php?page={$page}" class="multi mb-4 d-flex align-items-end justify-content-between flex-wrap gap-2">
        <input type="hidden" name="page" value="{$page|escape}">
        <input type="hidden" name="history_offset" value="{$history_offset}">

        {if $prefs.feature_multilingual eq 'y' and $tiki_p_edit eq 'y'}
            <div class="translation w-50">
                <label for="tra_lang" class="form-label">{tr}Translation{/tr}</label>
                <select name="tra_lang" class="form-select">
                    {section name=ix loop=$languages}
                        <option value="{$languages[ix].value|escape}"{if $lang eq $languages[ix].value} selected="selected"{/if}>{$languages[ix].name}</option>
                    {/section}
                </select>
            </div>

            <div class="d-flex flex-grow-0 gap-2">
                <input type="submit" class="btn btn-primary" name="update_translation" value="{tr}Update Translation{/tr}"/>
                {if $show_translation_history}
                    <input type="hidden" name="show_translation_history" value="1">
                    {button show_translation_history=0 _text="{tr}Hide translation history{/tr}" _auto_args="*" _class="btn btn-info"}
                {else}
                    {button show_translation_history=1 _text="{tr}Show translation history{/tr}" _auto_args="*" _class="btn btn-info"}
                {/if}
            </div>
        {/if}
    </form>

        <div class="row mb-4">
            <form id="pagehistory" action="tiki-pagehistory.php?page={$page}" class="col-sm-6">
                <input type="hidden" name="page" value="{$page|escape}">
                <input type="hidden" name="history_offset" value="{$history_offset}">

                    <input type="checkbox" name="paginate" id="paginate"{if $paginate} checked="checked"{/if}>
                    <label for="paginate">{tr}Enable pagination{/tr}</label>
                    {if $paginate}
                        <input type="text" name="history_pagesize" role="search" id="history_pagesize" value="{$history_pagesize}" class="form-control form-control-sm" style="width: 5em; display: inline-block">
                        <label for="history_pagesize">{tr}rows per page{/tr}</label>
                    {/if}
            </form>
            <form id="pagehistory-rmv-history" action="tiki-pagehistory.php?page={$page}" class="col-sm-6">
                <input type="hidden" name="page" value="{$page|escape}">
                <input type="hidden" name="history_offset" value="{$history_offset}">

                {if ($prefs.default_wiki_diff_style ne "old") and $history}
                    <div class="input-group">
                        <select class="form-select" name="diff_style">
                            <optgroup label="{tr}Simple{/tr}">
                                {foreach  $diff_styles.options as $value => $label}
                                    {if $value == 'htmldiff' || $value == 'sidediff'}
                                        <option value="{$value}" {if $diff_style == $value} selected="selected"{/if}>
                                            {tr}{$label}{/tr}
                                        </option>
                                    {/if}
                                {/foreach}
                            </optgroup>
                            <optgroup label="{tr}Advanced{/tr}">
                                {foreach  $diff_styles.options as $value => $label}
                                    {if $value == 'htmldiff' || $value == 'sidediff'}
                                        {continue}
                                    {/if}
                                    <option value="{$value}" {if $diff_style == $value} selected="selected"{/if}>
                                        {tr}{$label}{/tr}
                                    </option>
                                {/foreach}
                            </optgroup>
                        </select>
                            {jq}
        $("form#pagehistory")
            .each(function store_original_values(i, form){
                form.originals = {};

                $(form).find(':input').each(function(i, input){
                    var name = $(input).attr('name');
                    var value = $(input).val();
                    form.originals[name] = value;
                });
            })
            .on("submit", function submit_changed_values(evt){
                var always = ['page', 'oldver'];
                var originals = this.originals || {};

                $(this).find(':input:enabled').each(function(i, input){
                    var name = $(input).attr('name');
                    var value = $(input).val();

                    if(always.indexOf(name) === -1 && originals[name] === value) {
                        $(input).attr('disabled', 'disabled')
                                .prop('disabled', 'disabled');
                    }
                });
            });
                            {/jq}
                    </div>
                    <input type="hidden" name="show_all_versions" value="{$show_all_versions}">
                    {/if}
        </div>
        <div>
            <div class="{if $js}table-responsive{/if}"> {* table-responsive class cuts off css drop-down menus *}
                <table class="table table-condensed table-hover table-striped">
                    <tr>
                        {if $tiki_p_remove eq 'y'}
                            <th>
                                {select_all checkbox_names='checked[]'}
                            </th>
                        {/if}
                        <th>
                            {tr}Information{/tr}
                        </th>
                        {if $prefs.feature_contribution eq 'y'}
                            <th>
                                {tr}Contribution{/tr}
                            </th>
                        {/if}
                        {if $prefs.feature_contribution eq 'y' and $prefs.feature_contributor_wiki eq 'y'}
                            <th>
                                {tr}Contributors{/tr}
                            </th>
                        {/if}
                        <th>
                            {tr}Version{/tr}
                        </th>
                        <th>
                            {icon name='edit' iclass='tips' ititle="{tr}WYSIWYG or HTML allowed:{/tr}{tr}HTML syntax is allowed either by page setting or use of the WYSIWIG editor{/tr}"}
                        </th>
                        {if $prefs.markdown_enabled eq 'y'}
                            <th>
                                <a href="#"  class="tips" title="Syntax|Markdown" role="button">
                                    <svg aria-hidden="true" height="16" width="16">
                                        <path fill-rule="evenodd" d="M14.85 3H1.15C.52 3 0 3.52 0 4.15v7.69C0 12.48.52 13 1.15 13h13.69c.64 0 1.15-.52 1.15-1.15v-7.7C16 3.52 15.48 3 14.85 3zM9 11H7V8L5.5 9.92 4 8v3H2V5h2l1.5 2L7 5h2v6zm2.99.5L9.5 8H11V5h2v3h1.5l-2.51 3.5z"></path>
                                    </svg>
                                </a>
                            </th>
                        {/if}
                        <th></th>
                        {if $prefs.default_wiki_diff_style != "old" and $history}
                            <th colspan="3" class="text-center">
                                <input type="submit" class="btn btn-info btn-sm" name="compare" value="{tr}Compare{/tr}">
                            </th>
                        {/if}
                    </tr>
                    <tr class="active">
                        {if $history_offset eq 1}
                            {if $tiki_p_remove eq 'y'}
                                <td>&nbsp;</td>
                            {/if}
                            <td class="text-start">
                                {$info.lastModif|tiki_short_datetime}
                                {icon name="user"} {$info.user|userlink}
                                {if $prefs.feature_wiki_history_ip ne 'n'}{tr _0=$info.ip}from %0{/tr}{/if}

                                {if (isset($flaggedrev_approval) and $flaggedrev_approval) and $tiki_p_wiki_view_latest eq 'y'
                                    and $info.approved}<strong>({tr}approved{/tr})</strong>{/if}

                                {if !empty($info.comment)}<div>{$info.comment|escape}</div>{/if}

                                {if isset($translation_sources[$info.version]) and $translation_sources[$info.version]}
                                    {foreach item=source from=$translation_sources[$info.version]}
                                        <div>
                                            {tr}Updated from:{/tr} {self_link _script="tiki-index.php" page=$source.page|escape}{$source.page}{/self_link} at version {$source.version}
                                        </div>
                                    {/foreach}
                                {/if}
                                {if isset($translation_targets[$info.version]) and $translation_targets[$info.version]}
                                    {foreach item=target from=$translation_targets[$info.version]}
                                    <div>
                                        {tr}Used to update:{/tr} {self_link _script="tiki-index.php" page=$target.page|escape}{$target.page}{/self_link} to version {$target.version}
                                    </div>
                                    {/foreach}
                                {/if}
                            </td>
                            {if $prefs.feature_contribution eq 'y'}
                                <td>{section name=ix loop=$contributions}{if !$smarty.section.ix.first},{/if}{$contributions[ix].name|escape}{/section}</td>
                            {/if}
                            {if $prefs.feature_contribution eq 'y' and $prefs.feature_contributor_wiki eq 'y'}
                                <td>
                                    {section name=ix loop=$contributors}{if !$smarty.section.ix.first},{/if}{$contributors[ix].login|username}{/section}
                                </td>
                            {/if}
                            <td class="button_container">
                                {if $current eq $info.version}
                                    <strong>{/if}{$info.version}<br>{tr}Current{/tr}{if $current eq $info.version}</strong>
                                {/if}
                            </td>
                            <td class="button_container">
                                {if $info.is_html || $info.wysiwyg eq "y"}
                                    {icon name='html' iclass='tips' ititle=':{tr}HTML allowed or WYSIWYG t{/tr}'}
                                {/if}
                            </td>
                            <td class="button_container">
                                {if !empty($info.is_markdown)}
                                    {icon name='check' iclass='tips' ititle=':{tr}Markdown{/tr}'}
                                {/if}
                            </td>
                            <td class="button_container" style="white-space: nowrap">
                                {actions}
                                    {strip}
                                        <action>
                                            {self_link _noauto="y" page=$page preview=$info.version _icon_name="view" _menu_text='y' _menu_icon='y'}
                                                {tr}View{/tr}
                                            {/self_link}
                                        </action>
                                        {if $tiki_p_wiki_view_source eq "y" and $prefs.feature_source eq "y"}
                                            <action>
                                                {self_link _noauto="y" page=$page source=$info.version _icon_name="code" _menu_text='y' _menu_icon='y'}
                                                    {tr}Source{/tr}
                                                {/self_link}
                                            </action>
                                        {/if}
                                    {/strip}
                                {/actions}
                            </td>
                            {if $prefs.default_wiki_diff_style ne "old" and $history}
                                <td class="button_container">
                                    <input type="radio" class="form-check-input" name="oldver" value="0" title="{tr}Compare{/tr}" {if isset($old.version)
                                        and $old.version == $info.version}checked="checked"{/if}>
                                </td>
                                <td class="button_container">
                                    <input type="radio" class="form-check-input" name="newver" value="0" title="{tr}Compare{/tr}" {if (isset($new.version)
                                        and $new.version == $info.version) or (!isset($smarty.request.diff_style)
                                        or !$smarty.request.diff_style)}checked="checked"{/if}>
                                </td>
                            {/if}
                        {/if}
                    </tr>

                    {foreach name=hist item=element from=$history}
                        <tr>
                            {if $tiki_p_remove eq 'y'}
                                <td>
                                    <input type="checkbox" name="checked[]" value="{$element.version}">
                                </td>
                            {/if}
                            <td class="text-start">
                                {$element.lastModif|tiki_short_datetime}
                                {icon name="user"} {$element.user|userlink}
                                {if $prefs.feature_wiki_history_ip ne 'n'}{tr _0=$element.ip}from %0{/tr}{/if}

                                {if !empty($element.comment)}<span class="form-text">{$element.comment|escape}</span>{/if}

                                {if (isset($flaggedrev_approval) and $flaggedrev_approval) and $tiki_p_wiki_view_latest eq 'y' and $element.approved}<strong>({tr}approved{/tr})</strong>{/if}
                                {if (isset($flaggedrev_approval) and $flaggedrev_approval) and $element.rejected}<strong>({tr}rejected:{/tr}</strong> {$element.rejection_reason}<strong>)</strong>{/if}

                                {if isset($translation_sources[$element.version]) and $translation_sources[$element.version]}
                                    {foreach item=source from=$translation_sources[$element.version]}
                                    <div>
                                        {tr}Updated from:{/tr} {self_link _script="tiki-index.php" page=$source.page|escape}{$source.page}{/self_link} at version {$source.version}
                                    </div>
                                    {/foreach}
                                {/if}
                                {if isset($translation_targets[$element.version]) and $translation_targets[$element.version]}
                                    {foreach item=target from=$translation_targets[$element.version]}
                                    <div>
                                        {tr}Used to update:{/tr} {self_link _script="tiki-index.php" page=$target.page|escape}{$target.page}{/self_link} to version {$target.version}
                                    </div>
                                    {/foreach}
                                {/if}
                            </td>
                            {if $prefs.feature_contribution eq 'y'}
                                <td>
                                    {section name=ix loop=$element.contributions}{if !$smarty.section.ix.first}&nbsp;{/if}{$element.contributions[ix].name|escape}{/section}
                                </td>
                            {/if}
                            {if $prefs.feature_contribution eq 'y' and $prefs.feature_contributor_wiki eq 'y'}
                                <td>
                                    {section name=ix loop=$element.contributors}{if !$smarty.section.ix.first},{/if}{$element.contributors[ix].login|username}{/section}
                                </td>
                            {/if}
                            <td class="button_container">
                                {if $current eq $element.version}<strong>{/if}
                                {if $show_all_versions eq "n" and not empty($element.session)}
                                    <em>{$element.session} - {$element.version}</em>
                                {else}
                                    {$element.version}
                                {/if}
                                {if $current eq $element.version}</strong>{/if}
                            </td>
                            <td class="button_container">
                                {if $element.is_html eq "1"}
                                    {icon name='html' iclass='tips' ititle=':{tr}HTML allowed{/tr}'}
                                {elseif $element.wysiwyg eq "y"}
                                    {icon name='bold' iclass='tips' ititle=':{tr}WYSIWYG{/tr}'}
                                {/if}
                            </td>
                            <td class="button_container">
                                {if !empty($element.is_markdown)}
                                    {icon name='check' iclass='tips' ititle=':{tr}Markdown{/tr}'}
                                {/if}
                            </td>
                            <td class="button_container" style="white-space: nowrap">
                                {actions}
                                    {strip}
                                        <action>
                                            {self_link _noauto="y" page=$page preview=$element.version _icon_name="view" _menu_text='y' _menu_icon='y'}
                                                {tr}View{/tr}
                                            {/self_link}
                                        </action>
                                        {if $tiki_p_wiki_view_source eq "y" and $prefs.feature_source eq "y"}
                                            <action>
                                                {self_link _noauto="y" page=$page source=$element.version _icon_name="code" _menu_text='y' _menu_icon='y'}
                                                    {tr}Source{/tr}
                                                {/self_link}
                                            </action>
                                        {/if}
                                        {if $prefs.default_wiki_diff_style eq "old"}
                                            <action>
                                                {self_link _noauto="y" page=$page diff2=$element.version diff_style="sideview" _icon_name="copy" _menu_text='y' _menu_icon='y'}
                                                    {tr}Compare{/tr}
                                                {/self_link}
                                            </action>
                                            <action>
                                                {self_link _noauto="y" page=$page diff2=$element.version diff_style="unidiff" _icon_name="difference" _menu_text='y' _menu_icon='y'}
                                                    {tr}Difference{/tr}
                                                {/self_link}
                                            </action>
                                        {/if}
                                        {if $tiki_p_rollback eq 'y' && $lock neq true && $current neq $element.version}
                                            <action>
                                                {self_link _noauto="y" _script="tiki-rollback.php" page=$page version=$element.version _icon_name="undo" _menu_text='y' _menu_icon='y'}
                                                    {tr _0=$element.version}Revert to version %0{/tr}
                                                {/self_link}
                                            </action>
                                        {/if}
                                    {/strip}
                                {/actions}
                            </td>
                            {if $prefs.default_wiki_diff_style ne "old"}
                                <td class="button_container">
                                    {if $show_all_versions eq 'n' and not empty($element.session)}
                                        <input type="radio" class="form-check-input" name="oldver" value="{$element.session}"
                                            title="{tr}Older Version{/tr}" {if (isset($old.version) and isset($element.session) and $old.version == $element.session)
                                            or ((!isset($smarty.request.diff_style) or !$smarty.request.diff_style)
                                            and $smarty.foreach.hist.first)}checked="checked"{/if}>
                                    {else}
                                        <input type="radio" class="form-check-input" name="oldver" value="{$element.version}"
                                            title="{tr}Older Version{/tr}" {if (isset($old.version) and isset($element.version) and $old.version == $element.version)
                                            or ((!isset($smarty.request.diff_style) or !$smarty.request.diff_style)
                                            and $smarty.foreach.hist.first)}checked="checked"{/if}>
                                    {/if}
                                </td>
                                <td class="button_container">
                                    {* if $smarty.foreach.hist.last &nbsp; *}
                                    <input type="radio" class="form-check-input" name="newver" value="{$element.version}" title="Select a newer version for comparison"
                                        {if isset($new.version) and $new.version == $element.version}checked="checked"{/if} >
                                </td>
                            {/if}
                        </tr>
                    {/foreach}
                </table>
            </div>
            <div class="input-group col-sm-8 mb-4">
                <select class="form-select" name="action">
                    <option value="no_action" selected disabled>
                        {tr}Select action to perform with checked{/tr}...
                    </option>
                    <option value="remove_page_versions">
                        {tr}Remove{/tr}
                    </option>
                </select>
                <button
                    type="submit"
                    form="pagehistory-rmv-history"
                    formaction="{bootstrap_modal controller=wiki}"
                    class="btn btn-primary"
                    onclick="confirmPopup()"
                >
                    {tr}OK{/tr}
                </button>
            </div>
            {if $paginate}
                {if isset($smarty.request.history_offset)}
                    {pagination_links count=$history_count offset=$smarty.request.history_offset offset_arg="history_offset" step=$history_pagesize}
                    {/pagination_links}
                {else}
                    {pagination_links count=$history_count offset_arg="history_offset" step=$history_pagesize}
                    {/pagination_links}
                {/if}
            {/if}
            </form>
        </div>
{/if}
