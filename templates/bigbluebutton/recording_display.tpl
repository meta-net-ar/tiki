{* Shared template for BigBlueButton recording display *}
{* Parameters: $recording, $show_actions (boolean), $input_name (for input mode), $show_formats (boolean), $url_only (boolean), $show_empty_message (boolean) *}

{* Handle empty state *}
{if $show_empty_message}
    <div class="text-muted">
        <i class="fas fa-info-circle me-1"></i>
        {tr}No recording selected{/tr}
    </div>
{else}
<div class="bbb-recording-display">
    {* Handle URL-only mode (no metadata) *}
    {if $url_only && $recording.url}
        <div class="d-flex align-items-center">
            <i class="fas fa-video me-2"></i>
            <a href="{$recording.url|escape}" target="_blank" class="btn btn-success btn-sm">
                <i class="fas fa-play me-1"></i>
                Play
            </a>
        </div>
        {if $show_url_info}
            <div class="small text-muted mt-1">{$recording.url|escape|truncate:60}</div>
        {/if}
    {else}
    {if $show_actions && $input_name}
        {* Input mode - show checkbox/radio *}
        {* Get URL from playback formats *}
        {assign var="recordingUrl" value=""}
        {if $recording.playback && is_array($recording.playback)}
            {foreach from=$recording.playback item=url name=pb}
                {if $smarty.foreach.pb.first}
                    {assign var="recordingUrl" value=$url}
                {/if}
            {/foreach}
        {/if}
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" 
                   name="{$input_name}[]" 
                   value="{$recordingUrl|escape}" 
                   id="bbb_{$input_name}_{$recording.recordID|escape}"
                   {if $recording.selected}checked="checked"{/if}>
            <label class="form-check-label" for="bbb_{$input_name}_{$recording.recordID|escape}">
                <strong>{$recording.meetingName|escape|default:"Recording"}</strong>
                {if $recording.startTime}
                    <small class="text-muted d-block">
                        {$recording.startTime|tiki_long_date} {$recording.startTime|tiki_short_time}
                    </small>
                {/if}
            </label>
        </div>
    {else}
        {* Display mode - just show title *}
        <div class="mb-2">
            <strong>
                <i class="fas fa-video me-2"></i>
                {$recording.meetingName|escape|default:"BBB Recording"}
            </strong>
        </div>
    {/if}
    
    {* Always show main Play button first *}
    {if $recording.playback && is_array($recording.playback)}
        {* Get first URL for main button *}
        {assign var="firstUrl" value=""}
        {foreach from=$recording.playback item=url name=formats}
            {if $smarty.foreach.formats.first}
                {assign var="firstUrl" value=$url}
            {/if}
        {/foreach}
        {if $firstUrl}
            <div class="mb-2">
                <a href="{$firstUrl|escape}" target="_blank" class="btn btn-success btn-sm">
                    <i class="fas fa-play me-1"></i>{tr}Play{/tr}
                </a>
            </div>
        {/if}
        
        {* Show additional formats if enabled *}
        {if $show_formats|default:true && count($recording.playback) > 1}
            <div class="bbb-playback-formats mb-2">
                <small class="text-muted">{tr}Other formats:{/tr}</small>
                <div class="d-flex flex-wrap gap-1 mt-1">
                    {foreach from=$recording.playback key=format item=url name=formats}
                        {if !$smarty.foreach.formats.first}
                            <a href="{$url|escape}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                {if $format eq 'presentation'}
                                    <i class="fas fa-presentation me-1"></i>{tr}Slides{/tr}
                                {elseif $format eq 'video'}
                                    <i class="fas fa-video me-1"></i>{tr}Video{/tr}
                                {elseif $format eq 'notes'}
                                    <i class="fas fa-sticky-note me-1"></i>{tr}Notes{/tr}
                                {elseif $format eq 'podcast'}
                                    <i class="fas fa-podcast me-1"></i>{tr}Audio{/tr}
                                {elseif $format eq 'screenshare'}
                                    <i class="fas fa-desktop me-1"></i>{tr}Screen{/tr}
                                {else}
                                    {$format|escape|capitalize}
                                {/if}
                            </a>
                        {/if}
                    {/foreach}
                </div>
            </div>
        {/if}
    {elseif $recording.url}
        {* Fallback: show simple play button if no playback formats but we have a URL *}
        <div class="mb-2">
            <a href="{$recording.url|escape}" target="_blank" class="btn btn-success btn-sm">
                <i class="fas fa-play me-1"></i>{tr}Play{/tr}
            </a>
        </div>
    {/if}
    
    {* Recording metadata in compact format *}
    {if $show_metadata|default:true}
    <div class="small text-muted">
        {if $recording.startTime}
            <div class="mb-1">
                <i class="fas fa-calendar me-1"></i>
                {$recording.startTime|tiki_long_date} at {$recording.startTime|tiki_short_time}
            </div>
        {/if}
        {if $recording.endTime}
            <div class="mb-1">
                <i class="fas fa-clock me-1"></i>
                {tr}Duration:{/tr} 
                {math equation="(end - start) / 60" end=$recording.endTime start=$recording.startTime assign=duration}
                {$duration|string_format:"%.0f"} {tr}minutes{/tr}
            </div>
        {/if}
        {if $recording.participants && is_array($recording.participants)}
            <div class="mb-1">
                <i class="fas fa-users me-1"></i>
                {$recording.participants|@count} {tr}participants{/tr}
            </div>
        {/if}
        {if $recording.published}
            <div class="mb-1">
                <i class="fas fa-{if $recording.published eq 'true'}check-circle text-success{else}times-circle{/if} me-1"></i>
                {if $recording.published eq 'true'}{tr}Published{/tr}{else}{tr}Not Published{/tr}{/if}
            </div>
        {/if}
    </div>
    {/if}
    {/if}
</div>
{/if}
