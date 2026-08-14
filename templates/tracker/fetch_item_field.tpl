{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
    {if $mode eq 'output'}
        {trackeroutput field=$field list_mode=$listMode itemId=$itemId}
    {else}
        {trackerinput field=$field itemId=$itemId}
    {/if}
{/block}
