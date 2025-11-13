{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="navigation"}
    {if $skip_form neq 'y'}
        {include file='tracker_actions.tpl'}
    {/if}
{/block}

{block name="content"}
    <div class="previewTrackerItem"></div>
    {if $skip_form eq 'y'}
        <form method="post" action="{service controller=tracker action=update_item trackerId=$trackerId itemId=$itemId}">
            <p>
                {$skip_form_message}
            </p>
            <div class="submit">
                <input type="hidden" name="status" value="{$status|escape}">
                {foreach from=$forced key=permName item=value}
                    <input type="hidden" name="forced~{$permName|escape}" value="{$value|escape}">
                {/foreach}
                {if $skip_preview neq 'y'}
                    <input type="button" class="btn btn-primary previewItemBtn" title="{tr}Preview your changes.{/tr}" name="preview" value="{tr}Preview{/tr}">
                {/if}
                <input type="hidden" name="redirect" value="{$redirect|escape}">
                <input type="hidden" name="conflictoverride" value="{$conflictoverride|escape}">
                <input type="hidden" name="skipRefresh" value="{$skipRefresh|escape}">
                <input type="submit" class="btn btn-primary" value="{$button_label}" onclick="needToConfirm=false;">
            </div>
        </form>
    {else}
        <form method="post" action="{service controller=tracker action=update_item format=$format editItemPretty=$editItemPretty suppressFeedback=$suppressFeedback}" id="updateItemForm{$trackerId|escape}">
            {trackerfields trackerId=$trackerId fields=$fields status=$status itemId=$itemId format=$format editItemPretty=$editItemPretty}
            <hr>
            <div class="form-check form-switch alert alert-warning">
                <input type="checkbox"
                    class="form-check-input tracker-notify-switch"
                    id="notify_watchers"
                    name="notify_watchers"
                    value="1"
                    checked>
                <label class="form-check-label" for="notify_watchers">
                    {tr}Notify users following this item{/tr}
                </label>
            </div>
            <div class="submit">
                {if $skip_preview neq 'y'}
                    <input type="button" class="btn btn-secondary previewItemBtn" title="{tr}Preview your changes.{/tr}" name="preview" value="{tr}Preview{/tr}">
                {/if}
                {if not empty($saveAndComment) and $saveAndComment neq 'n'}
                    <input type="hidden" name="saveAndComment" id="saveAndComment" value="">
                    <input type="submit" class="btn btn-primary" onclick="$('#saveAndComment').val(1);" value="{tr}Save and Comment{/tr}">
                {/if}
                {if $save_return eq 'y'}
                    <input type="submit" class="btn btn-primary" name="save_return" value="{tr}Save Returning to Item List{/tr}" onclick="$('input[name=redirect]').val('{$trackerId|sefurl:'tracker'}'); needToConfirm=false">
                {/if}
                <input type="hidden" name="itemId" value="{$itemId|escape}">
                <input type="hidden" name="trackerId" value="{$trackerId|escape}">
                {foreach from=$forced key=permName item=value}
                    <input type="hidden" name="forced~{$permName|escape}" value="{$value|escape}">
                {/foreach}
                <input type="hidden" name="redirect" value="{$redirect|escape}">
                <input type="hidden" name="conflictoverride" value="{$conflictoverride|escape}">
                <input type="hidden" name="skipRefresh" value="{$skipRefresh|escape}">
                <input type="submit" class="btn btn-primary" value="{$button_label}" onclick="needToConfirm=false;">
                {if $can_remove and $prefs.tracker_legacy_insert eq 'y'}
                    <a class="btn btn-danger" href="tiki-view_tracker.php?trackerId={$trackerId|escape}&amp;remove={$itemId|escape}" title="Delete" role="button">
                        {tr}Delete{/tr}
                    </a>
                {/if}
            </div>
        </form>
        {* Don't warn on leaving page if the modal is closed without saving *}
        {jq}$(".modal.fade.show").one("hide.bs.modal", function () {window.needToConfirm=false;});{/jq}
    {/if}
{/block}
