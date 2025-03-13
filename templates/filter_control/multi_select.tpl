<select class="form-select" id="{$control.name|escape}" name="{$control.field|escape}[]" multiple>
    <option></option>
    {foreach $control.options as $key => $label}
        <option value="{$key|escape}" {if isset($control.values[$key]) && $control.values[$key]}selected{/if}>{$label|escape}</option>
    {/foreach}
</select>
