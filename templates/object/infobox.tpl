{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
{if $show_title}
    <h1>{object_link type=$type id=$object backuptitle="{tr}Information{/tr}"}</h1>
{/if}
{/block}

{block name="content"}
{if $plain}
{$content}
{else}
<div>{$content}</div>
{/if}
{/block}
