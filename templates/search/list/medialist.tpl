<ul class="list-unstyled media-list" id="media-container">
    {foreach from=$results item=row}
        <li class="media">
            {if $icon and $icon.field}
                <img class="me-3" src="{$row[$icon.field]}" alt="{tr}media image{/tr}">
            {/if}
            <div class="flex-grow-1 ms-3">
                <h4 class="mt-0 mb-1">{object_link type=$row.object_type id=$row.object_id}</h4>
                {if $body and not empty($row[$body.field])}
                    {if not empty($body.mode) and $body.mode eq 'raw'}
                        {$row[$body.field]}
                    {else}
                        {$row[$body.field]|escape}
                    {/if}
                {/if}
            </div>
        </li>
    {/foreach}
</ul>
{if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
    <el-infinite-scroll container-id="media-container" offset="{$results.offset}" step="{$results.maxRecords}" total-count="{$results.count}"></el-infinite-scroll>
{else}
    {pagination_links resultset=$results}{/pagination_links}
{/if}
