{title help="Real User Measurement"}{tr}Search performance statistics{/tr}{/title}

<div class="t_navbar">
    {button href="?clear=1" class="btn btn-primary" _text="{tr}Clear Stats{/tr}"}
</div>

{include file='find.tpl'}

<h5>{tr}Average time taken by request{/tr}</h5>
<div class="table-responsive">
    <table class="table">
        <tr>
            <th>{tr}URL{/tr}</th>
        <th class="text-end"><a href="tiki-performance_stats.php?offset={$average_stat_offset}&amp;no_of_requests={if $average_stat_order eq 'DESC'}ASC{else}DESC{/if}">{tr}Number of requests{/tr}</a></th>
            <th class="text-end">
                <a href="tiki-performance_stats.php?offset={$average_stat_offset}&amp;average_stat_order={if $average_stat_order eq 'DESC'}ASC{else}DESC{/if}">{tr}Time taken (seconds){/tr}</a>
            </th>
        </tr>
        {foreach from=$average_load_time_stats item=stat}
            <tr>
                <td class="text"><a href="{$stat.url}">{$performance_stats_lib->simplifyURL($stat.url)}</a></td>
                <td class="integer">
                    <a href="tiki-performance_stats.php?find={$find|escape:url}&amp;average_stat_offset={$average_stat_offset}&amp;maximum_stat_offset={$maximum_stat_offset}&amp;average_stat_order={$average_stat_order}&amp;maximum_stat_order={$maximum_stat_order}&amp;details_url={$stat.url|escape:url}#request-details">{$stat.number_of_requests}</a>
                </td>
                <td class="integer">
                    <a href="tiki-performance_stats.php?find={$find|escape:url}&amp;average_stat_offset={$average_stat_offset}&amp;maximum_stat_offset={$maximum_stat_offset}&amp;average_stat_order={$average_stat_order}&amp;maximum_stat_order={$maximum_stat_order}&amp;details_url={$stat.url|escape:url}#request-details">{$stat.average_time_taken / 1000}</a>
                </td>
            </tr>
        {/foreach}
    </table>
</div>
{pagination_links count=$pages_count step=25 offset=$average_stat_offset offset_arg="average_stat_offset"}{/pagination_links}

<h5>{tr}Maximum time taken by request{/tr}</h5>
<div class="table-responsive">
    <table class="table">
        <tr>
            <th>{tr}URL{/tr}</th>
            <th class="text-end">
                <a href="tiki-performance_stats.php?offset={$maximum_stat_offset}&amp;maximum_stat_order={if $maximum_stat_order eq 'DESC'}ASC{else}DESC{/if}">{tr}Time taken (seconds){/tr}</a>
            </th>
        </tr>

        {foreach from=$maximum_load_time_stats item=stat}
            <tr>
                <td class="text"><a href="{$stat.url}">{$performance_stats_lib->simplifyURL($stat.url)}</a></td>
                <td class="integer">
                    <a href="tiki-performance_stats.php?find={$find|escape:url}&amp;average_stat_offset={$average_stat_offset}&amp;maximum_stat_offset={$maximum_stat_offset}&amp;average_stat_order={$average_stat_order}&amp;maximum_stat_order={$maximum_stat_order}&amp;details_url={$stat.url|escape:url}#request-details">{$stat.maximum_time_taken / 1000}</a>
                </td>
            </tr>
        {/foreach}
    </table>
</div>
{pagination_links count=$pages_count step=25 offset=$maximum_stat_offset offset_arg="maximum_stat_offset"}{/pagination_links}

{if $details_url}
    <hr>
    <h5 id="request-details">{tr}Request details{/tr}</h5>

    {if $request_detail_summary}
        <p>
            <strong>{tr}URL:{/tr}</strong>
            <a href="{$details_url}">{$performance_stats_lib->simplifyURL($details_url)}</a>
        </p>

        <div class="table-responsive">
            <table class="table">
                <tr>
                    <th>{tr}Metric{/tr}</th>
                    <th class="text-end">{tr}Value{/tr}</th>
                </tr>
                <tr>
                    <td>{tr}Requests collected{/tr}</td>
                    <td class="text-end">{$request_detail_summary.number_of_requests}</td>
                </tr>
                <tr>
                    <td>{tr}Average total load time (seconds){/tr}</td>
                    <td class="text-end">{$request_detail_summary.average_time_taken / 1000}</td>
                </tr>
                <tr>
                    <td>{tr}Minimum total load time (seconds){/tr}</td>
                    <td class="text-end">{$request_detail_summary.minimum_time_taken / 1000}</td>
                </tr>
                <tr>
                    <td>{tr}Maximum total load time (seconds){/tr}</td>
                    <td class="text-end">{$request_detail_summary.maximum_time_taken / 1000}</td>
                </tr>
                <tr>
                    <td>{tr}Average backend response (seconds){/tr}</td>
                    <td class="text-end">
                        {if $request_detail_summary.average_backend_time ne null}
                            {$request_detail_summary.average_backend_time / 1000}
                        {else}
                            {tr}n/a{/tr}
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>{tr}Average frontend render (seconds){/tr}</td>
                    <td class="text-end">
                        {if $request_detail_summary.average_frontend_time ne null}
                            {$request_detail_summary.average_frontend_time / 1000}
                        {else}
                            {tr}n/a{/tr}
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td>{tr}Samples with timing breakdown{/tr}</td>
                    <td class="text-end">{$request_detail_summary.breakdown_samples}</td>
                </tr>
            </table>
        </div>

        <h6>{tr}Slowest samples{/tr}</h6>
        <div class="table-responsive">
            <table class="table">
                <tr>
                    <th>{tr}Sample ID{/tr}</th>
                    <th class="text-end">{tr}Total (seconds){/tr}</th>
                    <th class="text-end">{tr}Backend (seconds){/tr}</th>
                    <th class="text-end">{tr}Frontend (seconds){/tr}</th>
                    <th class="text-end">{tr}Other (seconds){/tr}</th>
                </tr>
                {foreach from=$request_detail_samples item=sample}
                    <tr>
                        <td>{$sample.id}</td>
                        <td class="text-end">{$sample.time_taken / 1000}</td>
                        <td class="text-end">
                            {if $sample.backend_time ne null}
                                {$sample.backend_time / 1000}
                            {else}
                                {tr}n/a{/tr}
                            {/if}
                        </td>
                        <td class="text-end">
                            {if $sample.frontend_time ne null}
                                {$sample.frontend_time / 1000}
                            {else}
                                {tr}n/a{/tr}
                            {/if}
                        </td>
                        <td class="text-end">
                            {if $sample.backend_time ne null && $sample.frontend_time ne null}
                                {($sample.time_taken - $sample.backend_time - $sample.frontend_time) / 1000}
                            {else}
                                {tr}n/a{/tr}
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </table>
        </div>
        <p class="help-block">
            {tr}Use this breakdown to distinguish backend bottlenecks from frontend/network effects. "Other" is the part of total time not explained by backend + frontend timings.{/tr}
        </p>
    {else}
        <div class="alert alert-warning">{tr}No records were found for the selected URL.{/tr}</div>
    {/if}
{/if}
