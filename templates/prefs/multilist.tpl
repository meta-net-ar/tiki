<div class="adminoptionbox preference clearfix mb-3 row text-start {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
    <label class="col-sm-4 col-form-label" for="{$p.id|escape}">{$p.name|escape}
        {include file="prefs/shared-help-icon.tpl"}
    </label>
    <div class="col">
        <select class="form-select resize-vertical mb-3" name="{$p.preference|escape}[]" id="{$p.id|escape}" multiple="multiple"{if $p.parameters.disabled} disabled="disabled"{/if}>
            {foreach from=$p.options key=value item=label}
                <option value="{$value|escape}"{if is_array($p.value) and in_array($value, $p.value)} selected="selected"{/if} {$p.params}>{$label|escape}</option>
            {/foreach}
        </select>
        {include file="prefs/shared-form-text.tpl"}
        {if $prefs.elementplus_select neq 'y'}
            {remarksbox type="tip" title="{tr}Tip{/tr}"}{tr}Use Ctrl+Click to select multiple options{/tr}{/remarksbox}
        {/if}
    </div>
        <div class="tikihelp-reset-wrapper col-sm-2">
            {include file="prefs/shared.tpl"}
        </div>
</div>
