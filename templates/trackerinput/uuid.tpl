{if $field.value|strlen > 0}
    <div class="input-group">
        <input type="text" class="form-control" name="{$field.ins_id}" id="{$field.ins_id}" value="{$field.value|escape}"
            disabled="disabled">
    </div>
{else}
    {if $field.options_map.readonly == 'n'}
        <div class="input-group">
            <input type="text" class="form-control" name="{$field.ins_id}" id="{$field.ins_id}" value="{$field.value|escape}"
                placeholder="{tr}Enter a valid UUID or leave empty for auto-generation{/tr}">
        </div>
    {else}
        {* Read-only: just display a notice *}
        <div class="form-text text-muted">
            {tr}(UUID will be automatically generated after item creation){/tr}
        </div>
    {/if}
{/if}
