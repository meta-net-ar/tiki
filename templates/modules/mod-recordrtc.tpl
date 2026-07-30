{if $show_recordrtc_module === true}
    {tikimodule error=$module_error title=$tpl_module_title name="recordrtc" flip=$module_params.flip decorations=$module_params.decorations nobox=$module_params.nobox notitle=$module_params.notitle}
        {if empty($module_error)}
            <div class="mb-3">
                <input id="record-name" class="form-control" type="text" value="" placeholder="Record name">
            </div>
            <div class="mb-3">
                <select id="mod_record_rtc_recording_type" class="form-control">
                    {foreach from=$mod_recordrtc_recording_types item=type key=option}
                        <option value="{$option}">{tr}{$type}{/tr}</option>
                    {/foreach}
                </select>
            </div>
            <div class="mb-3">
                <button id="btn-start-recording" class="btn btn-primary start-recording">
                    <span class="icon fa fa-video"></span> {tr}Start recording{/tr}
                </button>
                <button class="btn btn-danger stop-recording d-none">
                    <span class="icon fa fa-stop"></span> {tr}Stop recording{/tr}
                </button>
                <video style="display:none;" controls autoplay playsinline></video>
                <input id="record-rtc-url" type="hidden" value="{service controller=recordrtc action=upload}">
                <input id="record-rtc-ticket" type="hidden" value="{ticket mode=get}">
                <input id="record-rtc-gallery-id" type="hidden" value="{$module_params.gallery_id}">
            </div>
            <div class="mb-3">
                <input id="record-rtc-auto-upload" type="checkbox" class="form-check-input" aria-label="{tr}Auto-upload{/tr}" name="auto-upload"> {tr}Auto-upload{/tr}
            </div>
            <div class="mb-3">
                <span id="upload-feedback" style="width: 100%"></span>
            </div>
            <div class="mb-3">
                <button id="btn-upload-recording" class="btn btn-primary" style="display:none">
                    <span class="icon fa fa-upload"></span> {tr}Upload recording{/tr}
                </button>
            </div>
        {/if}
    {/tikimodule}
{/if}

{* Audio preview *}
{if isset($show_audio_preview)}
    <audio style="width: 100%; max-width: 500px" src="{$media_src|escape}" controls></audio>
{/if}

{* Video preview *}
{if isset($show_video_preview)}
    <video style="width: 100%; max-width: 640px" src="{$media_src|escape}" controls></video>
{/if}
