{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    {if $show_lock_controls}
        <div class="alert alert-info">
            {tr}Are you sure you want to unlock this item?{/tr}
        </div>
        
        <form method="post" action="tiki-view_tracker_item.php?trackerId={$trackerId}&itemId={$itemId}">
            {ticket}
                <div class="submit">
                    <input type="submit" class="btn btn-primary" value="{tr}Unlock{/tr}" onclick="needToConfirm=true;">
                </div>
        </form>
    {else}
        <div class="alert alert-danger">
            {tr}You do not have permission to unlock this item{/tr}.
        </div>
    {/if}
{/block}
