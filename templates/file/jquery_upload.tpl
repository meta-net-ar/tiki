{* Used by smarty_function_filegal_uploader() when $prefs.file_galleries_use_jquery_upload is enabled *}
{* The fileinput-button span is used to style the file input field as button *}
<div class="mb-3 row">
    <div class="col-md-12">
        <div class="card bg-body-tertiary fileupload mb-0">
            <div class="card-body">
                <div class="h3 text-center">{icon name="cloud-upload"} {tr}Drop files or {/tr}
                    <div class="btn btn-primary fileinput-button">
                        <span>{tr}Choose files{/tr}</span>
                        {* The file input field used as target for the file upload widget *}
                        <input id="fileupload" type="file" name="files[]" aria-label="{tr}Choose files{/tr}" multiple{if isset($allowedMimeTypes)} accept="{$allowedMimeTypes|escape}"{/if}>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="mb-3 row">
    <div id="files" class="files text-center col-md-12"></div>
</div>
<div class="col-sm-12">
<div class="form-check">
    <label for="autoupload" class="form-check-label">{* auto-upload user pref *}
        <input class="form-check-input" type="checkbox" id="autoupload" name="autoupload"{if $prefs.filegals_autoupload eq 'y'} checked="checked"{/if}>
        {tr}Automatic upload{/tr}
    </label>{* The container for the uploaded files *}
</div>
</div>
<div class="d-none">
    {icon name='file' id='file_icon'}
    {icon name='pdf' id='pdf_icon'}
    {icon name='video' id='video_icon'}
    {icon name='audio' id='audio_icon'}
    {icon name='zip' id='zip_icon'}
    {icon name='word' id='word_icon'}
    {icon name='excel' id='excel_icon'}
    {icon name='powerpoint' id='powerpoint_icon'}
    {icon name='textfile' id="txt_icon"}
    {icon name='css3' id='css_icon'}
    {icon name='mailbox' id='mailbox_icon'}
    {icon name='html' id='html_icon'}
    {icon name='php' id='php_icon'}
    {icon name='js' id='js_icon'}
    {icon name='font-awesome' id='font_icon'}
    {icon name='trackers' id='trackers_icon'}
</div>
