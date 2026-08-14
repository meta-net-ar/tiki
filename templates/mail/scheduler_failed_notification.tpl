{tr}Warning{/tr}

{tr _0=$schedulerName _1=$healingTimeout}Scheduler "%0" has failed.{/tr}


{tr}Details{/tr}
{tr}Reason:{/tr} {$customMessage}
{tr}Site Name:{/tr} {$siteName}
{if !empty($siteUrl)}
    {tr}Site URL:{/tr} {$siteUrl}
{/if}
{tr}Server:{/tr} {$server}
{tr}Webroot:{/tr} {$webroot}
