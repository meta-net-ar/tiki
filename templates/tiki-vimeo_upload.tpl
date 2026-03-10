{title help="Vimeo" admpage="video"}{tr}Upload Video to Vimeo{/tr}{/title}

<div class="t_navbar mb-4">
    {button href="tiki-list_vimeo_entries.php" class="btn btn-primary" _text="{tr}Browse Videos{/tr}"}
</div>

{if isset($errors) and count($errors) > 0}
    <div class="alert alert-danger">
        <h2>{tr}Errors detected{/tr}</h2>
        {section name=ix loop=$errors}
            {$errors[ix]}<br>
        {/section}
        {button href="#upload" _text="{tr}Retry{/tr}"}
    </div>
{else}
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
            <div class="fgal_file">
                <div class="fgal_file_c1">
                    <div class="mb-3 row">
                        <label for="name" class="col-md-4 col-form-label">{tr}Video Title{/tr}</label>
                        <div class="col-md-8">
                            <input class="form-control" type="text" id="name" name="name" size="40">
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="description" class="col-md-4 col-form-label">{tr}Video Description{/tr}</label>
                        <div class="col-md-8">
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="privacy" class="col-md-4 col-form-label">{tr}Privacy{/tr}</label>
                        <div class="col-md-8">
                            <select name="privacy" id="privacy" class="form-control">
                                <option value="anybody">{tr}Public (Anybody){/tr}</option>
                                <option value="unlisted">{tr}Unlisted{/tr}</option>
                                <option value="nobody">{tr}Private (Nobody){/tr}</option>
                                <option value="disable">{tr}Disable{/tr}</option>
                            </select>
                            <small class="form-text text-muted">{tr}Note: Some privacy levels require a paid Vimeo account.{/tr}</small>
                        </div>
                    </div>
                    <div class="mb-3 row">
                        <label for="video" class="col-md-4 col-form-label">{tr}Upload Video File{/tr}</label>
                        <div class="col-md-8">
                            <input id="video" name="video" type="file" accept="video/*" required size="40">
                        </div>
                    </div>
                </div>
                <div class="fgal_file_c3">
                    <div id="page_bar" class="mb-3 row">
                        <div class="col-md-8 offset-md-4">
                            <input type="submit" class="btn btn-primary" value="{tr}Upload Video{/tr}">
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
{/if}
