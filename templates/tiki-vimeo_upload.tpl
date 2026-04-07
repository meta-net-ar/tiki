{if $isEdit}
    {title help="Vimeo" admpage="video"}{tr}Edit Vimeo Video{/tr}{/title}
{else}
    {title help="Vimeo" admpage="video"}{tr}Upload Video to Vimeo{/tr}{/title}
{/if}

<div class="t_navbar mb-4">
    {button href="tiki-list_vimeo_entries.php" class="btn btn-primary" _text="{tr}Browse Videos{/tr}"}
    {if $isEdit && $videoInfo}
        {button href="tiki-vimeo_video.php?videoId={$videoInfo->shortUUID|escape}" class="btn btn-secondary" _text="{tr}Cancel / Back to Video{/tr}"}
    {/if}
</div>

{if isset($errors) and count($errors) > 0}
    <div class="alert alert-danger">
        <h2>{tr}Errors detected{/tr}</h2>
        {section name=ix loop=$errors}
            {$errors[ix]}<br>
        {/section}
    </div>
{/if}

<div class="col-md-12">
    {remarksbox type="note" title="{tr}Information{/tr}"}
        {tr}Maximum file size is around:{/tr}
        {if $tiki_p_admin eq 'y'}<a title="|{$max_upload_size_comment}" class="alert-link tips">{/if}
            {$max_upload_size|kbsize:true:0}
        {if $tiki_p_admin eq 'y'}</a>
            {if $is_iis}<br>{tr}Note: You are running IIS{/tr}. {tr}maxAllowedContentLength also limits upload size{/tr}. {tr}Please check web.config in the Tiki root folder{/tr}{/if}
        {/if}
    {/remarksbox}
</div>

<div>
    <form method="post" action="tiki-vimeo_upload.php" enctype="multipart/form-data" class="form-horizontal">
        {ticket}

        {if $isEdit && $videoInfo}
            <input type="hidden" name="videoId" value="{$videoInfo->uuid|escape}">
        {/if}

        <div class="fgal_file">
            <div class="fgal_file_c1">
                <div class="mb-3 row">
                    <label for="name" class="col-md-4 col-form-label">{tr}Video Title{/tr}</label>
                    <div class="col-md-8">
                        <input class="form-control" type="text" id="name" name="name" size="40" 
                               value="{if $isEdit && $videoInfo}{$videoInfo->name|escape}{/if}">
                    </div>
                </div>
                
                <div class="mb-3 row">
                    <label for="description" class="col-md-4 col-form-label">{tr}Video Description{/tr}</label>
                    <div class="col-md-8">
                        <textarea class="form-control" id="description" name="description" rows="3">{if $isEdit && $videoInfo}{$videoInfo->description|escape}{/if}</textarea>
                    </div>
                </div>
                
                <div class="mb-3 row">
                    <label for="privacy" class="col-md-4 col-form-label">{tr}Privacy{/tr}</label>
                    <div class="col-md-8">
                        <select name="privacy" id="privacy" class="form-control">
                            {assign var=currentPrivacy value='anybody'}
                            {if $isEdit && $videoInfo && $videoInfo->privacyLabel}
                                {assign var=currentPrivacy value=$videoInfo->privacyLabel}
                            {/if}
                            <option value="anybody" {if $currentPrivacy eq 'anybody'}selected{/if}>{tr}Public (Anybody){/tr}</option>
                            <option value="unlisted" {if $currentPrivacy eq 'unlisted'}selected{/if}>{tr}Unlisted{/tr}</option>
                            <option value="nobody" {if $currentPrivacy eq 'nobody'}selected{/if}>{tr}Private (Nobody){/tr}</option>
                            <option value="disable" {if $currentPrivacy eq 'disable'}selected{/if}>{tr}Disable{/tr}</option>
                        </select>
                        <small class="form-text text-muted">{tr}Note: Some privacy levels require a paid Vimeo account.{/tr}</small>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="thumbnail" class="col-md-4 col-form-label">
                        {if $isEdit}{tr}Replace Thumbnail{/tr}{else}{tr}Upload Thumbnail{/tr}{/if}
                    </label>
                    <div class="col-md-8">
                        {if $isEdit && $videoInfo && $videoInfo->thumbUrl}
                            <div class="mb-2">
                                <img src="{$videoInfo->thumbUrl|escape}" width="150" alt="Current Thumbnail" class="img-thumbnail">
                            </div>
                        {/if}
                        <input id="thumbnail" name="thumbnail" type="file" accept="image/*" class="form-control">
                        <small class="form-text text-muted">{tr}Optional. Select an image file to set as the video thumbnail cover.{/tr}</small>
                    </div>
                </div>

                <div class="mb-3 row">
                    <label for="video" class="col-md-4 col-form-label">
                        {if $isEdit}{tr}Replace Video File{/tr}{else}{tr}Upload Video File{/tr}{/if}
                    </label>
                    <div class="col-md-8">
                        <input id="video" name="video" type="file" accept="video/*" class="form-control" {if !$isEdit}required{/if}>
                        {if $isEdit}
                            <small class="form-text text-muted">{tr}Optional. Leave this empty to keep the existing video file. Picking a file will replace the current video entirely.{/tr}</small>
                        {/if}
                    </div>
                </div>
            </div>
            <div class="fgal_file_c3">
                <div id="page_bar" class="mb-3 row">
                    <div class="col-md-8 offset-md-4">
                        <input type="submit" class="btn btn-primary" value="{if $isEdit}{tr}Update Video{/tr}{else}{tr}Upload Video{/tr}{/if}">
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
