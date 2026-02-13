{title help="PeerTube" admpage="video"}{tr}Upload Video{/tr}{/title}

<div class="t_navbar mb-4">
    {button href="tiki-list_peertube_entries.php" class="btn btn-primary" _text="{tr}Browse Videos{/tr}"}
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
    <form method="post" action="tiki-peertube_upload.php" enctype="multipart/form-data" class="form-horizontal">
        {ticket}
        <div class="fgal_file">
            <div class="fgal_file_c1">
                <div class="mb-3 row">
                    <label for="channelId" class="col-md-4 col-form-label">{tr}Select Channel{/tr}</label>
                    <div class="col-md-8">
                        <select name="channelId" id="channelId" class="form-control" required>
                            {foreach from=$channelList key=cId item=channelName}
                                <option value="{$cId}" {if isset($channelId) and $channelId eq $cId}selected{/if}>{$channelName|escape}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="name" class="col-md-4 col-form-label">{tr}Video Title{/tr}</label>
                    <div class="col-md-8">
                        <input class="form-control" type="text" id="name" name="name" size="40" value="{$name|default:''|escape}">
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="description" class="col-md-4 col-form-label">{tr}Video Description{/tr}</label>
                    <div class="col-md-8">
                        <textarea class="form-control" id="description" name="description" rows="3">{$description|default:''|escape}</textarea>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="privacy" class="col-md-4 col-form-label">{tr}Privacy{/tr}</label>
                    <div class="col-md-8">
                        <select name="privacy" id="privacy" class="form-control">
                            <option value="1" {if isset($privacy) and $privacy eq 1}selected{/if}>{tr}Public{/tr}</option>
                            <option value="2" {if isset($privacy) and $privacy eq 2}selected{/if}>{tr}Unlisted{/tr}</option>
                            <option value="3" {if isset($privacy) and $privacy eq 3}selected{/if}>{tr}Private{/tr}</option>
                            <option value="4" {if isset($privacy) and $privacy eq 4}selected{/if}>{tr}Internal{/tr}</option>
                            <option value="5" {if isset($privacy) and $privacy eq 5}selected{/if}>{tr}Password protected{/tr}</option>
                        </select>
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
