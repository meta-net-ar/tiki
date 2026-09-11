<div align="center" class="attention" style="padding-top: 20px; padding-bottom: 20px;">
    <strong>{tr}Note: Remember that this is only a preview, and has not yet been saved!{/tr}</strong>
</div>

{if $tracker_is_multilingual}
    <div class="translations">
        <a href="{bootstrap_modal controller=translation action=manage type=trackeritem source=$itemId}">{tr}Translations{/tr}</a>
    </div>
{/if}

{* show item *}
{trackerfields mode=view trackerId=$trackerId itemId=$itemId fields=$fields itemId=$itemId viewItemPretty=$viewItemPretty.value preview='y'}

{* -------------------------------------------------- section with comments --- *}
{if $tracker_info.useComments eq 'y' and ($tiki_p_tracker_view_comments ne 'n' or $tiki_p_comment_tracker_items ne 'n' or $canViewCommentsAsItemOwner) and $prefs.tracker_show_comments_below eq 'y'}
    <a id="Comments"></a>
    <div id="comment-container-below" class="well well-sm" data-bs-target="{service controller=comment action=list type=trackeritem objectId=$itemId}"></div>
    {jq}
        var id = '#comment-container-below';
        $(id).comment_load($(id).data('bs-target'));
        $(document).on("ajaxComplete", function(){$(id).tiki_popover();});
    {/jq}
{/if}
<br><br>
