<div class="modal {if $showAnim eq 'y'}fade{/if}" id="{$id}" tabindex="-1" aria-labelledby="{$id}-title" {if $staticBackdrop eq 'y'}data-bs-backdrop="static"{/if}>
  <div class="modal-dialog modal-{$size}">
    <div class="modal-content">
      <div class="modal-header justify-content-between">
        <h5 class="modal-title" id="{$id}-title">{$title}</h5>
        {if $showCloseIcon eq 'y'}
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        {/if}
      </div>
      <div class="modal-body">
        {$content}
      </div>
      <div class="modal-footer">
        {foreach $buttons as $button}
          <button type="button" class="{$button.className}" data-bs-dismiss="modal" {if $button.action}onclick="{$button.action}"{/if}>{$button.label}</button>
        {/foreach}
      </div>
    </div>
  </div>
</div>
<script type="module">{literal}import { initializeDialog } from "@jquery-tiki/plugins/dialog";{/literal}initializeDialog("{{$id}}", Boolean("{{$autoOpen}}" === "y"), {{$openAction}});</script> {* It is actually impossible to use proper formatting here, the plugin parser is introducing a <p> wherever it encounters a line break. *}
