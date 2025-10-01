<div class="adminoptionbox preference d-flex mb-3 row {$p.tagstring|escape}{if isset($smarty.request.highlight) and $smarty.request.highlight eq $p.preference} highlight{/if}">
    <label class="col-form-label col-sm-3" for="{$p.id|escape}">{$p.name|escape}
        {include file="prefs/shared-help-icon.tpl"}
    </label>
    <div class="col">
        {if not empty($p.units) or not empty($p.fgal_picker)}
            <div class="input-group">
        {/if}
        {if is_array( $p.value )}
            <input name="{$p.preference|escape}" id="{$p.id|escape}" value="{$p.value|join:$p.separator|escape}" class="form-control" size="{$p.size|default:40|escape}"
                type="text" {$p.params}{if $p.parameters.disabled} disabled="disabled"{/if}>
        {else}
            <input name="{$p.preference|escape}" id="{$p.id|escape}" value="{$p.value|escape}" class="form-control" size="{$p.size|default:40|escape}"
                   type="text" {$p.params}{if $p.parameters.disabled} disabled="disabled"{/if}>
        {/if}
        {if !empty($p.units)}
                <span class="input-group-text">{$p.units}</span>
            </div>
        {/if}
        {if !empty($p.fgal_picker)}
            {$fileId = {ldelim}|cat:'fileId'|cat:{rdelim}}
                    <a
                        class="btn btn-primary"
                        title="{tr}Upload image{/tr}"
                        href="#"
                        onclick="$('#{$p.id|escape}').trigger('select'); openFgalsWindow('tiki-upload_file.php?filegals_manager={$p.id|escape}&insertion_syntax={$fileId|sefurl:display}', true);return false;"
                        role="button"
                    >
                    {icon name='image'}&nbsp;{tr}Upload image{/tr}
                    </a>
            </div>
        {/if}
    {include file="prefs/shared-form-text.tpl"}
</div>
<div class="tikihelp-reset-wrapper col-sm-2">
    {include file="prefs/shared.tpl"}
</div>
</div>
