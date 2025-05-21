{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <ul class="list-group list-group-flush">
        {foreach from=$result item=activity}
            <li class="list-group-item">{activity info=$activity}</li>
        {foreachelse}
            <li class="invalid list-group-item">{tr}There is no activity to display in this stream.{/tr}</li>
        {/foreach}
    </ul>
    {pagination_links resultset=$result}{/pagination_links}
{/block}
