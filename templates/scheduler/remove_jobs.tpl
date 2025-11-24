{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
{if $items|count > 0}
    <form class="simple" method="post" action="{service controller=scheduler action=remove_jobs}">
        {if $items|count == 1}
            <p>{tr}Do you really want to remove the following job?{/tr}</p>
        {else}
            <p>{tr _0=$items|count}Do you really want to remove the following %0 jobs?{/tr}</p>
        {/if}

        <ul class="list-group mb-3">
        {foreach from=$items key=id item=name}
            <li class="list-group-item">{$name|escape}</li>
        {/foreach}
        </ul>

        <div class="submit">
            {foreach from=$items key=id item=name}
                <input type="hidden" name="items[]" value="{$id|escape}">
            {/foreach}
            <input type="submit" class="btn btn-primary" value="{tr}Remove{/tr}">
            {ticket mode='confirm'}
        </div>
    </form>
{else}
    <a href="tiki-admin_schedulers.php">{tr}Back to schedulers{/tr}</a>
{/if}
{/block}
