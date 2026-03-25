{if !empty($filegals_manager) and !isset($smarty.request.simpleMode)}
    {$simpleMode='y'}
{else}
    {$simpleMode='n'}
{/if}
{title help="File Galleries" admpage="fgal"}{if $editFileId}{tr}Edit File:{/tr} {$fileInfo.filename}{else}{tr}Upload File{/tr}{/if}{/title}
{if !empty($galleryId) or (isset($galleries) and count($galleries) > 0 and $tiki_p_list_file_galleries eq 'y') or (isset($uploads) and count($uploads) > 0)}
    <div class="t_navbar mb-4">
        {if !empty($galleryId) and !isset($insertion_syntax)}
            {$btnHref="tiki-list_file_gallery.php"}
            {if isset($allowedMimeTypes)}
                {$btnHref=$btnHref|cat:"?allowedMimeTypes="|cat:$allowedMimeTypes}
            {/if}
            {button galleryId="$galleryId" href=$btnHref class="btn btn-primary" _text="{tr}Browse Gallery{/tr}"}
        {/if}
        {if isset($galleries) and count($galleries) > 0 and $tiki_p_list_file_galleries eq 'y'}
            {if !empty($filegals_manager)}
                {$fgmanager=$filegals_manager|escape}
                {$btnHref="tiki-list_file_gallery.php?filegals_manager=$fgmanager"}
                {if isset($allowedMimeTypes)}
                    {$btnHref=$btnHref|cat:"&allowedMimeTypes="|cat:$allowedMimeTypes}
                {/if}
                {button href=$btnHref|escape class="btn btn-info" _text="{tr}List Galleries{/tr}"}
            {else}
                {$btnHref="tiki-list_file_gallery.php"}
                {if isset($allowedMimeTypes)}
                    {$btnHref=$btnHref|cat:"?allowedMimeTypes="|cat:$allowedMimeTypes}
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
<div id='progress'>
    <div id='progress_0'></div>
</div>
{if isset($uploads) and count($uploads) > 0}
    <div class="table-responsive">
        <table class="table">
            {section name=ix loop=$uploads}
                <tr>
                    <td class="text-center">
                        <img src="{$uploads[ix].fileId|sefurl:thumbnail}">
                    </td>
                    <td>
                        {if !empty($filegals_manager)}
                            <a href="#" onclick="window.opener.insertAt('{$filegals_manager}','{$files[changes].wiki_syntax|escape}');checkClose();return false;" title="{tr}Click here to use the file{/tr}" role="button">{$uploads[ix].name} ({$uploads[ix].size|kbsize})</a>
                        {else}
                            <b>{$uploads[ix].name} ({$uploads[ix].size|kbsize})</b>
                        {/if}
                            {button href="#" _flip_id="uploadinfos"|cat:$uploads[ix].fileId _text="{tr}Additional Info{/tr}"}
                         <div style="display:none;" id="uploadinfos{$uploads[ix].fileId}">
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
                {if $prefs.elementplus_upload neq 'y' or $editFileId}
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
                    {if $prefs.elementplus_upload neq 'y' || $editFileId}
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
                {/if}
            </div>

            {if $simpleMode neq 'y'}
                <div class="fgal_file_c2">                
                    {if $prefs.fgal_delete_after eq 'y'}
                        <div class="mb-3 row">
                            <label for="deleteAfter" class="col-md-4 col-form-label">{tr}File can be deleted after{/tr}</label>
                            <div class="col-md-8">
                                {if $editFileId or $prefs.elementplus_upload eq 'y'}
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
                        {if isset($galleries) and count($galleries) eq 0}
                            {if !empty($galleryId)}
                                <input type="hidden" name="galleryId[]" value="{$galleryId}">
                            {else}
                                <input type="hidden" name="galleryId[]" value="{$treeRootId}">
                            {/if}
                        {elseif empty($groupforalert)}
                            <input type="hidden" id="gallery_type"
                                   value="{if isset($gal_info['type'])}{$gal_info['type']}{else}default{/if}">

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
                                    {if $fileInfo.filetype ne ''}
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
                    {if $editFileId}
                        <div class="mb-3 row">
                            <label for="comment" class="col-md-4 form-label">{tr}Comment{/tr}</label>
                            <div class="col-md-8">
                                <input type="text" id="comment" name="comment[]" value="" size="40" class="form-control">
                            </div>
                        </div>
                    {/if}
                </div>
                {if !$editFileId}
                    {include file='categorize.tpl'}<br/>
                {/if}
            {else}
                {if !$editFileId and $category_jail}
                    {include file='categorize.tpl'}<br/>
                {/if}
                <input type="hidden" name="galleryId[]" value="{$galleryId}">
            {/if}

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

            {* Image Compression Controls *}
            {if isset($gal_info.image_compression_enabled) && $gal_info.image_compression_enabled eq 'y'}
                <div class="mb-3 row d-none" id="image_compression_controls">
                    <label class="col-md-4 col-form-label">{tr}Image Compression{/tr}</label>
                    <div class="col-md-8">
                        {* Default Preset Summary *}
                        <div id="compression_default_summary">
                            <div class="border rounded p-3 bg-light mb-3">
                                <strong>{tr}Current Settings:{/tr}</strong>
                                <ul class="mb-2">
                                    <li>
                                        {tr}Max width (X):{/tr}
                                        <span id="default_max_size_x" data-default="{$gal_info.image_max_size_x}">
                                            {$gal_info.image_max_size_x} px
                                        </span>
                                    </li>
                                    <li>
                                        {tr}Max height (Y):{/tr}
                                        <span id="default_max_size_y" data-default="{$gal_info.image_max_size_y}">
                                            {$gal_info.image_max_size_y} px
                                        </span>
                                    </li>
                                    <li>
                                        {tr}Compress files larger than:{/tr}
                                        <span data-default="{$gal_info.image_compression_max_size_mb}" id="default_min_file">
                                            {$gal_info.image_compression_max_size_mb} MB
                                        </span>
                                    </li>
                                    <li>{tr}Save original:{/tr}
                                       <span id="default_save_original">
                                           {if $gal_info.image_compression_save_original eq 'y'}{tr}Yes{/tr}{else}{tr}No{/tr}{/if}
                                       </span>
                                    </li>
                                </ul>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="customize-compression-toggle">
                                    <i class="fas fa-cog"></i> {tr}Customize{/tr}
                                </button>
                            </div>
                        </div>

                        {* Custom Controls (hidden by default) *}
                        <div id="compression_custom_controls" class="d-none">
                            {* Compression type: preset levels or slider *}
                            {if $gal_info.image_compression_preset_type eq 'levels'}
                                <div class="mb-2 row">
                                    <label class="col-md-4 col-form-label">{tr}Compression Level{/tr}</label>
                                    <div class="col-md-8">
                                        <div class="btn-group w-100" role="group">
                                           <input type="radio" class="btn-check" name="compression_preset_main"
                                                id="compression_high_main" value="high" checked>
                                            <label class="btn btn-outline-success btn-sm" for="compression_high_main">
                                                <i class="fas fa-star"></i> {tr}High Quality{/tr}
                                            </label>

                                            <input type="radio" class="btn-check" name="compression_preset_main"
                                                id="compression_balanced_main" value="balanced">
                                            <label class="btn btn-outline-primary btn-sm" for="compression_balanced_main">
                                                <i class="fas fa-balance-scale"></i> {tr}Balanced{/tr}
                                            </label>

                                            <input type="radio" class="btn-check" name="compression_preset_main"
                                               id="compression_compressed_main" value="compressed">
                                            <label class="btn btn-outline-warning btn-sm" for="compression_compressed_main">
                                               <i class="fas fa-compress"></i> {tr}Compressed{/tr}
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            {else}
                                <div class="mb-2 row">
                                    <label for="compression_quality_main" class="col-md-4 col-form-label">{tr}Compression Quality{/tr}</label>
                                    <div class="col-md-8">
                                        <div class="d-flex align-items-center">
                                            <input type="range" class="form-range me-3" id="compression_quality_main"
                                                name="compression_quality" min="10" max="100"
                                                value="{$gal_info.image_compression_quality}">
                                            <span class="quality-display badge bg-primary"
                                                id="quality_display_main">{$gal_info.image_compression_quality}%</span>
                                        </div>
                                    </div>
                                </div>
                            {/if}

                            <div class="mb-2 row">
                                <label for="compression_max_dimension_x_main" class="col-md-4 col-form-label">{tr}Max Image Width (X){/tr}</label>
                                <div class="col-md-8">
                                    <input type="number" id="compression_max_dimension_x_main" name="compression_max_dimension_x" class="form-control"
                                        value="{$gal_info.image_max_size_x}">
                                    <small id="compression_max_dimension_x_msg" class="text-success small"></small>
                                    <small class="form-text text-muted d-block">{tr}Maximum width in pixels. Images wider than this will be resized.{/tr}</small>
                                </div>
                            </div>

                            <div class="mb-2 row">
                                <label for="compression_max_dimension_y_main" class="col-md-4 col-form-label">{tr}Max Image Height (Y){/tr}</label>
                                <div class="col-md-8">
                                    <input type="number" id="compression_max_dimension_y_main" name="compression_max_dimension_y" class="form-control"
                                        value="{$gal_info.image_max_size_y}">
                                    <small id="compression_max_dimension_y_msg" class="text-success small"></small>
                                    <small class="form-text text-muted d-block">{tr}Maximum height in pixels. Images taller than this will be resized.{/tr}</small>
                                </div>
                            </div>

                            <div class="mb-2 row">
                                <label class="col-md-4 col-form-label">{tr}Min File Size to Compress{/tr}</label>
                                <div class="col-md-8">
                                    <input type="number" name="compression_max_size" class="form-control"
                                        value="{$gal_info.image_compression_max_size_mb}" min="1" step="0.5">
                                    <small class="form-text text-muted d-block">{tr}Only compress images larger than this size (MB).{/tr}</small>
                                </div>
                            </div>

                            <div class="mb-1 row">
                                <div class="col-md-4"></div>
                                <div class="col-md-8">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="compression_save_original_main"
                                            name="compression_save_original" value="y"
                                            {if $gal_info.image_compression_save_original eq 'y'}checked{/if}>
                                        <label for="compression_save_original_main" class="form-check-label">{tr}Save original file{/tr}</label>
                                    </div>
                                </div>
                            </div>

                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                id="compression-back-to-default">
                                <i class="fas fa-arrow-left"></i> {tr}Use default compression settings{/tr}
                            </button>
                        </div>
                    </div>
                </div>
            {/if}
            {if $prefs.elementplus_upload eq 'y'}
                <div class="mb-3 row">
                    <tiki-el-file-gal-uploader
                        id="uploader"
                        accept="{$allowedMimeTypes}"
                        max-size="{$max_upload_size}"
                        max-files="{$max_file_uploads}"
                        compression_enabled="{if isset($gal_info.image_compression_enabled) && $gal_info.image_compression_enabled eq 'y'}true{else}false{/if}"
                        compression_preset_type="{if isset($gal_info.image_compression_preset_type)}{$gal_info.image_compression_preset_type}{/if}"
                        compression_quality="{if isset($gal_info.image_compression_quality)}{$gal_info.image_compression_quality}{/if}"
                        compression_max_dimension_x="{if isset($gal_info.image_max_size_x)}{$gal_info.image_max_size_x}{/if}"
                        compression_max_dimension_y="{if isset($gal_info.image_max_size_y)}{$gal_info.image_max_size_y}{/if}"
                        compression_max_size_mb="{if isset($gal_info.image_compression_max_size_mb)}{$gal_info.image_compression_max_size_mb}{/if}"
                        compression_save_original="{if isset($gal_info.image_compression_save_original)}{$gal_info.image_compression_save_original}{/if}"
                    />
                </div>
            {/if}
            {if !$editFileId}
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
            {/if}
            {if !$editFileId && $prefs.elementplus_upload neq 'y'}
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
        <span class="attention">{tr _0=$fileInfo.lockedby}The file has been locked by %0{/tr}</span>
    {/if}
</div>
{if not empty($metarray) and $metarray|count gt 0}
    {include file='metadata/meta_view_tabs.tpl'}
{/if}
{if ! $editFileId and $prefs.elementplus_upload neq 'y'}
    {jq notonready=true}
    {literal}
        function initAjaxForm(formId, progressId) {
            var ajaxConfig = {target: '#' + progressId, forceSync: true};
            if (typeof window.tikiCompressBeforeAjaxSubmit === 'function') {
                ajaxConfig.beforeSubmit = window.tikiCompressBeforeAjaxSubmit;
            }
            $('#' + formId).ajaxForm(ajaxConfig);
        }
        
        initAjaxForm('file_0', 'progress_0');
        var nb_upload = 1;
        function add_upload_file() {
            var clone = $('#form form').eq(0).clone().resetForm().attr('id', 'file_' + nb_upload);
            $(clone[0].submission).val(parseInt($(clone[0].submission).val(), 10) + parseInt(nb_upload, 10));
            clone.insertAfter($('#form form').eq(-1));
            document.getElementById('progress').innerHTML += "<div id='progress_"+nb_upload+"'></div>";
            initAjaxForm('file_' + nb_upload, 'progress_' + nb_upload);
            nb_upload += 1;
        }
        function upload_files(){
            var totalSubmissions = $("#form form").length;
            $("#form form").append($('<input />', {type: 'hidden', name: 'totalSubmissions', value: totalSubmissions}));
            $("#form form").each(async function(n) {
                if ($(this).find('input[name="userfile\\[\\]"]').val() != '') {
                    const $form = $(this);
                    const $fileInput = $form.find('input[name="userfile\\[\\]"]');
                    const originalFiles = $fileInput[0].files;

                    if(typeof window.tikiGetCompressionSettings === "function"){
                        const pressets = window.tikiGetCompressionSettings();
                        if(pressets?.isInitialized && pressets?.current?.saveOriginal !== "y") {
                            try {
                                const dataTransfer = await window.tikiCompressFileList($fileInput[0].files, pressets.current);
                                $fileInput[0].files = dataTransfer.files;
                            } catch (err) {
                                console.error('[Upload] Compression failed:', err);
                                show('form');
                                $("#form").showError(tr("File compression error:") + " " + err.message);
                                return;
                            }
                        }
                    }

                    var $progress = $('#progress_'+n).html(`{/literal}{icon name='spinner' iclass='fa-spin' _menu_text='y' _menu_icon='y' ititle="{tr}Uploading file...{/tr}"}{literal}`);
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
    {/literal}
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
        {literal}
            var handleVimeoFile = function (link, data) {
                if (data != undefined) {
                $("#form").hide();
                $("#progress").append(
                    $("<p> {tr}Video file uploaded:{/tr} " + data.file + "</p>")
                        .prepend($("{/literal}{icon name='vimeo'}{literal}"))
                    );
                }
            }
        {/literal}
        {/jq}
    {/if}
{/if}
{jq}
var defaultDimX = $("#image_max_dimension_x").attr('value');
var defaultDimY = $("#image_max_dimension_y").attr('value');

$("#imagesize").on("click", function () {
    if ($(this).prop("checked")) {
        $("#customsize").css("display", "none");
        //resetting size to default
        $("#image_max_dimension_x").attr('value', defaultDimX);
        $("#image_max_dimension_y").attr('value', defaultDimY);
        $("element-plus-ui#uploader").attr('max-width', defaultDimX);
        $("element-plus-ui#uploader").attr('max-height', defaultDimY);
    } else {
        $("#customsize").css("display", "");
        $("#image_max_dimension_x").on("change", function () {
            $("element-plus-ui#uploader").attr('max-width', $(this).val());
        });
        $("#image_max_dimension_y").on("change", function () {
            $("element-plus-ui#uploader").attr('max-height', $(this).val());
        });
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

var currentGalInfo = {};

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
            if (data.canUpload && data.gal_info) {
                // Store complete gal_info object
                currentGalInfo = data.gal_info;
                
                // Update gallery type
                $("#gallery_type").attr('value', currentGalInfo.type);

                // Update image dimensions display
                const hasMaxWidth = Number(currentGalInfo.image_max_size_x) > 0;
                const hasMaxHeight = Number(currentGalInfo.image_max_size_y) > 0;

                if (hasMaxWidth && hasMaxHeight) {
                    $("#imageResizeInfo").html(currentGalInfo.image_max_size_x + 'px (width) × ' + currentGalInfo.image_max_size_y + 'px (height)');
                } else if (hasMaxWidth) {
                    $("#imageResizeInfo").html(currentGalInfo.image_max_size_x + 'px (width)');
                } else if (hasMaxHeight) {
                    $("#imageResizeInfo").html(currentGalInfo.image_max_size_y + 'px (height)');
                } else {
                    $("#imageResizeInfo").html('(No resize)');
                }

                // Update compression settings from gallery info
                if (currentGalInfo.image_compression_enabled === 'y') {
                    $("#image_compression_controls").removeClass('d-none');
                    
                    // Update default summary display
                    if (currentGalInfo.image_max_size_x !== undefined) {
                        const maxWidthText = hasMaxWidth ? currentGalInfo.image_max_size_x + ' px' : tr('No resize');
                        $("#default_max_size_x").text(maxWidthText).attr('data-default', currentGalInfo.image_max_size_x);
                    }
                    if (currentGalInfo.image_max_size_y !== undefined) {
                        const maxHeightText = hasMaxHeight ? currentGalInfo.image_max_size_y + ' px' : tr('No resize');
                        $("#default_max_size_y").text(maxHeightText).attr('data-default', currentGalInfo.image_max_size_y);
                    }
                    if (currentGalInfo.image_compression_max_size_mb) {
                        $("#default_min_file").text(currentGalInfo.image_compression_max_size_mb + ' MB').attr('data-default', currentGalInfo.image_compression_max_size_mb);
                    }
                    if (currentGalInfo.image_compression_save_original !== undefined) {
                        const saveOrigText = currentGalInfo.image_compression_save_original === 'y' ? tr("Yes") : tr("No");
                        $("#default_save_original").text(saveOrigText);
                    }

                    // Update custom controls
                    if (currentGalInfo.image_compression_preset_type === 'levels') {
                        $('input[name="compression_preset_main"]').prop('checked', false);
                        $('input[name="compression_preset_main"][value="high"]').prop('checked', true);
                    } else if (currentGalInfo.image_compression_quality) {
                        $("#compression_quality_main").val(currentGalInfo.image_compression_quality);
                        $("#quality_display_main").text(currentGalInfo.image_compression_quality + '%');
                    }

                    if (currentGalInfo.image_max_size_x !== undefined) {
                        $("#compression_max_dimension_x_main").val(currentGalInfo.image_max_size_x);
                    }
                    if (currentGalInfo.image_max_size_y !== undefined) {
                        $("#compression_max_dimension_y_main").val(currentGalInfo.image_max_size_y);
                    }
                    if (currentGalInfo.image_compression_max_size_mb) {
                        $('input[name="compression_max_size"]').val(currentGalInfo.image_compression_max_size_mb);
                    }
                    if (currentGalInfo.image_compression_save_original !== undefined) {
                        $("#compression_save_original_main").prop('checked', currentGalInfo.image_compression_save_original === 'y');
                    }
                } else {
                    $("#image_compression_controls").addClass('d-none');
                }
            } else {
                currentGalInfo = {};
                $("#gallery_type").attr('value', '');
                $("#imageResizeInfo").html('');
                $("#image_compression_controls").addClass('d-none');
            }
        },
        error: function (e) {
            console.log(e);
        }
    });
});

// Show/hide compression controls based on selected file type
$("#userfile").on("change", function () {
    const compressionSection = $("#image_compression_controls");
    const files = this.files;
    
    // Check if any image file is selected
    const hasImageFile = files && files.length > 0 && Array.from(files).some(file => {
        return file.type.startsWith('image/');
    });
    
    if (hasImageFile) {
        compressionSection.removeClass('d-none');
    } else {
        compressionSection.addClass('d-none');
    }
});
{/jq}
