{if $field.value neq '' and $field.value neq null}
<span
    class="d-inline-flex align-items-center gap-1 js-secret-output"
    data-field-id="{$field.fieldId|escape}"
    data-item-id="{if $item.itemId}{$item.itemId|escape}{else}0{/if}"
    {if !$item.itemId and !$context.preview}data-value="{$field.value|escape}"{/if}
>
    <span class="js-secret-output-text">●●●●●●</span>
    <button
        type="button"
        class="btn btn-sm btn-outline-secondary js-secret-output-toggle"
        aria-label="{tr}Reveal value{/tr}"
        aria-pressed="false"
        {if $context.preview}disabled title="{tr}Not available in preview{/tr}"{/if}
    ><i class="fa fa-eye"></i></button>
    <span class="js-secret-output-err text-danger ms-1" style="display:none"><i class="fa fa-warning"></i></span>
</span>
{/if}
