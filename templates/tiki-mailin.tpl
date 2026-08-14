{title}{tr}Mail-in feature{/tr}{/title}
{if $results}
    {foreach from=$results item=res}
        {if $res.status == 'success'}
            <div class="alert alert-success">
                <strong>{$res.account|escape}</strong> :
                {if isset($res.summary.processedMessages)}
                    {tr}Messages processed{/tr} : {$res.summary.processedMessages}
                {else}
                    {tr}No messages processed.{/tr}
                {/if}
            </div>
        {/if}
    {/foreach}
{else}
    <p>{tr}No mail-in accounts were checked.{/tr}</p>
{/if}
{if $tiki_p_admin_mailin}
    <p>
        {tr}Click here to go to mail-in admin.{/tr}
        {icon name="next" href="tiki-admin_mailin.php"}
    </p>
{/if}
