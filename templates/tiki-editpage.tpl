{extends $global_extend_layout|default:'layout_edit.tpl'}
{block name=title}
    {if $translation_mode eq 'n'}
        <h1 class="pagetitle">
            {capture name="pageDescription"}
                {$page|escape}{if $pageAlias ne ''} ({$pageAlias|escape}){/if}
            {/capture}
            {if isset($hdr) && $prefs.wiki_edit_section eq 'y'}
                {tr}Edit Section:{/tr} {$smarty.capture.pageDescription}
            {else}
                {tr _0='<a class="edit_pagetitle" href="'|cat:($page|sefurl)|cat:'">'|cat:$smarty.capture.pageDescription|cat:'</a>'}Edit %0{/tr}
            {/if}
        </h1>
    {else}
        {title}{tr _0=$page}Update '%0'{/tr}{/title}
    {/if}
{/block}
{function name="render_editor" content=""}
    {textarea codemirror='true' aria-label="{tr}Edit page{/tr}"}{$content}{/textarea}
{/function}
{$loadtextarea={render_editor content=$pagedata}}

{block name=content}
    {if $page|lower neq 'sandbox' and $prefs.feature_contribution eq 'y' and $prefs.feature_contribution_mandatory eq 'y'}
        {remarksbox type='tip' title="{tr}Tip{/tr}"}
            <strong class='mandatory_note'>{tr}Fields marked with an * are mandatory.{/tr}</strong>
        {/remarksbox}
    {/if}
    {if isset($customTip)}
        {remarksbox type='tip' title=$customTipTitle}
            {tr}{$customTip|escape}{/tr}
        {/remarksbox}
    {/if}
    {if isset($wikiHeaderTpl)}
        {$wikiHeaderTpl}
    {/if}
    {if $prefs.ajax_autosave eq "y"}
        <div class="float-end">
            {self_link _icon_name="view" _class="previewBtn tips" _ajax="n" _title=":{tr}Preview your changes{/tr}"}
            {/self_link}
        </div>
        {jq} $(".previewBtn").on("click", function(){
            $("body > .popover").hide();
            auto_save('editwiki', autoSaveId);
            if ($('#autosave_preview:visible').length === 0) {
                if (!ajaxPreviewWindow) {
                    setCookie("preview_diff_style", "", "preview", "session", window.tikiCookieConstants.BUILTIN_COOKIE_CATEGORY_FUNCTIONAL);
                    $("#preview_diff_style").val("");
                    $('#autosave_preview').slideDown('slow', function(){ ajax_preview( 'editwiki', autoSaveId, true );});
                }
            } else {
                ajax_preview( 'editwiki', autoSaveId, true );
            }
            return false;
        });
        $(".pdfPreviewBtn").on("click", function(){
            function autoSaveCallback() {
                $.getJSON($.service("edit", "preview"), {
                    editor_id: 'editwiki',
                    autoSaveId: autoSaveId,
                    inPage: 1,
                    show_preview: 1
                }, function(response) {
                    if (response.pdf) {
                        var pdfDataUri = 'data:application/pdf;base64,' + response.pdf;
                        var embedHtml = "<embed width='100%' height='100%' src='" + pdfDataUri + "' type='application/pdf' />";
                        var pdfWindow = window.open("", "_blank");
                        pdfWindow.document.open();
                        pdfWindow.document.write(embedHtml);
                        pdfWindow.document.close();
                        return false;
                    } else if (response.error) {
                        $("#tikifeedback").html(
                            `<div class = "alert alert-danger alert-dismissible">
                                ${response.error}
                                <button type="button" class= "btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>`
                        );
                        return false;
                    } else {
                        $("#tikifeedback").html(
                            `<div class = "alert alert-danger alert-dismissible">
                                Error generating PDF preview.
                                <button type="button" class= "btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>`
                        );
                        return false;
                    }
                }).fail(function() {
                    $("#tikifeedback").html(
                        `<div class = "alert alert-danger alert-dismissible">
                            Error generating PDF preview.
                            <button type="button" class= "btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>`
                    );
                    return false;
                });
            }
            auto_save('editwiki', autoSaveCallback);
            return false;
        });{/jq}
    {else}
        <input type="hidden" value="{$alert_content}" name="alert_content">
        <div id="tiki_autosave_alert"></div>
    {/if}
    {if isset($data.draft)}
        {tr}Draft written on{/tr} {$data.draft.lastModif|tiki_long_time}<br/>
        {if $data.draft.lastModif < $data.lastModif}
            <b>{tr}Warning: new versions of this page have been made after this draft{/tr}</b>
        {/if}
    {/if}
    {if $page|lower eq 'sandbox'}
        {remarksbox type='tip' title="{tr}Tip{/tr}"}
            {tr}The Sandbox is a page where you can practice editing, etc. and use the preview feature to check the appearance of the page. No versions are stored for this page.{/tr}
        {/remarksbox}
    {/if}
        {if isset($included_by)}
                {remarksbox type='Warning' title="{tr}Warning{/tr}"}
                        {tr}The following item(s) include this one with Plugin Include and might be affected by these changes. Renaming sections can break related item(s).{/tr}
                {include file='tiki-edit-page-included_by.tpl'}
                {/remarksbox}
        {/if}
    {if $recursive_include eq 'y'}
        {remarksbox type='warning' title="{tr}Warning{/tr}"}
            {tr}You can not include a page within itself{/tr}
        {/remarksbox}
    {/if}
    {if $category_needed eq 'y'}
        {remarksbox type='Warning' title="{tr}Warning{/tr}"}
            <div class="alert alert-warning alert-dismissible"><em class=''>{tr}A category is mandatory{/tr}</em></div>
        {/remarksbox}
    {/if}
    {if $contribution_needed eq 'y'}
        {remarksbox type='Warning' title="{tr}Warning{/tr}"}
            <div class="alert alert-warning alert-dismissible"><em class=''>{tr}A contribution is mandatory {/tr}</em></div>
        {/remarksbox}
    {/if}
    {if isset($summary_needed) && $summary_needed eq 'y'}
        {remarksbox type='Warning' title="{tr}Warning{/tr}"}
            <div class="alert alert-warning alert-dismissible"><em class=''>{tr}An edit summary is mandatory {/tr}</em></div>
        {/remarksbox}
    {/if}
    {if $likepages}
        <div>
            {tr}Perhaps you are looking for:{/tr}
            {if $likepages|@count < 0}
                <ul>
                    {section name=back loop=$likepages}
                        <li>
                            <a href="{$likepages[back]|sefurl}" class="wiki">{$likepages[back]|escape}</a>
                        </li>
                    {/section}
                </ul>
            {else}
                <div class="table-responsive">
                    <table class="table">
                        <tr>
                            {cycle name=table values=',,,,</tr><tr>' print=false advance=false}
                            {section name=back loop=$likepages}
                                <td><a href="{$likepages[back]|sefurl}" class="wiki">{$likepages[back]|escape}</a></td>
                                {cycle name=table}
                            {/section}
                        </tr>
                    </table>
                </div>
            {/if}
        </div>
    {/if}
    {if $preview or $prefs.ajax_autosave eq "y"}
        {include file='tiki-preview.tpl'}
    {/if}
    {if isset($diff_style)}
        <div id="diff_outer">
            {if $translation_mode == 'y'}
                <div class="translation_message">
                    <h2>{icon name="language"} {tr}Translate from:{/tr} {$source_page|escape}</h2>
                    {tr}Changes that need to be translated are highlighted below.{/tr}
                </div>
            {/if}
            <div id="diff_history">
                {include file='pagehistory.tpl' count=0}
                {if $diff_summaries}
                    <div class="wikitext" id="diff_versions">
                        <ul>
                            {foreach item=diff from=$diff_summaries}
                                <li>
                                    {if is_array($diff)}
                                        {tr}Version:{/tr} {$diff.version|default:"<em>{tr}No diff yet{/tr}</em>"|escape} - {$diff.comment|default:"<em>{tr}No comment{/tr}</em>"|escape}
                                    {else}
                                        {tr}Version:{/tr} <em>{tr}No diff yet{/tr}</em> - <em>{tr}No comment{/tr}</em>
                                    {/if}
                                    {if count($diff_summaries) gt 1}
                                        {$diff_version=$diff.version}
                                        {icon name="next" onclick="\$('input[name=oldver]').val($diff_version);\$('#editpageform').trigger('submit');return false;" title=":{tr}View{/tr}" style="cursor: pointer"}
                                    {/if}
                                </li>
                            {/foreach}
                            {button _onclick="\$('input[name=oldver]').val(1);\$('#editpageform').trigger('submit');return false;" _text="{tr}All Versions{/tr}" _ajax="n"}
                        </ul>
                    </div>
                {/if}
            </div>
        </div>
    {/if}
    {if $prompt_for_edit_or_translate == 'y'}
        {include file='tiki-edit-page-include-prompt_for_edit_or_translate.tpl'}
    {/if}
    <form enctype="multipart/form-data" method="post" action="tiki-editpage.php?page={$page|escape:'url'}" {*class="form-horizontal"*} id='editpageform' name='editpageform'>
        {ticket}
        <input type="hidden" name="no_bl" value="y">
        {if !empty($smarty.request.returnto)}<input type="hidden" name="returnto" value="{$smarty.request.returnto}">{/if}
        {if isset($diff_style)}
            <select name="diff_style" class="wikiaction" title="{tr}Edit wiki page{/tr}|{tr}Select the style used to display differences to be translated.{/tr}">
                {foreach  $diff_styles.options as $value => $label}
                    {if $value eq 'htmldiff' or $value eq 'inlinediff' or $value eq 'inlinediff-full' }
                        <option value="{$value}" {if $diff_style == $value} selected="selected"{/if}>
                            {tr}{$label}{/tr}
                        </option>
                    {/if}
                {/foreach}
            </select>
            <input type="submit" class="wikiaction tips btn btn-primary" title="{tr}Edit wiki page{/tr}|{tr}Change the style used to display differences to be translated.{/tr}" name="preview" value="{tr}Change diff styles{/tr}" onclick="needToConfirm=false;">
        {/if}
        {if $page_ref_id}<input type="hidden" name="page_ref_id" value="{$page_ref_id}">{/if}
        {if isset($hdr)}<input type="hidden" name="hdr" value="{$hdr}">{/if}
        {if isset($cell)}<input type="hidden" name="cell" value="{$cell}">{/if}
        {if isset($pos)}<input type="hidden" name="pos" value="{$pos}">{/if}
        {if $current_page_id}<input type="hidden" name="current_page_id" value="{$current_page_id}">{/if}
        {if $add_child}<input type="hidden" name="add_child" value="true">{/if}
        {if $preview or $prefs.wiki_actions_bar eq 'top' or $prefs.wiki_actions_bar eq 'both'}
            <div class='top_actions'>
                {include file='wiki_edit_actions.tpl' rootPath=$ajaxURL wysiwyg=$wysiwyg}
            </div>
        {/if}
        <div class="mb-3 mx-0">
            {if isset($page_badchars_display)}
                {if $prefs.wiki_badchar_prevent eq 'y'}
                    {remarksbox type=errors title="{tr}Invalid page name{/tr}"}
                        {tr _0=$page_badchars_display|escape}The page name specified contains unallowed characters. It will not be possible to save the page until those are removed: <strong>%0</strong>{/tr}
                    {/remarksbox}
                {else}
                    {remarksbox type=tip title="{tr}Tip{/tr}"}
                        {tr _0=$page_badchars_display|escape}The page name specified contains characters that may render the page hard to access. You may want to consider removing those: <strong>%0</strong>{/tr}
                    {/remarksbox}
                {/if}
                    <p>{tr}Page name:{/tr} <input type="text" name="page" value="{$page|escape}">
                        <input type="submit" class="btn btn-primary btn-sm" name="rename" value="{tr}Rename{/tr}">
                    </p>
            {else}
                <input type="hidden" name="page" value="{$page|escape}">
                {* the above hidden field is needed for auto-save to work *}
            {/if}
            {tabset name='tabs_editpage' cookietab=1}
                {tab name="{tr}Edit page{/tr}"}
                    {if $prefs.wiki_description_edit_tab_input eq 'y'}
                        {if $page|lower neq 'sandbox'}
                            {if $prefs.feature_wiki_description eq 'y' or $prefs.metatag_pagedesc eq 'y'}
                                <div class="mb-3 row">
                                    {if $prefs.metatag_pagedesc eq 'y'}
                                        <label for="description" class="col col-auto col-form-label">{tr}Description (used for metatags){/tr}</label>
                                    {else}
                                        <label for="description" class="col col-auto col-form-label">{tr}Description{/tr}</label>
                                    {/if}
                                    <div class="col flex-grow-1">
                                        <input class="form-control" type="text" id="description" name="description" maxlength="200" value="{$description|escape}">
                                        <small id="description-limit-msg" class="text-danger" style="display:none;">
                                            {tr}You have reached the number of characters allowed (200 max) for the description field{/tr}
                                        </small>
                                        {jq}
                                            $("#description").on("keyup", function () {
                                                var length = $(this).val().length;
                                                if(length >= 200) {
                                                    $("#description-limit-msg").show();
                                                } else {
                                                    $("#description-limit-msg").hide();
                                                }
                                            });
                                        {/jq}
                                        <div id="description-limit-msg" class="form-text" style="display: none;">
                                            {tr}The recommended maximum length for the description is 200 characters.{/tr}
                                        </div>
                                    </div>
                                </div>
                            {/if}
                        {/if}
                    {/if}
                    {if $translation_mode == 'y'}
                        <div class="translation_message">
                            <h2>{tr}Translate to:{/tr} {$target_page|escape}</h2>
                            {tr}Reproduce the changes highlighted on the left using the editor below{/tr}.
                        </div>
                    {/if}
                    {$loadtextarea}
                        {if $prefs.wiki_freetags_edit_position eq 'edit'}
                            {if $prefs.feature_freetags eq 'y' and $tiki_p_freetags_tag eq 'y'}
                                <fieldset>
                                    <legend>{tr}Tags{/tr}</legend>
                                    <table>
                                        {include file='freetag.tpl'}
                                    </table>
                                </fieldset>
                            {/if}
                    {/if}
                    {if $page|lower neq 'sandbox'}
                        {if (!isset($wysiwyg) || $wysiwyg neq 'y') and $prefs.feature_wiki_pictures eq 'y' and $tiki_p_upload_picture eq 'y' and $prefs.feature_filegals_manager neq 'y'}
                            <fieldset>
                                <legend>{tr}Upload picture{/tr}</legend>
                                <input type="hidden" name="MAX_FILE_SIZE" value="1000000000">
                                <input type="hidden" name="hasAlreadyInserted" value="">
                                <input type="hidden" name="prefix" value="img/wiki_up/{if $tikidomain}{$tikidomain}/{/if}">
                                <input name="picfile1" type="file" accept="image/*" class="form-control" onchange="insertImgFile('editwiki','picfile1','hasAlreadyInserted','img')">
                                <a href="#" onclick="needToConfirm = false; addImgForm(event)" role="button">{tr}Add another image{/tr}</a>
                            </fieldset>
                        {/if}
                    {/if}
                {/tab}
                {if $prefs.feature_categories eq 'y' and $tiki_p_modify_object_categories eq 'y' and count($categories) gt 0}
                    {tab name="{tr}Categories{/tr}"}
                        {if $categIds}
                            {remarksbox type="note" title="{tr}Note:{/tr}"}
                                <strong>{tr}Categorization has been preset for this edit{/tr}</strong>
                            {/remarksbox}
                            {section name=o loop=$categIds}
                                <input type="hidden" name="cat_categories[]" value="{$categIds[o]}">
                            {/section}
                            <input type="hidden" name="cat_categorize" value="on">
                            {if $prefs.feature_wiki_categorize_structure eq 'y'}
                                {tr}Categories will be inherited from the structure top page{/tr}
                            {/if}
                        {else}
                            {if $page|lower ne 'sandbox'}
                                {include file='categorize.tpl' notable='y'}
                            {/if}{* sandbox *}
                        {/if}
                    {/tab}
                {/if}
                {if $prefs.wiki_freetags_edit_position eq 'freetagstab'}
                    {if $prefs.feature_freetags eq 'y' and $tiki_p_freetags_tag eq 'y'}
                        {tab name="{tr}Tags{/tr}"}
                            {include file='freetag.tpl'}
                        {/tab}
                    {/if}
                {/if}
                {if !empty($showPropertiesTab)}
                    {tab name="{tr}Properties{/tr}"}
                        <div class="t_navbar mb-4 clearfix">
                            {if $tiki_p_admin_wiki eq "y"}
                                <a href="tiki-admin.php?page=wiki" class="btn btn-link" title="{tr}Leave page and go to....{/tr}">
                                    {icon name="cog"} {tr}Wiki Preferences{/tr}
                                </a>
                            {/if}
                            {if $tiki_p_admin_wiki eq 'y' or $tiki_p_assign_perm_wiki_page eq 'y'}
                                {permission_link mode=link type="wiki page" id=$page permType=wiki title=$page}
                            {/if}
                            {if isset($tiki_p_edit_content_templates) and tiki_p_edit_content_templates eq 'y'}
                                <a href="tiki-admin_content_templates.php" class="btn btn-link"  title="{tr}Leave page and go to....{/tr}" onclick="needToConfirm = true;">
                                    {icon name="content-template"} {tr}Content Templates{/tr}
                                </a>
                            {/if}
                            {if isset($tiki_p_edit_structures) and $tiki_p_edit_structures eq 'y'}
                                <a href="tiki-admin_structures.php" class="btn btn-link" title="{tr}Leave page and go to....{/tr}">
                                    {icon name="structure"} {tr}Structures{/tr}
                                </a>
                            {/if}
                            {if $prefs.feature_copyright eq 'y' and $prefs.wiki_feature_copyrights eq 'y'}
                                {if !empty($copyrights)}
                                    <a href="copyrights.php?page={$page|escape}" class="btn btn-link" title="{tr}Leave page and go to....{/tr}">{icon name="copyright"} {tr}Copyright notices{/tr}</a>
                                {/if}
                                <a href="{$prefs.wikiLicensePage|sefurl}" class="btn btn-link" title="{tr}Leave page and go to....{/tr}">{icon name="wiki"} {tr}Copyright page{/tr}</a>
                            {/if}
                        </div>

                        {assign var="attachments" value=( !isset($wysiwyg) || $wysiwyg neq 'y') && $prefs.feature_wiki_attachments == 'y' && ($tiki_p_wiki_attach_files eq 'y' or $tiki_p_wiki_admin_attachments eq 'y') }{* for Attach file *}
                        {assign var="authors" value=$page|lower neq 'sandbox' && $tiki_p_admin_wiki eq 'y' && $prefs.wiki_authors_style_by_page eq 'y'}{* for Authors *}
                        {assign var="biblioSection" value=$page|lower neq 'sandbox' && $prefs.wikiplugin_addreference eq 'y' && $showBiblioSection}{* for Bibliography *}
                        {assign var="copyright" value=$page|lower neq 'sandbox' && $prefs.feature_copyright eq 'y' && $prefs.wiki_feature_copyrights eq 'y'}{* for Copyright *}
                        {assign var="geolocation" value=$prefs.geo_locate_wiki eq 'y'}{* for Geolocation *}
                        {assign var="myfootnotes" value=$prefs.feature_wiki_footnotes eq 'y' && $user}{* for My Footnotes *}
                        {assign var="structures" value=$page|lower neq 'sandbox' && $prefs.feature_wiki_structure eq 'y' && $showstructs|@count gt 0}{* for Structures *}

                        {if  $attachments || $authors || $biblioSection || $copyright || $geolocation || $myfootnotes || $structures}
                            <h3>{tr}Editing : {/tr}</h3>
                            {if $attachments}
                                <input type="hidden" name="MAX_FILE_SIZE" value="1000000000">
                                <input type="hidden" name="hasAlreadyInserted2" value="">
                                <input type="hidden" id="page2" name="page2" value="{$page}">
                                <div class="mb-3 row align-items-left">
                                    <label for="attach-upload" class="col-sm-4 col-form-label">{tr}Attach file{/tr}</label>
                                    <div class="col-sm-8 mb-2">
                                        <input name="{if $prefs.feature_use_fgal_for_wiki_attachments neq 'y'}userfile2{else}userfile[0]{/if}" type="file" id="attach-upload" class="form-control">
                                    </div>
                                    <div class="col-sm-8 offset-sm-4 d-flex flex-row flex-wrap align-items-center">
                                        <input type="text" name="attach_comment" class="col-sm-10 form-control me-2" maxlength="250" id="attach-comment" placeholder="{tr}Comment{/tr}">
                                        <input type="submit" class="wikiaction btn btn-primary" name="attach" value="{tr}Attach{/tr}" onclick="javascript:needToConfirm=false;{if $prefs.feature_use_fgal_for_wiki_attachments neq 'y'}insertImgFile('editwiki','userfile2','hasAlreadyInserted2','file', 'page2', 'attach_comment');{/if} return true;">
                                    </div>
                                </div>
                            {/if}
                            {if $authors}
                                <div class="mb-3 row">
                                    <label class="col-md-4 col-form-label">{tr}Authors{/tr}</label>
                                    <div class="col-md-8">
                                        <select name="wiki_authors_style" id="wiki_authors_style" class="form-control">
                                            <option value="" style="font-style:italic;border-bottom:1px dashed #666;"{if $wiki_authors_style eq ''} selected="selected"{/if}>{tr}Site default{/tr}</option>
                                            <option value="classic"{if $wiki_authors_style eq 'classic'} selected="selected"{/if}>{tr}as Creator &amp; Last Editor{/tr}</option>
                                            <option value="business"{if $wiki_authors_style eq 'business'} selected="selected"{/if}>{tr}Business style{/tr}</option>
                                            <option value="collaborative"{if $wiki_authors_style eq 'collaborative'} selected="selected"{/if}>{tr}Collaborative style{/tr}</option>
                                            <option value="lastmodif"{if $wiki_authors_style eq 'lastmodif'} selected="selected"{/if}>{tr}Page last modified on{/tr}</option>
                                            <option value="none"{if $wiki_authors_style eq 'none'} selected="selected"{/if}>{tr}no (disabled){/tr}</option>
                                        </select>
                                    </div>
                                </div>
                            {/if}
                            {if $biblioSection}
                                <div class="mb-3 row">
                                    <label for="" class="col-md-4 col-form-label">{tr}Bibliography{/tr}</label>
                                    <div class="col-md-8">
                                        {include file='addreference.tpl'}
                                    </div>
                                </div>
                            {/if}
                            {if $copyright}
                                <div class="mb-3 row clearfix">
                                    <label for="" class="col-md-4 col-form-label">{tr}Copyright{/tr}</label>
                                    <div class="col-md-8">
                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label" for="copyrightTitle">{tr}Title{/tr}</label>
                                            <div class="col-md-8">
                                                <input class="form-control wikiedit" type="text" id="copyrightTitle" name="copyrightTitle" value="{$copyrightTitle|escape}">
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label" for="copyrightYear">{tr}Year{/tr}</label>
                                            <div class="col-md-8">
                                                <input size="4" class="form-control wikiedit" type="text" id="copyrightYear" name="copyrightYear" value="{$copyrightYear|escape}">
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label" for="copyrightAuthors">{tr}Authors{/tr}</label>
                                            <div class="col-md-8">
                                                <input class="form-control wikiedit" id="copyrightAuthors" name="copyrightAuthors" type="text" value="{$copyrightAuthors|escape}">
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label class="col-md-4 col-form-label" for="copyrightHolder">{tr}Copyright Holder{/tr}</label>
                                            <div class="col-md-8">
                                                <input class="form-control wikiedit" id="copyrightHolder" name="copyrightHolder" type="text" value="{$copyrightHolder|escape}">
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            {if $prefs.wikiSubmitNotice neq ""}
                                                {remarksbox type="note" title="{tr}Important:{/tr}"}
                                                    {tr}{$prefs.wikiSubmitNotice}{/tr}
                                                {/remarksbox}
                                            {/if}
                                        </div>
                                    </div>
                                </div>
                            {/if}
                            {if $geolocation}
                                <div class="mb-3 row">
                                    <label for="" class="col-md-4 col-form-label">{tr}Geolocation{/tr}</label>
                                    <div class="col-md-8">
                                        <div class="map-container form-control" data-geo-center="{defaultmapcenter}" data-target-field="geolocation" style="height: 250px;"></div>
                                        <input type="hidden" name="geolocation" value="{$geolocation_string}">
                                    </div>
                                </div>
                            {/if}
                            {if $myfootnotes}
                                {if $user}
                                    <div class="mb-3 row">
                                        <label for="footnote" class="col-md-4 col-form-label">{tr}My Footnotes{/tr}</label>
                                        <div class="col-md-8">
                                            <textarea id="footnote" name="footnote" class="form-control" rows="8">{$footnote|escape}</textarea>
                                        </div>
                                    </div>
                                {/if}
                            {/if}
                            {if $structures}
                                <div class="mb-3 row">
                                    <label class="col-md-4 col-form-label">{tr}Structures{/tr}</label>
                                    <div class="col-md-8" id="showstructs">
                                        <ul>
                                            {foreach from=$showstructs item=page_info}
                                                <li>{$page_info.pageName}{if !empty(${$page_info.outputType}.page_alias)}({$page_info.page_alias}){/if}</li>
                                            {/foreach}
                                        </ul>
                                    </div>
                                </div>
                            {/if}
                            <hr>
                        {/if}

                        {assign var="prefTagTitle" value=($prefs.wiki_customize_title_tag eq 'y')} {* for Content of the tag title *}
                        {assign var="prefDescription" value=(($prefs.feature_wiki_description eq 'y' || $prefs.metatag_pagedesc eq 'y') && $prefs.wiki_description_edit_tab_input neq 'y')} {* for Description *}
                        {assign var="freetags" value=$page|lower neq 'sandbox' && ($prefs.wiki_freetags_edit_position eq 'properties' || $prefs.wiki_freetags_edit_position eq '') && $prefs.feature_freetags eq 'y' && $tiki_p_freetags_tag eq 'y'} {* for Tags *}
                        {assign var="metaRobots" value=$prefs.metatag_robotscustom eq 'y'} {* for Meta robots *}
                        {assign var="forSitemap" value=$prefs.sitemap_enable eq 'y'} {* for For sitemap *}

                        {if $prefTagTitle || $prefDescription || $freetags || $metaRobots || $forSitemap}
                            <h3>{tr}SEO :{/tr}</h3>
                            {if $prefTagTitle}
                                <div class="mb-3 row clearfix">
                                    <label for="content_title" class="col-md-4 col-form-label">{tr}Content of the tag title{/tr}</label>
                                    <div class="col-md-8">
                                        <input type="text" class="form-control" id="content_title" name="content_title" value="{$tagTitle|escape}" placeholder="{tr}Type the content of title{/tr}">
                                        <span class="form-text">
                                            {tr}this field allows you to customize the content of your title tag to get a better referencing{/tr}
                                        </span>
                                    </div>
                                </div>
                            {/if}
                            {if $prefDescription}
                                <div class="mb-3 row">
                                    {if $prefs.metatag_pagedesc eq 'y'}
                                        <label for="" class="col-md-4 col-form-label">{tr}Description (used for metatags){/tr}</label>
                                    {else}
                                        <label for="" class="col-md-4 col-form-label">{tr}Description{/tr}</label>
                                    {/if}
                                    <div class="col-md-8">
                                        <input class="form-control" type="text" id="description" name="description" maxlength="200" value="{$description|escape}">
                                        <small id="description-limit-msg" class="text-danger" style="display:none;">
                                            {tr}You have reached the number of characters allowed (200 max) for the description field{/tr}
                                        </small>
                                        {jq}
                                            $("#description").on("keyup", function () {
                                                var length = $(this).val().length;
                                                if(length >= 200) {
                                                    $("#description-limit-msg").show();
                                                } else {
                                                    $("#description-limit-msg").hide();
                                                }
                                            });
                                        {/jq}
                                    </div>
                                </div>
                            {/if}
                            {if $freetags}
                                {include file='freetag.tpl'}
                            {/if}
                            {if $metaRobots}
                                <div class="mb-3 row">
                                    <label for="metatag_robotscustom" class="col-md-4 col-form-label">{tr}Custom Meta robots. The values should be comma separated eg. noimageindex, nocache.{/tr}</label>
                                    <div class="col-md-8">
                                        <input class="form-control" type="text" id="metatag_robotscustom" name="metatag_robotscustom" value="{$metatag_robotscustom|escape}">
                                    </div>
                                </div>
                            {/if}
                            {if $forSitemap}
                                <div class="mb-3 row clearfix">
                                    <label for="for_sitemap" class="col-md-4 col-form-label">{tr}Will be added to the sitemap{/tr}</label>
                                    <div class="col-md-8">
                                        <select name="for_sitemap" class="form-control">
                                            <option value = 'y' {if $for_sitemap == 'y'}selected {/if}>{tr}Yes, the page will appear on the sitemap{/tr}</option>
                                            <option value = 'n' {if $for_sitemap == 'n'}selected {/if}>{tr}No, the page will not appear on the sitemap{/tr}</option>
                                        </select>
                                    </div>
                                </div>
                            {/if}
                            <hr>
                        {/if}

                        {assign var="comments_enabled_section" value=($prefs.wiki_comments_allow_per_page neq 'n')} {* for Allow comments on this page *}
                        {assign var="prefRating" value=($prefs.feature_wiki_ratings eq 'y' && $tiki_p_wiki_admin_ratings eq 'y')} {* for Rating *}
                        {assign var="show_page_title" value=($prefs.wiki_page_hide_title eq 'y' && ($prefs.wiki_page_name_above eq 'y' || $prefs.feature_page_title eq 'y' || $prefs.wiki_page_name_inside eq 'y'))} {* for Show page title *}
                        {assign var="auto_toc" value=($prefs.wiki_auto_toc eq 'y' && $prefs.wiki_page_hide_title eq 'y')} {* for Show automatic table of contents *}
                        {assign var="cache" value=($page|lower neq 'sandbox' && $prefs.feature_wiki_icache eq 'y')} {* for Cache*}
                        {assign var="multilingual" value=($prefs.feature_multilingual eq 'y')} {* for Language *}
                        {assign var="lock_page" value=($prefs.feature_wiki_usrlock eq 'y' && ($tiki_p_lock eq 'y' || $tiki_p_admin_wiki eq 'y'))} {* for Lock page *}
                        {assign var="namespace" value=($prefs.namespace_enabled eq 'y')} {* for Namespace *}
                        {assign var="page_layout" value=($prefs.site_layout_per_object eq 'y')} {* for Page layout *}
                        {assign var="object_maintainers" value=($prefs.object_maintainers_enable eq 'y')} {* for Object maintainers *}

                        {if $comments_enabled_section || $prefRating || $show_page_title || $auto_toc || $cache || $multilingual || $lock_page || $namespace || $page_layout || $object_maintainers}
                            <h3>{tr}Management : {/tr}</h3>
                            {if $comments_enabled_section || $prefRating || $show_page_title || $auto_toc}
                                <h4>{tr}Additional content options{/tr}</h4>
                                {if $prefs.wiki_comments_allow_per_page neq 'n'}
                                    <div class="mb-3 row">
                                        <label for="comments_enabled" class="col-md-4 col-form-label">{tr}Allow comments on this page{/tr}</label>
                                        <div class="col-md-8">
                                            <input type="checkbox" class="form-check-input" id="comments_enabled" name="comments_enabled" {if $comments_enabled eq 'y'}checked="checked"{/if}>
                                        </div>
                                    </div>
                                {/if}
                                {if $prefs.feature_wiki_ratings eq 'y' && $tiki_p_wiki_admin_ratings eq 'y'}
                                    <div class="mb-3 row">
                                        <label for="" class="col-md-4 col-form-label">{tr}Rating{/tr}</label>
                                        <div class="col-md-8">
                                            {foreach from=$poll_rated item=rating}
                                                <div>
                                                    <a href="tiki-admin_poll_options.php?pollId={$rating.info.pollId}">{$rating.info.title}</a>
                                                    {$thispage=$page|escape:"url"}
                                                    {$thispoll_rated=$rating.info.pollId}
                                                    {button href="?page=$thispage&amp;removepoll=$thispoll_rated" _text="{tr}Disable{/tr}"}
                                                </div>
                                            {/foreach}
                                            {if $tiki_p_admin_poll eq 'y'}
                                                {button href="tiki-admin_polls.php" _text="{tr}Admin Polls{/tr}"}
                                            {/if}
                                            {if $poll_rated|@count <= 1 or $prefs.poll_multiple_per_object eq 'y'}
                                                <div class="row mb-0">
                                                    {if count($polls_templates)}
                                                        <label class="col-sm-2 col-form-label">{tr}Type{/tr}</label>
                                                        <div class="col-sm-10 mb-2">
                                                            <select name="poll_template" class="form-control">
                                                                <option value="0">{tr}none{/tr}</option>
                                                                {foreach item=template from=$polls_templates}
                                                                    <option value="{$template.pollId|escape}"{if $template.pollId eq $poll_template} selected="selected"{/if}>{tr}{$template.title|escape}{/tr}</option>
                                                                {/foreach}
                                                            </select>
                                                        </div>
                                                        <label class="col-sm-2 col-form-label">{tr}Title{/tr}</label>
                                                        <div class="col-sm-10 mb-2">
                                                            <input type="text" name="poll_title" class="form-control">
                                                        </div>
                                                    {else}
                                                        <div class="col-sm-12">
                                                        {remarksbox type="info" title="{tr}Information{/tr}" close="n"}
                                                            {tr}There is no available poll template.{/tr}
                                                            {if $tiki_p_admin_polls ne 'y'}
                                                                {tr}Please ask an administrator to create one.{/tr}
                                                            {/if}
                                                        {/remarksbox}
                                                        </div>
                                                    {/if}
                                                </div>
                                            {/if}
                                        </div>
                                    </div>
                                {/if}
                                {if $prefs.wiki_page_hide_title eq 'y' && ($prefs.wiki_page_name_above eq 'y' || $prefs.feature_page_title eq 'y' || $prefs.wiki_page_name_inside eq 'y')}
                                    <div class="mb-3 row clearfix">
                                        <label for="page_hide_title" class="col-md-4 col-form-label">{tr}Show page title{/tr}</label>
                                        <div class="col-md-8">
                                            <select name="page_hide_title" class="form-control">
                                                <option value="0" {if $page_hide_title == 0}selected{/if}>{tr}Default{/tr}</option>
                                                {*<option value="1" {if $page_hide_title == 1}selected{/if}>{tr}On_C(on-off){/tr}</option>*}
                                                <option value="-1" {if $page_hide_title == -1}selected{/if}>{tr}Off_C(on-off){/tr}</option>
                                            </select>
                                        </div>
                                    </div>
                                {/if}
                                {* check if wiki_auto_toc is set - but don't understand why wiki_page_hide_title is checked - also the logic made into an 'and' since the previous 'or' made no sense *}
                                {if $prefs.wiki_auto_toc eq 'y' and $prefs.wiki_page_hide_title eq 'y'}
                                    <div class="mb-3 row clearfix">
                                        <label for="pageAutoToc" class="col-md-4 col-form-label">{tr}Show automatic table of contents{/tr}</label>
                                        <div class="col-md-8">
                                            <select name="pageAutoToc" class="form-control">
                                                <option value="0" {if $pageAutoToc == 0}selected{/if}>{tr}Default{/tr} ({if $prefs.wiki_toc_default eq 'off'}{tr}No{/tr}{else}{tr}Yes{/tr}{/if})</option>
                                                <option value="1" {if $pageAutoToc == 1}selected{/if}>{tr}Yes{/tr}</option>
                                                <option value="-1" {if $pageAutoToc == -1}selected{/if}>{tr}No{/tr}</option>
                                            </select>
                                        </div>
                                    </div>
                                {/if}
                            {/if}
                            {if $cache || $multilingual || $lock_page || $namespace || $page_layout}
                                <h4>{tr}Page behavior options and preferences{/tr}</h4>
                                {* merged tool and property tabs for tiki 6 *}
                                {if $cache}
                                    <div class="mb-3 row">
                                        <label for="wiki_cache" class="col-form-label col-md-4">{tr}Cache{/tr}</label>
                                        <div class="col-md-8">
                                            <select id="wiki_cache" name="wiki_cache" class="form-control">
                                                <option value="0" {if $prefs.wiki_cache eq 0}selected="selected"{/if}>0 ({tr}no cache{/tr})</option>
                                                <option value="60" {if $prefs.wiki_cache eq 60}selected="selected"{/if}>1 {tr}minute{/tr}</option>
                                                <option value="300" {if $prefs.wiki_cache eq 300}selected="selected"{/if}>5 {tr}minutes{/tr}</option>
                                                <option value="600" {if $prefs.wiki_cache eq 600}selected="selected"{/if}>10 {tr}minute{/tr}</option>
                                                <option value="900" {if $prefs.wiki_cache eq 900}selected="selected"{/if}>15 {tr}minutes{/tr}</option>
                                                <option value="1800" {if $prefs.wiki_cache eq 1800}selected="selected"{/if}>30 {tr}minute{/tr}</option>
                                                <option value="3600" {if $prefs.wiki_cache eq 3600}selected="selected"{/if}>1 {tr}hour{/tr}</option>
                                                <option value="7200" {if $prefs.wiki_cache eq 7200}selected="selected"{/if}>2 {tr}hours{/tr}</option>
                                            </select>
                                            {if $prefs.wiki_cache == 0}
                                                {remarksbox type="warning" title="{tr}Warning{/tr}" close="n"}{tr}Only cache a page if it should look the same to all groups authorized to see it.{/tr}{/remarksbox}
                                            {/if}
                                        </div>
                                    </div>
                                {/if}
                                {if $multilingual}
                                    <fieldset>
                                        <div class="mb-3 row clearfix">
                                            <label for="" class="col-md-4 col-form-label">{tr}Language{/tr}</label>
                                            <div class="col-md-8">
                                                <select name="lang" id="lang" class="form-control mb-3">
                                                    <option value=""{if empty($lang)} selected="selected"{/if}>{tr}Unknown{/tr}</option>
                                                    {section name=ix loop=$languages}
                                                        <option value="{$languages[ix].value|escape}"{if $lang eq $languages[ix].value} selected="selected"{/if}>{$languages[ix].name}</option>
                                                    {/section}
                                                </select>
                                                <span class="form-text">
                                                    {tr _0="tiki-edit_translation.php?no_bl=y&amp;page={$page|escape:url}"}To translate, do not change the language and the content. Instead, <a class="alert-link" href="%0">create a new translation</a> in the new language.{/tr}
                                                    {if $translationOf}
                                                        <input type="hidden" name="translationOf" value="{$translationOf|escape}">
                                                    {/if}
                                                </span>
                                            </div>
                                        </div>
                                    </fieldset>
                                    {if $trads}
                                        {if $trads|@count > 1 and $urgent_allowed}
                                            <div class="mb-3 row">
                                                <label for="" class="col-md-4 col-form-label">{tr}Translation{/tr}</label>
                                                <div class="col-md-8">fb
                                                    <fieldset {if $prefs.feature_urgent_translation neq 'y' or $diff_style} style="display:none;"{/if}>
                                                        <legend>{tr}Translation request:{/tr}</legend>
                                                        <input type="hidden" name="lang" id="lang_request" value="{$lang|escape}">
                                                        <input type="checkbox" class="form-check-input" name="translation_critical" id="translation_critical"{if $translation_critical} checked="checked"{/if}>
                                                        <label for="translation_critical">{tr}Send urgent translation request.{/tr}</label>
                                                        {if $diff_style}
                                                            <input type="hidden" name="oldver" value="{$diff_oldver|escape}">
                                                            <input type="hidden" name="newver" value="{$diff_newver|escape}">
                                                        {/if}
                                                    </fieldset>
                                                </div>
                                            </div>
                                            {jq}
                                                $("#lang").change(function() {
                                                    $("#lang_request").val($(this).val());
                                                });
                                            {/jq}
                                        {/if}
                                    {/if}
                                {/if}
                                {if $lock_page}
                                    <div class="mb-3 row">
                                        <label for="lock_it" class="col-md-4 col-form-label">{tr}Lock this page{/tr}</label>
                                        <div class="col-md-8">
                                            <input type="checkbox" class="form-check-input" id="lock_it" name="lock_it" {if $lock_it eq 'y'}checked="checked"{/if}>
                                        </div>
                                    </div>
                                {/if}
                                {if $namespace}
                                    <div class="mb-3 row clearfix">
                                        <label for="explicit_namespace" class="col-md-4 col-form-label">{tr}Namespace{/tr}</label>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control" name="explicit_namespace" value="{$explicit_namespace|escape}" placeholder="{tr}Explicit Namespace{/tr}">
                                            <span class="form-text">
                                                {tr}The namespace for a page is guessed automatically from the page name. However, some exceptions may arise. This option allows to override the namespace.{/tr}
                                            </span>
                                        </div>
                                    </div>
                                {/if}
                                {if $page_layout}
                                    <fieldset>
                                        <div class="mb-3 row">
                                            <label for="object_layout" class="col-md-4 col-form-label">{tr}Page layout{/tr}</label>
                                            <div class="col-md-8">
                                                <select name="object_layout" class="form-control">
                                                    <option value="">{tr}Site Default{/tr}</option>
                                                    {foreach $object_layout.available as $key => $label}
                                                        <option value="{$key|escape}"{if $object_layout.current eq $key} selected{/if}>{$label|escape}</option>
                                                    {/foreach}
                                                </select>
                                            </div>
                                        </div>
                                    </fieldset>
                                {/if}
                            {/if}
                            {if $object_maintainers}
                                <h4>{tr}Object maintainers and freshness{/tr}</h4>
                                {if $object_maintainers}
                                    <fieldset>
                                        <div class="mb-3 row">
                                            <label for="object_layout" class="col-md-4 col-form-label">{tr}Page Maintainers{/tr}</label>
                                            <div class="col-sm-8 mb-2">
                                                {object_selector_multi _simplename=maintainers _simplevalue=$object_maintainers type=user _threshold=-1 _class="user-selector" _separator=";" _placeholder="{tr}Name{/tr}"}
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <label for="object_layout" class="col-md-4 col-form-label">{tr}Update Frequency{/tr}</label>
                                            <div class="col-sm-8 mb-2">
                                                <div class="input-group">
                                                    <input type="text" name="update_frequency" class="col-sm-10 form-control me-2" maxlength="4" id="update_frequency" value="{$update_frequency}" placeholder="{tr}Days{/tr}">
                                                    <span class="input-group-text">{tr}days{/tr}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </fieldset>
                                {/if}
                            {/if}
                            <hr>
                        {/if}

                        {assign var="use_content_templates" value=($prefs.feature_wiki_templates eq 'y' && $tiki_p_use_content_templates eq 'y')} {* for Apply content template *}
                        {assign var="allow_html" value=($prefs.feature_wiki_allowhtml eq 'y' && $tiki_p_use_HTML eq 'y' && ($wysiwyg neq 'y' || $prefs.wysiwyg_htmltowiki eq 'y'))} {* for Allow HTML *}
                        {assign var="import_html" value=($prefs.feature_wiki_import_html eq 'y')} {* for Import HTML *}
                        {assign var="import_page" value=($prefs.feature_wiki_import_page eq 'y')} {* for Import page *}

                        {if $use_content_templates || $allow_html || $import_html || $import_page}
                            <h3>{tr}Advanced :{/tr}</h3>
                            {if $use_content_templates}
                                <div class="mb-3 row">
                                    <label for="templateId" class="col-md-4 col-form-label">{tr}Apply content template{/tr}</label>
                                    <div class="col-md-8">
                                        <select class="form-select" id="templateId" name="templateId" onchange="needToConfirm=false;$('#editpageform').trigger('submit');">
                                            <option value="0">{tr}none{/tr}</option>
                                                {section name=ix loop=$templates}
                                                    <option value="{$templates[ix].templateId|escape}" {if $templateId eq $templates[ix].templateId}selected="selected"{/if}>{tr}{$templates[ix].name|escape}{/tr}</option>
                                                {/section}
                                        </select>
                                    </div>
                                </div>
                            {/if}
                            {if $allow_html}
                                <div class="mb-3 row">
                                    <label class="col-md-4 col-form-label" for="allowhtml">{tr}Allow HTML{/tr}</label>
                                    <div class="col-md-8">
                                        <input type="checkbox" class="form-check-input" name="allowhtml" id="allowhtml" {if $allowhtml eq 'y'}checked="checked"{/if}>
                                        <span class="form-text">
                                            {tr}HTML tags are used to create elements of the wiki page, instead of being displayed as code.{/tr}
                                        </span>
                                    </div>
                                </div>
                                {if $prefs.ajax_autosave eq "y"}
                                    {jq}
                                        $("input[name=allowhtml]").on("change", function() {
                                        auto_save( "editwiki", autoSaveId );
                                        });
                                    {/jq}
                                {/if}
                            {else}
                                <input type="hidden" name="allowhtml" value="{if $allowhtml eq 'y'}on{/if}">
                            {/if}
                            {if $import_html}
                                <div class="mb-3 row">
                                    <label for="suck_url" class="col-md-4 col-form-label">{tr}Import HTML{/tr}</label>
                                    <div class="col-md-8 d-flex flex-row flex-wrap align-items-center">
                                        <input class="form-control wikiedit me-2" type="text" id="suck_url" name="suck_url" value="{$suck_url|escape}">
                                        <input type="submit" class="wikiaction btn btn-primary me-2" name="do_suck" value="{tr}Import{/tr}" onclick="needToConfirm=false;">
                                        <label><input type="checkbox" class="form-check-input" name="parsehtml" {if $parsehtml eq 'y'}checked="checked"{/if}>&nbsp;
                                        {tr}Try to convert HTML to wiki{/tr}</label>
                                    </div>
                                </div>
                            {/if}
                            {if $import_page}
                                <div class="mb-3 row clearfix">
                                    <label for="userfile1" class="col-md-4 col-form-label">{tr}Import page{/tr}</label>
                                    <div class="col-md-8 d-flex flex-row flex-wrap align-items-center">
                                        <input type="hidden" name="MAX_FILE_SIZE" value="1000000000">
                                        <input class="form-control me-2" id="userfile1" name="userfile1" type="file">
                                        <input type="submit" class="wikiaction btn btn-primary" name="attach" value="{tr}Import{/tr}" onclick="javascript:needToConfirm=false; insertImgFile('editwiki','userfile2','hasAlreadyInserted2','file', 'page2', 'attach_comment'); return true;">
                                    </div>
                                </div>
                            {/if}
                            <hr>
                        {/if}
                    {/tab}{* end properties tab *}
                {else}
                    {if $wysiwyg eq 'y'}{* include hidden allowhtml for wysiwyg if the properties tab isn't needed *}
                        <input type="hidden" name="allowhtml" value="{if $allowhtml eq 'y'}on{/if}">
                    {/if}
                {/if}
            {/tabset}
            {if $page|lower neq 'sandbox'}
                <fieldset class="edit-zone-footer my-3">
                    <label for="comment">{tr}Describe the change you made{/tr} {help url='Editing-Wiki-Pages' desc="{tr}Edit comment: Enter some text to describe the changes you are currently making{/tr}"}</label>
                    <input class="form-control wikiedit" type="text" id="comment" name="comment" value="{$commentdata|escape}" maxlength="255">
                </fieldset>
                {if $prefs.feature_contribution eq 'y'}
                    <fieldset>
                        <legend>{tr}Contributions{/tr}</legend>
                        {include file='contribution.tpl'}
                    </fieldset>
                {/if}
            {/if}
        </div>
        <div class="row d-flex justify-content-center">
            {if $page|lower ne 'sandbox'}
                {if $prefs.feature_antibot eq 'y' && (isset($anon_user) && $anon_user eq 'y')}
                    {include file='antibot.tpl' tr_style="formcolor"}
                {/if}
            {/if}{* sandbox *}
            {if $prefs.wiki_actions_bar neq 'top'}
                {include file='wiki_edit_actions.tpl' wysiwyg=$wysiwyg page_info=$page_info rootPath=$ajaxURL}
            {/if}
        </div>
    </form>
    {include file='tiki-page_bar.tpl'}
{/block}
