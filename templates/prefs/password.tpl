<div class="adminoptionbox preference clearfix mb-3 row {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
    <label class="col-sm-3 col-form-label" for="{$p.id|escape}">{$p.name|escape}
        {include file="prefs/shared-help-icon.tpl"}
    </label>
    <div class="col">
        <input name="{$p.preference|escape}" id="{$p.id|escape}" value="{$p.value|escape}" class="form-control" {* size="{$p.size|default:80|escape}" *} type="password" {$p.params}{if $p.parameters.disabled} disabled="disabled"{/if}>
        {$p.detail|escape}
        {include file="prefs/shared-form-text.tpl"}
    </div>
    <div class="tikihelp-reset-wrapper col-sm-2">
        {include file="prefs/shared.tpl"}
    </div>
</div>
