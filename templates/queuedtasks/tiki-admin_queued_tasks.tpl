{title help="Queued Tasks" admpage="general" url="tiki-admin_queued_tasks.php"}{tr}Queued Tasks{/tr}{/title}
{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
<div id="admin_queued_tasks_div">
    <div class="table-responsive queued_jobs">
        <table id="admin_queued_tasks" class="table normal table-striped table-hover" data-count="{$jobs|count}">
            <thead>
                <tr>
                    <th>{tr}Id{/tr}</th>
                    <th>{tr}Type{/tr}</th>
                    <th>{tr}Status{/tr}</th>
                    <th>{tr}Output{/tr}</th>
                    <th>{tr}Started{/tr}</th>
                    <th>{tr}Ended{/tr}</th>
                    <th>{tr}Created{/tr}</th>
                </tr>
            </thead>
            <tbody>
                {section name=job loop=$jobs}
                    <tr>
                        <td>{$jobs[job].id|escape}</td>
                        <td>{$jobs[job].type|escape}</td>
                        <td>
                            {if $jobs[job].status eq 'InProgress'}
                                <span class="badge bg-warning">{tr}Running{/tr}</span>
                            {elseif $jobs[job].status eq 'Pending'}
                                <span class="badge bg-warning">{tr}Pending{/tr}</span>
                            {elseif $jobs[job].status eq 'Failed'}
                                <span class="badge bg-danger">{tr}Failed{/tr}</span>
                            {elseif $jobs[job].status eq 'Completed'}
                                <span class="badge bg-success">{tr}Done{/tr}</span>
                            {/if}
                        </td>
                        <td>
                            {if empty($jobs[job].result)}
                                <em>{tr}No output available yet{/tr}</em>
                            {else}
                                {$jobs[job].result|nl2br}
                            {/if}
                        </td>
                        <td class="date">{$jobs[job].started_at|default:''}</td>
                        <td class="date">{$jobs[job].ended_at|default:''}</td>
                        <td class="date">{$jobs[job].created_at|default:''}</td>
                    </tr>
                {/section}
            </tbody>
        </table>
    </div>
</div>
{pagination_links cant=$cant step=$maxRecords offset=$offset}{/pagination_links}
{if $prefs.feature_queued_tasks eq "y"}
    {jq}
        var jobId = "{{$jobId|default: null}}";
        $(window).on('load', function() {
            function pollingLiveTaskQueuedStatus() {
                var queuedOffset = getCookieBrowser('queued_offset') || 0;
                var queryParams = "offset=" + queuedOffset;
                if (jobId) {
                    queryParams += "&id=" + jobId;
                }
                $.get("tiki-admin_queued_tasks.php?" + queryParams, function(data) {
                    var rows = '';
                    $.each(data, function(index, job) {
                        var statusBadge = '';
                        switch (job.status) {
                            case 'Pending':
                                statusBadge = '<span class="badge bg-warning">{tr}Pending{/tr}</span>';
                                break;
                            case 'InProgress':
                                statusBadge = '<span class="badge bg-warning">{tr}Running{/tr}</span>';
                                break;
                            case 'Failed':
                                statusBadge = '<span class="badge bg-danger">{tr}Failed{/tr}</span>';
                                break;
                            case 'Completed':
                                statusBadge = '<span class="badge bg-success">{tr}Done{/tr}</span>';
                                break;
                        }
                        var result = job.result ? job.result.replace(/(?:\r\n|\r|\n)/g, '<br>') : '<em>{tr}No output available yet{/tr}</em>';
                        rows += '<tr>' +
                            '<td>' + job.id + '</td>' +
                            '<td>' + job.type + '</td>' +
                            '<td>' + statusBadge + '</td>' +
                            '<td>' + result + '</td>' +
                            '<td class="date">' + (job.started_at || '') + '</td>' +
                            '<td class="date">' + (job.ended_at || '') + '</td>' +
                            '<td class="date">' + (job.created_at || '') + '</td>' +
                        '</tr>';
                    });
                    $('#admin_queued_tasks tbody').html(rows);
                });
            }
            setTimeout(pollingLiveTaskQueuedStatus, 10000);
            setInterval(pollingLiveTaskQueuedStatus, 60000);
        });
    {/jq}
{/if}
