{if $status == 'InProgress'}
    {tr _0=$jobId}Task (#%0) is executing...{/tr}
{elseif $status == 'Completed'}
    {tr _0=$jobId}Task (#%0) execution has been completed.{/tr}
{elseif $status == 'Pending'}
    {tr _0=$jobId}Task (#%0) is pending and will be started soon...{/tr}
{elseif $status == 'Failed'}
    {tr _0=$jobId}Task (#%0) execution has failed.{/tr}
{else}
    {tr _0=$jobId}Unknown status for task #%0{/tr}
{/if}
<a target="_blank" href="tiki-admin_queued_tasks.php?id={$jobId}" title='{tr}View details{/tr}'>{tr}View details{/tr}</a>
