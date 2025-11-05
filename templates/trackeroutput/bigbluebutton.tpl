{* BigBlueButton Recordings Tracker Field Output Template *}

{if $recording_data && $recording_data.url}
    <div class="bbb-recording-output">
        {if isset($recording_data.metadata) && $recording_data.metadata}
            {* Use shared template with metadata *}
            {assign var="recording" value=$recording_data.metadata}
            {assign var="recording.url" value=$recording_data.url}
            {include file="bigbluebutton/recording_display.tpl" 
                     recording=$recording 
                     show_actions=false 
                     show_formats=$field.options.show_playback_formats
                     show_metadata=$field.options.show_metadata}
        {else}
            {* Use shared template for URL without metadata *}
            {assign var="recording" value=[]}
            {assign var="recording.url" value=$recording_data.url}
            {include file="bigbluebutton/recording_display.tpl" 
                     recording=$recording 
                     show_actions=false 
                     url_only=true
                     show_url_info=false}
        {/if}
    </div>
{else}
    {include file="bigbluebutton/recording_display.tpl" 
             show_empty_message=true}
{/if}
