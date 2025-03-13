<select class="form-select" id="{$control.field|escape}" name="{$control.field|escape}">
    <option></option>
    {foreach $control.options as $key => $label}
        <option value="{$key|escape}" {if (string)$key === (string)$control.value}selected{/if}>{$label|escape}</option>
    {/foreach}
</select>
