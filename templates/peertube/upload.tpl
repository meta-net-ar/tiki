{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {if $uploadInModal}{title}{$title}{/title}{/if}
{/block}

{block name="content"}
    {if isset($upload_success) && $upload_success}
        {* This block should not be reached if upload was successful - broker returns JSON *}
    {else}
        {feedback}

        {if isset($channelList) and count($channelList) > 0}
            <form method="post" action="{service controller=peertube action=upload targetName=$targetName}" enctype="multipart/form-data" class="form">
                {ticket}
                <div class="mb-3">
                    <label for="channelId" class="form-label">{tr}Select Channel{/tr}</label>
                    <select name="channelId" id="channelId" class="form-select" required>
                        {foreach from=$channelList key=cId item=channelName}
                            <option value="{$cId|escape}" {if isset($channelId) and $channelId eq $cId}selected{/if}>{$channelName|escape}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">{tr}Video Title{/tr} <span class="text-danger">*</span></label>
                    <input class="form-control" type="text" id="name" name="name" size="40" required minlength="3" maxlength="120" value="{$name|escape}">
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">{tr}Video Description{/tr}</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{$description|escape}</textarea>
                </div>
                <div class="mb-3">
                    <label for="privacy" class="form-label">{tr}Privacy{/tr}</label>
                    <select name="privacy" id="privacy" class="form-select">
                        <option value="1" {if isset($privacy) and $privacy eq 1}selected{/if}>{tr}Public{/tr}</option>
                        <option value="2" {if isset($privacy) and $privacy eq 2}selected{/if}>{tr}Unlisted{/tr}</option>
                        <option value="3" {if isset($privacy) and $privacy eq 3}selected{/if}>{tr}Private{/tr}</option>
                        <option value="4" {if isset($privacy) and $privacy eq 4}selected{/if}>{tr}Internal{/tr}</option>
                        <option value="5" {if isset($privacy) and $privacy eq 5}selected{/if}>{tr}Password protected{/tr}</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="video" class="form-label">{tr}Upload Video File{/tr} <span class="text-danger">*</span></label>
                    <input id="video" name="video" type="file" accept="video/*" required class="form-control">
                </div>
                <div class="text-end">
                    <input type="submit" class="btn btn-primary" value="{tr}Upload Video{/tr}">
                </div>
            </form>
            
        {else}
            <div class="alert alert-warning">
                {tr}No channels available. Please configure PeerTube channels first.{/tr}
            </div>
        {/if}
    {/if}
{/block}

{jq}
// Intercept form submission response to trigger original clickModal success callback
// This needs to run BEFORE the default handler closes the modal
$(document).on('ajaxSuccess', '.modal-body form', function(e, xhr, settings) {
    try {
        var data = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText);
        // Check if this is a successful upload response with entries
        if (data && data.entries && data.entries.length > 0 && data.extra === 'close') {
            // Find the modal and get the clickModal success callback
            var $modal = $(this).closest('.modal');
            var $trigger = $modal.data('clickModalTrigger');
            
            // Trigger custom event for trackerinput template to handle
            // This will add the video to the field
            $(document).trigger('peertube.upload.success', [data]);
            
            // Also try to trigger the clickModal success callback if it exists
            // The modal might have stored the success callback
            if ($modal.data('clickModalSuccess')) {
                $modal.data('clickModalSuccess').call($trigger || this, data);
            }
        }
    } catch(err) {
        // Not JSON or not our response, ignore
    }
});
{/jq}
