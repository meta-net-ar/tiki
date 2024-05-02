{foreach $control.options as $key => $label}
    <div class="form-check">
        <input type="checkbox" class="form-check-input" id="{$control.name|escape}-{$key|escape}" name="{$control.field|escape}[]" value="{$key|escape}" {if $control.values[$key]}checked{/if}>
        <label class="form-check-label" for="{$control.name|escape}-{$key|escape}">
            {$label|escape}
        </label>
    </div>
{/foreach}
