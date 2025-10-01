<div class="adminoptionbox preference mb-3 row d-flex {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}" style="text-align: left;">
    <label class="col-sm-3 col-form-label" for="{$p.id|escape}">{$p.name|escape|breakline}
        {include file="prefs/shared-help-icon.tpl"}
    </label>
    <div class="col-sm-7">
        {if !empty($p.units)}
            <div class="input-group">
        {/if}
        <select
            class="form-select"
            name="{$p.preference|escape}"
            id="{$p.id|escape}"
            data-tiki-admin-child-block=".{$p.preference|escape}_childcontainer"
            data-tiki-admin-child-mode="{$mode|escape}"
            {if $p.parameters.disabled}disabled="disabled"{/if}
        >
            {foreach from=$p.options key=value item=label}
                <option value="{$value|escape}"{if $value eq $p.value} selected="selected"{/if} {$p.params}>
                    {$label|escape}
                </option>
            {/foreach}
        </select>
        {if !empty($p.units)}
                <span class="input-group-text">{$p.units}</span>
            </div>
        {/if}
        {include file="prefs/shared-form-text.tpl"}
    </div>
    <div class="tikihelp-reset-wrapper col-sm-2">
        {include file="prefs/shared.tpl"}
    </div>
</div>
