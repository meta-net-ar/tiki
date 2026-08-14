{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title}{/title}
{/block}

{block name="content"}
{if $schedulerId}
    <p>{$message}</p>
{else}
    <a href="tiki-admin_schedulers.php">{tr}Back to schedulers list{/tr}
{/if}
{/block}
