{* BigBlueButton Recordings Tracker Field Input Template *}

{* Local server selection mode *}
{if $input_mode eq 'local' || $input_mode eq 'both'}
    {if $recordings && count($recordings) > 0}
        {if $field.options.allow_multiple eq 'y'}
            {* Multiple selection mode *}
            <div class="bbb-recordings-container">
                <label class="form-label">{tr}Select from local BBB server:{/tr}</label>
                {foreach from=$recordings item=recording}
                    <div class="bbb-recording-item mb-3 p-3 border rounded">
                        {include file="bigbluebutton/recording_display.tpl" 
                                 recording=$recording 
                                 show_actions=true 
                                 input_name=$ins_id
                                 show_formats=$field.options.show_playback_formats
                                 show_metadata=$field.options.show_metadata}
                    </div>
                {/foreach}
            </div>
        {else}
            {* Single selection mode - dropdown *}
            <div class="mb-3">
                <label class="form-label">{tr}Select from local BBB server:{/tr}</label>
                <select name="{$ins_id}" class="form-select bbb-recording-select">
                    <option value="">{tr}Select a recording...{/tr}</option>
                    {foreach from=$recordings item=recording}
                        {assign var="recordUrl" value=""}
                        {if $recording.playback && is_array($recording.playback)}
                            {foreach from=$recording.playback item=url name=pb}
                                {if $smarty.foreach.pb.first}
                                    {assign var="recordUrl" value=$url}
                                {/if}
                            {/foreach}
                        {/if}
                        <option value="{$recordUrl|escape}" 
                                data-recording='{$recording|@json_encode}'
                                {if $current_data.url eq $recordUrl}selected="selected"{/if}>
                            {$recording.meetingName|escape|default:"Recording"} - 
                            {if $recording.startTime}{$recording.startTime|tiki_long_date} {$recording.startTime|tiki_short_time}{/if}
                        </option>
                    {/foreach}
                </select>
            </div>
            
            {* Simple preview for selected recording *}
            <div id="bbb-preview-{$ins_id}" class="mt-2 p-2 border rounded bg-light" style="display:none;">
                <small class="text-muted d-block mb-1" id="bbb-preview-title-{$ins_id}"></small>
                <a id="bbb-preview-link-{$ins_id}" href="#" target="_blank" class="btn btn-success btn-sm">
                    <i class="fas fa-play me-1"></i>{tr}Play{/tr}
                </a>
            </div>
        {/if}
    {elseif $input_mode eq 'local'}
        <div class="alert alert-info">
            {tr}No BigBlueButton recordings found.{/tr}
            {if $field.options.meeting_filter}
                <br><small>{tr}Meeting filter:{/tr} {$field.options.meeting_filter|escape}</small>
            {/if}
        </div>
    {/if}
{/if}

{* URL input mode separator *}
{if $input_mode eq 'both'}
    <div class="my-3">
        <hr>
        <p class="text-center text-muted">{tr}OR{/tr}</p>
    </div>
{/if}

{* Manual URL input mode *}
{if $input_mode eq 'url' || $input_mode eq 'both'}
    <div class="mb-3">
        <label class="form-label">{tr}Enter BBB Recording URL:{/tr}</label>
        <input type="text" 
               name="{$ins_id}_url" 
               class="form-control" 
               placeholder="https://bbb.example.com/playback/presentation/2.3/abc123..." 
               value="{if $current_data.url && $input_mode eq 'url'}{$current_data.url|escape}{/if}">
        <small class="form-text text-muted">
            {tr}Enter the full URL of a BigBlueButton recording from any BBB server{/tr}
        </small>
    </div>
{/if}

{* Display current value if available *}
{if $current_data && $current_data.url}
    <div class="mt-3 p-3 border rounded bg-light">
        <h6 class="mb-2">{tr}Current value:{/tr}</h6>
        {if isset($current_data.metadata)}
            {* Use shared template with metadata *}
            {assign var="recording" value=$current_data.metadata}
            {assign var="recording.url" value=$current_data.url}
            {include file="bigbluebutton/recording_display.tpl" 
                     recording=$recording 
                     show_actions=false 
                     show_formats=$field.options.show_playback_formats
                     show_metadata=$field.options.show_metadata}
        {else}
            {* Use shared template for URL without metadata *}
            {assign var="recording" value=[]}
            {assign var="recording.url" value=$current_data.url}
            {include file="bigbluebutton/recording_display.tpl" 
                     recording=$recording 
                     show_actions=false 
                     url_only=true
                     show_url_info=true}
        {/if}
    </div>
{/if}

{jq}
// Checkbox interaction for multiple selection
$('.bbb-recordings-container input[type="checkbox"]').on('change', function() {
    var container = $(this).closest('.bbb-recording-item');
    if ($(this).is(':checked')) {
        container.addClass('border-primary');
    } else {
        container.removeClass('border-primary');
    }
});

// Simple dropdown preview (minimal, no duplication)
$('.bbb-recording-select').on('change', function() {
    var selectedOption = $(this).find('option:selected');
    var recordingData = selectedOption.data('recording');
    var previewContainer = $('#bbb-preview-{$ins_id}');
    var previewTitle = $('#bbb-preview-title-{$ins_id}');
    var previewLink = $('#bbb-preview-link-{$ins_id}');
    
    if (recordingData && selectedOption.val()) {
        var title = recordingData.meetingName || '{tr}Recording{/tr}';
        if (recordingData.startTime) {
            title += ' - ' + new Date(recordingData.startTime * 1000).toLocaleString();
        }
        previewTitle.text(title);
        previewLink.attr('href', selectedOption.val());
        previewContainer.show();
    } else {
        previewContainer.hide();
    }
});

// Trigger on page load if there's a selection
if ($('.bbb-recording-select').val()) {
    $('.bbb-recording-select').trigger('change');
}
{/jq}
