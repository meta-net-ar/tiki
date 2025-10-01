<div class="">
    {if $operators_online > 0}
        <a class="btn btn-primary" role="button" href="tiki-live_support_message.php">{tr}Request live support{/tr}</a>
    {else}
        {if $leave_message eq "y"}
             <a class="btn btn-primary" role="button" href="tiki-live_support_message.php">{tr}Leave message{/tr}</a>
        {/if}
    {/if}
</div>
