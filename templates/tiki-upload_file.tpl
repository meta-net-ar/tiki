{if !empty($filegals_manager) and !isset($smarty.request.simpleMode)}
    {assign var=simpleMode value='y'}
{else}
    {assign var=simpleMode value='n'}
{/if}
{title help="File Galleries" admpage="fgal"}{if $editFileId}{tr}Edit File:{/tr} {$fileInfo.filename}{else}{tr}Upload File{/tr}{/if}{/title}
{if !empty($galleryId) or (isset($galleries) and count($galleries) > 0 and $tiki_p_list_file_galleries eq 'y') or (isset($uploads) and count($uploads) > 0)}
    <div class="t_navbar mb-4">
        {if !empty($galleryId)}
            {assign var=btnHref value="tiki-list_file_gallery.php"}
            {if isset($allowedMimeTypes)}
                {assign var=btnHref value=$btnHref|cat:"?allowedMimeTypes="|cat:$allowedMimeTypes}
            {/if}
            {button galleryId="$galleryId" href=$btnHref class="btn btn-primary" _text="{tr}Browse Gallery{/tr}"}
        {/if}
        {if isset($galleries) and count($galleries) > 0 and $tiki_p_list_file_galleries eq 'y'}
            {if !empty($filegals_manager)}
                {assign var=fgmanager value=$filegals_manager|escape}
                {assign var=btnHref value="tiki-list_file_gallery.php?filegals_manager=$fgmanager"}
                {if isset($allowedMimeTypes)}
                    {assign var=btnHref value=$btnHref|cat:"&allowedMimeTypes="|cat:$allowedMimeTypes}
                {/if}
                {button href=$btnHref|escape class="btn btn-info" _text="{tr}List Galleries{/tr}"}
            {else}
                {assign var=btnHref value="tiki-list_file_gallery.php"}
                {if isset($allowedMimeTypes)}
                    {assign var=btnHref value=$btnHref|cat:"?allowedMimeTypes="|cat:$allowedMimeTypes}
                {/if}
                {button href=$btnHref class="btn btn-info" _text="{tr}List Galleries{/tr}"}
            {/if}
        {/if}
        {if isset($uploads) and count($uploads) > 0}
            {button href="#upload" class="btn btn-primary" _text="{tr}Upload File{/tr}"}
        {/if}
        {if !empty($filegals_manager)}
            {if $simpleMode eq 'y'}{button simpleMode='n' galleryId=$galleryId href="" class="btn btn-primary" _text="{tr}Advanced mode{/tr}" _ajax="n"}{else}{button galleryId=$galleryId href="" _text="{tr}Simple mode{/tr}" _ajax="n"}{/if}
            <span{if $simpleMode eq 'y'} style="display:none;"{/if}>
                <label for="keepOpenCbx">{tr}Keep gallery window open{/tr}</label>
                <input type="checkbox" id="keepOpenCbx" checked="checked">
            </span>
            <div id="tikifeedback" class="col-sm-8 offset-2"></div>
        {/if}
    </div>
{/if}
{if isset($errors) and count($errors) > 0}
    <div class="alert alert-danger">
        <h2>{tr}Errors detected{/tr}</h2>
        {section name=ix loop=$errors}
            {$errors[ix]}<br>
        {/section}
        {button href="#upload" _text="{tr}Retry{/tr}"}
    </div>
{/if}
{if $prefs.javascript_enabled eq 'y'}
    <div id='progress'>
        <div id='progress_0'></div>
    </div>
{/if}
{if isset($uploads) and count($uploads) > 0}
    <div class="table-responsive">
        <table class="table">
            {section name=ix loop=$uploads}
                <tr>
                    <td class="text-center">
                        <img src="{$uploads[ix].fileId|sefurl:thumbnail}">
                    </td>
                    <td>
                        {if !empty($filegals_manager) && $prefs.javascript_enabled === 'y'}
                            <a href="#" onclick="window.opener.insertAt('{$filegals_manager}','{$files[changes].wiki_syntax|escape}');checkClose();return false;" title="{tr}Click here to use the file{/tr}">{$uploads[ix].name} ({$uploads[ix].size|kbsize})</a>
                        {else}
                            <b>{$uploads[ix].name} ({$uploads[ix].size|kbsize})</b>
                        {/if}
                        {if $prefs.javascript_enabled === 'y'}
                            {button href="#" _flip_id="uploadinfos"|cat:$uploads[ix].fileId _text="{tr}Additional Info{/tr}"}
                        {/if}
                        <div style="{if $prefs.javascript_enabled eq 'y'}display:none;{/if}" id="uploadinfos{$uploads[ix].fileId}">
                            <h5>
                                {tr}Syntax Tips{/tr}
                            </h5>
                            <div>
                                {tr}Download link using Tiki syntax:{/tr}
                            </div>
                            <div class="ms-3">
                                <code>
                                    [{$uploads[ix].fileId|sefurl:file}|{$uploads[ix].name}]
                                </code>
                            </div>
                            <div>
                                {tr}Display an image using Tiki syntax:{/tr}
                            </div>
                            <div class="ms-3">
                                <code>
                                    &#x7b;img src="{$uploads[ix].fileId|sefurl:preview}" link="{$uploads[ix].fileId|sefurl:file}" alt="{$uploads[ix].name}"}
                                </code>
                            </div>
                            {if $prefs.feature_shadowbox eq 'y'}
                                <div>
                                    {tr}Use as a thumbnail with ShadowBox:{/tr}
                                </div>
                            <div class="ms-3">
                                <code>
                                    &#x7b;img src="{$uploads[ix].fileId|sefurl:thumbnail}" link="{$uploads[ix].fileId|sefurl:preview}" rel="shadowbox[gallery];type=img" alt="{$name}"}
                                </code>
                            </div>
                            {/if}
                            <div>
                                {tr}Download link using HTML:{/tr}
                            </div>
                            <div class="ms-3">
                                <code>
                                    &lt;a href="{$uploads[ix].dllink}"&gt;{$uploads[ix].name}&lt;/a&gt;
                                </code>
                            </div>
                        </div>
                    </td>
                </tr>
            {/section}
        </table>
    </div>
    <h2>{tr}Upload File{/tr}</h2>
{elseif isset($fileChangedMessage)}
    <div align="center">
        <div class="wikitext">
            {$fileChangedMessage}
        </div>
    </div>
{/if}
{if $editFileId and isset($fileInfo.lockedby) and $fileInfo.lockedby neq ''}
    {remarksbox type="note" title="{tr}Info{/tr}" icon="lock"}
        {if $user eq $fileInfo.lockedby}
            {tr}You locked the file{/tr}
        {else}
            {tr _0=$fileInfo.lockedby}The file has been locked by %0{/tr}
        {/if}
    {/remarksbox}
{/if}
{if !$editFileId}
    <div class="col-md-12">
        <input type="hidden" id="max_file_uploads" value="{$max_file_uploads}">
        {remarksbox type="note" title="{tr}Information{/tr}"}
            {tr}Maximum file size is around:{/tr}
            {if $tiki_p_admin eq 'y'}<a title="|{$max_upload_size_comment}" class="alert-link tips">{/if}
                {$max_upload_size|kbsize:true:0}
            {if $tiki_p_admin eq 'y'}</a>
                {if $is_iis}<br>{tr}Note: You are running IIS{/tr}. {tr}maxAllowedContentLength also limits upload size{/tr}. {tr}Please check web.config in the Tiki root folder{/tr}{/if}
            {/if}
        {/remarksbox}
    </div>
{/if}
<div>
    {capture name=upload_file assign=upload_str}
        <div class="fgal_file">
            <div class="fgal_file_c1">
                {if $prefs.javascript_enabled !== 'y' || $prefs.file_galleries_use_jquery_upload neq 'y' or $editFileId}
                    {if $simpleMode neq 'y'}
                        <div class="mb-3 row">
                            <label for="name" class="col-md-4 col-form-label">{tr}File title{/tr}</label>
                            <div class="col-md-8">
                                <input class="form-control" type="text" id="name" name="name[]"
                                    {if isset($fileInfo) and $fileInfo.name}
                                        value="{$fileInfo.name|escape}"
                                    {/if}
                                    size="40"
                                >
                                {if isset($gal_info.type) and ($gal_info.type eq "podcast" or $gal_info.type eq "vidcast")}
                                    ({tr}required field for podcasts{/tr})
                                {/if}
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="description" class="col-md-4 col-form-label">{tr}File description{/tr}</label>
                            <div class="col-md-8">
                                <textarea class="form-control" id="description" name="description[]">{if isset($fileInfo.description)}{$fileInfo.description|escape}{/if}</textarea>
                                {if isset($gal_info.type) and ($gal_info.type eq "podcast" or $gal_info.type eq "vidcast")}
                                    <br><em>{tr}Required for podcasts{/tr}.</em>
                                {/if}
                            </div>
                        </div>
                        <div class="mb-3 row">
                            <label for="parentGalleryId" class="col-sm-4">
                                {tr}Parent Gallery:{/tr}
                            </label>
                            <div class="col-sm-8">
                                {if isset($gal_info.type) and $gal_info.type eq "direct"}
                                    <input type="hidden" name="parentGalleryId" value="{$gal_info.galleryId}">
                                    {$gal_info.name|escape}
                                {else}
                                    <select name="parentGalleryId" id="parentGalleryId" class="form-select">
                                        {foreach $all_galleries as $gallery}
                                            {if $gallery.perms.tiki_p_upload_files eq 'y' and
                                                    ($gallery.public eq 'y' or $gallery.user eq $user or $gallery.perms.tiki_p_admin_file_galleries eq 'y')}
                                                <option value="{$gallery.id}"{if $fileInfo.galleryId eq $gallery.id} selected="selected"{/if}>
                                                    {$gallery.label|escape}
                                                </option>
                                            {/if}
                                        {/foreach}
                                    </select>
                                {/if}
                            </div>
                        </div>
                    {/if}
                    {if $prefs.javascript_enabled !== 'y' || $prefs.file_galleries_use_jquery_upload neq 'y' || $editFileId}
                        <div class="mb-3 row">
                            <label for="userfile" class="col-md-4 col-form-label">{if $editFileId}{tr}Re-upload from disk{/tr}{else}{tr}Upload from disk{/tr}{/if}</label>
                            <div class="col-md-8">
                                {if $editFileId}
                                    {$fileInfo.filename|escape}
                                {/if}
                                <input id="userfile" name="userfile[]" type="file" size="40">
                            </div>
                        </div>
                    {/if}
                {else}{* file_galleries_use_jquery_upload = y *}
                    {filegal_uploader allowedMimeTypes=$allowedMimeTypes}
                {/if}
            </div>
            <div class="col-sm-12">
                <div class="form-check">
                    <label for="imagesize" class="form-check-label">
                        <input class="form-check-input" type="checkbox" id="imagesize" name="imagesize" checked="checked" value="yes" />{tr}Use Gallery default resize settings for images <span id="imageResizeInfo">{if $gal_info["image_max_size_x"]}({$gal_info["image_max_size_x"]}px X {$gal_info["image_max_size_y"]} px){else}(No resize){/if}</span>{/tr}
                    </label>
                </div>
            </div>
            <div id="customsize" style="display:none">
                <div class="mb-3 row">
                    <label for="image_max_size_x" class="col-sm-4 text-end">{tr}Maximum width of images{/tr}</label>
                    <div class="col-sm-8">
                        <div class="input-group col-sm-4">
                            <input type="text" name="image_max_size_x" id="image_max_size_x" value="{$gal_info["image_max_size_x"]}" class="form-control text-end">
                            <span class="input-group-text"> {tr}pixel{/tr}</span>
                        </div>
                        <span class="form-text">{tr}If an image is wider than this, it will be resized. Attention: In this case, the original image will be lost. (0=unlimited){/tr}</span>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="image_max_size_y" class="col-sm-4 text-end">{tr}Maximum height of images in gallery{/tr}</label>
                    <div class="col-sm-8">
                        <div class="input-group col-sm-4">
                            <input type="text" name="image_max_size_y" id="image_max_size_y" value="{$gal_info["image_max_size_y"]}" class="form-control text-end">
                            <span class="input-group-text"> {tr}pixel{/tr}</span>
                        </div>
                        <span class="form-text">{tr}If an image is higher than this, it will be resized. Attention: In this case, the original image will be lost. (0=unlimited){/tr}</span>
                    </div>
                </div>
            </div>
            {if $simpleMode neq 'y'}
                <div class="fgal_file_c2">
                    {if !$editFileId and $tiki_p_batch_upload_files eq 'y'}
                        <div class="col-sm-12">
                            <div class="form-check mb-3">
                                <label for="isbatch" class="form-check-label">
                                    <input type="checkbox" id="isbatch" name="isbatch[]" class="form-check-input">
                                    {tr}Unzip zip files{/tr}
                                </label>
                            </div>
                        </div>
                    {/if}
                    {if $prefs.fgal_delete_after eq 'y'}
                        <div class="mb-3 row">
                            <label for="deleteAfter" class="col-md-4 col-form-label">{tr}File can be deleted after{/tr}</label>
                            <div class="col-md-8">
                                {if $editFileId}
                                    {html_select_duration prefix='deleteAfter' id="deleteAfter" default_value=$fileInfo.deleteAfter}
                                {else}
                                    {html_select_duration prefix='deleteAfter[]' id="deleteAfter" default_unit=week}
                                {/if}
                            </div>
                        </div>
                    {/if}
                    {if $editFileId}
                        <input type="hidden" name="galleryId" value="{$galleryId}">
                        <input type="hidden" name="fileId" value="{$editFileId}">
                        <input type="hidden" name="lockedby" value="{$fileInfo.lockedby|escape}">
                    {else}
                        {if count($galleries) eq 0}
                            {if !empty($galleryId)}
                                <input type="hidden" name="galleryId[]" value="{$galleryId}">
                            {else}
                                <input type="hidden" name="galleryId[]" value="{$treeRootId}">
                            {/if}
                        {elseif empty($groupforalert)}
                            <input type="hidden" id="gallery_type" value="{$gal_info['type']}">

                            <div class="mb-3 row">
                                <label for="galleryId" class="col-md-4 col-form-label">{tr}File gallery{/tr}</label>
                                <div class="col-md-8">
                                    <select id="galleryId" name="galleryId[]" class="form-select" data-action="{service controller=file action=find_gallery}">
                                        <option value="{$treeRootId}" {if $treeRootId eq $galleryId}selected="selected"{/if} style="font-style:italic; border-bottom:1px dashed #666;">{tr}Root{/tr}</option>
                                        {section name=idx loop=$galleries}
                                            {if $galleries[idx].id neq $treeRootId and ($galleries[idx].perms.tiki_p_upload_files eq 'y' or $tiki_p_userfiles eq 'y') and ($galleries[idx].type neq 'direct' or $galleries[idx].id == $galleryId)}
                                                <option value="{$galleries[idx].id|escape}" {if $galleries[idx].id eq $galleryId}selected="selected"{/if}>{$galleries[idx].name|escape}</option>
                                            {/if}
                                        {/section}
                                    </select>
                                    <div class="form-check mt-3 d-none" id="current_gallery_group">
                                        <label for="current_gallery" class="form-check-label">
                                            <input class="form-check-input" type="checkbox" id="current_gallery" name="current_gallery" value="yes" />{tr}Use current gallery{/tr}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        {else}
                            <input type="hidden" name="galleryId[]" value="{$galleryId}">
                        {/if}
                    {/if}
                    <div class="mb-3 row">
                        <label for="user" class="col-md-4 col-form-label">{tr}Uploaded by{/tr}</label>
                        <div class="col-md-8">
                            {user_selector id='user' name='user[]' select=$fileInfo.user editable=$tiki_p_admin_file_galleries}
                        </div>
                    </div>
                    {if $prefs.feature_file_galleries_author eq 'y'}
                        <div class="mb-3 row">
                            <label for="author" class="col-md-4 col-form-label">{tr}Creator{/tr}</label>
                            <div class="col-md-8">
                                <input type="text" id="author"name="author[]" value="{$fileInfo.author|escape}"><br>
                                <span class="description">{tr}Creator of file, if different from the 'Uploaded by' user{/tr}</span>
                            </div>
                        </div>
                    {/if}
                    {if !empty($groupforalert)}
                        {if $showeachuser eq 'y'}
                            <div class="mb-3 row">
                                <label class="col-md-4 col-form-label">{tr}Choose users to alert{/tr}</label>
                                <div class="col-md-8">
                                    {section name=idx loop=$listusertoalert}
                                        <label>
                                            <input type="checkbox" name="listtoalert[]" value="{$listusertoalert[idx].user|escape}"> {$listusertoalert[idx].user|escape}
                                        </label>
                                    {/section}
                                </div>
                            </div>
                        {else}
                            {section name=idx loop=$listusertoalert}
                                <input type="hidden" name="listtoalert[]" value="{$listusertoalert[idx].user}">
                            {/section}
                        {/if}
                    {/if}
                    {if $editFileId}
                        <div class="mb-3 row">
                            <label for="filetype" class="col-md-4 col-form-label">{tr}File Type{/tr}</label>
                            <div class="col-md-8">
                                <select id="filetype" class="form-select" name="filetype[]">
                                    {if $fileInfo.filetype ne '' }
                                        <option value="{$fileInfo.filetype|escape}" selected="selected">{$fileInfo.filetype|truncate:60|escape}</option>
                                    {/if}
                                    <option value="" > {tr}No type{/tr} </option>
                                    {foreach $mimetypes as $type}
                                        <option value="{$type}">{$type|truncate:60} (*.{$type@key})</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    {/if}

                {if $prefs.ocr_enable eq 'y'}
                    {if isset($selectedLanguages) || isset($languages)}
                        <div class="mb-3 row">
                            <label for="ocr_lang" class="col-md-4 col-form-label">{tr}Override Default Languages{/tr}</label>
                            <div class="col-md-8">
                                <select multiple id="ocr_lang" class="form-select" name="ocr_lang[]">
                                    {foreach $selectedLanguages as $code => $language}
                                        <option value="{$code|escape}" selected="selected">{$language|truncate:60|escape}</option>
                                    {/foreach}

                                    {foreach $languages as $code => $language}
                                        <option value="{$code}">{$language|truncate:60}</option>
                                    {/foreach}
                                </select>
                            </div>
                        </div>
                    {/if}
                    {if $editFileId || $prefs.ocr_every_file !== 'y' && !$editFileId}
                        <div class="form-check">
                            <label for="imagesize" class="form-check-label">
                                <input type="checkbox" value='3' {if $ocr_state}checked="checked"{/if} id="ocr_state" class="form-check-input" name="ocr_state" title="{tr}Attempt to OCR this file.{/tr}"> {tr}OCR this file{/tr}
                            </label>
                        </div>
                    {/if}
                {/if}
                </div>
                <div class="fgal_file_c3">
                    {if $prefs.fgal_limit_hits_per_file eq 'y'}
                        <div class="mb-3 row">
                            <label for="hit_limit" class="col-md-4 form-label">{tr}Maximum number of downloads{/tr}</label>
                            <div class="col-md-8">
                                <input type="text" id="hit_limit" name="hit_limit[]" value="{$hit_limit|default:0}">
                                <br><em>{tr}Use{/tr} {tr}-1 for no limit{/tr}.</em>
                            </div>
                        </div>
                    {else}
                        <input type="hidden" id="hit_limit" name="hit_limit[]" value="{$hit_limit|default:-1}">
                    {/if}
                    {* We want comments only on updated files *}
                    {if $prefs.javascript_enabled neq 'y' && $editFileId}
                        <div class="mb-3 row">
                            <label for="comment" class="col-md-4 form-label">{tr}Comment{/tr}</label>
                            <div class="col-md-8">
                                <input type="text" id="comment" name="comment[]" value="" size="40">
                            </div>
                        </div>
                    {/if}
                </div>
                {if $prefs.javascript_enabled eq 'y' and !$editFileId}
                    {include file='categorize.tpl'}<br/>
                {/if}
            {else}
                {if $prefs.javascript_enabled eq 'y' and !$editFileId and $category_jail}
                    {include file='categorize.tpl'}<br/>
                {/if}
                <input type="hidden" name="galleryId[]" value="{$galleryId}">
            {/if}
            {if $prefs.javascript_enabled eq 'y' and !$editFileId}
                <input type="hidden" name="upload">
            {/if}
        </div>
    {/capture}
    <div id="form">
        <form method="post"
            action='tiki-upload_file.php'
            enctype='multipart/form-data'
            {*class="form-horizontal"*}
            id="file_0"
        >
            {ticket}
            <input type="hidden" name="simpleMode" value="{$simpleMode}">
            <input type="hidden" name="submission" value="1">
            {if !empty($filegals_manager)}
                <input type="hidden" name="filegals_manager" value="{$filegals_manager}">
            {/if}
            {if !empty($insertion_syntax)}
                <input type="hidden" name="insertion_syntax" value="{$insertion_syntax|escape}">
            {/if}
            {if isset($token_id) and $token_id neq ''}
                <input type="hidden" value="{$token_id}" name="TOKEN">
            {/if}
            {$upload_str}
            {if $editFileId}
                {include file='categorize.tpl'}<br>
                <div id="page_bar" class="mb-3 row">
                    <div class="col-md-8 offset-md-4">
                        <input name="upload" type="submit" class="btn btn-primary" value="{tr}Save{/tr}">
                    </div>
                </div>
            {elseif $prefs.javascript_enabled neq 'y'}
                {$upload_str}
                {$upload_str}
                {include file='categorize.tpl'}<br>
                <div id="page_bar" class="mb-3 row">
                    <div class="col-md-8 offset-md-4">
                        <input type="submit" class="btn btn-primary" name="upload" value="{tr}Upload{/tr}">
                    </div>
                </div>
            {/if}
            {if !$editFileId && $prefs.file_galleries_use_jquery_upload neq 'y' && $prefs.javascript_enabled === 'y'}
                <div id="page_bar" class="mb-3 row">
                    <div class="col-md-8 offset-md-4">
                        <input type="submit" class="btn btn-primary"
                            onclick="upload_files(); return false;"
                            id="btnUpload"
                            name="upload"
                            value="{tr}Upload File(s){/tr}"
                        >
                        <input type="submit" class="btn btn-primary btn-sm" onclick="javascript:add_upload_file('multiple_upload'); return false" value="{tr}Add Another File{/tr}">
                    </div>
                </div>
            {/if}
        </form>
    </div>
    {if !empty($fileInfo.lockedby) and $user ne $fileInfo.lockedby}
        {icon name="lock"}
        <span class="attention">{tr}The file has been locked by {$fileInfo.lockedby}{/tr}</span>
    {/if}
</div>
{if not empty($metarray) and $metarray|count gt 0}
    {include file='metadata/meta_view_tabs.tpl'}
{/if}
{if ! $editFileId and $prefs.file_galleries_use_jquery_upload neq 'y'}
    {if $prefs.feature_jquery_ui eq 'y'}
        {jq}$('.datePicker').datepicker({minDate: 0, maxDate: '+1m', dateFormat: 'dd/mm/yy'});{/jq}
    {/if}
    {jq notonready=true}
        $('#file_0').ajaxForm({target: '#progress_0', forceSync: true});
        var nb_upload = 1;
        function add_upload_file() {
            var clone = $('#form form').eq(0).clone().resetForm().attr('id', 'file_' + nb_upload).ajaxForm({target: '#progress_' + nb_upload, forceSync: true});
            $(clone[0].submission).val(parseInt($(clone[0].submission).val(), 10) + parseInt(nb_upload, 10));
            clone.insertAfter($('#form form').eq(-1));
            document.getElementById('progress').innerHTML += "<div id='progress_"+nb_upload+"'></div>";
            nb_upload += 1;
        }
        function upload_files(){
            var totalSubmissions = $("#form form").length;
            $("#form form").append($('<input />', {type: 'hidden', name: 'totalSubmissions', value: totalSubmissions}));
            $("#form form").each(function(n) {
                if ($(this).find('input[name="userfile\\[\\]"]').val() != '') {
                    var $progress = $('#progress_'+n).html("<img src='img/spinner.gif'>{tr}Uploading file...{/tr}");
                    $( document ).on('ajaxError', function(event, jqxhr, ajaxSettings, thrownError ) {
                        $progress.hide();
                        show('form');
                        $("#form").showError(tr("File upload error:") + " " + thrownError)
                    });
                    $(this).trigger("submit");
                    this.reset();
                } else {
                    $('#progress_'+n).html("{tr}No File to Upload...{/tr} <span class='button'><a href='#' onclick='location.replace(location.href);return false;'>{tr}Retry{/tr}</a></span>");
                }
            });
            hide('form');
        }
    {/jq}
{/if}
{if not $editFileId and $prefs.fgal_upload_from_source eq 'y' and $tiki_p_upload_files eq 'y'}
    <form class="remote-upload" method="post" action="{service controller=file action=remote}">
        <span class="h3">{tr}Upload from URL{/tr}</span>
        <div class="mb-3 row">
            <input type="hidden" name="galleryId" value="{$galleryId|escape}">
            <label class="col-md-4 col-form-label">{tr}URL:{/tr}</label>
            <div class="col-md-8">
                <input type="url" name="url" placeholder="https://" class="form-control">
            </div>
            {if $prefs.vimeo_upload eq 'y'}
                <label class="col-md-8 offset-md-4">
                    <input type="checkbox" name="reference" value="1" class="tips" title="{tr}Upload from URL{/tr}|{tr}Keeps a reference to the remote file{/tr}">
                    {tr}Reference{/tr}
                </label>
            {/if}
            <div class="col-md-8 offset-md-4">
                <input type="submit" class="btn btn-primary btn-sm" value="{tr}Add{/tr}">
            </div>
            <div class="result col-md-8 offset-md-4"></div>
        </div>
    </form>
    {jq}
        $('.remote-upload').on("submit", function () {
            var form = this;
            // use the current value of the galleryId selector
            $('input[name=galleryId]', form).val($('#galleryId').val());
            $.ajax({
                method: 'POST',
                url: $(form).attr('action'),
                data: $(form).serialize(),
                dataType: 'html',
                success: function (data) {
                    $('.result', form).html(data);
                    $(form.url).val('');
                },
                complete: function () {
                    $('input', form).prop('disabled', false);
                },
                error: function (e) {
                    alert(tr("A remote file upload error occurred:") + "\n\"" + e.statusText + "\" (" + e.status + ")");
                }
            });
            $('input', this).prop('disabled', true);
            return false;
        });
    {/jq}
    {if $prefs.vimeo_upload eq 'y'}
        <fieldset>
            <h3>{tr}Upload Video{/tr}</h3>
            <div class="col-md-8 offset-md-4">
                {wikiplugin _name='vimeo'}{/wikiplugin}
            </div>
        </fieldset>
        {jq}
            var handleVimeoFile = function (link, data) {
                if (data != undefined) {
                $("#form").hide();
                $("#progress").append(
                    $("<p> {tr}Video file uploaded:{/tr} " + data.file + "</p>")
                        .prepend($("<img src='img/icons/vimeo.png' width='16' height='16'>"))
                    );
                }
            }
        {/jq}
    {/if}
{/if}
{jq}
var defaultx= $("#image_max_size_x").attr('value');
var defaulty= $("#image_max_size_y").attr('value');

$("#imagesize").on("click", function () {
    if ($(this).prop("checked")) {
        $("#customsize").css("display", "none");
        //resetting size to default
        $("#image_max_size_x").attr('value',defaultx);
        $("#image_max_size_y").attr('value',defaulty);
    } else {
        $("#customsize").css("display", "");
    }
});

$.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null) {
        return 0;
    }
    return decodeURI(results[1]) || 0;
}

function isCurrentGallery() {
    if (($.urlParam('galleryId') == "0" &&  $("#galleryId").val() == rootGalleryId) || $.urlParam('galleryId') ==  $("#galleryId").val()) {
        $("#current_gallery_group").addClass('d-none').hide();
    } else {
        $("#current_gallery_group").removeClass('d-none').show();
    }
}

var rootGalleryId = $("#galleryId").find("option:first-child").val();
isCurrentGallery();

$("#current_gallery").on("change", function (e) {
    const checked = $(this).is(':checked');
    var currentGalleryId = ($.urlParam('galleryId') != "0") ? $.urlParam('galleryId') : rootGalleryId;

    if (checked) {
        $("#galleryId")
            .val(currentGalleryId)
            .trigger('change');

        $('#current_gallery').prop('checked', false);
    }
});

$("#galleryId").on("change", function(){
    var galleryId = $("#galleryId").val();
    var action = $("#galleryId").attr('data-action');

    isCurrentGallery();

    $.ajax({
        method: 'GET',
        url: action,
        data: { galleryId },
        dataType: 'json',
        success: function (data) {
            if (data.canUpload) {
                $("#gallery_type").attr('value', data.type);

                $("#image_max_size_x").attr('value', data.image_max_size_x);
                $("#image_max_size_y").attr('value', data.image_max_size_y);
                if (data.image_max_size_x) {
                    $("#imageResizeInfo").html(data.image_max_size_x + 'px X ' + data.image_max_size_y + 'px');
                } else {
                    $("#imageResizeInfo").html('(No resize)');
                }
            } else {
                $("#gallery_type").attr('value', '');
                $("#image_max_size_x").attr('value','');
                $("#image_max_size_y").attr('value',"");
                $("#imageResizeInfo").html('');
                defaultx='';
                efaulty='';
            }
        },
        error: function (e) {
            console.log(e);
        }
    });
});
{/jq}
