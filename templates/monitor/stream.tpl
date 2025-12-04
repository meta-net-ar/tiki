{extends $global_extend_layout|default:'layout_view.tpl'}

{block name="title"}
    {title}{$title|escape}{/title}
{/block}

{block name="content"}
    <ul class="list-group list-group-flush" id="activity-container">
        {foreach from=$result item=activity}
            <li class="list-group-item">{activity info=$activity}</li>
        {foreachelse}
            <li class="invalid list-group-item">{tr}There is no activity to display in this stream.{/tr}</li>
        {/foreach}
    </ul>
    {if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
        <el-infinite-scroll container-id="activity-container" offset="{$results.offset}" step="{$results.maxRecords}" total-count="{$results.count}"></el-infinite-scroll>
    {else}
        {pagination_links resultset=$result}{/pagination_links}
    {/if}
{/block}
