{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    <form method="post" action="{service controller=translation action=detach}" class="form" role="form">
        <p>
            {if $source neq $target}
                {tr _0=$target _1=$source}Are you sure you want to detach the translation %0 from %1 page?{/tr}
            {else}
                {tr _0=$target}Are you sure you want to detach %0 from translations?{/tr}
            {/if}
        </p>
        <div class="submit">
            <input type="hidden" name="type" value="{$type|escape}">
            <input type="hidden" name="source" value="{$source|escape}">
            <input type="hidden" name="target" value="{$target|escape}">
            <input type="hidden" name="confirm" value="1">
            <input type="submit" class="btn btn-primary" value="{tr}Confirm{/tr}">
        </div>
    </form>
{/block}
