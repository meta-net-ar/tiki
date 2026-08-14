<div id="tiki_queued_tasks_banner" role="alert">
    {if !empty($queuedInfo)}
        {remarksbox type='note' title='{tr}Note{/tr}' icon='information'}
            <ul>
                {foreach $queuedInfo as $id => $info}
                    <li>{$info.mes}</li>
                {/foreach}
            </ul>
        {/remarksbox}
    {/if}
</div>
