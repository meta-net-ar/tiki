<div class="adminoptionbox preference clearfix {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
    <div class="adminoption mb-3 row d-flex">
        <label class="col-sm-3" for="{$p.id|escape}">
            {$p.name|escape}
            {include file="prefs/shared-help-icon.tpl"}
        </label>
        <div class="flex-shrink-1 w-auto">
            <div class="form-check">
                <input id="{$p.id|escape}" class="form-check-input" type="checkbox" name="{$p.preference|escape}" {if $p.value eq 'y'}checked="checked" {/if}
                    {if ! $p.available}disabled="disabled"{/if} {$p.params}
                    data-tiki-admin-child-block="#{$p.preference|escape}_childcontainer"
                    data-tiki-admin-child-mode="{$mode|escape}"
                >
            </div>

        </div>
        <div class="tikihelp-reset-wrapper col">
            {include file="prefs/shared.tpl"}
            {include file="prefs/shared-form-text.tpl"}
        </div>
    </div>
</div>
