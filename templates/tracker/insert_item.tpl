{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="navigation"}
    {include file='tracker_actions.tpl'}
    <a class="btn btn-primary" href="{service controller=tracker action=select_tracker}">{tr}Select Tracker{/tr}</a>
{/block}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <div class="previewTrackerItem"></div>
    {if ! $itemId}
        {if $trackerLogo}
            <div class="page_header media">
                <img src="{$trackerLogo|escape}" class="float-start img-fluid rounded" alt="{$trackerName|escape}" height="64px" width="64px">
            </div>
        {/if}
        <form method="post" action="{service controller=tracker action=insert_item format=$format editItemPretty=$editItemPretty suppressFeedback=$suppressFeedback}" id="insertItemForm{$trackerId|escape}" {if ! $trackerId}display="hidden"{/if}>
            {ticket}
            {trackerfields trackerId=$trackerId fields=$fields status=$status format=$format editItemPretty=$editItemPretty}
            {if ! $modal}
                <div class="form-check">
                    <input type="hidden" name="next" value="{$next}">
                    <input type="checkbox" class="form-check-input" name="next" id="next" value="{service controller=tracker action=insert_item trackerId=$trackerId next=$next}">
                    <label class="form-check-label" for="next">
                        {tr}Create another{/tr}
                    </label>
                </div>
            {/if}
            {if !$user and $prefs.feature_antibot eq 'y'}
                {include file='antibot.tpl'}
            {/if}
            <div class="submit">
                {if $skip_preview neq 'y'}
                    <input type="button" class="btn btn-secondary previewItemBtn" title="{tr}Preview your changes.{/tr}" name="preview" value="{tr}Preview{/tr}">
                {/if}
                <input type="hidden" name="trackerId" value="{$trackerId|escape}">
                <input type="hidden" name="skipRefresh" value="{$skipRefresh|escape}">
                <input type="hidden" name="refreshMeta" value="{$refreshMeta|escape}">
                <input type="hidden" name="refreshObject" value="{$refreshObject|escape}">
                <input
                    type="submit"
                    class="btn btn-primary"
                    onclick="needToConfirm=false;"
                    value="{tr}Create{/tr}"
                >
                {foreach from=$forced key=permName item=value}
                    <input type="hidden" name="forced~{$permName|escape}" value="{$value|escape}">
                {/foreach}
            </div>
        </form>
    {else}
        {object_link type=trackeritem id=$itemId}
    {/if}
{/block}
