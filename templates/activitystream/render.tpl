{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
<div class="stream-container{if $autoScroll} auto-scroll{/if}">
    {$body}
    {if $nextPossible}
        <button class="show-more btn btn-primary" data-page="{$pageNumber|escape}" data-stream="{$stream|escape}">{tr}Show More{/tr}</button>
    {/if}
</div>
{/block}
