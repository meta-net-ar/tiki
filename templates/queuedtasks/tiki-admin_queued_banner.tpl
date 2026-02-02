{if $status == 'InProgress'}
    {tr}Task (#{$jobId}) is executing...{/tr}
{elseif $status == 'Completed'}
    {tr}Task (#{$jobId}) execution has been completed.{/tr}
{elseif $status == 'Pending'}
    {tr}Task (#{$jobId}) is pending and will be started soon...{/tr}
{elseif $status == 'Failed'}
    {tr}Task (#{$jobId}) execution has failed.{/tr}
{else}
    {tr}Unknown status for task #{$jobId}{/tr}
{/if}
<a target="_blank" href="tiki-admin_queued_tasks.php?id={$jobId}" title='{tr}View details{/tr}'>{tr}View details{/tr}</a>
