{extends $global_extend_layout|default:'layout_view.tpl'}
{block name="title"}
    {title}{$title}{/title}
{/block}
{block name="content"}
    {include file='access/include_items.tpl'}
    <form id='confirm-action' class='confirm-action' action="{service controller="$confirmController" action="$confirmAction"}" method="post">
        {include file='access/include_hidden.tpl'}
        {include file='access/include_submit.tpl'}
    </form>
{/block}
