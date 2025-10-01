<div class="adminoptionbox preference d-flex multicheckbox mb-3 row text-start {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
    <label for="{$p.id|escape}" class="col-form-label col-sm-3">{$p.name|escape}
        {include file="prefs/shared-help-icon.tpl"}
    </label>
    <div class="col">
        {foreach from=$p.options key=value item=label}
            <div class="form-check form-check-inline">
                <label class="col-form-label me-3">
                    <input class="form-check-inline" type="checkbox" name="{$p.preference|escape}[]" value="{$value|escape}"{if is_array($p.value) and in_array($value, $p.value)} checked="checked"{/if} {$p.params}{if $p.parameters.disabled} disabled="disabled"{/if}>
                    {$label|escape}
                </label>
            </div>
        {/foreach}

        <div class="tikihelp-reset-wrapper col-sm-2">
            {include file="prefs/shared.tpl"}
            {include file="prefs/shared-form-text.tpl"}
        </div>
    </div>
</div>
