{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {if $uploadInModal}{title}{$title}{/title}{/if}
{/block}

{block name="content"}
    {feedback}

    <form method="post" action="{service controller=kaltura action=upload targetName=$targetName}" enctype="multipart/form-data" class="form">
        {ticket}
        <div class="mb-3">
            <label for="name" class="form-label">{tr}Video Title{/tr} <span class="text-danger">*</span></label>
            <input class="form-control" type="text" id="name" name="name" size="40" required minlength="3" maxlength="120" value="{$name|escape}">
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">{tr}Video Description{/tr}</label>
            <textarea class="form-control" id="description" name="description" rows="3">{$description|escape}</textarea>
        </div>
        <div class="mb-3">
            <label for="tags" class="form-label">{tr}Tags{/tr}</label>
            <input class="form-control" type="text" id="tags" name="tags" placeholder="{tr}Comma-separated tags{/tr}" value="{$tags|escape}">
        </div>
        <div class="mb-3">
            <label for="video" class="form-label">{tr}Upload Video File{/tr} <span class="text-danger">*</span></label>
            <input id="video" name="video" type="file" accept="video/*" required class="form-control">
            <div class="form-text">{tr}Maximum file size:{/tr} {$max_upload_size_comment}</div>
        </div>
        <div class="text-end">
            <input type="submit" class="btn btn-primary" value="{tr}Upload Video{/tr}">
        </div>
    </form>
{/block}

{jq}
// Intercept form submission response to trigger original clickModal success callback
$(document).on('ajaxSuccess', '.modal-body form', function(e, xhr, settings) {
    try {
        var data = typeof xhr.responseJSON !== 'undefined' ? xhr.responseJSON : JSON.parse(xhr.responseText);
        // Check if this is a successful upload response with entries
        if (data && data.entries && data.entries.length > 0 && data.extra === 'close') {
            // Trigger custom event for trackerinput template to handle
            $(document).trigger('kaltura.upload.success', [data]);
            
            // Also try to trigger the clickModal success callback if it exists
            var $modal = $(this).closest('.modal');
            if ($modal.data('clickModalSuccess')) {
                $modal.data('clickModalSuccess').call(this, data);
            }
        }
    } catch(err) {
        // Not JSON or not our response, ignore
    }
});
{/jq}
