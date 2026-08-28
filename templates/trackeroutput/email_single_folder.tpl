<div class="list-group email-single-folder">
  {foreach from=$emails item=email}
    <div class="list-group-item d-flex align-items-center gap-2 email-row{if !$email.flags['seen']} fw-bold{/if}">
      <span class="fw-semibold text-truncate border-end pe-2" style="flex:0 0 30%;" {if !empty($email.contact_email)}title="{$email.contact_email|escape}"{/if}>
        {if !empty($email.contact_name)}{$email.contact_name|escape}{else}{$email.contact_email|escape}{/if}
      </span>
      <a href="{$email.view_path}" class="text-truncate flex-grow-1">
        {if !empty($email.subject)}{$email.subject|escape}{else}{tr}(None){/tr}{/if}
      </a>
      <span class="text-muted small text-nowrap">{$email.date|tiki_short_datetime}</span>
    </div>
  {/foreach}
</div>
