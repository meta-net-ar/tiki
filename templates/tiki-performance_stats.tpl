{title help="Real User Measurement"}{tr}Search performance statistics{/tr}{/title}

<div class="t_navbar">
    {button href="?clear=1" class="btn btn-primary" _text="{tr}Clear Stats{/tr}"}
</div>

{include file='find.tpl'}

<h5>{tr}Average time taken by request{/tr}</h5>
<div class="table-responsive">
    <table class="table" id="average-stat-table">
        <thead>
        <tr>
            <th>{tr}URL{/tr}</th>
        <th class="text-end"><a href="tiki-performance_stats.php?offset={$average_stat_offset}&amp;no_of_requests={if $average_stat_order eq 'DESC'}ASC{else}DESC{/if}">{tr}Number of requests{/tr}</a></th>
            <th class="text-end">
                <a href="tiki-performance_stats.php?offset={$average_stat_offset}&amp;average_stat_order={if $average_stat_order eq 'DESC'}ASC{else}DESC{/if}">{tr}Time taken (seconds){/tr}</a>
            </th>
        </tr>
        </thead>

        <tbody id="average-stat-body">
        {foreach from=$average_load_time_stats item=stat}
            <tr>
                <td class="text"><a href="{$stat.url}">{$performance_stats_lib->simplifyURL($stat.url)}</a></td>
                <td class="integer">{$stat.number_of_requests}</td>
                <td class="integer">{$stat.average_time_taken / 1000}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    {if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
        <el-infinite-scroll container-id="average-stat-table" target-container-id="average-stat-body" offset="{$average_stat_offset}" step="25" total-count="{$pages_count}" offset-arg="average_stat_offset"></el-infinite-scroll>
    {/if}
</div>
{if $prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y'}
    {pagination_links count=$pages_count step=25 offset=$average_stat_offset offset_arg="average_stat_offset"}{/pagination_links}
{/if}

<h5>{tr}Maximum time taken by request{/tr}</h5>
<div class="table-responsive">
    <table class="table" id="maximum-stat-table">
        <thead>
        <tr>
            <th>{tr}URL{/tr}</th>
            <th class="text-end">
                <a href="tiki-performance_stats.php?offset={$maximum_stat_offset}&amp;maximum_stat_order={if $maximum_stat_order eq 'DESC'}ASC{else}DESC{/if}">{tr}Time taken (seconds){/tr}</a>
            </th>
        </tr>
        </thead>

        <tbody id="maximum-stat-body">
        {foreach from=$maximum_load_time_stats item=stat}
            <tr>
                <td class="text"><a href="{$stat.url}">{$performance_stats_lib->simplifyURL($stat.url)}</a></td>
                <td class="integer">{$stat.maximum_time_taken / 1000}</td>
            </tr>
        {/foreach}
        </tbody>
    </table>
    {if $prefs.feature_elementplus eq 'y' && $prefs.elementplus_infinite_scroll eq 'y'}
        <el-infinite-scroll container-id="maximum-stat-table" target-container-id="maximum-stat-body" offset="{$maximum_stat_offset}" step="25" total-count="{$pages_count}" offset-arg="maximum_stat_offset"></el-infinite-scroll>
    {/if}
</div>
{if $prefs.feature_elementplus neq 'y' || $prefs.elementplus_infinite_scroll neq 'y'}
    {pagination_links count=$pages_count step=25 offset=$maximum_stat_offset offset_arg="maximum_stat_offset"}{/pagination_links}
{/if}
