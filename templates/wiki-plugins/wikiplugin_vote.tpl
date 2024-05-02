{strip}
<span style="display:inline;{if $float}float:{$float}{/if}" class="poll">

<div class="pollnav">
{if $show_toggle ne 'n'}
<a onclick="javascript:$('#pollzone{$tracker.trackerId}').toggle();$('#polledit{$tracker.trackerId}').toggle();$('#pollicon{$tracker.trackerId}').toggle();$('#pollicon{$tracker.trackerId}o').toggle()" class="link" title="{tr}Toggle display{/tr}">
<span id="pollicon{$tracker.trackerId}" style="display:inline;float:left;"><i class="fa-solid fa-square-plus"></i></span>
<span id="pollicon{$tracker.trackerId}o" style="display:none;float:left;"><i class="fa-regular fa-square-minus"></i></span>
</a>
{/if}
{if $has_already_voted ne 'y'}<span class="highlight">{/if}{$tracker.name|escape}{if $has_already_voted ne 'y'}</span>{/if}
{if $tracker_creator}<br>{$tracker_creator|userlink}}{/if}
</div>

<div style="display:{if $wikiplugin_tracker eq $tracker.trackerId or $show_toggle eq 'n'}block{else}none{/if};" id="polledit{$tracker.trackerId}">
{if $p_create_tracker_items eq 'y'}
{$vote}
{elseif !empty($options) and $options.start > 0 and $options.start > $date}
{tr}Start:{/tr} {$options.start|tiki_short_datetime}<br>
{/if}
{if !empty($options) and $options.end > 0 and $options.end > $date}
{tr}Close:{/tr} {$options.end|tiki_short_datetime}<br>
{/if}
</div>

<div style="display:{if $wikiplugin_tracker eq $tracker.trackerId or $show_toggle eq 'n'}block{else}none{/if};" id="pollzone{$tracker.trackerId}">
{$stat}
</div>

</span>
{/strip}
