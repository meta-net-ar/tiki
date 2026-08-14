{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
{if $errMsg}
    {remarksbox title="{tr}Vimeo Setup Error{/tr}" type='error'}
        <p>{tr}{$errMsg}{/tr}</p>
    {/remarksbox}
    {$disabled=true}
{else}
    {remarksbox title="{tr}Info{/tr}" type='info'}
        <p>{tr _0=$availableMB}Available space: %0 megabytes{/tr}</p>
        {if $availableSD eq '0'}<p>{tr}No standard definition uploads available currently{/tr}</p>{/if}
        {if $availableHD eq '0'}<p>{tr}No high definition uploads available currently{/tr}</p>{/if}
    {/remarksbox}
    {if $availableSD eq '0' and $availableHD eq '0'}
        {$disabled=true}
    {/if}
{/if}
<form class="simple no-ajax vimeo_upload" id="form{$ticket.ticket_id|escape}">
    <tiki-el-file-gal-uploader accept="video/*" max-size="{$availableMB * 1000000}" max-files="1" vimeo-url="{$ticket.upload_link_secure}" />
</form>

{jq}
// Disable OK button because dialog auto closes when complete, and also causes problems if clicked
$(".ui-dialog-buttonpane button:contains(tr('OK'))").prop("disabled", true);
{/jq}

{jq notonready=true}
function checkProgress(async) {
        var jqxhr = $.ajax({
                type: "PUT",
                url: uploadlinksecure,
                async: async,
                global: false,
                headers: {
                        "Content-Range": "bytes */*"
                }
        });
        jqxhr.always(function(data, textStatus, jqXHR) {
                var response = data.getResponseHeader('Range');
                current_bytes = response.replace(/[a-z0-9=]+-/, '');
                progress = (current_bytes/total_bytes) * 100;
        });
}
function updateProgressBar() {
    console.log(progress);
    $('#progress').find('.progress-bar').css('width', Math.round(progress) + '%');
}
function completeVimeoUpload(file) {
    var ticket = '{{$ticket.ticket_id|escape}}';
    var $form = $('#form' + ticket);
    var completeUri = {{$ticket.complete_uri|json_encode}};
    var galleryId = {{$galleryId|json_encode}};
    var fieldId = {{$fieldId|json_encode}};
    var itemId = {{$itemId|json_encode}};
    var $title = $('input[name=title]', $form);

    if (file) {
        var updata = {
            title: $title.val(),
            galleryId: galleryId,
            completeUri: completeUri,
            file: file,
            fieldId: fieldId,
            itemId: itemId
        };
        if (updata.file.indexOf("C:\\fakepath\\") === 0) {
            updata.file = updata.file.substr(12);    // webkit fakepath?
        }
        $.post($.service('vimeo', 'complete'), updata, function(data) {
            $form.parents(".ui-dialog").tikiModal();
            if (data.err) {
                alert("Upload Error:\n" + data.err);
            } else {
                $(".vimeo_upload").trigger("vimeo_uploaded", [data]);
            }
        }, 'json')
        .fail(function (e) {
            alert(tr("An error occurred uploading your video.") + "\n" + e.statusText + " (" + e.status + ")");
            $form.parents(".ui-dialog").tikiModal();
            $(".vimeo_upload").trigger("vimeo_uploaded", [{}]);    // get vimeo_uploaded to close the dialog
        });
    } else {
        $form.parents(".ui-dialog").tikiModal();
    }
}
{/jq}
{/block}
