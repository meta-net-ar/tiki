{assign var='safeId' value=$field.ins_id|replace:'[':'_'|replace:']':''}
{assign var='hasStored' value=($item.itemId > 0 && $field.value neq '' && $field.value neq null)}
{assign var='isEncrypted' value=(!empty($field.encryptionKeyId))}
<div class="js-secret-field" data-field-id="{$field.fieldId|escape}" data-item-id="{$item.itemId|escape}">
    <div class="input-group js-secret-input-group" data-field-id="{$field.fieldId|escape}" data-item-id="{$item.itemId|escape}">
        <input
            type="password"
            id="{$safeId}"
            name="{$field.ins_id}"
            class="form-control js-secret-input"
            {if !empty($field.options_map.max)}maxlength="{$field.options_map.max}"{/if}
            {if !empty($context.disabled)}disabled{/if}
            autocomplete="new-password"
            data-has-stored="{if $hasStored}1{else}0{/if}"
            data-is-encrypted="{if $isEncrypted}1{else}0{/if}"
            {if $hasStored}placeholder="●●●●●●"{/if}
        >
        <button
            type="button"
            class="btn btn-outline-secondary js-secret-toggle"
            id="{$safeId}_toggle"
            aria-label="{tr}Reveal value{/tr}"
            aria-pressed="false"
            {if !empty($context.disabled)}disabled{/if}
        >
            <i class="fa fa-eye"></i>
        </button>
    </div>
    {if $hasStored && empty($context.disabled)}
    <div class="form-check mt-1">
        <input
            type="checkbox"
            class="form-check-input js-secret-clear"
            id="{$safeId}_clear"
            name="{$field.ins_id}_clear"
            value="1"
        >
        <label class="form-check-label small text-muted" for="{$safeId}_clear">{tr}Clear stored value{/tr}</label>
    </div>
    {/if}
    {if !empty($field.cloneSource)}
    <input type="hidden" name="{$field.ins_id}_clone_source" value="{$field.cloneSource|escape}">
    {/if}
</div>
