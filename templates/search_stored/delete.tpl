{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {if $success}
        {remarksbox type=feedback title="{tr}Operation Completed{/tr}"}
            <p>{tr _0=$label}%0 was removed{/tr}</p>
            <a class="btn btn-success" href="{service controller=search_stored action=list}" role="button">{tr}Return to List of Saved Searches{/tr}</a>
        {/remarksbox}
    {else}
        <form method="post" action="{service controller=search_stored action=delete}">
            <div class="mb-3 row">{tr _0=$label}Do you really want to remove the %0 saved search?{/tr}</div>
            <div class="mb-3 submit">
                <input type="hidden" name="queryId" value="{$queryId|escape}"/>
                <input class="btn btn-danger" type="submit" value="{tr}Delete{/tr}"/>
            </div>
        </form>
    {/if}
{/block}
