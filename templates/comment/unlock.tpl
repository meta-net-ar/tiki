{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {if $status neq 'DONE'}
        <form method="post" action="{service controller=comment action=unlock}">
            <div class="mb-3">
                {tr}Are you sure you want to unlock comments on this object?{/tr}
            </div>
            <div class="submit">
                <input type="hidden" name="type" value="{$type|escape}"/>
                <input type="hidden" name="objectId" value="{$objectId|escape}"/>
                <input type="hidden" name="confirm" value="1"/>
                <input type="submit" class="btn btn-primary" value="{tr}Confirm{/tr}"/>
            </div>
        </form>
    {/if}
{/block}
